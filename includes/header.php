<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0077B6">
    <meta name="description" content="MediConnect - Telemedicine platform for Zambia. Consult doctors remotely via chat, voice, or video.">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/assets/img/icons/icon-192.png">
    <title><?= $pageTitle ?? APP_NAME ?></title>

    <!-- Core Styles -->
    <link rel="stylesheet" href="/assets/css/main.css?v=<?= APP_VERSION ?>">

    <?php if (isLoggedIn()): ?>
    <link rel="stylesheet" href="/assets/css/dashboard.css?v=<?= APP_VERSION ?>">
    <?php endif; ?>

    <?php
    $uri = $_SERVER['REQUEST_URI'];
    if (strpos($uri, 'login') !== false || strpos($uri, 'register') !== false || strpos($uri, 'forgot') !== false || strpos($uri, 'reset') !== false): ?>
    <link rel="stylesheet" href="/assets/css/auth.css?v=<?= APP_VERSION ?>">
    <?php endif; ?>

    <link rel="stylesheet" href="/assets/css/responsive.css?v=<?= APP_VERSION ?>">

    <!-- Scripts -->
    <script src="/assets/js/main.js" defer></script>
    <?php if (isLoggedIn()): ?>
    <script src="/assets/js/notifications.js" defer></script>
    <?php endif; ?>
</head>
<body>
