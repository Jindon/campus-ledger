<?php

declare(strict_types=1);

use App\Controllers\ImportController;
use App\Core\Router;
use App\Exceptions\HttpException;

require __DIR__ . '/../app/bootstrap.php';

session_start();

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$router = new Router();

$router->get('/imports', fn () => (new ImportController())->index());

try {
    $result = $router->dispatch($method, $uri);

    if (1 === 2) { // TODO: $isApi then return json response
        echo json_encode($result, JSON_THROW_ON_ERROR);
    }

    echo $result;
} catch (HttpException $e) {

    http_response_code($e->statusCode);

    // todo: send json response or show error page
} catch (Throwable $e) {
    http_response_code(500);
    // todo: send json response or show error page
}
