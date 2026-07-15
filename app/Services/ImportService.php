<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;
use App\Validators\TransactionValidator;
use PDO;
use Throwable;

final class ImportService
{
    private readonly CsvReader $csvReader;
    private readonly TransactionValidator $validator;
    private readonly TransactionNormalizer $normalizer;

    public function __construct(private PDO $pdo)
    {
        $this->csvReader = new CsvReader();
        $this->validator = new TransactionValidator();
        $this->normalizer = new TransactionNormalizer();
    }

    public function import(string $path, ?string $originalFilename = null): void
    {
        $this->pdo->beginTransaction();

        try {
            foreach ($this->csvReader->read($path) as $rowNo => $raw) {
                $normalized = $this->normalizer->normalize($raw);

                $errors = $this->validator->validate($normalized);

                if (!empty($errors)) {
                    Logger::info('CSV import errors', [
                        'error' => $errors,
                    ]);
                }
            }

            $this->pdo->commit();
        } catch(Throwable $e) {
            $this->pdo->rollBack();
        }
    }
}
