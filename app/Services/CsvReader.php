<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ValidationException;
use Generator;

final class CsvReader
{
    public const REQUIRED_COLUMNS = [
        'transaction_id', 'occurred_at', 'amount', 'currency', 'transaction_type', 'status',
    ];

    /** @return Generator<int, array<string, ?string>> row number (1-based, header excluded) => row */
    public function read(string $path): Generator
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new \RuntimeException("Unable to open file: {$path}");
        }

        try {
            $header = fgetcsv($handle, escape: '\\');
            if ($header === false) {
                throw new ValidationException(['file' => 'CSV file is empty.']);
            }

            $header = array_map(fn ($column) => strtolower(trim((string) $column)), $header);
            $missing = array_diff(self::REQUIRED_COLUMNS, $header);
            if ($missing !== []) {
                throw new ValidationException(['file' => 'CSV is missing required columns: ' . implode(', ', $missing)]);
            }

            $rowNo = 0;
            while (($row = fgetcsv($handle, escape: '\\')) !== false) {
                if ($row === [null]) {
                    continue; // blank line
                }

                $rowNo++;
                $columnCount = count($header);
                $row = array_slice(array_pad($row, $columnCount, null), 0, $columnCount);
                yield $rowNo => array_combine($header, $row);
            }
        } finally {
            fclose($handle);
        }
    }
}
