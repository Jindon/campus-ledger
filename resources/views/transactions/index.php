<?php

use App\DTO\PageResult;

/** @var PageResult $result */
/** @var array $filters */
?>
<h1 class="text-2xl font-semibold mb-6">Transactions</h1>

<form action="/transactions" method="get" class="bg-white rounded-lg border border-slate-200 p-5 mb-6 grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
    <input type="text" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="Search transaction ID or merchant"
           class="col-span-2 sm:col-span-4 border border-slate-300 rounded-md px-3 py-2">
    <label class="flex flex-col gap-1">
        <span class="text-slate-500">Date from</span>
        <input type="date" name="date_from" value="<?= e($filters['date_from'] ?? '') ?>" class="border border-slate-300 rounded-md px-3 py-2">
    </label>
    <label class="flex flex-col gap-1">
        <span class="text-slate-500">Date to</span>
        <input type="date" name="date_to" value="<?= e($filters['date_to'] ?? '') ?>" class="border border-slate-300 rounded-md px-3 py-2">
    </label>
    <label class="flex flex-col gap-1">
        <span class="text-slate-500">Merchant</span>
        <input type="text" name="merchant" value="<?= e($filters['merchant'] ?? '') ?>" class="border border-slate-300 rounded-md px-3 py-2">
    </label>
    <label class="flex flex-col gap-1">
        <span class="text-slate-500">Status</span>
        <input type="text" name="status" value="<?= e($filters['status'] ?? '') ?>" class="border border-slate-300 rounded-md px-3 py-2">
    </label>
    <label class="flex flex-col gap-1">
        <span class="text-slate-500">Account</span>
        <input type="text" name="account" value="<?= e($filters['account'] ?? '') ?>" class="border border-slate-300 rounded-md px-3 py-2">
    </label>
    <label class="flex flex-col gap-1">
        <span class="text-slate-500">Card Number</span>
        <input type="text" name="card_number" value="<?= e($filters['card_number'] ?? '') ?>" class="border border-slate-300 rounded-md px-3 py-2">
    </label>
    <label class="flex flex-col gap-1">
        <span class="text-slate-500">Amount Min</span>
        <input type="number" step="0.01" name="amount_min" value="<?= e($filters['amount_min'] ?? '') ?>" class="border border-slate-300 rounded-md px-3 py-2">
    </label>
    <label class="flex flex-col gap-1">
        <span class="text-slate-500">Amount Max</span>
        <input type="number" step="0.01" name="amount_max" value="<?= e($filters['amount_max'] ?? '') ?>" class="border border-slate-300 rounded-md px-3 py-2">
    </label>
    <div class="col-span-2 sm:col-span-4 flex gap-3">
        <button type="submit" class="bg-slate-900 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-slate-800">Filter</button>
        <a href="/transactions" class="bg-white border border-slate-300 px-4 py-2 rounded-md text-sm font-medium hover:bg-slate-100">Clear</a>
    </div>
</form>

<div class="bg-white rounded-lg border border-slate-200 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-left">
        <tr>
            <th class="px-4 py-2">Transaction ID</th>
            <th class="px-4 py-2">Date</th>
            <th class="px-4 py-2">Merchant</th>
            <th class="px-4 py-2">Amount</th>
            <th class="px-4 py-2">Status</th>
            <th class="px-4 py-2">Account</th>
            <th class="px-4 py-2">Card</th>
        </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
        <?php foreach ($result->items as $tx): ?>
            <tr>
                <td class="px-4 py-2 font-medium"><?= e($tx->transactionId) ?></td>
                <td class="px-4 py-2"><?= e($tx->occurredAt) ?></td>
                <td class="px-4 py-2"><?= e($tx->merchant ?? '-') ?></td>
                <td class="px-4 py-2"><?= e($tx->currency . ' ' . $tx->amount) ?></td>
                <td class="px-4 py-2"><?= e($tx->status) ?></td>
                <td class="px-4 py-2"><?= e($tx->account ?? '-') ?></td>
                <td class="px-4 py-2"><?= e($tx->cardNumber ?? '-') ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($result->items === []): ?>
            <tr><td colspan="7" class="px-4 py-6 text-center text-slate-500">No transactions found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?= view('partials/pagination', ['page' => $result->page, 'lastPage' => $result->lastPage(), 'baseUrl' => '/transactions', 'query' => $filters]) ?>
