<?php

namespace app\Controllers\Web;
use App\Core\Database;
use App\Services\ImportService;
use App\Validators\UploadValidator;

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

    public function store(): never
    {
        $file = $_FILES['csv'] ?? null;

        UploadValidator::validate($file);

        (new ImportService(Database::connection()))->import($file['tmp_name'], $file['name']);

        redirect('/imports');
    }
}
