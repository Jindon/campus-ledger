<?php
/** @var array $rows */
/** @var callable $currencyLabel */
/** @var string $emptyMessage */
?>

<table class="w-full text-sm mt-3">
    <thead class="bg-slate-50 text-slate-500 text-left">
    <tr><th class="px-4 py-2">Currency</th><th class="px-4 py-2">Count</th><th class="px-4 py-2">Total</th></tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
    <?php foreach ($rows as $row): ?>
        <tr>
            <td class="px-4 py-2"><?= e($currencyLabel($row['currency'])) ?></td>
            <td class="px-4 py-2"><?= e((string) $row['transaction_count']) ?></td>
            <td class="px-4 py-2"><?= e(number_format((float) $row['total_amount'], 2)) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if ($rows === []): ?>
        <tr><td colspan="3" class="px-4 py-6 text-center text-slate-500"><?= e($emptyMessage) ?></td></tr>
    <?php endif; ?>
    </tbody>
</table>
