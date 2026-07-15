<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTO\PageResult;
use App\Models\RejectedTransaction;
use PDO;

final class RejectedTransactionRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function insert(int $importBatchId, int $rowNo, ?string $transactionId, array $errors, array $rawData): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO rejected_transactions (import_batch_id, row_no, transaction_id, errors, raw_data)
             VALUES (:import_batch_id, :row_no, :transaction_id, :errors, :raw_data)'
        );
        $stmt->execute([
            'import_batch_id' => $importBatchId,
            'row_no' => $rowNo,
            'transaction_id' => $transactionId,
            'errors' => json_encode($errors, JSON_THROW_ON_ERROR),
            'raw_data' => json_encode($rawData, JSON_THROW_ON_ERROR),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function findByImportBatch(int $importBatchId, int $page = 1, int $perPage = 25): PageResult
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->pdo->prepare('SELECT COUNT(*) FROM rejected_transactions WHERE import_batch_id = :id');
        $countStmt->execute(['id' => $importBatchId]);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->pdo->prepare(
            "SELECT * FROM rejected_transactions WHERE import_batch_id = :id ORDER BY row_no ASC LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute(['id' => $importBatchId]);

        $items = array_map(fn (array $row) => RejectedTransaction::fromRow($row), $stmt->fetchAll());

        return new PageResult($items, $total, $page, $perPage);
    }
}
