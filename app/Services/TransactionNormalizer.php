<?php

declare(strict_types=1);

namespace App\Services;

final class TransactionNormalizer
{
    public function normalize(array $row): array
    {
        $get = static fn (string $key): string => trim((string) ($row[$key] ?? ''));
        // Trims and maps blank to null without PHP's string truthiness rules, which
        // would otherwise treat a legitimate value of "0" as empty and drop it.
        $optional = static function (string $key) use ($row): ?string {
            $trimmed = trim((string) ($row[$key] ?? ''));
            return $trimmed === '' ? null : $trimmed;
        };

        $amount = str_replace(',', '', $get('amount'));
        $occurredAt = $get('occurred_at');
        $timestamp = strtotime($occurredAt);

        return [
            'transaction_id' => $get('transaction_id'),
            'occurred_at' => $timestamp !== false ? date('Y-m-d H:i:s', $timestamp) : $occurredAt,
            'amount' => is_numeric($amount) ? number_format((float) $amount, 2, '.', '') : $amount,
            'currency' => strtoupper($get('currency')),
            'transaction_type' => $get('transaction_type'),
            'status' => $get('status'),
            'merchant' => $optional('merchant_name'),
            'account' => $optional('account'),
            'card_number' => $optional('card_number'),
        ];
    }
}
