<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTO\PageResult;
use App\Models\Transaction;
use PDO;
use PDOStatement;

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

    public function search(array $filters, int $page = 1, int $perPage = 25): PageResult
    {
        [$where, $params] = $this->buildWhere($filters);
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $total = (int) $this->prepareAndRun(
            "SELECT COUNT(*) FROM transactions {$where}",
            $params
        )->fetchColumn();

        $stmt = $this->prepareAndRun(
            "SELECT * FROM transactions {$where} ORDER BY occurred_at DESC, id DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $items = array_map(fn (array $row) => Transaction::fromRow($row), $stmt->fetchAll());

        return new PageResult($items, $total, $page, $perPage);
    }

    private function buildWhere(array $filters): array
    {
        $clauses = [];
        $params = [];

        if (!empty($filters['date_from'])) {
            $clauses[] = 'occurred_at >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $clauses[] = 'occurred_at <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }
        if (!empty($filters['merchant'])) {
            $clauses[] = 'merchant LIKE :merchant';
            $params['merchant'] = '%' . $filters['merchant'] . '%';
        }
        if (!empty($filters['status'])) {
            $clauses[] = 'status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['account'])) {
            $clauses[] = 'account = :account';
            $params['account'] = $filters['account'];
        }
        if (!empty($filters['card_number'])) {
            $clauses[] = 'card_number = :card_number';
            $params['card_number'] = $filters['card_number'];
        }
        if (!empty($filters['amount_min'])) {
            $clauses[] = 'amount >= :amount_min';
            $params['amount_min'] = $filters['amount_min'];
        }
        if (!empty($filters['amount_max'])) {
            $clauses[] = 'amount <= :amount_max';
            $params['amount_max'] = $filters['amount_max'];
        }
        if (!empty($filters['q'])) {
            // Native (non-emulated) prepared statements require a distinct placeholder
            // per occurrence — reusing :q twice throws "Invalid parameter number".
            $clauses[] = '(transaction_id LIKE :q1 OR merchant LIKE :q2)';
            $params['q1'] = $params['q2'] = '%' . $filters['q'] . '%';
        }

        $where = empty($clauses) ? '' : 'WHERE ' . implode(' AND ', $clauses);

        return [$where, $params];
    }

    private function prepareAndRun(string $sql, array $params): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }

    public function dailySummary(string $date): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT currency, COUNT(*) AS transaction_count, SUM(amount) AS total_amount
             FROM transactions
             WHERE DATE(occurred_at) = :date
             GROUP BY currency
             ORDER BY currency ASC'
        );
        $stmt->execute(['date' => $date]);

        return $stmt->fetchAll();
    }

    public function merchantTotals(?string $date = null): array
    {
        $where = 'WHERE merchant IS NOT NULL';
        $params = [];
        if ($date !== null) {
            $where .= ' AND DATE(occurred_at) = :date';
            $params['date'] = $date;
        }

        $stmt = $this->pdo->prepare(
            "SELECT merchant, currency, COUNT(*) AS transaction_count, SUM(amount) AS total_amount
             FROM transactions
             {$where}
             GROUP BY merchant, currency
             ORDER BY merchant ASC, total_amount DESC"
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function currencyTotals(): array
    {
        return $this->pdo->query(
            'SELECT currency, COUNT(*) AS transaction_count, SUM(amount) AS total_amount
             FROM transactions
             GROUP BY currency
             ORDER BY total_amount DESC'
        )->fetchAll();
    }
}
