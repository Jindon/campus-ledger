<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;
use App\DTO\ImportSummary;
use App\Repositories\ImportBatchRepository;
use App\Repositories\RejectedTransactionRepository;
use App\Repositories\TransactionRepository;
use App\Validators\TransactionValidator;
use JsonException;
use PDO;
use Throwable;

final class ImportService
{
    private readonly CsvReader $csvReader;
    private readonly TransactionValidator $validator;
    private readonly TransactionNormalizer $normalizer;
    private readonly ImportBatchRepository $importBatchRepository;
    private readonly RejectedTransactionRepository $rejectedTransactionRepository;
    private readonly TransactionRepository $transactionRepository;

    private const CHUNK_SIZE = 1000;

    public function __construct(private PDO $pdo)
    {
        $this->csvReader = new CsvReader();
        $this->validator = new TransactionValidator();
        $this->normalizer = new TransactionNormalizer();
        $this->importBatchRepository = new ImportBatchRepository($pdo);
        $this->rejectedTransactionRepository = new RejectedTransactionRepository($pdo);
        $this->transactionRepository = new TransactionRepository($pdo);
    }

    /**
     * @throws Throwable
     */
    public function import(string $path, ?string $originalFilename = null): ImportSummary
    {
        $startedAtMs = microtime(true);
        $filename = $originalFilename ?? basename($path);

        // Not really needed for now, but can be used to detect duplicate uploads
        $checksum = hash_file('sha256', $path);

        $imported = 0;
        $rejected = 0;
        $duplicates = 0;
        $seenIds = [];
        $chunk = [];

        $this->pdo->beginTransaction();

        try {
            $batchId = $this->importBatchRepository->create($filename, $checksum, date('Y-m-d H:i:s', (int) $startedAtMs));

            foreach ($this->csvReader->read($path) as $rowNo => $raw) {
                $normalized = $this->normalizer->normalize($raw);

                $errors = $this->validator->validate($normalized);

                $transactionId = $normalized['transaction_id'] ?? null;

                $alreadyProcessed = isset($seenIds[$transactionId]);

                if (!empty($errors) || $alreadyProcessed) {
                    $this->rejectedTransactionRepository->insert(
                        $batchId,
                        $rowNo,
                        $transactionId,
                        $alreadyProcessed ? ['Duplicate transaction_id'] : $errors,
                        $raw,
                    );

                    $alreadyProcessed ? $duplicates++ : $rejected++;

                    // proceed as it doesn't need to be added in the chunk array
                    continue;
                }

                // put in seen id to help detect duplicate in the current upload operation
                $seenIds[$transactionId] = true;

                // put in chunk for batch processing
                $chunk[] = ['row_no' => $rowNo, 'raw' => $raw, 'data' => $normalized];

                // if chunk size is reached
                // flush and save the transactions in chunk array
                if (count($chunk) >= self::CHUNK_SIZE) {
                    [$imp, $dup] = $this->flushChunk($chunk, $batchId);
                    $imported += $imp;
                    $duplicates += $dup;
                    $chunk = [];
                }

            }

            // if chunk is still not empty after the loop
            // flush and add the remaining transactions from the chunk here
            if (!empty($chunk)) {
                [$imp, $dup] = $this->flushChunk($chunk, $batchId);
                $imported += $imp;
                $duplicates += $dup;
            }

            // finish the batch import operation
            $this->importBatchRepository->finish(
                $batchId,
                $imported,
                $rejected,
                $duplicates,
                date('Y-m-d H:i:s'),
            );

            $this->pdo->commit();
        } catch(Throwable $e) {
            $this->pdo->rollBack();

            throw $e;
        }

        $processingTimeMs = (int) round((microtime(true) - $startedAtMs) * 1000);

        Logger::info('CSV import completed', [
            'batch_id' => $batchId,
            'filename' => $filename,
            'imported' => $imported,
            'rejected' => $rejected,
            'duplicates' => $duplicates,
            'processing_time_ms' => $processingTimeMs,
        ]);

        return new ImportSummary($batchId, $imported, $rejected, $duplicates, $processingTimeMs);
    }

    /**
     * @throws JsonException
     */
    private function flushChunk(array $chunk, int $batchId): array
    {
        $ids = array_map(static fn (array $item) => $item['data']['transaction_id'], $chunk);
        // flipping the index with value for faster isset check on the id itself in use case below
        $existing = array_flip($this->transactionRepository->existingTransactionIds($ids));

        $imported = 0;
        $duplicates = 0;

        foreach ($chunk as $item) {
            $transactionId = $item['data']['transaction_id'];

            if (isset($existing[$transactionId])) {
                $this->rejectedTransactionRepository->insert($batchId, $item['row_no'], $transactionId, ['Duplicate transaction_id'], $item['raw']);
                $duplicates++;
                continue;
            }

            $this->transactionRepository->insert($item['data'] + ['import_batch_id' => $batchId]);
            $imported++;
        }

        return [$imported, $duplicates];
    }
}
