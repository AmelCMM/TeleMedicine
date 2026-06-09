<?php

require_once dirname(__DIR__) . '/includes/auth_guard.php';
requireRole(ROLE_PATIENT);

$userId = getCurrentUserId();
$db = Database::getConnection();

$stmt = $db->prepare('SELECT id FROM patients WHERE user_id = ?');
$stmt->execute([$userId]);
$patient = $stmt->fetch();
$patientId = $patient ? (int)$patient['id'] : null;

$upcomingAppts = [];
if ($patientId) {
    $stmt = $db->prepare("
        SELECT a.id, a.scheduled_at, a.type, a.status, a.jitsi_room,
               u.name AS doctor_name, d.specialization
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.id
        JOIN users u ON d.user_id = u.id
        WHERE a.patient_id = ? AND a.status IN ('confirmed', 'pending', 'in_progress')
        ORDER BY a.scheduled_at ASC LIMIT 5
    ");
    $stmt->execute([$patientId]);
    $upcomingAppts = $stmt->fetchAll();
}

$recentRx = [];
if ($patientId) {
    $stmt = $db->prepare("
        SELECT p.id, p.issued_at, p.status, u.name AS doctor_name
        FROM prescriptions p
        JOIN doctors d ON p.doctor_id = d.id
        JOIN users u ON d.user_id = u.id
        WHERE p.patient_id = ?
        ORDER BY p.issued_at DESC LIMIT 5
    ");
    $stmt->execute([$patientId]);
    $recentRx = $stmt->fetchAll();
}

$stmt = $db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
$stmt->execute([$userId]);
$unreadNotifs = (int)$stmt->fetchColumn();

$pageTitle = 'Patient Dashboard';
?>
<div class="topbar">
    <div>
        <h1 class="topbar-title">Dashboard</h1>
        <p class="topbar-subtitle">Welcome back, <?= e(getCurrentUserName()) ?></p>
    </div>
    <div class="topbar-actions">
        <a href="/patient/find-doctor" class="btn btn-primary">Find a doctor</a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-info">
            <div class="stat-card-value"><?= count($upcomingAppts) ?></div>
            <div class="stat-card-label">Upcoming appointments</div>
        </div>
        <div class="stat-card-icon blue"><?= icon('calendar') ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-info">
            <div class="stat-card-value"><?= count($recentRx) ?></div>
            <div class="stat-card-label">Recent prescriptions</div>
        </div>
        <div class="stat-card-icon coral"><?= icon('file-text') ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-info">
            <div class="stat-card-value"><?= $unreadNotifs ?></div>
            <div class="stat-card-label">New notifications</div>
        </div>
        <div class="stat-card-icon amber"><?= icon('bell') ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-info">
            <div class="stat-card-value">2</div>
            <div class="stat-card-label">Health records</div>
        </div>
        <div class="stat-card-icon green"><?= icon('heart') ?></div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="card">
        <div class="card-header">
            <h3>Upcoming appointments</h3>
            <a href="/patient/appointments" class="card-link">View all</a>
        </div>
        <?php if (empty($upcomingAppts)): ?>
            <div class="empty-state">
                <div class="empty-state-icon"><?= icon('calendar') ?></div>
                <div class="empty-state-title">No upcoming appointments</div>
                <p class="empty-state-text">Find a doctor and book your first consultation.</p>
                <a href="/patient/find-doctor" class="btn btn-primary btn-sm">Find a doctor</a>
            </div>
        <?php else: ?>
            <div style="display:flex;flex-direction:column;">
                <?php foreach ($upcomingAppts as $appt): ?>
                    <div class="appointment-item card-status" data-status="<?= $appt['status'] ?>">
                        <div class="appt-info">
                            <div style="font-weight:600;">Dr. <?= e($appt['doctor_name']) ?></div>
                            <div class="appt-type"><?= e($appt['specialization']) ?></div>
                            <div class="appt-date"><?= date('M j, Y g:i A', strtotime($appt['scheduled_at'])) ?></div>
                        </div>
                        <div class="appt-meta">
                            <span class="badge badge-<?= $appt['status'] ?>"><?= $appt['status'] ?></span>
                            <?php if (in_array($appt['status'], ['confirmed', 'in_progress'])): ?>
                                <a href="/patient/consultation?appt=<?= $appt['id'] ?>" class="btn btn-primary btn-sm">Join</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Recent prescriptions</h3>
            <a href="/patient/prescriptions" class="card-link">View all</a>
        </div>
        <?php if (empty($recentRx)): ?>
            <div class="empty-state">
                <div class="empty-state-icon"><?= icon('file-text') ?></div>
                <div class="empty-state-title">No prescriptions yet</div>
                <p class="empty-state-text">Prescriptions from your doctors will appear here.</p>
            </div>
        <?php else: ?>
            <?php foreach ($recentRx as $rx): ?>
                <div class="appointment-item">
                    <div class="appt-info">
                        <strong>Dr. <?= e($rx['doctor_name']) ?></strong>
                        <span class="appt-date"><?= date('M j, Y', strtotime($rx['issued_at'])) ?></span>
                    </div>
                    <span class="badge badge-<?= $rx['status'] ?>"><?= $rx['status'] ?></span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
