<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Controllers\Web\ImportController;
use App\Exceptions\NotFoundException;
use App\Repositories\ImportBatchRepository;
use Tests\DatabaseTestCase;

final class ImportPagesFeatureTest extends DatabaseTestCase
{
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
