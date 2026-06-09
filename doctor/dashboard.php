<?php

require_once dirname(__DIR__) . '/includes/auth_guard.php';
requireRole(ROLE_DOCTOR);

$userId = getCurrentUserId();
$doctorId = getDoctorId($userId);

if (!$doctorId) {
    setFlashMessage('error', 'Doctor profile not found.');
    redirect('/login');
}

$db = Database::getConnection();

// Check approval status
$stmt = $db->prepare('SELECT is_approved FROM doctors WHERE id = ?');
$stmt->execute([$doctorId]);
$doc = $stmt->fetch();

if (!$doc || !$doc['is_approved']) {
    $pageTitle = 'Doctor Dashboard';
    ?>
    <div class="topbar">
        <h1 class="topbar-title">Welcome, Dr. <?= e(getCurrentUserName()) ?>!</h1>
    </div>
    <div class="card">
        <div class="alert alert-info" style="padding:var(--space-5);">
            <div class="alert-title">Your account is being reviewed</div>
            <div class="alert-text">
                This usually takes 24 hours. Once verified, you will be able to start consultations and manage your practice.
            </div>
        </div>
    </div>
    <?php
    return;
}

// Upcoming appointments
$stmt = $db->prepare("
    SELECT a.id, a.scheduled_at, a.type, a.status, a.jitsi_room,
           u.name AS patient_name, p.date_of_birth
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    JOIN users u ON p.user_id = u.id
    WHERE a.doctor_id = ? AND a.status IN ('confirmed', 'pending', 'in_progress')
    ORDER BY a.scheduled_at ASC
    LIMIT 10
");
$stmt->execute([$doctorId]);
$appointments = $stmt->fetchAll();

// Today's appointments count
$stmt = $db->prepare("
    SELECT COUNT(*) FROM appointments
    WHERE doctor_id = ? AND DATE(scheduled_at) = CURDATE()
    AND status IN ('confirmed', 'in_progress')
");
$stmt->execute([$doctorId]);
$todayCount = (int)$stmt->fetchColumn();

// Total patients count
$stmt = $db->prepare("
    SELECT COUNT(DISTINCT patient_id) FROM appointments WHERE doctor_id = ?
");
$stmt->execute([$doctorId]);
$patientCount = (int)$stmt->fetchColumn();

$pageTitle = 'Doctor Dashboard';
?>
<div class="topbar">
    <div>
        <h1 class="topbar-title">Welcome, Dr. <?= e(getCurrentUserName()) ?>!</h1>
        <p class="topbar-subtitle">Manage your practice and patient consultations.</p>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-info">
            <div class="stat-card-value"><?= $todayCount ?></div>
            <div class="stat-card-label">Today's appointments</div>
        </div>
        <div class="stat-card-icon blue"><?= icon('calendar') ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-info">
            <div class="stat-card-value"><?= $patientCount ?></div>
            <div class="stat-card-label">Total patients</div>
        </div>
        <div class="stat-card-icon green"><?= icon('users') ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-info">
            <div class="stat-card-value"><?= count($appointments) ?></div>
            <div class="stat-card-label">Upcoming</div>
        </div>
        <div class="stat-card-icon amber"><?= icon('clock') ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-info">
            <div class="stat-card-value">12</div>
            <div class="stat-card-label">Reports pending</div>
        </div>
        <div class="stat-card-icon coral"><?= icon('file-text') ?></div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Upcoming appointments</h3>
        <a href="/doctor/appointments" class="card-link">View all</a>
    </div>
    <?php if (empty($appointments)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><?= icon('calendar') ?></div>
            <div class="empty-state-title">No upcoming appointments</div>
            <p class="empty-state-text">New appointments from patients will appear here.</p>
        </div>
    <?php else: ?>
        <div style="display:flex;flex-direction:column;">
            <?php foreach ($appointments as $appt): ?>
                <div class="appointment-item card-status" data-status="<?= $appt['status'] ?>">
                    <div class="appt-info">
                        <div style="font-weight:600;"><?= e($appt['patient_name']) ?></div>
                        <div class="appt-type" style="text-transform:capitalize;"><?= $appt['type'] ?> consultation</div>
                        <div class="appt-date"><?= date('M j, Y g:i A', strtotime($appt['scheduled_at'])) ?></div>
                    </div>
                    <div class="appt-meta">
                        <span class="badge badge-<?= $appt['status'] ?>"><?= $appt['status'] ?></span>
                        <?php if (in_array($appt['status'], ['confirmed', 'in_progress'])): ?>
                            <a href="/doctor/consultation?appt=<?= $appt['id'] ?>" class="btn btn-primary btn-sm"><?= icon('video') ?> Join</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
