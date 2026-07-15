<?php

declare(strict_types=1);

use App\Core\Env;

return [
    'debug' => Env::get('APP_DEBUG', 'false') === 'true',
    'upload_max_bytes' => (int) Env::get('UPLOAD_MAX_BYTES', 10485760),
];
