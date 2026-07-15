<?php

declare(strict_types=1);

namespace App\Core;

final class Config
{
    private static array $items = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        [$file, $rest] = array_pad(explode('.', $key, 2), 2, null);

        if (!array_key_exists($file, self::$items)) {
            $path = dirname(__DIR__, 2) . "/config/{$file}.php";
            self::$items[$file] = is_file($path) ? require $path : [];
        }

        if ($rest === null) {
            return self::$items[$file];
        }

        return self::$items[$file][$rest] ?? $default;
    }
}
