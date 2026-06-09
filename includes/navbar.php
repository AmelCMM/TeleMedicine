<?php
$role = $_SESSION['role'] ?? null;
$name = $_SESSION['user_name'] ?? null;
$userId = $_SESSION['user_id'] ?? null;

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
<div class="landing-nav">
    <a href="/" class="sidebar-logo">
        <span class="sidebar-logo-mark"><?= icon('heart') ?></span>
        <span class="sidebar-logo-text">MediConnect</span>
    </a>
    <div class="landing-nav-links" id="navLinks">
        <button class="landing-mobile-close" id="mobileNavClose"><?= icon('x') ?></button>
        <a href="/#how-it-works">How it works</a>
        <a href="/#for-doctors">For Doctors</a>
        <a href="/emergency/nearest">Emergency</a>
        <div class="landing-nav-mobile-actions">
            <a href="/login" class="btn btn-ghost" style="width:100%;justify-content:center;">Sign in</a>
            <a href="/register" class="btn btn-accent" style="width:100%;justify-content:center;">Get started</a>
        </div>
    </div>
    <div class="landing-nav-actions">
        <button class="landing-mobile-toggle" id="navToggle"><?= icon('menu') ?></button>
        <a href="/login" class="btn btn-ghost landing-desktop-btn">Sign in</a>
        <a href="/register" class="btn btn-accent landing-desktop-btn">Get started</a>
    </div>
</div>

<div class="layout" style="padding-top:80px;">
    <main class="main-content" style="margin-left:0;max-width:100%;">
        <div class="container">
            <?php displayFlashMessage(); ?>
<?php else: ?>
<div class="layout">
    <aside class="sidebar" id="sidebar">
        <a href="/" class="sidebar-logo">
            <span class="sidebar-logo-mark"><?= icon('heart') ?></span>
            <span class="sidebar-logo-text">MediConnect</span>
        </a>

        <nav class="sidebar-nav">
            <?php if ($role === ROLE_PATIENT): ?>
                <div class="nav-section-label">Main</div>
                <a href="/patient/dashboard" class="nav-link"><?= icon('home') ?> Dashboard</a>
                <a href="/patient/find-doctor" class="nav-link"><?= icon('search') ?> Find Doctor</a>
                <a href="/patient/appointments" class="nav-link"><?= icon('calendar') ?> Appointments</a>
                <div class="nav-section-label">Health</div>
                <a href="/patient/health-records" class="nav-link"><?= icon('heart') ?> Health Records</a>
                <a href="/patient/prescriptions" class="nav-link"><?= icon('file-text') ?> Prescriptions</a>

            <?php elseif ($role === ROLE_DOCTOR): ?>
                <div class="nav-section-label">Main</div>
                <a href="/doctor/dashboard" class="nav-link"><?= icon('home') ?> Dashboard</a>
                <a href="/doctor/appointments" class="nav-link"><?= icon('calendar') ?> Appointments</a>
                <a href="/doctor/patients" class="nav-link"><?= icon('users') ?> Patients</a>
                <div class="nav-section-label">Practice</div>
                <a href="/doctor/profile" class="nav-link"><?= icon('user') ?> Profile</a>

            <?php elseif ($role === ROLE_PHARMACY): ?>
                <div class="nav-section-label">Main</div>
                <a href="/pharmacy/dashboard" class="nav-link"><?= icon('home') ?> Dashboard</a>
                <a href="/pharmacy/verify-prescription" class="nav-link"><?= icon('qr-code') ?> Verify Prescription</a>

            <?php elseif ($role === ROLE_ADMIN): ?>
                <div class="nav-section-label">Management</div>
                <a href="/admin/dashboard" class="nav-link"><?= icon('home') ?> Dashboard</a>
                <a href="/admin/doctors" class="nav-link"><?= icon('users') ?> Doctors</a>
                <a href="/admin/patients" class="nav-link"><?= icon('users') ?> Patients</a>
                <a href="/admin/pharmacies" class="nav-link"><?= icon('users') ?> Pharmacies</a>
                <a href="/admin/facilities" class="nav-link"><?= icon('map-pin') ?> Facilities</a>
                <a href="/admin/reports" class="nav-link"><?= icon('file-text') ?> Reports</a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-user">
            <div class="avatar avatar-sm">
                <?= getInitials($name) ?>
            </div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= e($name) ?></div>
                <div class="sidebar-user-role"><?= e($role) ?></div>
            </div>
            <a href="/logout" class="btn btn-sm btn-ghost" title="Logout"><?= icon('log-out') ?></a>
        </div>
    </aside>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <main class="main-content">
        <div class="topbar">
            <div>
                <button class="sidebar-toggle" id="sidebarToggle"><?= icon('menu', 'sidebar-toggle-icon') ?></button>
            </div>
            <div class="topbar-actions">
                <button class="notification-btn" id="notifBtn" title="Notifications">
                    <?= icon('bell') ?>
                    <?php if ($unreadNotifs > 0): ?><span class="dot"></span><?php endif; ?>
                </button>
            </div>
        </div>
        <?php displayFlashMessage(); ?>
<?php endif; ?>
