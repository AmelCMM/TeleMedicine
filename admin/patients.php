<?php

require_once dirname(__DIR__) . '/includes/auth_guard.php';
requireRole(ROLE_ADMIN);

$db = Database::getConnection();

$search = trim($_GET['search'] ?? '');

$query = "
    SELECT u.id, u.name, u.email, u.phone, u.is_active, u.created_at,
           p.date_of_birth, p.gender
    FROM users u
    LEFT JOIN patients p ON p.user_id = u.id
    WHERE u.role = 'patient'
";
$params = [];

if (!empty($search)) {
    $query .= ' AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
    $params = ['%' . $search . '%', '%' . $search . '%', '%' . $search . '%'];
}

$query .= ' ORDER BY u.created_at DESC';
$stmt = $db->prepare($query);
$stmt->execute($params);
$patients = $stmt->fetchAll();

$pageTitle = 'Manage Patients';
?>
<div class="topbar">
    <div>
        <h1 class="topbar-title">Manage patients</h1>
    </div>
</div>

<div class="card" style="padding:var(--space-4);margin-bottom:var(--space-6);">
    <form method="GET" class="search-form">
        <input type="text" name="search" placeholder="Search patients..." value="<?= e($search) ?>">
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if (!empty($search)): ?>
            <a href="/admin/patients" class="btn btn-ghost">Clear</a>
        <?php endif; ?>
    </form>
</div>

<?php if (empty($patients)): ?>
    <div class="empty-state">
        <div class="empty-state-icon"><?= icon('users') ?></div>
        <div class="empty-state-title">No patients found</div>
    </div>
<?php else: ?>
    <div style="display:flex;flex-direction:column;gap:var(--space-4);">
        <?php foreach ($patients as $patient): ?>
            <div class="card card-status" data-status="<?= $patient['is_active'] ? 'confirmed' : 'cancelled' ?>">
                <div class="card-body">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:var(--space-4);">
                        <div style="display:flex;align-items:center;gap:var(--space-3);">
                            <div class="avatar avatar-md"><?= getInitials($patient['name']) ?></div>
                            <div>
                                <h3 style="font-size:var(--text-lg);"><?= e($patient['name']) ?></h3>
                                <div style="font-size:var(--text-sm);color:var(--text-secondary);"><?= e($patient['email']) ?></div>
                            </div>
                        </div>
                        <span class="badge badge-<?= $patient['is_active'] ? 'confirmed' : 'cancelled' ?>">
                            <?= $patient['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </div>
                    <div style="display:flex;flex-wrap:wrap;gap:var(--space-4);font-size:var(--text-sm);color:var(--text-secondary);">
                        <span><?= icon('phone') ?> <?= e($patient['phone']) ?></span>
                        <?php if ($patient['gender']): ?>
                            <span style="text-transform:capitalize;"><?= icon('user') ?> <?= e($patient['gender']) ?></span>
                        <?php endif; ?>
                        <span><?= icon('calendar') ?> Joined <?= date('M j, Y', strtotime($patient['created_at'])) ?></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
