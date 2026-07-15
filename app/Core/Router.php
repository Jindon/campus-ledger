<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\NotFoundException;

final class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    private function addRoute(string $method, string $path, callable $handler): void
    {
        $paramNames = [];

        $regex = preg_replace_callback('/\{(\w+)}/', function (array $m) use (&$paramNames) {
            $paramNames[] = $m[1];
            return '(?P<' . $m[1] . '>[^/]+)';
        }, $path);

        $this->routes[] = [
            'method' => $method,
            'regex' => '#^' . $regex . '$#',
            'params' => $paramNames,
            'handler' => $handler,
        ];
    }

    /**
     * @throws NotFoundException
     */
    public function dispatch(string $method, string $uri): mixed
    {
        // extract path and handle empty $uri
        $path = rtrim(parse_url($uri, PHP_URL_PATH) ?: '/', '/');

        // after rtrim if '/' is removed for root path, set it back to '/'
        $path = $path === '' ? '/' : $path;

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['regex'], $path, $matches)) {
                $params = array_intersect_key($matches, array_flip($route['params']));

                return ($route['handler'])($params);
            }
        }

        throw new NotFoundException("No route matches {$method} {$path}");
    }
}
