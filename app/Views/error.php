<?php
$flashMessage = null;
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · Fasilitas Kampus</title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="error-page">
    <main class="error-card">
        <span class="error-icon">!</span>
        <p class="eyebrow">PERMINTAAN TIDAK DAPAT DIPROSES</p>
        <h1><?= e($title) ?></h1>
        <p class="muted"><?= e($message) ?></p>
        <a class="button button-primary" href="index.php?page=dashboard">Kembali ke aplikasi</a>
    </main>
</body>
</html>
