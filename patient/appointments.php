<?php

require_once dirname(__DIR__) . '/includes/auth_guard.php';
requireRole(ROLE_PATIENT);

$userId = getCurrentUserId();
$patientId = getPatientId($userId);

if (!$patientId) {
    setFlashMessage('error', 'Patient profile not found.');
    redirect('/patient/dashboard');
}

$db = Database::getConnection();
$filter = $_GET['filter'] ?? 'upcoming';

$query = "
    SELECT a.id, a.scheduled_at, a.type, a.status, a.chief_complaint, a.jitsi_room,
           u.name AS doctor_name, d.specialization, d.consultation_fee
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    JOIN users u ON d.user_id = u.id
    WHERE a.patient_id = ?
";

if ($filter === 'upcoming') {
    $query .= " AND a.status IN ('pending', 'confirmed', 'in_progress')";
} elseif ($filter === 'past') {
    $query .= " AND a.status IN ('completed', 'cancelled')";
}

$query .= ' ORDER BY a.scheduled_at DESC';

$stmt = $db->prepare($query);
$stmt->execute([$patientId]);
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
        <p class="empty-state-text">Book a consultation with a doctor to get started.</p>
        <a href="/patient/find-doctor" class="btn btn-primary">Find a doctor</a>
    </div>
<?php else: ?>
    <div style="display:flex;flex-direction:column;gap:var(--space-4);">
        <?php foreach ($appointments as $appt): ?>
            <div class="card card-status" data-status="<?= $appt['status'] ?>">
                <div class="card-body">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:var(--space-4);">
                        <div style="display:flex;align-items:center;gap:var(--space-3);">
                            <div class="avatar avatar-md"><?= getInitials($appt['doctor_name']) ?></div>
                            <div>
                                <div style="font-weight:700;">Dr. <?= e($appt['doctor_name']) ?></div>
                                <div style="font-size:var(--text-sm);color:var(--text-secondary);"><?= e($appt['specialization']) ?></div>
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
                            <a href="/patient/consultation?appt=<?= $appt['id'] ?>" class="btn btn-primary btn-sm">Join consultation</a>
                        <?php endif; ?>
                        <?php if (in_array($appt['status'], ['pending', 'confirmed'])): ?>
                            <?php if (strtotime($appt['scheduled_at']) > time() + (CANCEL_WINDOW_HOURS * 3600)): ?>
                                <button class="btn btn-danger btn-sm cancel-btn" data-appt-id="<?= $appt['id'] ?>">Cancel</button>
                            <?php else: ?>
                                <span style="font-size:var(--text-xs);color:var(--text-muted);">Cancellation window closed</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
document.querySelectorAll('.cancel-btn').forEach(function(btn) {
    btn.addEventListener('click', async function() {
        if (!confirm('Are you sure you want to cancel this appointment?')) return;
        const apptId = this.dataset.apptId;
        this.disabled = true;
        this.textContent = 'Cancelling...';
        try {
            const resp = await fetch('/api/cancel-appointment', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'appointment_id=' + apptId + '&csrf_token=<?= generateCsrfToken() ?>'
            });
            const data = await resp.json();
            if (data.success) { location.reload(); }
            else { showToast('error', data.message); this.disabled = false; this.textContent = 'Cancel'; }
        } catch (err) {
            showToast('error', 'An error occurred.');
            this.disabled = false;
            this.textContent = 'Cancel';
        }
    });
});
</script>
