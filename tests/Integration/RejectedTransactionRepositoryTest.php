<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Repositories\ImportBatchRepository;
use App\Repositories\RejectedTransactionRepository;
use Tests\DatabaseTestCase;

final class RejectedTransactionRepositoryTest extends DatabaseTestCase
{
    private RejectedTransactionRepository $repository;
    private int $batchId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new RejectedTransactionRepository($this->pdo);
        $this->batchId = (new ImportBatchRepository($this->pdo))->create('seed.csv', 'checksum', date('Y-m-d H:i:s'));
    }

    public function test_find_by_import_batch_paginates_results(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->repository->insert($this->batchId, $i, "TX{$i}", ['some error'], ['row' => $i]);
        }

        $result = $this->repository->findByImportBatch($this->batchId, page: 1, perPage: 2);

        self::assertSame(5, $result->total);
        self::assertCount(2, $result->items);
        self::assertSame(3, $result->lastPage());
        self::assertSame(1, $result->items[0]->rowNo);
    }

    public function test_find_by_import_batch_orders_by_row_number(): void
    {
        $this->repository->insert($this->batchId, 3, 'TX3', ['e'], []);
        $this->repository->insert($this->batchId, 1, 'TX1', ['e'], []);
        $this->repository->insert($this->batchId, 2, 'TX2', ['e'], []);

        $result = $this->repository->findByImportBatch($this->batchId);

        self::assertSame([1, 2, 3], array_map(fn ($r) => $r->rowNo, $result->items));
    }

    public function test_only_returns_rows_for_the_given_batch(): void
    {
        $otherBatchId = (new ImportBatchRepository($this->pdo))->create('other.csv', 'checksum', date('Y-m-d H:i:s'));
        $this->repository->insert($this->batchId, 1, 'TX1', ['e'], []);
        $this->repository->insert($otherBatchId, 1, 'TX2', ['e'], []);

        $result = $this->repository->findByImportBatch($this->batchId);

        self::assertSame(1, $result->total);
    }
}
