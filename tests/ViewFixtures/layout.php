<!DOCTYPE html>
<html lang="en">
<head>
    <title><?= $view->escape($title) ?></title>
</head>
<body>
    <header><?= $view->section('heading', 'Fallback') ?></header>
    <main><?= $view->section('content') ?></main>
</body>
</html>
