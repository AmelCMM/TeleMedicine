<?php

$pageTitle = 'Find a Doctor';

$db = Database::getConnection();

$specialization = trim($_GET['specialization'] ?? '');
$search = trim($_GET['search'] ?? '');

$query = "
    SELECT d.id, u.name, u.avatar, d.specialization, d.consultation_fee, d.bio, d.is_approved
    FROM doctors d
    JOIN users u ON d.user_id = u.id
    WHERE d.is_approved = 1
";
$params = [];

if (!empty($specialization)) {
    $query .= ' AND d.specialization LIKE ?';
    $params[] = '%' . $specialization . '%';
}

if (!empty($search)) {
    $query .= ' AND (u.name LIKE ? OR d.specialization LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$query .= ' ORDER BY u.name ASC';

$stmt = $db->prepare($query);
$stmt->execute($params);
$doctors = $stmt->fetchAll();

$specStmt = $db->query('SELECT DISTINCT specialization FROM doctors WHERE is_approved = 1 ORDER BY specialization');
$specializations = $specStmt->fetchAll(PDO::FETCH_COLUMN);
?>
<div class="topbar">
    <div>
        <h1 class="topbar-title">Find a doctor</h1>
        <p class="topbar-subtitle">Browse our network of licensed healthcare professionals</p>
    </div>
</div>

<div class="card" style="padding:var(--space-5);margin-bottom:var(--space-6);">
    <form method="GET" action="/patient/find-doctor" class="search-form">
        <input type="text" name="search" placeholder="Search by name or specialization..." value="<?= e($search) ?>">
        <select name="specialization">
            <option value="">All specializations</option>
            <?php foreach ($specializations as $spec): ?>
                <option value="<?= e($spec) ?>" <?= $specialization === $spec ? 'selected' : '' ?>><?= e($spec) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if (!empty($search) || !empty($specialization)): ?>
            <a href="/patient/find-doctor" class="btn btn-ghost">Clear</a>
        <?php endif; ?>
    </form>
</div>

<?php if (empty($doctors)): ?>
    <div class="empty-state">
        <div class="empty-state-icon"><?= icon('search') ?></div>
        <div class="empty-state-title">No doctors found</div>
        <p class="empty-state-text">Try adjusting your search or filter criteria.</p>
    </div>
<?php else: ?>
    <div class="doctor-grid">
        <?php foreach ($doctors as $doctor): ?>
            <div class="doctor-card">
                <div style="display:flex;align-items:center;gap:var(--space-4);">
                    <div class="doctor-card-avatar">
                        <?php if ($doctor['avatar']): ?>
                            <img src="<?= e($doctor['avatar']) ?>" alt="<?= e($doctor['name']) ?>">
                        <?php else: ?>
                            <?= getInitials($doctor['name']) ?>
                        <?php endif; ?>
                    </div>
                    <div class="doctor-card-info">
                        <div class="doctor-card-name">Dr. <?= e($doctor['name']) ?></div>
                        <div class="doctor-card-spec"><?= e($doctor['specialization']) ?></div>
                    </div>
                </div>
                <?php if ($doctor['bio']): ?>
                    <p style="font-size:var(--text-sm);color:var(--text-secondary);"><?= e(substr($doctor['bio'], 0, 120)) ?></p>
                <?php endif; ?>
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <div class="doctor-card-fee"><?= formatCurrency($doctor['consultation_fee']) ?></div>
                    <a href="/patient/book-appointment?doctor_id=<?= $doctor['id'] ?>" class="btn btn-primary btn-sm">Book appointment</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
