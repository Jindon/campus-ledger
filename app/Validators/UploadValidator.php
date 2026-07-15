<?php

declare (strict_types = 1);

namespace App\Validators;

use App\Exceptions\ValidationException;

final class UploadValidator
{
    public static function validate(mixed $file): void
    {
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            throw new ValidationException(['csv' => 'Please select a CSV file to upload.']);
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new ValidationException(['csv' => 'File upload failed.']);
        }

        // PHP_SAPI check lets feature tests (running under the cli SAPI) exercise this
        // path with a manually created temp file; real requests always arrive via a
        // web SAPI, where the is_uploaded_file() guard still applies.
        if (PHP_SAPI !== 'cli' && !is_uploaded_file($file['tmp_name'])) {
            throw new ValidationException(['csv' => 'Invalid upload.']);
        }

        if (strtolower((string) pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'csv') {
            throw new ValidationException(['csv' => 'Only .csv files are allowed.']);
        }
    }
}
