<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class Migrator
{
    private string $migrationsPath;

    public function __construct(private readonly PDO $pdo)
    {
        $this->migrationsPath = dirname(__DIR__, 2) . '/database/migrations';
    }

    /** @return string[] filenames applied during this run */
    public function run(): array
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS migrations (
                filename VARCHAR(255) NOT NULL PRIMARY KEY,
                ran_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        $applied = $this->pdo->query('SELECT filename FROM migrations')->fetchAll(PDO::FETCH_COLUMN);
        $files = glob($this->migrationsPath . '/*.sql') ?: [];
        sort($files);

        $ran = [];
        foreach ($files as $file) {
            $filename = basename($file);
            if (in_array($filename, $applied, true)) {
                continue;
            }

            $this->pdo->exec((string) file_get_contents($file));
            $stmt = $this->pdo->prepare('INSERT INTO migrations (filename) VALUES (:filename)');
            $stmt->execute(['filename' => $filename]);
            $ran[] = $filename;
        }

        return $ran;
    }
}
