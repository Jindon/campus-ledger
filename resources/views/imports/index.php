<?php
use App\DTO\PageResult;

/** @var PageResult $batches */
?>

<h1 class="text-2xl font-semibold mb-6">Imports</h1>

<div class="bg-white rounded-lg border border-slate-200 p-5 mb-8">
    <h2 class="text-lg font-medium mb-3">Import CSV</h2>
    <form action="/imports" method="post" enctype="multipart/form-data" data-loading-text="Importing…" class="flex items-center gap-3">
        <?= csrf_field() ?>
        <input type="file" name="csv" accept=".csv,text/csv" required
               class="text-sm border border-slate-300 rounded-md px-3 py-2 flex-1">
        <button type="submit" class="bg-slate-900 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-slate-800 disabled:opacity-60 disabled:cursor-not-allowed">
            Start Import
        </button>
    </form>
</div>

<div class="bg-white rounded-lg border border-slate-200 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-left">
        <tr>
            <th class="px-4 py-2">ID</th>
            <th class="px-4 py-2">Filename</th>
            <th class="px-4 py-2">Imported</th>
            <th class="px-4 py-2">Rejected</th>
            <th class="px-4 py-2">Duplicates</th>
            <th class="px-4 py-2">Imported At</th>
            <th class="px-4 py-2">Actions</th>
        </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
        <?php foreach ($batches->items as $batch): ?>
            <tr>
                <td class="px-4 py-2">#<?= e((string) $batch->id) ?></td>
                <td class="px-4 py-2"><?= e($batch->filename) ?></td>
                <td class="px-4 py-2"><?= e((string) $batch->importedCount) ?></td>
                <td class="px-4 py-2"><?= e((string) $batch->rejectedCount) ?></td>
                <td class="px-4 py-2"><?= e((string) $batch->duplicateCount) ?></td>
                <td class="px-4 py-2"><?= e($batch->createdAt) ?></td>
                <td class="px-4 py-2">
                    <a href="<?= '/imports/' . $batch->id ?>" class="text-blue-600 hover:underline">View Details</a>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if ($batches->items === []): ?>
            <tr><td colspan="7" class="px-4 py-6 text-center text-slate-500">No imports yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?= view('partials/pagination', ['page' => $batches->page, 'lastPage' => $batches->lastPage(), 'baseUrl' => '/imports']) ?>

