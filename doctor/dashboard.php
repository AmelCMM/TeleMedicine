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
    $pageTitle = 'Profile Pending Review';
    ?>
    <div class="page-header animate-fade">
        <h1>Welcome, Dr. <?= explode(' ', getCurrentUserName())[0] ?></h1>
    </div>
    <div class="card animate-slide">
        <div class="card-body" style="padding: var(--space-10); text-align: center;">
            <div style="width:72px; height:72px; background:var(--info-bg); color:var(--info); border-radius:50%; display:flex; align-items:center; justify-content:center; margin: 0 auto var(--space-6);">
                <?= icon('clock') ?>
            </div>
            <h2 style="margin-bottom: var(--space-2);">Your account is being reviewed</h2>
            <p class="text-secondary" style="max-width: 480px; margin: 0 auto var(--space-8);">Our medical board is currently verifying your credentials. This process typically takes 24 hours. You'll be notified as soon as you're ready to start consultations.</p>
            <div class="alert alert-info" style="display:inline-flex; align-items:center; gap:var(--space-3);">
                <?= icon('info') ?>
                <div class="alert-text">Profile verification in progress</div>
            </div>
        </div>
    </div>
    <?php
    return;
}

// Upcoming appointments
$stmt = $db->prepare("
    SELECT a.id, a.scheduled_at, a.type, a.status, u.name AS patient_name
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    JOIN users u ON p.user_id = u.id
    WHERE a.doctor_id = ? AND a.status IN ('confirmed', 'pending', 'in_progress')
    ORDER BY a.scheduled_at ASC LIMIT 10
");
$stmt->execute([$doctorId]);
$appointments = $stmt->fetchAll();

// Stats
$stmt = $db->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND DATE(scheduled_at) = CURDATE() AND status IN ('confirmed', 'in_progress')");
$stmt->execute([$doctorId]);
$todayCount = (int)$stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(DISTINCT patient_id) FROM appointments WHERE doctor_id = ?");
$stmt->execute([$doctorId]);
$patientCount = (int)$stmt->fetchColumn();

$pageTitle = 'Provider Dashboard';
?>
<div class="page-header animate-fade">
    <div>
        <h1 class="topbar-title">Medical Dashboard</h1>
        <?php
            $nameParts = explode(' ', getCurrentUserName());
            $displayName = count($nameParts) > 1 ? $nameParts[count($nameParts)-1] : $nameParts[0];
        ?>
        <p class="topbar-subtitle">Welcome back, Dr. <?= e($displayName) ?></p>
    </div>
    <div class="topbar-actions">
        <a href="/doctor/appointments" class="btn btn-secondary"><?= icon('calendar') ?> Full Schedule</a>
    </div>
</div>

<div class="stats-grid animate-slide">
    <div class="stat-card">
        <div class="stat-card-label">Consultations Today</div>
        <div class="stat-card-value"><?= $todayCount ?></div>
        <div class="stat-card-icon blue"><?= icon('video') ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-label">Total Patients</div>
        <div class="stat-card-value"><?= $patientCount ?></div>
        <div class="stat-card-icon green"><?= icon('users') ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-label">Upcoming Week</div>
        <div class="stat-card-value"><?= count($appointments) ?></div>
        <div class="stat-card-icon amber"><?= icon('clock') ?></div>
    </div>
</div>

<div class="card animate-slide" style="animation-delay: 100ms;">
    <div class="card-header">
        <h3>Patient Queue</h3>
        <span class="badge badge-active"><?= count($appointments) ?> Scheduled</span>
    </div>
    <div class="card-body" style="padding-top: 0;">
        <?php if (empty($appointments)): ?>
            <div class="empty-state">
                <div class="empty-state-icon" style="background: var(--gray-50);"><?= icon('calendar') ?></div>
                <div class="empty-state-title">No consultations scheduled</div>
                <p class="empty-state-text">Patient bookings will appear here.</p>
            </div>
        <?php else: ?>
            <?php foreach ($appointments as $appt): ?>
                <div class="appointment-item card-status" data-status="<?= $appt['status'] ?>">
                    <div class="appt-info">
                        <div class="appt-doctor"><?= e($appt['patient_name']) ?></div>
                        <div class="appt-meta"><?= ucfirst($appt['type']) ?> consultation • <?= date('M j, g:i A', strtotime($appt['scheduled_at'])) ?></div>
                    </div>
                    <div style="display:flex; align-items:center; gap:var(--space-4);">
                        <span class="badge badge-<?= $appt['status'] ?>"><?= $appt['status'] ?></span>
                        <?php if (in_array($appt['status'], ['confirmed', 'in_progress'])): ?>
                            <a href="/doctor/consultation?appt=<?= $appt['id'] ?>" class="btn btn-primary btn-sm">Start</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
