<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Validators\TransactionValidator;
use PHPUnit\Framework\TestCase;

final class TransactionValidatorTest extends TestCase
{
    private TransactionValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new TransactionValidator();
    }

    private function validRow(array $overrides = []): array
    {
        return array_merge([
            'transaction_id' => 'TX1',
            'occurred_at' => '2026-01-01 10:00:00',
            'amount' => '10.00',
            'currency' => 'USD',
            'transaction_type' => 'purchase',
            'status' => 'settled',
        ], $overrides);
    }

    public function test_valid_row_has_no_errors(): void
    {
        self::assertSame([], $this->validator->validate($this->validRow()));
    }

    public function test_missing_transaction_id_is_rejected(): void
    {
        self::assertContains('transaction_id is required', $this->validator->validate($this->validRow(['transaction_id' => ''])));
    }

    public function test_invalid_datetime_is_rejected(): void
    {
        self::assertContains('occurred_at must be a valid datetime', $this->validator->validate($this->validRow(['occurred_at' => 'not-a-date'])));
    }

    public function test_datetime_with_out_of_range_values_is_rejected(): void
    {
        // Feb 30th, 25:61 — DateTime::createFromFormat silently rolls this over to a
        // different valid date instead of failing, so it must be caught explicitly.
        self::assertContains('occurred_at must be a valid datetime', $this->validator->validate($this->validRow(['occurred_at' => '2026-02-30 25:61:00'])));
    }

    public function test_invalid_amount_is_rejected(): void
    {
        self::assertContains('amount must be a valid decimal', $this->validator->validate($this->validRow(['amount' => 'abc'])));
    }

    public function test_missing_currency_is_rejected(): void
    {
        self::assertContains('currency is required', $this->validator->validate($this->validRow(['currency' => ''])));
    }

    public function test_missing_transaction_type_is_rejected(): void
    {
        self::assertContains('transaction_type is required', $this->validator->validate($this->validRow(['transaction_type' => ''])));
    }

    public function test_missing_status_is_rejected(): void
    {
        self::assertContains('status is required', $this->validator->validate($this->validRow(['status' => ''])));
    }

    public function test_negative_amount_is_valid(): void
    {
        self::assertSame([], $this->validator->validate($this->validRow(['amount' => '-25.50'])));
    }

    public function test_oversized_card_number_is_rejected(): void
    {
        $row = $this->validRow(['card_number' => str_repeat('1', 26)]);

        self::assertContains('card_number must not exceed 25 characters', $this->validator->validate($row));
    }

    public function test_card_number_at_max_length_is_valid(): void
    {
        $row = $this->validRow(['card_number' => str_repeat('1', 25)]);

        self::assertSame([], $this->validator->validate($row));
    }
}
