<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'MediConnect') ?> — MediConnect</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="/assets/css/auth.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body>
    <header class="site-header">
        <a href="/" class="logo"><?= icon('activity') ?> MediConnect Zambia</a>
        <nav class="nav-links">
            <?php if (isLoggedIn()): ?>
                <span>Welcome, <?= e(getCurrentUserName()) ?></span>
                <a href="/logout">Logout</a>
            <?php else: ?>
                <a href="/login">Login</a>
                <a href="/register">Register</a>
            <?php endif; ?>
        </nav>
    </header>

    <main>
        <div class="container">
            <?php if (hasFlashMessage()): ?>
                <?php $flash = getFlashMessage(); ?>
                <div class="alert alert-<?= $flash['type'] ?>"><?= e($flash['message']) ?></div>
            <?php endif; ?>

            <?= $content ?? '' ?>
        </div>
    </main>

    <footer class="site-footer">
        <div class="container">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:var(--space-4);">
                <p>&copy; <?= date('Y') ?> MediConnect Zambia. All rights reserved.</p>
                <p><?= icon('alert-triangle') ?> Emergency? <a href="/emergency/nearest">Find nearest hospital now</a> | Hotline: <a href="tel:991">991</a></p>
            </div>
        </div>
    </footer>

    <script src="/assets/js/main.js"></script>
</body>
</html>
