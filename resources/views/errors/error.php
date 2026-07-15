<?php
/** @var int $statusCode */
/** @var string $message */
/** @var ?Throwable $trace */
$trace ??= null;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Error <?= e((string) $statusCode) ?> - CampusLedger</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen flex items-center justify-center">
    <div class="<?= $trace ? 'max-w-3xl' : 'max-w-md' ?> text-center p-8">
        <p class="text-6xl font-bold text-slate-300"><?= e((string) $statusCode) ?></p>
        <p class="mt-4 text-lg"><?= e($message) ?></p>
        <a href="/" class="mt-6 inline-block text-blue-600 hover:underline">Back to dashboard</a>

        <?php if ($trace !== null): ?>
            <pre class="mt-6 bg-slate-900 text-slate-100 text-xs text-left p-4 rounded-md overflow-x-auto"><?= e(get_class($trace) . ': ' . $trace->getMessage() . "\n\n" . $trace->getTraceAsString()) ?></pre>
        <?php endif; ?>
    </div>
</body>
</html>
