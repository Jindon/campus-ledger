<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Database;
use PDO;
use PHPUnit\Framework\TestCase;

abstract class DatabaseTestCase extends TestCase
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = Database::connection();
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach (['rejected_transactions', 'transactions', 'import_batches'] as $table) {
            $this->pdo->exec("TRUNCATE TABLE {$table}");
        }
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }
}
