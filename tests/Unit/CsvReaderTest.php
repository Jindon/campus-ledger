<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\ValidationException;
use App\Services\CsvReader;
use PHPUnit\Framework\TestCase;

final class CsvReaderTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        $this->path = tempnam(sys_get_temp_dir(), 'csv_reader_test_');
    }

    protected function tearDown(): void
    {
        @unlink($this->path);
    }

    public function test_streams_rows_keyed_by_header(): void
    {
        file_put_contents($this->path, "transaction_id,occurred_at,amount,currency,transaction_type,status\n" .
            "TX1,2026-01-01 10:00:00,10.00,USD,purchase,settled\n" .
            "TX2,2026-01-02 10:00:00,20.00,USD,purchase,settled\n");

        $rows = iterator_to_array((new CsvReader())->read($this->path));

        self::assertCount(2, $rows);
        self::assertSame('TX1', $rows[1]['transaction_id']);
        self::assertSame('TX2', $rows[2]['transaction_id']);
    }

    public function test_skips_blank_lines(): void
    {
        file_put_contents($this->path, "transaction_id,occurred_at,amount,currency,transaction_type,status\n" .
            "TX1,2026-01-01 10:00:00,10.00,USD,purchase,settled\n\n" .
            "TX2,2026-01-02 10:00:00,20.00,USD,purchase,settled\n");

        $rows = iterator_to_array((new CsvReader())->read($this->path));

        self::assertCount(2, $rows);
    }

    public function test_throws_on_empty_file(): void
    {
        file_put_contents($this->path, '');

        $this->expectException(ValidationException::class);
        iterator_to_array((new CsvReader())->read($this->path));
    }

    public function test_throws_when_required_column_missing(): void
    {
        file_put_contents($this->path, "transaction_id,amount\nTX1,10.00\n");

        $this->expectException(ValidationException::class);
        iterator_to_array((new CsvReader())->read($this->path));
    }

    public function test_row_numbers_are_one_based_and_exclude_header(): void
    {
        file_put_contents($this->path, "transaction_id,occurred_at,amount,currency,transaction_type,status\n" .
            "TX1,2026-01-01 10:00:00,10.00,USD,purchase,settled\n");

        $rows = iterator_to_array((new CsvReader())->read($this->path));

        self::assertArrayHasKey(1, $rows);
    }
}
