<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTO\PageResult;
use App\Models\Transaction;
use PDO;

final class TransactionRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function insert(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO transactions
                (transaction_id, occurred_at, amount, currency, transaction_type, status, merchant, account, card_number, import_batch_id)
             VALUES
                (:transaction_id, :occurred_at, :amount, :currency, :transaction_type, :status, :merchant, :account, :card_number, :import_batch_id)'
        );
        $stmt->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    public function existingTransactionIds(array $transactionIds): array
    {
        if ($transactionIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($transactionIds), '?'));
        $stmt = $this->pdo->prepare("SELECT transaction_id FROM transactions WHERE transaction_id IN ({$placeholders})");
        $stmt->execute(array_values($transactionIds));

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
