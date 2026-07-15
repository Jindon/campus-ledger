<?php
/** @var string $title */
/** @var string $content */

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
</head>
<body>
    <nav>
        <div>
            <span>CampusLedger</span>
            <div>
                <?php foreach ($navItems as $key => [$href, $label]): ?>
                    <a href="<?= e($href) ?>"><?= e($label) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </nav>
    <main>
        <?= $content ?>
    </main>
</body>
</html>
