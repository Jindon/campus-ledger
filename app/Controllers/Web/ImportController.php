<?php

namespace app\Controllers\Web;
use App\Core\Database;
use App\Exceptions\NotFoundException;
use App\Repositories\ImportBatchRepository;
use App\Services\ImportService;
use App\Validators\UploadValidator;
use Throwable;

final class ImportController
{
    public function index(): string
    {
        $content = view('imports/index');

        return view('layouts/app', [
            'title' => 'Imports',
            'active' => 'imports',
            'content' => $content,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(): never
    {
        $file = $_FILES['csv'] ?? null;
        UploadValidator::validate($file);

        $summary = (new ImportService(Database::connection()))->import($file['tmp_name'], $file['name']);

        redirect('/imports/' . $summary->importBatchId);
    }

    public function show(int $id): string
    {
        $batch = (new ImportBatchRepository(Database::connection()))->find($id);

        if (!$batch) {
            throw new NotfoundException('Import batch '. $id .' not found');
        }

        $content = view('imports/show', ['batch' => $batch]);

        return view('layouts/app', [
            'title' => 'Import #' . $id,
            'active' => 'imports',
            'content' => $content,
        ]);
    }
}
