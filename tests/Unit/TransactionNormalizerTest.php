<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\TransactionNormalizer;
use PHPUnit\Framework\TestCase;

final class TransactionNormalizerTest extends TestCase
{
    private TransactionNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new TransactionNormalizer();
    }

    public function test_trims_whitespace_on_string_fields(): void
    {
        $result = $this->normalizer->normalize([
            'transaction_id' => '  TX1  ',
            'occurred_at' => ' 2026-01-01 10:00:00 ',
            'amount' => ' 10.00 ',
            'currency' => ' usd ',
            'transaction_type' => ' purchase ',
            'status' => ' settled ',
        ]);

        self::assertSame('TX1', $result['transaction_id']);
        self::assertSame('purchase', $result['transaction_type']);
        self::assertSame('settled', $result['status']);
    }

    public function test_uppercases_currency(): void
    {
        $result = $this->normalizer->normalize(['currency' => 'eur']);

        self::assertSame('EUR', $result['currency']);
    }

    public function test_normalizes_amount_with_thousands_separator(): void
    {
        $result = $this->normalizer->normalize(['amount' => '1,250.5']);

        self::assertSame('1250.50', $result['amount']);
    }

    public function test_leaves_invalid_amount_untouched_for_validator_to_reject(): void
    {
        $result = $this->normalizer->normalize(['amount' => 'not-a-number']);

        self::assertSame('not-a-number', $result['amount']);
    }

    public function test_parses_occurred_at_into_canonical_format(): void
    {
        $result = $this->normalizer->normalize(['occurred_at' => '01/02/2026 3:04pm']);

        self::assertSame('2026-01-02 15:04:00', $result['occurred_at']);
    }

    public function test_optional_fields_default_to_null_when_blank(): void
    {
        $result = $this->normalizer->normalize(['merchant_name' => '']);

        self::assertNull($result['merchant']);
    }

    public function test_optional_field_with_literal_zero_is_preserved(): void
    {
        // "0" is falsy in PHP — a naive truthiness check would wrongly drop it to null.
        $result = $this->normalizer->normalize(['merchant_name' => '0', 'account' => '0', 'card_number' => '0']);

        self::assertSame('0', $result['merchant']);
        self::assertSame('0', $result['account']);
        self::assertSame('0', $result['card_number']);
    }

    public function test_maps_merchant_name_column_to_merchant(): void
    {
        $result = $this->normalizer->normalize(['merchant_name' => 'Health Center']);

        self::assertSame('Health Center', $result['merchant']);
    }

    public function test_optional_field_with_only_whitespace_becomes_null(): void
    {
        $result = $this->normalizer->normalize(['merchant_name' => '   ']);

        self::assertNull($result['merchant']);
    }
}
