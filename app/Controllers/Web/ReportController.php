<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Database;
use App\Services\ReportService;

final class ReportController
{
    public function index(): string
    {
        $report = new ReportService(Database::connection());
        $date = ReportService::resolveDate($_GET['date'] ?? null);

        $currencyTotals = $report->currencyTotals();

        $content = view('reports/index', [
            'date' => $date,
            'daily' => $report->dailySummary($date),
            'dailyMerchantTotals' => $report->merchantTotals($date),
            'merchantTotals' => $report->merchantTotals(),
            'currencyTotals' => $currencyTotals,
        ]);

        return view('layouts/app', ['title' => 'Reports', 'active' => 'reports', 'content' => $content]);
    }
}
