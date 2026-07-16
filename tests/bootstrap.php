<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Env;
use App\Core\Migrator;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/Support/import_controller_redirect_override.php';

Env::load(__DIR__ . '/../.env.test');

Database::createDatabaseIfMissing();
(new Migrator(Database::connection()))->run();
