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
$filter = $_GET['filter'] ?? 'upcoming';

$query = "
    SELECT a.id, a.scheduled_at, a.type, a.status, a.chief_complaint, a.jitsi_room,
           u.name AS patient_name, p.date_of_birth
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    JOIN users u ON p.user_id = u.id
    WHERE a.doctor_id = ?
";

if ($filter === 'upcoming') {
    $query .= " AND a.status IN ('pending', 'confirmed', 'in_progress')";
} elseif ($filter === 'past') {
    $query .= " AND a.status IN ('completed', 'cancelled')";
}

$query .= ' ORDER BY a.scheduled_at DESC';

$stmt = $db->prepare($query);
$stmt->execute([$doctorId]);
$appointments = $stmt->fetchAll();

$pageTitle = 'My Appointments';
?>
<div class="topbar">
    <div>
        <h1 class="topbar-title">My appointments</h1>
    </div>
</div>

<div class="filter-tabs">
    <a href="?filter=upcoming" class="filter-tab <?= $filter === 'upcoming' ? 'active' : '' ?>">Upcoming</a>
    <a href="?filter=past" class="filter-tab <?= $filter === 'past' ? 'active' : '' ?>">Past</a>
    <a href="?filter=all" class="filter-tab <?= $filter === 'all' ? 'active' : '' ?>">All</a>
</div>

<?php if (empty($appointments)): ?>
    <div class="empty-state">
        <div class="empty-state-icon"><?= icon('calendar') ?></div>
        <div class="empty-state-title">No <?= $filter ?> appointments</div>
        <p class="empty-state-text">New appointments from patients will appear here.</p>
    </div>
<?php else: ?>
    <div style="display:flex;flex-direction:column;gap:var(--space-4);">
        <?php foreach ($appointments as $appt): ?>
            <div class="card card-status" data-status="<?= $appt['status'] ?>">
                <div class="card-body">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:var(--space-4);">
                        <div style="display:flex;align-items:center;gap:var(--space-3);">
                            <div class="avatar avatar-md"><?= getInitials($appt['patient_name']) ?></div>
                            <div>
                                <div style="font-weight:700;"><?= e($appt['patient_name']) ?></div>
                                <?php if ($appt['date_of_birth']): ?>
                                    <div style="font-size:var(--text-sm);color:var(--text-secondary);">
                                        Age: <?= date_diff(date_create($appt['date_of_birth']), date_create('today'))->y ?> yrs
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <span class="badge badge-<?= $appt['status'] ?>"><?= $appt['status'] ?></span>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-4);margin-bottom:var(--space-4);">
                        <div>
                            <div style="font-size:var(--text-xs);color:var(--text-muted);text-transform:uppercase;font-weight:600;">Date</div>
                            <div><?= date('M j, Y', strtotime($appt['scheduled_at'])) ?></div>
                        </div>
                        <div>
                            <div style="font-size:var(--text-xs);color:var(--text-muted);text-transform:uppercase;font-weight:600;">Time</div>
                            <div><?= date('g:i A', strtotime($appt['scheduled_at'])) ?></div>
                        </div>
                        <div>
                            <div style="font-size:var(--text-xs);color:var(--text-muted);text-transform:uppercase;font-weight:600;">Type</div>
                            <div style="text-transform:capitalize;"><?= $appt['type'] ?></div>
                        </div>
                    </div>

                    <?php if ($appt['chief_complaint']): ?>
                        <div style="background-color:var(--surface-alt);padding:var(--space-3);border-radius:var(--radius);margin-bottom:var(--space-4);">
                            <div style="font-size:var(--text-xs);color:var(--text-muted);text-transform:uppercase;font-weight:600;margin-bottom:4px;">Chief complaint</div>
                            <p style="font-size:var(--text-sm);"><?= e($appt['chief_complaint']) ?></p>
                        </div>
                    <?php endif; ?>

                    <div style="display:flex;gap:var(--space-3);">
                        <?php if (in_array($appt['status'], ['confirmed', 'in_progress'])): ?>
                            <a href="/doctor/consultation?appt=<?= $appt['id'] ?>" class="btn btn-primary btn-sm"><?= icon('video') ?> Start consultation</a>
                        <?php endif; ?>
                        <?php if ($appt['status'] === 'in_progress'): ?>
                            <a href="/doctor/write-prescription?consultation=<?= $appt['id'] ?>" class="btn btn-secondary btn-sm"><?= icon('file-text') ?> Write prescription</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
