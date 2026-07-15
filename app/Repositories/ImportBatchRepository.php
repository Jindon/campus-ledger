<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTO\PageResult;
use App\Models\ImportBatch;
use PDO;

final class ImportBatchRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(string $filename, string $checksum, string $startedAt): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO import_batches (filename, checksum, started_at) VALUES (:filename, :checksum, :started_at)'
        );
        $stmt->execute(['filename' => $filename, 'checksum' => $checksum, 'started_at' => $startedAt]);

        return (int) $this->pdo->lastInsertId();
    }

    public function finish(int $id, int $importedCount, int $rejectedCount, int $duplicateCount, string $finishedAt): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE import_batches
             SET imported_count = :imported_count,
                 rejected_count = :rejected_count,
                 duplicate_count = :duplicate_count,
                 finished_at = :finished_at
             WHERE id = :id'
        );
        $stmt->execute([
            'imported_count' => $importedCount,
            'rejected_count' => $rejectedCount,
            'duplicate_count' => $duplicateCount,
            'finished_at' => $finishedAt,
            'id' => $id,
        ]);
    }

    public function find(int $id): ?ImportBatch
    {
        $stmt = $this->pdo->prepare('SELECT * FROM import_batches WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? ImportBatch::fromRow($row) : null;
    }

    public function latest(): ?ImportBatch
    {
        $row = $this->pdo->query('SELECT * FROM import_batches ORDER BY id DESC LIMIT 1')->fetch();

        return $row ? ImportBatch::fromRow($row) : null;
    }

    public function count(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM import_batches')->fetchColumn();
    }

    public function totalRejectedRows(): int
    {
        return (int) $this->pdo->query('SELECT COALESCE(SUM(rejected_count), 0) FROM import_batches')->fetchColumn();
    }

    public function paginate(int $page = 1, int $perPage = 25): PageResult
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $total = $this->count();
        $stmt = $this->pdo->prepare("SELECT * FROM import_batches ORDER BY id DESC LIMIT {$perPage} OFFSET {$offset}");
        $stmt->execute();

        $items = array_map(fn (array $row) => ImportBatch::fromRow($row), $stmt->fetchAll());

        return new PageResult($items, $total, $page, $perPage);
    }
}
