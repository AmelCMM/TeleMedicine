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

$pageTitle = 'My Health Dashboard';
?>
<div class="page-header animate-fade">
    <div>
        <h1 class="topbar-title">Hello, <?= explode(' ', getCurrentUserName())[0] ?></h1>
        <p class="topbar-subtitle">Here is an overview of your health activities.</p>
    </div>
    <div class="topbar-actions">
        <a href="/patient/find-doctor" class="btn btn-primary"><?= icon('plus') ?> New Consultation</a>
    </div>
</div>

<div class="stats-grid animate-slide">
    <div class="stat-card">
        <div class="stat-card-label">Next Appointment</div>
        <div class="stat-card-value"><?= !empty($upcomingAppts) ? date('M j', strtotime($upcomingAppts[0]['scheduled_at'])) : 'None' ?></div>
        <div class="stat-card-icon blue"><?= icon('calendar') ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-label">Active Prescriptions</div>
        <div class="stat-card-value"><?= count($recentRx) ?></div>
        <div class="stat-card-icon coral"><?= icon('file-text') ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-label">Pending Notifications</div>
        <div class="stat-card-value"><?= $unreadNotifs ?></div>
        <div class="stat-card-icon amber"><?= icon('bell') ?></div>
    </div>
</div>

<div class="dashboard-grid animate-slide" style="animation-delay: 100ms;">
    <div class="card">
        <div class="card-header">
            <h3>Upcoming Consultations</h3>
            <a href="/patient/appointments" class="card-link">View all</a>
        </div>
        <div class="card-body" style="padding-top: 0;">
            <?php if (empty($upcomingAppts)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon" style="background: var(--gray-50); color: var(--gray-400);"><?= icon('calendar') ?></div>
                    <div class="empty-state-title">No appointments found</div>
                    <p class="empty-state-text">Your scheduled consultations will appear here.</p>
                    <a href="/patient/find-doctor" class="btn btn-secondary btn-sm">Find a Doctor</a>
                </div>
            <?php else: ?>
                <?php foreach ($upcomingAppts as $appt): ?>
                    <div class="appointment-item">
                        <div class="appt-info">
                            <div class="appt-doctor">Dr. <?= e($appt['doctor_name']) ?></div>
                            <div class="appt-meta"><?= e($appt['specialization']) ?> • <?= date('g:i A', strtotime($appt['scheduled_at'])) ?></div>
                        </div>
                        <div style="display:flex; align-items:center; gap:var(--space-4);">
                            <span class="badge badge-<?= $appt['status'] ?>"><?= $appt['status'] ?></span>
                            <?php if (in_array($appt['status'], ['confirmed', 'in_progress'])): ?>
                                <a href="/patient/consultation?appt=<?= $appt['id'] ?>" class="btn btn-primary btn-sm">Join</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Recent Prescriptions</h3>
            <a href="/patient/prescriptions" class="card-link">View all</a>
        </div>
        <div class="card-body" style="padding-top: 0;">
            <?php if (empty($recentRx)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon" style="background: var(--gray-50); color: var(--gray-400);"><?= icon('file-text') ?></div>
                    <p class="empty-state-text">No prescriptions yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($recentRx as $rx): ?>
                    <div class="appointment-item">
                        <div class="appt-info">
                            <div class="appt-doctor">Dr. <?= e($rx['doctor_name']) ?></div>
                            <div class="appt-meta">Issued <?= date('M j, Y', strtotime($rx['issued_at'])) ?></div>
                        </div>
                        <span class="badge badge-<?= $rx['status'] ?>"><?= $rx['status'] ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
