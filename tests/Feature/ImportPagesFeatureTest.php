<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Controllers\Web\ImportController;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Repositories\ImportBatchRepository;
use App\Repositories\TransactionRepository;
use Tests\DatabaseTestCase;
use Tests\Support\RedirectException;

final class ImportPagesFeatureTest extends DatabaseTestCase
{
    private ?string $uploadedPath = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // Loaded here (not just tests/bootstrap.php) so this file works no matter
        // how PHPUnit is invoked (e.g. an IDE run config with its own bootstrap
        // path). Without it, ImportController::store()'s real redirect() calls
        // exit() and kills the whole PHPUnit process instead of failing the test.
        require_once __DIR__ . '/../Support/import_controller_redirect_override.php';
    }

    protected function tearDown(): void
    {
        if ($this->uploadedPath !== null) {
            @unlink($this->uploadedPath);
        }
        unset($_FILES['csv']);
    }

    private function uploadCsv(string $contents): void
    {
        $this->uploadedPath = tempnam(sys_get_temp_dir(), 'import_upload_test_');
        file_put_contents($this->uploadedPath, $contents);

        $_FILES['csv'] = [
            'name' => 'transactions.csv',
            'type' => 'text/csv',
            'tmp_name' => $this->uploadedPath,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($this->uploadedPath),
        ];
    }

    public function test_store_imports_the_uploaded_csv_and_redirects_to_the_import_details_page(): void
    {
        $this->uploadCsv(
            "transaction_id,occurred_at,amount,currency,transaction_type,status\n" .
            "TX1,2026-01-01 10:00:00,10.00,usd,purchase,settled\n"
        );

        try {
            (new ImportController())->store();
            self::fail('Expected a redirect to the import details page.');
        } catch (RedirectException $e) {
            $batch = (new ImportBatchRepository($this->pdo))->paginate(1)->items[0];
            self::assertSame('/imports/' . $batch->id, $e->location);
        }

        self::assertSame(1, (new TransactionRepository($this->pdo))->count());
    }

    public function test_store_rejects_a_missing_upload(): void
    {
        $this->expectException(ValidationException::class);

        (new ImportController())->store();
    }

    public function test_import_batches_page_lists_batches(): void
    {
        (new ImportBatchRepository($this->pdo))->create('transactions.csv', 'checksum', date('Y-m-d H:i:s'));

        $html = (new ImportController())->index();

        self::assertStringContainsString('transactions.csv', $html);
        self::assertStringContainsString('Imports', $html);
    }

    public function test_import_details_page_shows_summary_and_rejected_rows(): void
    {
        $repo = new ImportBatchRepository($this->pdo);
        $id = $repo->create('transactions.csv', 'checksum', date('Y-m-d H:i:s'));
        $repo->finish($id, 5, 1, 0, date('Y-m-d H:i:s'));

        $html = (new ImportController())->show($id);

        self::assertStringContainsString("Import #{$id}", $html);
        self::assertStringContainsString('Rejected Transactions', $html);
    }

    public function test_import_details_page_404s_for_unknown_batch(): void
    {
        $this->expectException(NotFoundException::class);
        (new ImportController())->show(999999);
    }
}
