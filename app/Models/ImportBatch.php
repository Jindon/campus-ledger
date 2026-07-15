<?php

declare(strict_types=1);

namespace App\Models;

final class ImportBatch
{
    public function __construct(
        public int $id,
        public string $filename,
        public string $checksum,
        public int $importedCount,
        public int $rejectedCount,
        public int $duplicateCount,
        public string $startedAt,
        public ?string $finishedAt,
        public string $createdAt,
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            filename: $row['filename'],
            checksum: $row['checksum'],
            importedCount: (int) $row['imported_count'],
            rejectedCount: (int) $row['rejected_count'],
            duplicateCount: (int) $row['duplicate_count'],
            startedAt: $row['started_at'],
            finishedAt: $row['finished_at'],
            createdAt: $row['created_at'],
        );
    }

    public function processingTimeMs(): ?int
    {
        if ($this->finishedAt === null) {
            return null;
        }

        return (strtotime($this->finishedAt) - strtotime($this->startedAt)) * 1000;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'filename' => $this->filename,
            'checksum' => $this->checksum,
            'imported_count' => $this->importedCount,
            'rejected_count' => $this->rejectedCount,
            'duplicate_count' => $this->duplicateCount,
            'started_at' => $this->startedAt,
            'finished_at' => $this->finishedAt,
            'created_at' => $this->createdAt,
            'processing_time_ms' => $this->processingTimeMs(),
        ];
    }
}
