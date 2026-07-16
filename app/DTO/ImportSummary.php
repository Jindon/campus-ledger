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
}
