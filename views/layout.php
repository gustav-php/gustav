<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $view->escape($title) ?></title>
    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="stylesheet" href="https://unpkg.com/@picocss/pico@1/css/pico.min.css">
    <style>
        :root {
            font-size: 14px;
        }

        .shiki {
            background: none !important;
        }
    </style>
</head>
<body>
<div>
    <main class="container">
        <nav>
            <ul>
                <li><strong>GustavPHP</strong></li>
            </ul>
            <ul>
                <li><?= $view->escape($version) ?></li>
            </ul>
        </nav>
        <?= $view->section('content') ?>
    </main>
</div>
</body>
</html>
