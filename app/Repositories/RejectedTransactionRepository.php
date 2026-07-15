<?php

declare(strict_types=1);

namespace App\Repositories;

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
}
