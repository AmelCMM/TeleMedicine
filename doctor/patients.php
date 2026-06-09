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

$search = trim($_GET['search'] ?? '');

$query = "
    SELECT DISTINCT u.id AS user_id, u.name, u.email, u.phone, u.avatar,
           p.date_of_birth, p.gender, p.blood_type,
           (SELECT COUNT(*) FROM appointments a2 WHERE a2.patient_id = p.id AND a2.doctor_id = ?) AS visit_count,
           (SELECT MAX(a3.scheduled_at) FROM appointments a3 WHERE a3.patient_id = p.id AND a3.doctor_id = ?) AS last_visit
    FROM patients p
    JOIN users u ON p.user_id = u.id
    JOIN appointments a ON a.patient_id = p.id
    WHERE a.doctor_id = ?
";
$params = [$doctorId, $doctorId, $doctorId];

if (!empty($search)) {
    $query .= ' AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$query .= ' ORDER BY last_visit DESC';

$stmt = $db->prepare($query);
$stmt->execute($params);
$patients = $stmt->fetchAll();

$pageTitle = 'My Patients';
?>
<div class="topbar">
    <div>
        <h1 class="topbar-title">My patients</h1>
    </div>
</div>

<div class="card" style="padding:var(--space-4);margin-bottom:var(--space-6);">
    <form method="GET" class="search-form">
        <input type="text" name="search" placeholder="Search patients..." value="<?= e($search) ?>">
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if (!empty($search)): ?>
            <a href="/doctor/patients" class="btn btn-ghost">Clear</a>
        <?php endif; ?>
    </form>
</div>

<?php if (empty($patients)): ?>
    <div class="empty-state">
        <div class="empty-state-icon"><?= icon('users') ?></div>
        <div class="empty-state-title">No patients yet</div>
        <p class="empty-state-text">Patients you consult with will appear here.</p>
    </div>
<?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:var(--space-4);">
        <?php foreach ($patients as $patient): ?>
            <div class="card">
                <div class="card-body">
                    <div style="display:flex;align-items:center;gap:var(--space-3);margin-bottom:var(--space-4);">
                        <div class="avatar avatar-md"><?= getInitials($patient['name']) ?></div>
                        <div>
                            <div style="font-weight:700;"><?= e($patient['name']) ?></div>
                            <div style="font-size:var(--text-sm);color:var(--text-secondary);"><?= e($patient['email']) ?></div>
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-3);font-size:var(--text-sm);">
                        <div>
                            <div style="color:var(--text-muted);font-size:var(--text-xs);text-transform:uppercase;font-weight:600;">Visits</div>
                            <div><?= $patient['visit_count'] ?></div>
                        </div>
                        <?php if ($patient['date_of_birth']): ?>
                            <div>
                                <div style="color:var(--text-muted);font-size:var(--text-xs);text-transform:uppercase;font-weight:600;">Age</div>
                                <div><?= date_diff(date_create($patient['date_of_birth']), date_create('today'))->y ?></div>
                            </div>
                        <?php endif; ?>
                        <?php if ($patient['blood_type']): ?>
                            <div>
                                <div style="color:var(--text-muted);font-size:var(--text-xs);text-transform:uppercase;font-weight:600;">Blood type</div>
                                <div><?= e($patient['blood_type']) ?></div>
                            </div>
                        <?php endif; ?>
                        <?php if ($patient['last_visit']): ?>
                            <div>
                                <div style="color:var(--text-muted);font-size:var(--text-xs);text-transform:uppercase;font-weight:600;">Last visit</div>
                                <div><?= date('M j, Y', strtotime($patient['last_visit'])) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
