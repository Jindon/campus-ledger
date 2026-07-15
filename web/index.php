<?php

declare(strict_types=1);

use App\Controllers\Web\DashboardController;
use App\Controllers\Web\ImportController;
use App\Controllers\Web\TransactionController;
use App\Controllers\Web\ReportController;
use App\Core\Router;
use App\Exceptions\HttpException;

require __DIR__ . '/../app/bootstrap.php';

session_start();

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$router = new Router();

# START Register Routes
$router->get('/', fn () => (new DashboardController())->index());

$router->get('/imports', fn () => (new ImportController())->index());
$router->post('/imports', fn () => (new ImportController())->store());
$router->get('/imports/{id}', fn (array $params) => (new ImportController())->show((int) $params['id']));

$router->get('/transactions', fn () => (new TransactionController())->index($_GET));
$router->get('/reports', fn () => (new ReportController())->index());

# END Register Routes

try {
    $result = $router->dispatch($method, $uri);

    if (1 === 2) { // TODO: $isApi then return json response
        echo json_encode($result, JSON_THROW_ON_ERROR);
    }

    echo $result;
} catch (HttpException $e) {

    http_response_code($e->statusCode);

    // todo: send json response or show error page

    echo view('errors/error', ['statusCode' => $e->statusCode, 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);

    // todo: send json response or show error page

    echo view('errors/error', [
        'statusCode' => 500,
        'message' => $e->getMessage(),
        'trace' => $e,
    ]);
}
