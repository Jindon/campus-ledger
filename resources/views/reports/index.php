<?php

/** @var string $date */
/** @var array $daily */
/** @var array $dailyMerchantTotals */
/** @var array $merchantTotals */
/** @var array $currencyTotals */
/** @var array $totalProcessedAmount */

$currencyLabel = static fn (string $currency): string => $currency !== '' ? $currency : '—';
?>
<h1 class="text-2xl font-semibold mb-6">Reports</h1>

<section class="mb-10">
    <div class="flex items-center justify-between flex-wrap gap-3 mb-1">
        <h2 class="text-xl font-semibold">Daily Report</h2>
        <form action="/reports" method="get" data-loading-text="Generating…" class="flex items-center gap-2">
            <input type="date" name="date" value="<?= e($date) ?>"
                   class="text-sm border border-slate-300 rounded-md px-3 py-1.5">
            <button type="submit" class="bg-slate-900 text-white px-3 py-1.5 rounded-md text-sm font-medium hover:bg-slate-800 disabled:opacity-60 disabled:cursor-not-allowed">
                Generate
            </button>
        </form>
    </div>
    <p class="text-sm text-slate-500 mb-4">Settlement summary for <?= e($date) ?></p>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg border border-slate-200 overflow-x-auto">
            <h3 class="text-lg font-medium p-5 pb-0">Merchant Totals</h3>
            <?= view('partials/merchant_totals_table', [
                'rows' => $dailyMerchantTotals,
                'currencyLabel' => $currencyLabel,
                'emptyMessage' => 'No transactions on this date.',
            ]) ?>
        </div>

        <div class="bg-white rounded-lg border border-slate-200 overflow-x-auto">
            <h3 class="text-lg font-medium p-5 pb-0">Transaction Summary</h3>
            <?= view('partials/currency_breakdown_table', [
                'rows' => $daily,
                'currencyLabel' => $currencyLabel,
                'emptyMessage' => 'No transactions on this date.',
            ]) ?>
        </div>
    </div>
</section>

<section>
    <h2 class="text-xl font-semibold mb-4">Overall Report</h2>

    <div class="bg-white rounded-lg border border-slate-200 p-5 mb-6">
        <p class="text-sm text-slate-500 mb-2">Total Processed Amount</p>
        <div class="flex flex-wrap gap-x-8 gap-y-2">
            <?php foreach ($totalProcessedAmount as $row): ?>
                <p class="text-3xl font-semibold">
                    <?= e(number_format((float) $row['total_amount'], 2)) ?>
                    <span class="text-base font-medium text-slate-500"><?= e($currencyLabel($row['currency'])) ?></span>
                </p>
            <?php endforeach; ?>
            <?php if ($totalProcessedAmount === []): ?>
                <p class="text-3xl font-semibold text-slate-400">0.00</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg border border-slate-200 overflow-x-auto">
            <h3 class="text-lg font-medium p-5 pb-0">Merchant Totals</h3>
            <?= view('partials/merchant_totals_table', [
                'rows' => $merchantTotals,
                'currencyLabel' => $currencyLabel,
                'emptyMessage' => 'No data.',
            ]) ?>
        </div>

        <div class="bg-white rounded-lg border border-slate-200 overflow-x-auto">
            <h3 class="text-lg font-medium p-5 pb-0">Currency Totals</h3>
            <?= view('partials/currency_breakdown_table', [
                'rows' => $currencyTotals,
                'currencyLabel' => $currencyLabel,
                'emptyMessage' => 'No data.',
            ]) ?>
        </div>
    </div>
</section>
