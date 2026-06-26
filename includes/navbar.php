<?php
$role = $_SESSION['role'] ?? null;
$name = $_SESSION['user_name'] ?? null;
$userId = $_SESSION['user_id'] ?? null;

// Use $route from public/index.php if available, else calculate it
if (!isset($route)) {
    $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $basePath = dirname($_SERVER['SCRIPT_NAME']);
    $route = $requestUri;
    if ($basePath !== '/' && $basePath !== '\\' && strpos($requestUri, $basePath) === 0) {
        $route = substr($requestUri, strlen($basePath));
    }
    $route = '/' . trim($route, '/');
}

function isActive($path) {
    global $route;
    return $route === $path ? 'active' : '';
}

$unreadNotifs = 0;
if ($userId) {
    try {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
        $stmt->execute([$userId]);
        $unreadNotifs = (int)$stmt->fetchColumn();
    } catch (Exception $e) {}
}

if (!isLoggedIn()): ?>
<header class="landing-nav">
    <div class="container" style="display:flex; align-items:center; justify-content:space-between; width:100%; height:100%;">
        <a href="/" class="sidebar-logo" style="padding: 0;">
            <span class="sidebar-logo-mark"><?= icon('heart') ?></span>
            <span class="sidebar-logo-text">MediConnect</span>
        </a>

        <nav class="landing-nav-links" id="navLinks">
            <button class="landing-mobile-close" id="mobileNavClose" aria-label="Close Menu"><?= icon('x') ?></button>
            <a href="/#how-it-works">How it works</a>
            <a href="/#for-doctors">For Doctors</a>
            <a href="/emergency/nearest">Emergency</a>
            <div class="landing-nav-mobile-actions" style="display:none; margin-top:var(--space-6); width:100%;">
                <a href="/login" class="btn btn-secondary btn-full">Sign in</a>
                <a href="/register" class="btn btn-primary btn-full" style="margin-top:var(--space-3);">Get started</a>
            </div>
        </nav>

        <div class="landing-nav-actions">
            <a href="/login" class="btn btn-ghost landing-desktop-btn">Sign in</a>
            <a href="/register" class="btn btn-primary landing-desktop-btn">Get started</a>
            <button class="landing-mobile-toggle" id="navToggle" aria-label="Toggle Menu"><?= icon('menu') ?></button>
        </div>
    </div>
</header>

<div class="layout" style="padding-top: var(--navbar-height);">
    <main class="main-content" style="padding:0;">
        <?php displayFlashMessage(); ?>
<?php else: ?>
<div class="layout">
    <script>document.body.classList.add('logged-in');</script>
    <aside class="sidebar" id="sidebar">
        <a href="/" class="sidebar-logo">
            <span class="sidebar-logo-mark"><?= icon('heart') ?></span>
            <span class="sidebar-logo-text">MediConnect</span>
        </a>

        <nav class="sidebar-nav">
            <?php if ($role === ROLE_PATIENT): ?>
                <div class="nav-section-label">Main</div>
                <a href="/patient/dashboard" class="nav-link <?= isActive('/patient/dashboard') ?>"><?= icon('home') ?> Dashboard</a>
                <a href="/patient/find-doctor" class="nav-link <?= isActive('/patient/find-doctor') ?>"><?= icon('search') ?> Find Doctor</a>
                <a href="/patient/appointments" class="nav-link <?= isActive('/patient/appointments') ?>"><?= icon('calendar') ?> Appointments</a>
                <div class="nav-section-label">Medical</div>
                <a href="/patient/health-records" class="nav-link <?= isActive('/patient/health-records') ?>"><?= icon('heart') ?> Health Records</a>
                <a href="/patient/prescriptions" class="nav-link <?= isActive('/patient/prescriptions') ?>"><?= icon('file-text') ?> Prescriptions</a>

            <?php elseif ($role === ROLE_DOCTOR): ?>
                <div class="nav-section-label">Practice</div>
                <a href="/doctor/dashboard" class="nav-link <?= isActive('/doctor/dashboard') ?>"><?= icon('home') ?> Dashboard</a>
                <a href="/doctor/appointments" class="nav-link <?= isActive('/doctor/appointments') ?>"><?= icon('calendar') ?> Appointments</a>
                <a href="/doctor/patients" class="nav-link <?= isActive('/doctor/patients') ?>"><?= icon('users') ?> Patients</a>
                <div class="nav-section-label">Account</div>
                <a href="/doctor/profile" class="nav-link <?= isActive('/doctor/profile') ?>"><?= icon('user') ?> My Profile</a>

            <?php elseif ($role === ROLE_PHARMACY): ?>
                <div class="nav-section-label">Main</div>
                <a href="/pharmacy/dashboard" class="nav-link <?= isActive('/pharmacy/dashboard') ?>"><?= icon('home') ?> Dashboard</a>
                <a href="/pharmacy/verify-prescription" class="nav-link <?= isActive('/pharmacy/verify-prescription') ?>"><?= icon('qr-code') ?> Verify Prescription</a>

            <?php elseif ($role === ROLE_ADMIN): ?>
                <div class="nav-section-label">System</div>
                <a href="/admin/dashboard" class="nav-link <?= isActive('/admin/dashboard') ?>"><?= icon('home') ?> Overview</a>
                <a href="/admin/doctors" class="nav-link <?= isActive('/admin/doctors') ?>"><?= icon('users') ?> Doctors</a>
                <a href="/admin/patients" class="nav-link <?= isActive('/admin/patients') ?>"><?= icon('users') ?> Patients</a>
                <a href="/admin/pharmacies" class="nav-link <?= isActive('/admin/pharmacies') ?>"><?= icon('users') ?> Pharmacies</a>
                <a href="/admin/facilities" class="nav-link <?= isActive('/admin/facilities') ?>"><?= icon('map-pin') ?> Facilities</a>
                <a href="/admin/reports" class="nav-link <?= isActive('/admin/reports') ?>"><?= icon('file-text') ?> Reports</a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-user">
            <div class="avatar avatar-sm"><?= getInitials($name) ?></div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= e($name) ?></div>
                <div class="sidebar-user-role"><?= e($role) ?></div>
            </div>
            <a href="/logout" class="btn btn-sm btn-ghost" title="Logout"><?= icon('log-out') ?></a>
        </div>
    </aside>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <main class="main-content">
        <header class="topbar animate-fade">
            <div style="display:flex; align-items:center; gap:var(--space-4);">
                <button class="sidebar-toggle" id="sidebarToggle"><?= icon('menu') ?></button>
                <div>
                    <h2 style="font-size:1.25rem; font-weight:600;"><?= $pageTitle ?? 'Dashboard' ?></h2>
                </div>
            </div>
            <div class="topbar-actions" style="position:relative;">
                <button class="notification-btn" id="notifBtn" title="Notifications">
                    <?= icon('bell') ?>
                    <span id="notifBadge" class="dot" style="<?= $unreadNotifs > 0 ? '' : 'display:none;' ?>"></span>
                </button>
                <div class="notif-dropdown" id="notifDropdown">
                    <div class="notif-dropdown-header">
                        <h3>Notifications</h3>
                        <button id="markAllRead">Mark all read</button>
                    </div>
                    <div class="notif-list" id="notifList">
                        <div class="notif-empty">No new notifications</div>
                    </div>
                </div>
            </div>
        </header>
        <?php displayFlashMessage(); ?>
<?php endif; ?>
