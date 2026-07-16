<?php

declare(strict_types=1);

namespace App\Validators;

use DateTime;

final class TransactionValidator
{
    private const MAX_LENGTHS = [
        'transaction_id' => 100,
        'currency' => 3,
        'transaction_type' => 50,
        'status' => 50,
        'merchant' => 150,
        'account' => 100,
        'card_number' => 25,
        'terminal_id' => 50,
        'merchant_id' => 100,
        'external_reference' => 150,
    ];

    public function validate(array $row): array
    {
        $errors = [];

        if ($row['transaction_id'] === '') {
            $errors[] = 'transaction_id is required';
        }

        $occurredAt = DateTime::createFromFormat('Y-m-d H:i:s', $row['occurred_at']);

        if ($occurredAt === false || $occurredAt->format('Y-m-d H:i:s') !== $row['occurred_at']) {
            $errors[] = 'occurred_at must be a valid datetime';
        }

        if (!preg_match('/^-?\d+\.\d{2}$/', (string) $row['amount'])) {
            $errors[] = 'amount must be a valid decimal';
        }

        if ($row['currency'] === '') {
            $errors[] = 'currency is required';
        }

        if ($row['transaction_type'] === '') {
            $errors[] = 'transaction_type is required';
        }

        if ($row['status'] === '') {
            $errors[] = 'status is required';
        }

        foreach (self::MAX_LENGTHS as $field => $maxLength) {
            if (isset($row[$field]) && strlen((string) $row[$field]) > $maxLength) {
                $errors[] = "{$field} must not exceed {$maxLength} characters";
            }
        }

        return $errors;
    }
}
