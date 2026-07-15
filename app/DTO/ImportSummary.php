<?php

declare(strict_types=1);

namespace App\DTO;

final class ImportSummary
{
    public function __construct(
        public int $importBatchId,
        public int $importedCount,
        public int $rejectedCount,
        public int $duplicateCount,
        public int $processingTimeMs,
    ) {
    }

    public function toArray(): array
    {
        return [
            'import_batch_id' => $this->importBatchId,
            'imported_count' => $this->importedCount,
            'rejected_count' => $this->rejectedCount,
            'duplicate_count' => $this->duplicateCount,
            'processing_time_ms' => $this->processingTimeMs,
        ];
    }
}
