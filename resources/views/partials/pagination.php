<?php
/** @var int $page */
/** @var int $lastPage */
/** @var string $baseUrl */

$query ??= [];
if ($lastPage <= 1) {
    return;
}
$linkFor = fn (int $p) => $baseUrl . '?' . http_build_query(array_merge($query, ['page' => $p]));
?>

<div class="flex items-center justify-between mt-4 text-sm">
    <?php if ($page > 1): ?>
        <a href="<?= e($linkFor($page - 1)) ?>" class="text-blue-600 hover:underline">&larr; Previous</a>
    <?php else: ?>
        <span></span>
    <?php endif; ?>

    <span class="text-slate-500">Page <?= e((string) $page) ?> of <?= e((string) $lastPage) ?></span>

    <?php if ($page < $lastPage): ?>
        <a href="<?= e($linkFor($page + 1)) ?>" class="text-blue-600 hover:underline">Next &rarr;</a>
    <?php else: ?>
        <span></span>
    <?php endif; ?>
</div>
