<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Controllers\Api\ImportController;
use App\Controllers\Api\ReportController;
use App\Controllers\Api\TransactionController;
use App\Exceptions\NotFoundException;
use App\Repositories\ImportBatchRepository;
use App\Repositories\TransactionRepository;
use Tests\DatabaseTestCase;

final class ApiFeatureTest extends DatabaseTestCase
{
    public function test_transactions_endpoint_returns_paginated_json_shape(): void
    {
        $batchId = (new ImportBatchRepository($this->pdo))->create('seed.csv', 'checksum', date('Y-m-d H:i:s'));
        (new TransactionRepository($this->pdo))->insert([
            'transaction_id' => 'TX1',
            'occurred_at' => '2026-01-01 10:00:00',
            'amount' => '10.00',
            'currency' => 'USD',
            'transaction_type' => 'purchase',
            'status' => 'settled',
            'merchant' => 'Acme Corp',
            'account' => 'ACC1',
            'card_number' => '4111111111111111',
            'import_batch_id' => $batchId,
        ]);

        $response = (new TransactionController())->index([]);

        self::assertCount(1, $response['data']);
        self::assertSame(1, $response['meta']['total']);
    }

    public function test_imports_endpoint_returns_batches(): void
    {
        (new ImportBatchRepository($this->pdo))->create('seed.csv', 'checksum', date('Y-m-d H:i:s'));

        $response = (new ImportController())->index([]);

        self::assertCount(1, $response['data']);
    }

    public function test_import_show_endpoint_includes_rejected_transactions(): void
    {
        $id = (new ImportBatchRepository($this->pdo))->create('seed.csv', 'checksum', date('Y-m-d H:i:s'));

        $response = (new ImportController())->show($id);

        self::assertSame($id, $response['data']['id']);
        self::assertArrayHasKey('rejected_transactions', $response['data']);
    }

    public function test_import_show_endpoint_throws_not_found(): void
    {
        $this->expectException(NotFoundException::class);
        (new ImportController())->show(999999);
    }

    public function test_daily_report_endpoint_returns_data(): void
    {
        $response = (new ReportController())->daily();

        self::assertArrayHasKey('data', $response);
    }
}
