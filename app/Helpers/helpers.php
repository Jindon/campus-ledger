<?php

declare(strict_types = 1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function view(string $name, array $data = []): string
{
    extract($data, EXTR_SKIP);
    $path = dirname(__DIR__, 2) . "/resources/views/{$name}.php";

    ob_start();
    require $path;
    return ob_get_clean();
}
