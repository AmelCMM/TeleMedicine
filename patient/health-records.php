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

$filterType = $_GET['type'] ?? '';

$query = "
    SELECT hr.id, hr.title, hr.record_type, hr.description, hr.created_at,
           u.name AS doctor_name
    FROM health_records hr
    JOIN users u ON hr.created_by = u.id
    WHERE hr.patient_id = ?
";
$params = [$patientId];

if (!empty($filterType)) {
    $query .= ' AND hr.record_type = ?';
    $params[] = $filterType;
}

$query .= ' ORDER BY hr.created_at DESC';

$stmt = $db->prepare($query);
$stmt->execute($params);
$records = $stmt->fetchAll();

$pageTitle = 'Health Records';
?>
<div class="topbar">
    <div>
        <h1 class="topbar-title">Health records</h1>
        <p class="topbar-subtitle">Your medical history, diagnoses, and lab results</p>
    </div>
</div>

<div class="filter-tabs">
    <a href="?" class="filter-tab <?= empty($filterType) ? 'active' : '' ?>">All</a>
    <a href="?type=diagnosis" class="filter-tab <?= $filterType === 'diagnosis' ? 'active' : '' ?>">Diagnoses</a>
    <a href="?type=allergy" class="filter-tab <?= $filterType === 'allergy' ? 'active' : '' ?>">Allergies</a>
    <a href="?type=vaccination" class="filter-tab <?= $filterType === 'vaccination' ? 'active' : '' ?>">Vaccinations</a>
    <a href="?type=lab_result" class="filter-tab <?= $filterType === 'lab_result' ? 'active' : '' ?>">Lab results</a>
</div>

<?php if (empty($records)): ?>
    <div class="empty-state">
        <div class="empty-state-icon"><?= icon('heart') ?></div>
        <div class="empty-state-title">No health records yet</div>
        <p class="empty-state-text">Your health records will appear here after consultations.</p>
        <a href="/patient/find-doctor" class="btn btn-primary">Find a doctor</a>
    </div>
<?php else: ?>
    <div style="display:flex;flex-direction:column;gap:var(--space-4);">
        <?php foreach ($records as $record): ?>
            <div class="card card-status" data-status="confirmed">
                <div class="card-body">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:var(--space-3);">
                        <div>
                            <span class="tag" style="margin-bottom:var(--space-2);display:inline-block;"><?= e(ucfirst(str_replace('_', ' ', $record['record_type']))) ?></span>
                            <h3 style="font-size:var(--text-lg);"><?= e($record['title']) ?></h3>
                        </div>
                        <span style="font-size:var(--text-xs);color:var(--text-muted);white-space:nowrap;"><?= date('M j, Y', strtotime($record['created_at'])) ?></span>
                    </div>
                    <p style="font-size:var(--text-sm);color:var(--text-secondary);margin-bottom:var(--space-4);line-height:1.6;"><?= e($record['description']) ?></p>
                    <div style="display:flex;align-items:center;gap:var(--space-2);padding-top:var(--space-3);border-top:1px solid var(--border);">
                        <div class="avatar avatar-sm"><?= getInitials($record['doctor_name']) ?></div>
                        <div style="font-size:var(--text-xs);color:var(--text-muted);">Recorded by <span style="font-weight:600;color:var(--text-secondary);">Dr. <?= e($record['doctor_name']) ?></span></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
