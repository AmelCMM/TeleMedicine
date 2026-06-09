<?php

require_once dirname(__DIR__) . '/includes/auth_guard.php';
requireRole(ROLE_ADMIN);

$db = Database::getConnection();

// Stats
$stmt = $db->query("SELECT COUNT(*) FROM users WHERE role = 'patient'");
$patientCount = (int)$stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM users WHERE role = 'doctor'");
$doctorCount = (int)$stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM doctors WHERE is_approved = 0");
$pendingDoctors = (int)$stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM users WHERE role = 'pharmacy'");
$pharmacyCount = (int)$stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM appointments WHERE DATE(scheduled_at) = CURDATE()");
$todayAppts = (int)$stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM facilities");
$facilityCount = (int)$stmt->fetchColumn();

$pageTitle = 'Admin Dashboard';
?>
<div class="topbar">
    <div>
        <h1 class="topbar-title">Admin dashboard</h1>
        <p class="topbar-subtitle">Manage the MediConnect platform</p>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-info">
            <div class="stat-card-value"><?= $patientCount ?></div>
            <div class="stat-card-label">Total patients</div>
        </div>
        <div class="stat-card-icon blue"><?= icon('users') ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-info">
            <div class="stat-card-value"><?= $doctorCount ?></div>
            <div class="stat-card-label">Total doctors</div>
        </div>
        <div class="stat-card-icon green"><?= icon('users') ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-info">
            <div class="stat-card-value"><?= $pendingDoctors ?></div>
            <div class="stat-card-label">Pending approval</div>
        </div>
        <div class="stat-card-icon amber"><?= icon('clock') ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-info">
            <div class="stat-card-value"><?= $pharmacyCount ?></div>
            <div class="stat-card-label">Registered pharmacies</div>
        </div>
        <div class="stat-card-icon coral"><?= icon('shield') ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-info">
            <div class="stat-card-value"><?= $todayAppts ?></div>
            <div class="stat-card-label">Today's appointments</div>
        </div>
        <div class="stat-card-icon blue"><?= icon('calendar') ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-info">
            <div class="stat-card-value"><?= $facilityCount ?></div>
            <div class="stat-card-label">Healthcare facilities</div>
        </div>
        <div class="stat-card-icon green"><?= icon('map-pin') ?></div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="card">
        <div class="card-header">
            <h3>Quick actions</h3>
        </div>
        <div class="card-body" style="display:flex;flex-wrap:wrap;gap:var(--space-3);">
            <a href="/admin/doctors" class="btn btn-primary">Manage doctors</a>
            <a href="/admin/pharmacies" class="btn btn-primary">Manage pharmacies</a>
            <a href="/admin/facilities" class="btn btn-primary">Manage facilities</a>
            <a href="/admin/reports" class="btn btn-primary">View reports</a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Pending approvals</h3>
        </div>
        <?php if ($pendingDoctors === 0): ?>
            <div class="empty-state">
                <div class="empty-state-icon"><?= icon('check') ?></div>
                <div class="empty-state-title">No pending approvals</div>
            </div>
        <?php else: ?>
            <div class="card-body">
                <p><?= $pendingDoctors ?> doctor(s) awaiting verification.</p>
                <a href="/admin/doctors?filter=pending" class="btn btn-primary btn-sm">Review now</a>
            </div>
        <?php endif; ?>
    </div>
</div>
