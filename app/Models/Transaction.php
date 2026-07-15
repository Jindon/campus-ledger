<?php

declare(strict_types=1);

namespace App\Models;

final class Transaction
{
    public function __construct(
        public int $id,
        public string $transactionId,
        public string $occurredAt,
        public string $amount,
        public string $currency,
        public string $transactionType,
        public string $status,
        public ?string $merchant,
        public ?string $account,
        public ?string $cardNumber,
        public int $importBatchId,
        public string $createdAt,
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            transactionId: $row['transaction_id'],
            occurredAt: $row['occurred_at'],
            amount: $row['amount'],
            currency: $row['currency'],
            transactionType: $row['transaction_type'],
            status: $row['status'],
            merchant: $row['merchant'],
            account: $row['account'],
            cardNumber: $row['card_number'],
            importBatchId: (int) $row['import_batch_id'],
            createdAt: $row['created_at'],
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'transaction_id' => $this->transactionId,
            'occurred_at' => $this->occurredAt,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'transaction_type' => $this->transactionType,
            'status' => $this->status,
            'merchant' => $this->merchant,
            'account' => $this->account,
            'card_number' => $this->cardNumber,
            'import_batch_id' => $this->importBatchId,
            'created_at' => $this->createdAt,
        ];
    }
}
