<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Database;
use App\Repositories\ImportBatchRepository;
use App\Repositories\TransactionRepository;

final class DashboardController
{
    public function index(): string
    {
        $pdo = Database::connection();
        $importBatches = new ImportBatchRepository($pdo);
        $transactions = new TransactionRepository($pdo);

        $content = view('dashboard/index', [
            'lastImport' => $importBatches->latest(),
            'totalTransactions' => $transactions->count(),
            'totalImports' => $importBatches->count(),
            'totalRejected' => $importBatches->totalRejectedRows(),
        ]);

        return view('layouts/app', ['title' => 'Dashboard', 'active' => 'dashboard', 'content' => $content]);
    }
}
