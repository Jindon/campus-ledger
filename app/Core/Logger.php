<?php

declare(strict_types=1);

namespace App\Core;

final class Logger
{
    private static function path(): string
    {
        return dirname(__DIR__, 2) . '/storage/logs/app.log';
    }

    public static function log(string $level, string $message, array $context = []): void
    {
        $line = sprintf(
            '[%s] %s: %s%s%s',
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            $context !== [] ? ' ' . json_encode($context) : '',
            PHP_EOL
        );

        file_put_contents(self::path(), $line, FILE_APPEND | LOCK_EX);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('info', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('error', $message, $context);
    }
}
