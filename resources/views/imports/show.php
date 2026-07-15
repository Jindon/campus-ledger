<?php
use App\DTO\PageResult;
use App\Models\ImportBatch;

/** @var ImportBatch $batch */
/** @var PageResult $rejected */

?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-semibold">Import #<?= e((string) $batch->id) ?></h1>
    <a href="/imports" class="text-blue-600 text-sm hover:underline">&larr; Back to Imports</a>
</div>

<div class="bg-white rounded-lg border border-slate-200 p-5 mb-8">
    <h2 class="text-lg font-medium mb-3">Import Summary</h2>
    <dl class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
        <div><dt class="text-slate-500">Filename</dt><dd class="font-medium"><?= e($batch->filename) ?></dd></div>
        <div><dt class="text-slate-500">Imported Rows</dt><dd class="font-medium"><?= e((string) $batch->importedCount) ?></dd></div>
        <div><dt class="text-slate-500">Rejected Rows</dt><dd class="font-medium"><?= e((string) $batch->rejectedCount) ?></dd></div>
        <div><dt class="text-slate-500">Duplicate Rows</dt><dd class="font-medium"><?= e((string) $batch->duplicateCount) ?></dd></div>
        <div><dt class="text-slate-500">Processing Time</dt><dd class="font-medium"><?= e($batch->processingTimeMs() === null ? '-' : $batch->processingTimeMs() . 'ms') ?></dd></div>
        <div><dt class="text-slate-500">Imported At</dt><dd class="font-medium"><?= e($batch->createdAt) ?></dd></div>
    </dl>
</div>
