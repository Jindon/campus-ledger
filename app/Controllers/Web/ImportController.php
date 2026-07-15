<?php

namespace app\Controllers\Web;
final class ImportController
{
    public function index(): string
    {
        $content = view('imports/index');

        return view('layouts/app', [
            'title' => 'Imports',
            'content' => $content,
        ]);
    }
}
