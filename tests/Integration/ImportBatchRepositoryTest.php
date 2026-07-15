<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Repositories\ImportBatchRepository;
use Tests\DatabaseTestCase;

final class ImportBatchRepositoryTest extends DatabaseTestCase
{
    private ImportBatchRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ImportBatchRepository($this->pdo);
    }

    public function test_create_and_find(): void
    {
        $id = $this->repository->create('transactions.csv', 'abc123', date('Y-m-d H:i:s'));

        $batch = $this->repository->find($id);

        self::assertNotNull($batch);
        self::assertSame('transactions.csv', $batch->filename);
        self::assertSame(0, $batch->importedCount);
    }

    public function test_finish_updates_counts(): void
    {
        $id = $this->repository->create('transactions.csv', 'abc123', date('Y-m-d H:i:s'));

        $this->repository->finish($id, 10, 2, 1, date('Y-m-d H:i:s'));

        $batch = $this->repository->find($id);
        self::assertSame(10, $batch->importedCount);
        self::assertSame(2, $batch->rejectedCount);
        self::assertSame(1, $batch->duplicateCount);
    }

    public function test_latest_returns_most_recent_batch(): void
    {
        $this->repository->create('first.csv', 'a', date('Y-m-d H:i:s'));
        $secondId = $this->repository->create('second.csv', 'b', date('Y-m-d H:i:s'));

        self::assertSame($secondId, $this->repository->latest()->id);
    }

    public function test_paginate_orders_newest_first(): void
    {
        $this->repository->create('first.csv', 'a', date('Y-m-d H:i:s'));
        $secondId = $this->repository->create('second.csv', 'b', date('Y-m-d H:i:s'));

        $result = $this->repository->paginate();

        self::assertSame($secondId, $result->items[0]->id);
    }

    public function test_total_rejected_rows_sums_across_batches(): void
    {
        $first = $this->repository->create('first.csv', 'a', date('Y-m-d H:i:s'));
        $second = $this->repository->create('second.csv', 'b', date('Y-m-d H:i:s'));
        $this->repository->finish($first, 5, 3, 0, date('Y-m-d H:i:s'));
        $this->repository->finish($second, 5, 2, 0, date('Y-m-d H:i:s'));

        self::assertSame(5, $this->repository->totalRejectedRows());
    }
}
