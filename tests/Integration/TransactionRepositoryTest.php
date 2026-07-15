<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Repositories\ImportBatchRepository;
use App\Repositories\TransactionRepository;
use Tests\DatabaseTestCase;

final class TransactionRepositoryTest extends DatabaseTestCase
{
    private TransactionRepository $repository;
    private int $batchId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new TransactionRepository($this->pdo);
        $this->batchId = (new ImportBatchRepository($this->pdo))->create('seed.csv', 'checksum', date('Y-m-d H:i:s'));
    }

    private function insert(array $overrides = []): int
    {
        return $this->repository->insert(array_merge([
            'transaction_id' => uniqid('TX', true),
            'occurred_at' => '2026-01-01 10:00:00',
            'amount' => '100.00',
            'currency' => 'USD',
            'transaction_type' => 'purchase',
            'status' => 'settled',
            'merchant' => 'Acme Corp',
            'account' => 'ACC1',
            'card_number' => '4111111111111111',
            'import_batch_id' => $this->batchId,
        ], $overrides));
    }

    public function test_existing_transaction_ids_returns_only_matches(): void
    {
        $this->insert(['transaction_id' => 'TX1']);
        $this->insert(['transaction_id' => 'TX2']);

        $existing = $this->repository->existingTransactionIds(['TX1', 'TX3']);

        self::assertSame(['TX1'], $existing);
    }

    public function test_search_with_q_matches_transaction_id_or_merchant(): void
    {
        $this->insert(['transaction_id' => 'TX-FINDME', 'merchant' => 'Beta LLC']);
        $this->insert(['transaction_id' => 'TX-OTHER', 'merchant' => 'Gamma Traders']);

        $byId = $this->repository->search(['q' => 'FINDME']);
        self::assertSame(1, $byId->total);

        $byMerchant = $this->repository->search(['q' => 'Gamma']);
        self::assertSame(1, $byMerchant->total);
    }

    public function test_search_filters_by_status(): void
    {
        $this->insert(['status' => 'settled']);
        $this->insert(['status' => 'pending']);

        $result = $this->repository->search(['status' => 'pending']);

        self::assertSame(1, $result->total);
        self::assertSame('pending', $result->items[0]->status);
    }

    public function test_search_filters_by_merchant(): void
    {
        $this->insert(['merchant' => 'Acme Corp']);
        $this->insert(['merchant' => 'Beta LLC']);

        $result = $this->repository->search(['merchant' => 'Beta']);

        self::assertSame(1, $result->total);
    }

    public function test_search_filters_by_amount_range(): void
    {
        $this->insert(['amount' => '10.00']);
        $this->insert(['amount' => '500.00']);

        $result = $this->repository->search(['amount_min' => '100', 'amount_max' => '1000']);

        self::assertSame(1, $result->total);
        self::assertSame('500.00', $result->items[0]->amount);
    }

    public function test_search_paginates_results(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->insert(['transaction_id' => "TX{$i}"]);
        }

        $result = $this->repository->search([], page: 1, perPage: 2);

        self::assertSame(5, $result->total);
        self::assertCount(2, $result->items);
        self::assertSame(3, $result->lastPage());
    }
}
