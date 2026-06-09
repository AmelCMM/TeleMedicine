<?php

require_once dirname(__DIR__) . '/includes/auth_guard.php';
requireRole(ROLE_PHARMACY);

$userId = getCurrentUserId();
$db = Database::getConnection();

// Get pharmacy profile
$stmt = $db->prepare("
    SELECT ph.*, f.name AS facility_name
    FROM pharmacies ph
    LEFT JOIN facilities f ON ph.facility_id = f.id
    WHERE ph.user_id = ?
");
$stmt->execute([$userId]);
$pharmacy = $stmt->fetch();

if (!$pharmacy) {
    setFlashMessage('error', 'Pharmacy profile not found.');
    redirect('/login');
}

// Recent dispensations
$stmt = $db->prepare("
    SELECT p.id, p.issued_at, p.dispensed_at, u.name AS patient_name,
           d2.name AS doctor_name
    FROM prescriptions p
    JOIN patients pt ON p.patient_id = pt.id
    JOIN users u ON pt.user_id = u.id
    JOIN doctors d ON p.doctor_id = d.id
    JOIN users d2 ON d.user_id = d2.id
    WHERE p.dispensed_by = ?
    ORDER BY p.dispensed_at DESC
    LIMIT 10
");
$stmt->execute([$userId]);
$dispensations = $stmt->fetchAll();

// Pending prescriptions count
$stmt = $db->prepare("SELECT COUNT(*) FROM prescriptions WHERE status = 'active' AND expires_at >= CURDATE()");
$stmt->execute();
$pendingRx = (int)$stmt->fetchColumn();

$pageTitle = 'Pharmacy Dashboard';
?>
<div class="topbar">
    <div>
        <h1 class="topbar-title">Welcome, <?= e(getCurrentUserName()) ?>!</h1>
        <?php if ($pharmacy['facility_name']): ?>
            <p class="topbar-subtitle"><?= e($pharmacy['facility_name']) ?></p>
        <?php endif; ?>
    </div>
    <div class="topbar-actions">
        <a href="/pharmacy/verify-prescription" class="btn btn-primary"><?= icon('search') ?> Verify prescription</a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-info">
            <div class="stat-card-value"><?= $pendingRx ?></div>
            <div class="stat-card-label">Active prescriptions in system</div>
        </div>
        <div class="stat-card-icon coral"><?= icon('file-text') ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-info">
            <div class="stat-card-value"><?= count($dispensations) ?></div>
            <div class="stat-card-label">Medications dispensed</div>
        </div>
        <div class="stat-card-icon green"><?= icon('check') ?></div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Recent dispensations</h3>
    </div>
    <?php if (empty($dispensations)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><?= icon('package') ?></div>
            <div class="empty-state-title">No prescriptions dispensed yet</div>
            <p class="empty-state-text">Verify and dispense prescriptions here.</p>
            <a href="/pharmacy/verify-prescription" class="btn btn-primary">Verify a prescription</a>
        </div>
    <?php else: ?>
        <?php foreach ($dispensations as $d): ?>
            <div class="appointment-item">
                <div class="appt-info">
                    <strong><?= e($d['patient_name']) ?></strong>
                    <span>by Dr. <?= e($d['doctor_name']) ?></span>
                    <span class="appt-date"><?= date('M j, Y g:i A', strtotime($d['dispensed_at'])) ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
