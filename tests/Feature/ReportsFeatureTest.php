<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Controllers\Web\ReportController;
use App\Repositories\ImportBatchRepository;
use App\Repositories\TransactionRepository;
use Tests\DatabaseTestCase;

final class ReportsFeatureTest extends DatabaseTestCase
{
    public function test_reports_page_renders_totals(): void
    {
        $batchId = (new ImportBatchRepository($this->pdo))->create('seed.csv', 'checksum', date('Y-m-d H:i:s'));
        (new TransactionRepository($this->pdo))->insert([
            'transaction_id' => 'TX1',
            'occurred_at' => '2026-01-01 10:00:00',
            'amount' => '42.00',
            'currency' => 'USD',
            'transaction_type' => 'purchase',
            'status' => 'settled',
            'merchant' => 'Acme Corp',
            'account' => 'ACC1',
            'card_number' => '4111111111111111',
            'import_batch_id' => $batchId,
        ]);

        $html = (new ReportController())->index();

        self::assertStringContainsString('Reports', $html);
        self::assertStringContainsString('Acme Corp', $html);
        self::assertStringContainsString('42.00', $html);
    }
}
