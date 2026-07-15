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

<div class="bg-white rounded-lg border border-slate-200 overflow-x-auto">
    <h2 class="text-lg font-medium p-5 pb-0">Rejected Transactions</h2>
    <table class="w-full text-sm mt-3">
        <thead class="bg-slate-50 text-slate-500 text-left">
        <tr>
            <th class="px-4 py-2">Row #</th>
            <th class="px-4 py-2">Transaction ID</th>
            <th class="px-4 py-2">Validation Errors</th>
            <th class="px-4 py-2">Raw Data</th>
        </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
        <?php foreach ($rejected->items as $row): ?>
            <tr class="align-top">
                <td class="px-4 py-2"><?= e((string) $row->rowNo) ?></td>
                <td class="px-4 py-2"><?= e($row->transactionId ?? '-') ?></td>
                <td class="px-4 py-2 text-red-600"><?= e(implode('; ', $row->errors)) ?></td>
                <td class="px-4 py-2">
                    <button type="button" class="raw-data-toggle text-blue-600 hover:underline" data-target="raw-data-<?= e((string) $row->id) ?>">
                        View
                    </button>
                </td>
            </tr>
            <tr id="raw-data-<?= e((string) $row->id) ?>" class="hidden">
                <td colspan="4" class="px-4 py-3 bg-slate-50">
                    <pre class="text-xs overflow-x-auto"><?= e(json_encode($row->rawData, JSON_PRETTY_PRINT)) ?></pre>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if ($rejected->items === []): ?>
            <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">No rejected rows.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="px-4 pb-4">
    <?= view('partials/pagination', ['page' => $rejected->page, 'lastPage' => $rejected->lastPage(), 'baseUrl' => '/imports/' . $batch->id]) ?>
</div>

// hide and show raw data for rejected transaction row
<script>
    document.querySelectorAll('.raw-data-toggle').forEach(function (button) {
        button.addEventListener('click', function () {
            const row = document.getElementById(button.dataset.target);
            const isHidden = row.classList.toggle('hidden');
            button.textContent = isHidden ? 'View' : 'Close';
        });
    });
</script>
