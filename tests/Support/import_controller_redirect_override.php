<?php

declare(strict_types=1);

// PHP resolves an unqualified function call inside a namespace by looking for
// App\Controllers\Web\redirect() first and only falling back to the global
// redirect() (app/Helpers/helpers.php) if it's not defined. Declaring it here
// lets tests intercept ImportController::store()'s redirect (which otherwise
// calls exit()) without touching production code.
namespace App\Controllers\Web;

use Tests\Support\RedirectException;

function redirect(string $path): never
{
    throw new RedirectException($path);
}
