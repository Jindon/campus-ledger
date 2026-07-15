<?php

declare(strict_types=1);

namespace App\Models;

final class RejectedTransaction
{
    public function __construct(
        public int $id,
        public int $importBatchId,
        public int $rowNo,
        public ?string $transactionId,
        public array $errors,
        public array $rawData,
        public string $createdAt,
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            importBatchId: (int) $row['import_batch_id'],
            rowNo: (int) $row['row_no'],
            transactionId: $row['transaction_id'],
            errors: json_decode($row['errors'], true) ?? [],
            rawData: json_decode($row['raw_data'], true) ?? [],
            createdAt: $row['created_at'],
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'import_batch_id' => $this->importBatchId,
            'row_no' => $this->rowNo,
            'transaction_id' => $this->transactionId,
            'errors' => $this->errors,
            'raw_data' => $this->rawData,
            'created_at' => $this->createdAt,
        ];
    }
}
