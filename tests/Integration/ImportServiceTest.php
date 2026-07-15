<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Repositories\RejectedTransactionRepository;
use App\Repositories\TransactionRepository;
use App\Services\ImportService;
use Tests\DatabaseTestCase;

final class ImportServiceTest extends DatabaseTestCase
{
    private string $path;

    protected function tearDown(): void
    {
        @unlink($this->path);
    }

    private function writeCsv(string $contents): string
    {
        $this->path = tempnam(sys_get_temp_dir(), 'import_service_test_');
        file_put_contents($this->path, $contents);

        return $this->path;
    }

    public function test_imports_valid_rows_and_rejects_invalid_ones(): void
    {
        $path = $this->writeCsv(
            "transaction_id,occurred_at,amount,currency,transaction_type,status\n" .
            "TX1,2026-01-01 10:00:00,10.00,usd,purchase,settled\n" .
            "TX2,not-a-date,20.00,usd,purchase,settled\n"
        );

        $summary = (new ImportService($this->pdo))->import($path);

        self::assertSame(1, $summary->importedCount);
        self::assertSame(1, $summary->rejectedCount);
        self::assertSame(0, $summary->duplicateCount);

        $rejected = (new RejectedTransactionRepository($this->pdo))->findByImportBatch($summary->importBatchId);
        self::assertCount(1, $rejected->items);
        self::assertSame(['occurred_at must be a valid datetime'], $rejected->items[0]->errors);
    }

    public function test_blank_currency_is_rejected(): void
    {
        $path = $this->writeCsv(
            "transaction_id,occurred_at,amount,currency,transaction_type,status\n" .
            "TX1,2026-01-01 10:00:00,10.00,,purchase,settled\n"
        );

        $summary = (new ImportService($this->pdo))->import($path);

        self::assertSame(0, $summary->importedCount);
        self::assertSame(1, $summary->rejectedCount);
    }

    public function test_oversized_field_is_rejected_without_aborting_the_whole_batch(): void
    {
        $badCardNumber = str_repeat('1', 26);
        $path = $this->writeCsv(
            "transaction_id,occurred_at,amount,currency,transaction_type,status,card_number\n" .
            "TX1,2026-01-01 10:00:00,10.00,usd,purchase,settled,{$badCardNumber}\n" .
            "TX2,2026-01-01 11:00:00,20.00,usd,purchase,settled,4111111111111111\n"
        );

        $summary = (new ImportService($this->pdo))->import($path);

        self::assertSame(1, $summary->importedCount);
        self::assertSame(1, $summary->rejectedCount);
    }

    public function test_detects_duplicates_within_the_same_file(): void
    {
        $path = $this->writeCsv(
            "transaction_id,occurred_at,amount,currency,transaction_type,status\n" .
            "TX1,2026-01-01 10:00:00,10.00,usd,purchase,settled\n" .
            "TX1,2026-01-01 10:00:00,10.00,usd,purchase,settled\n"
        );

        $summary = (new ImportService($this->pdo))->import($path);

        self::assertSame(1, $summary->importedCount);
        self::assertSame(1, $summary->duplicateCount);
    }

    public function test_detects_duplicates_against_previously_imported_transactions(): void
    {
        $path = $this->writeCsv(
            "transaction_id,occurred_at,amount,currency,transaction_type,status\n" .
            "TX1,2026-01-01 10:00:00,10.00,usd,purchase,settled\n"
        );
        (new ImportService($this->pdo))->import($path);

        $secondSummary = (new ImportService($this->pdo))->import($path);

        self::assertSame(0, $secondSummary->importedCount);
        self::assertSame(1, $secondSummary->duplicateCount);
        self::assertSame(1, (new TransactionRepository($this->pdo))->count());
    }
}
