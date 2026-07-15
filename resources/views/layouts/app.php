<?php
/** @var string $title */
/** @var string $content */

$active ??= '';
$navItems = [
    'dashboard' => ['/', 'Dashboard'],
    'imports' => ['/imports', 'Imports'],
    'transactions' => ['/transactions', 'Transactions'],
    'reports' => ['/reports', 'Reports'],
];
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= e($title) ?> - CampusLedger</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen">
    <nav class="bg-white border-b border-slate-200">
        <div class="max-w-6xl mx-auto px-4 flex items-center h-14 gap-6">
            <span class="font-semibold text-slate-800">CampusLedger</span>
            <div class="flex gap-1">
                <?php foreach ($navItems as $key => [$href, $label]): ?>
                    <a href="<?= e($href) ?>"
                       class="px-3 py-2 rounded-md text-sm font-medium <?= $active === $key ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' ?>"
                    ><?= e($label) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </nav>
    <main class="max-w-6xl mx-auto px-4 py-8">
        <?= $content ?>
    </main>

    <script>
        document.addEventListener('submit', function (e) {
            const form = e.target.closest('form[data-loading-text]');
            const button = form ? form.querySelector('button[type="submit"]') : null;
            if (!button) return;
            button.disabled = true;
            button.textContent = form.dataset.loadingText;
        });
    </script>
</body>
</html>
