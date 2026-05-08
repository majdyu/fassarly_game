<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function render_header(string $title, string $dir = 'ltr'): void
{
    ?>
    <!DOCTYPE html>
    <html lang="<?= $dir === 'rtl' ? 'ar' : 'fr' ?>" dir="<?= e($dir) ?>">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" href="/icon.png" type="image/png">
        <link rel="stylesheet" href="/app.css">
        <title><?= e($title) ?></title>
    </head>
    <body class="app-body">
    <main class="app-shell">
    <?php
}

function render_footer(): void
{
    ?>
    </main>
    </body>
    </html>
    <?php
}
