<?php

declare(strict_types=1);

use App\Controllers\Web\DashboardController;
use App\Controllers\Web\ImportController;
use App\Controllers\Web\TransactionController;
use App\Controllers\Api\ImportController as ApiImportController;
use App\Controllers\Api\TransactionController as ApiTransactionController;
use App\Controllers\Api\ReportController as ApiReportController;
use App\Controllers\Web\ReportController;
use App\Core\Logger;
use App\Core\Router;
use App\Exceptions\HttpException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;

require __DIR__ . '/../app/bootstrap.php';

session_start();

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$isApi = str_starts_with(parse_url($uri, PHP_URL_PATH) ?? '', '/api/');

if ($method === 'POST' && !$isApi && !csrf_verify($_POST['_csrf'] ?? null)) {
    http_response_code(419);
    echo view('errors/error', ['statusCode' => 419, 'message' => 'Your session has expired. Please try again.']);
    exit;
}

$router = new Router();

# START Register Routes
# Web routes
$router->get('/', fn () => (new DashboardController())->index());

$router->get('/imports', fn () => (new ImportController())->index());
$router->post('/imports', fn () => (new ImportController())->store());
$router->get('/imports/{id}', fn (array $params) => (new ImportController())->show((int) $params['id']));

$router->get('/transactions', fn () => (new TransactionController())->index($_GET));
$router->get('/reports', fn () => (new ReportController())->index());

# API routes
$router->get('/api/imports', fn () => (new ApiImportController())->index($_GET));
$router->get('/api/imports/{id}', fn (array $params) => (new ApiImportController())->show((int) $params['id'], $_GET));
$router->get('/api/transactions', fn (array $params) => (new ApiTransactionController())->index($_GET));
$router->get('/api/reports/daily', fn (array $params) => (new ApiReportController())->daily($_GET));

# END Register Routes

try {
    $result = $router->dispatch($method, $uri);

    if ($isApi) {
        header('Content-Type: application/json');
        echo json_encode($result, JSON_THROW_ON_ERROR);
    } else {
        echo $result;
    }
} catch (HttpException $e) {
    if (!($e instanceof NotFoundException)) {
        Logger::error($e->getMessage(), ['exception' => get_class($e)]);
    }

    http_response_code($e->statusCode);

    if ($isApi) {
        header('Content-Type: application/json');
        $payload = ['error' => $e->getMessage()];
        if ($e instanceof ValidationException) {
            $payload['errors'] = $e->errors;
        }
        echo json_encode($payload, JSON_THROW_ON_ERROR);
    } else {
        echo view('errors/error', ['statusCode' => $e->statusCode, 'message' => $e->getMessage()]);
    }

    echo view('errors/error', ['statusCode' => $e->statusCode, 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    $debug = config('app.debug');

    if ($isApi) {
        header('Content-Type: application/json');
        $payload = ['error' => 'Internal server error'];
        if ($debug) {
            $payload['exception'] = get_class($e);
            $payload['message'] = $e->getMessage();
            $payload['trace'] = explode("\n", $e->getTraceAsString());
        }
        echo json_encode($payload, JSON_THROW_ON_ERROR);
    } else {
        echo view('errors/error', [
            'statusCode' => 500,
            'message' => $e->getMessage(),
            'trace' => $e,
        ]);
    }
}
