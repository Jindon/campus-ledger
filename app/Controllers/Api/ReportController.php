<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Database;
use App\Services\ReportService;

final class ReportController
{
    public function daily(array $query = []): array
    {
        $report = new ReportService(Database::connection());
        $date = ReportService::resolveDate($query['date'] ?? null);

        return ['data' => $report->dailySummary($date), 'meta' => ['date' => $date]];
    }
}
