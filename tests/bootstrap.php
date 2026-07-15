<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Env;
use App\Core\Migrator;

require __DIR__ . '/../vendor/autoload.php';

Env::load(__DIR__ . '/../.env');
$_ENV['DB_DATABASE'] = 'campus_ledger_test';
putenv('DB_DATABASE=campus_ledger_test');

Database::createDatabaseIfMissing();
(new Migrator(Database::connection()))->run();
