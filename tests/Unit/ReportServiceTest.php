<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Repositories\ImportBatchRepository;
use App\Repositories\TransactionRepository;
use App\Services\ReportService;
use Tests\DatabaseTestCase;

final class ReportServiceTest extends DatabaseTestCase
{
    private ReportService $reportService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reportService = new ReportService($this->pdo);
    }

    private function seedTransaction(array $overrides = []): void
    {
        $batchId = (new ImportBatchRepository($this->pdo))->create('seed.csv', 'checksum', date('Y-m-d H:i:s'));

        (new TransactionRepository($this->pdo))->insert(array_merge([
            'transaction_id' => uniqid('TX', true),
            'occurred_at' => '2026-01-01 10:00:00',
            'amount' => '100.00',
            'currency' => 'USD',
            'transaction_type' => 'purchase',
            'status' => 'settled',
            'merchant' => 'Acme Corp',
            'account' => 'ACC1',
            'card_number' => '4111111111111111',
            'import_batch_id' => $batchId,
        ], $overrides));
    }

    public function test_total_processed_amount_sums_transactions_in_the_same_currency(): void
    {
        $this->seedTransaction(['currency' => 'USD', 'amount' => '100.00']);
        $this->seedTransaction(['currency' => 'USD', 'amount' => '50.00']);

        $totals = $this->reportService->totalProcessedAmount();

        self::assertCount(1, $totals);
        self::assertSame('USD', $totals[0]['currency']);
        self::assertSame('150.00', $totals[0]['total_amount']);
    }

    public function test_total_processed_amount_does_not_mix_currencies(): void
    {
        $this->seedTransaction(['currency' => 'USD', 'amount' => '100.00']);
        $this->seedTransaction(['currency' => 'EUR', 'amount' => '50.00']);

        $totals = $this->reportService->totalProcessedAmount();

        self::assertCount(2, $totals);
        $byCurrency = array_column($totals, 'total_amount', 'currency');
        self::assertSame('100.00', $byCurrency['USD']);
        self::assertSame('50.00', $byCurrency['EUR']);
    }

    public function test_merchant_totals_groups_by_merchant(): void
    {
        $this->seedTransaction(['merchant' => 'Acme Corp', 'amount' => '30.00']);
        $this->seedTransaction(['merchant' => 'Acme Corp', 'amount' => '20.00']);
        $this->seedTransaction(['merchant' => 'Beta LLC', 'amount' => '10.00']);

        $totals = $this->reportService->merchantTotals();

        $acme = current(array_filter($totals, fn ($row) => $row['merchant'] === 'Acme Corp'));
        self::assertSame('2', (string) $acme['transaction_count']);
        self::assertSame('50.00', $acme['total_amount']);
    }

    public function test_merchant_totals_does_not_mix_currencies_for_the_same_merchant(): void
    {
        $this->seedTransaction(['merchant' => 'Acme Corp', 'currency' => 'USD', 'amount' => '30.00']);
        $this->seedTransaction(['merchant' => 'Acme Corp', 'currency' => 'EUR', 'amount' => '20.00']);

        $totals = array_filter($this->reportService->merchantTotals(), fn ($row) => $row['merchant'] === 'Acme Corp');

        self::assertCount(2, $totals);
        $byCurrency = array_column($totals, 'total_amount', 'currency');
        self::assertSame('30.00', $byCurrency['USD']);
        self::assertSame('20.00', $byCurrency['EUR']);
    }

    public function test_merchant_totals_scoped_to_a_date_only_includes_that_date(): void
    {
        $this->seedTransaction(['merchant' => 'Acme Corp', 'occurred_at' => '2026-01-01 09:00:00', 'amount' => '30.00']);
        $this->seedTransaction(['merchant' => 'Acme Corp', 'occurred_at' => '2026-01-02 09:00:00', 'amount' => '20.00']);

        $totals = $this->reportService->merchantTotals('2026-01-01');

        self::assertCount(1, $totals);
        self::assertSame('30.00', $totals[0]['total_amount']);
    }

    public function test_merchant_totals_without_a_date_is_all_time(): void
    {
        $this->seedTransaction(['merchant' => 'Acme Corp', 'occurred_at' => '2026-01-01 09:00:00', 'amount' => '30.00']);
        $this->seedTransaction(['merchant' => 'Acme Corp', 'occurred_at' => '2026-01-02 09:00:00', 'amount' => '20.00']);

        $totals = $this->reportService->merchantTotals();

        self::assertCount(1, $totals);
        self::assertSame('50.00', $totals[0]['total_amount']);
    }

    public function test_currency_totals_groups_by_currency(): void
    {
        $this->seedTransaction(['currency' => 'USD', 'amount' => '10.00']);
        $this->seedTransaction(['currency' => 'EUR', 'amount' => '20.00']);

        $totals = $this->reportService->currencyTotals();

        self::assertCount(2, $totals);
    }

    public function test_daily_summary_only_includes_the_requested_date(): void
    {
        $this->seedTransaction(['occurred_at' => '2026-01-01 09:00:00', 'amount' => '10.00']);
        $this->seedTransaction(['occurred_at' => '2026-01-01 20:00:00', 'amount' => '20.00']);
        $this->seedTransaction(['occurred_at' => '2026-01-02 09:00:00', 'amount' => '30.00']);

        $daily = $this->reportService->dailySummary('2026-01-01');

        self::assertCount(1, $daily);
        self::assertSame('30.00', $daily[0]['total_amount']);
        self::assertSame('2', (string) $daily[0]['transaction_count']);
    }

    public function test_daily_summary_does_not_mix_currencies_on_the_same_day(): void
    {
        $this->seedTransaction(['occurred_at' => '2026-01-01 09:00:00', 'currency' => 'USD', 'amount' => '10.00']);
        $this->seedTransaction(['occurred_at' => '2026-01-01 20:00:00', 'currency' => 'EUR', 'amount' => '20.00']);

        $daily = $this->reportService->dailySummary('2026-01-01');

        self::assertCount(2, $daily);
        $byCurrency = array_column($daily, 'total_amount', 'currency');
        self::assertSame('10.00', $byCurrency['USD']);
        self::assertSame('20.00', $byCurrency['EUR']);
    }

    public function test_daily_summary_returns_empty_for_a_date_with_no_transactions(): void
    {
        $this->seedTransaction(['occurred_at' => '2026-01-01 09:00:00']);

        self::assertSame([], $this->reportService->dailySummary('2026-01-02'));
    }

    public function test_resolve_date_defaults_to_today_when_missing(): void
    {
        self::assertSame(date('Y-m-d'), ReportService::resolveDate(null));
        self::assertSame(date('Y-m-d'), ReportService::resolveDate(''));
    }

    public function test_resolve_date_falls_back_to_today_for_malformed_input(): void
    {
        self::assertSame(date('Y-m-d'), ReportService::resolveDate('not-a-date'));
        self::assertSame(date('Y-m-d'), ReportService::resolveDate('2026-13-99'));
    }

    public function test_resolve_date_accepts_a_valid_date(): void
    {
        self::assertSame('2026-01-01', ReportService::resolveDate('2026-01-01'));
    }
}
