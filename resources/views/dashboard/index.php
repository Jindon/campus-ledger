<?php

use App\Models\ImportBatch;

/** @var ?ImportBatch $lastImport */
/** @var int $totalTransactions */
/** @var int $totalImports */
/** @var int $totalRejected */

?>
<h1 class="text-2xl font-semibold mb-6">Dashboard</h1>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
    <div class="bg-white rounded-lg border border-slate-200 p-5">
        <p class="text-sm text-slate-500">Total Transactions</p>
        <p class="text-3xl font-semibold mt-1"><?= e(number_format($totalTransactions)) ?></p>
    </div>
    <div class="bg-white rounded-lg border border-slate-200 p-5">
        <p class="text-sm text-slate-500">Total Imports</p>
        <p class="text-3xl font-semibold mt-1"><?= e(number_format($totalImports)) ?></p>
    </div>
    <div class="bg-white rounded-lg border border-slate-200 p-5">
        <p class="text-sm text-slate-500">Total Rejected Rows</p>
        <p class="text-3xl font-semibold mt-1"><?= e(number_format($totalRejected)) ?></p>
    </div>
</div>

<div class="bg-white rounded-lg border border-slate-200 p-5 mb-8">
    <h2 class="text-lg font-medium mb-3">Last Import</h2>
    <?php if ($lastImport === null): ?>
        <p class="text-slate-500 text-sm">No imports yet.</p>
    <?php else: ?>
        <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
            <div><dt class="text-slate-500">Filename</dt><dd class="font-medium"><?= e($lastImport->filename) ?></dd></div>
            <div><dt class="text-slate-500">Imported</dt><dd class="font-medium"><?= e((string) $lastImport->importedCount) ?></dd></div>
            <div><dt class="text-slate-500">Rejected</dt><dd class="font-medium"><?= e((string) $lastImport->rejectedCount) ?></dd></div>
            <div><dt class="text-slate-500">Duplicates</dt><dd class="font-medium"><?= e((string) $lastImport->duplicateCount) ?></dd></div>
        </dl>
        <a href="<?= '/imports/' . $lastImport->id?>" class="inline-block mt-4 text-blue-600 text-sm hover:underline">View details &rarr;</a>
    <?php endif; ?>
</div>

<div class="flex gap-3">
    <a href="/imports" class="bg-white border border-slate-300 px-4 py-2 rounded-md text-sm font-medium hover:bg-slate-100">Go to Imports</a>
    <a href="/transactions" class="bg-white border border-slate-300 px-4 py-2 rounded-md text-sm font-medium hover:bg-slate-100">View Transactions</a>
    <a href="/reports" class="bg-white border border-slate-300 px-4 py-2 rounded-md text-sm font-medium hover:bg-slate-100">View Reports</a>
</div>
