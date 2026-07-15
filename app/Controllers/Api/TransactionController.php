<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Database;
use App\Models\Transaction;
use App\Repositories\TransactionRepository;

final class TransactionController
{
    public function index(array $query): array
    {
        $pdo = Database::connection();
        $keys = ['date_from', 'date_to', 'merchant', 'status', 'account', 'card_number', 'amount_min', 'amount_max', 'q'];
        $filters = array_filter(array_intersect_key($query, array_flip($keys)), fn ($v) => $v !== '' && $v !== null);
        $page = max(1, (int) ($query['page'] ?? 1));

        $result = (new TransactionRepository($pdo))->search($filters, $page);

        return [
            'data' => array_map(fn (Transaction $transaction) => $transaction->toArray(), $result->items),
            'meta' => [
                'page' => $result->page,
                'per_page' => $result->perPage,
                'total' => $result->total,
                'last_page' => $result->lastPage(),
            ],
        ];
    }
}
