<?php

namespace App\Controllers\Api;

use App\Core\Database;
use App\Exceptions\NotFoundException;
use App\Models\ImportBatch;
use App\Models\RejectedTransaction;
use App\Repositories\ImportBatchRepository;
use App\Repositories\RejectedTransactionRepository;

final class ImportController
{
    public function index(array $query): array
    {
        $pdo = Database::connection();
        $page = max(1, (int) ($query['page'] ?? 1));
        $result = (new ImportBatchRepository($pdo))->paginate($page);

        return [
            'data' => array_map(fn (ImportBatch $batch) => $batch->toArray(), $result->items),
            'meta' => [
                'page' => $result->page,
                'per_page' => $result->perPage,
                'total' => $result->total,
                'last_page' => $result->lastPage(),
            ],
        ];
    }

    public function show(int $id, array $query = []): array
    {
        $pdo = Database::connection();
        $batch = (new ImportBatchRepository($pdo))->find($id);
        if ($batch === null) {
            throw new NotFoundException("Import batch #{$id} not found");
        }

        $page = max(1, (int) ($query['page'] ?? 1));
        $rejected = (new RejectedTransactionRepository($pdo))->findByImportBatch($id, $page);

        return [
            'data' => $batch->toArray() + [
                    'rejected_transactions' => array_map(fn (RejectedTransaction $rejectedTransaction) => $rejectedTransaction->toArray(), $rejected->items),
                ],
            'meta' => [
                'rejected_transactions' => [
                    'page' => $rejected->page,
                    'per_page' => $rejected->perPage,
                    'total' => $rejected->total,
                    'last_page' => $rejected->lastPage(),
                ],
            ],
        ];
    }
}
