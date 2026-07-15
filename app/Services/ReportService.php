<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\TransactionRepository;
use DateTime;
use PDO;

final class ReportService
{
    private TransactionRepository $transactions;

    public function __construct(PDO $pdo)
    {
        $this->transactions = new TransactionRepository($pdo);
    }

    public static function resolveDate(?string $requested): string
    {
        $today = date('Y-m-d');
        if (empty($requested)) {
            return $today;
        }

        $parsed = DateTime::createFromFormat('Y-m-d', $requested);

        return ($parsed && $parsed->format('Y-m-d') === $requested) ? $requested : $today;
    }

    public function dailySummary(string $date): array
    {
        return $this->transactions->dailySummary($date);
    }

    public function merchantTotals(?string $date = null): array
    {
        return $this->transactions->merchantTotals($date);
    }

    public function currencyTotals(): array
    {
        return $this->transactions->currencyTotals();
    }

    public function totalProcessedAmount(): array
    {
        return array_map(
            static fn (array $row) => ['currency' => $row['currency'], 'total_amount' => $row['total_amount']],
            $this->currencyTotals()
        );
    }
}
