<?php

declare(strict_types=1);

use App\Core\Env;

require __DIR__ . '/../vendor/autoload.php';

Env::load(dirname(__DIR__) . '/.env');

date_default_timezone_set('UTC');

error_reporting(E_ALL);
ini_set('display_errors', '0');

return true;
