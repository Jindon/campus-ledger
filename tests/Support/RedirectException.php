<?php

declare(strict_types=1);

namespace Tests\Support;

use RuntimeException;

final class RedirectException extends RuntimeException
{
    public function __construct(public readonly string $location)
    {
        parent::__construct("Redirected to {$location}");
    }
}
