<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Database;
use App\Repositories\TransactionRepository;

final class TransactionController
{
    public function index(array $query): string
    {
        $pdo = Database::connection();

        $filters = $this->extractFilters($query);
        $page = max(1, (int) ($query['page'] ?? 1));

        $result = (new TransactionRepository($pdo))->search($filters, $page);

        $content = view('transactions/index', ['result' => $result, 'filters' => $filters]);

        return view('layouts/app', ['title' => 'Transactions', 'active' => 'transactions', 'content' => $content]);
    }

    private function extractFilters(array $query): array
    {
        $keys = ['date_from', 'date_to', 'merchant', 'status', 'account', 'card_number', 'amount_min', 'amount_max', 'q'];
        $filters = [];
        foreach ($keys as $key) {
            if (!empty($query[$key])) {
                $filters[$key] = trim((string) $query[$key]);
            }
        }

        return $filters;
    }
}
