<?php

declare(strict_types=1);

namespace App\DTO;

final class PageResult
{
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {
    }

    public function lastPage(): int
    {
        return (int) max(1, ceil($this->total / $this->perPage));
    }
}
