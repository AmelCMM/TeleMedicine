<?php

require_once dirname(__DIR__) . '/includes/auth_guard.php';
requireRole(ROLE_ADMIN);

$db = Database::getConnection();

$search = trim($_GET['search'] ?? '');

$query = "
    SELECT ph.id, ph.license_number, u.name, u.email, u.phone, u.is_active, u.created_at,
           f.name AS facility_name
    FROM pharmacies ph
    JOIN users u ON ph.user_id = u.id
    LEFT JOIN facilities f ON ph.facility_id = f.id
";
$params = [];

if (!empty($search)) {
    $query .= ' WHERE (u.name LIKE ? OR u.email LIKE ? OR ph.license_number LIKE ?)';
    $params = ['%' . $search . '%', '%' . $search . '%', '%' . $search . '%'];
}

$query .= ' ORDER BY u.created_at DESC';
$stmt = $db->prepare($query);
$stmt->execute($params);
$pharmacies = $stmt->fetchAll();

$pageTitle = 'Manage Pharmacies';
?>
<div class="topbar">
    <div>
        <h1 class="topbar-title">Manage pharmacies</h1>
    </div>
</div>

<div class="card" style="padding:var(--space-4);margin-bottom:var(--space-6);">
    <form method="GET" class="search-form">
        <input type="text" name="search" placeholder="Search pharmacies..." value="<?= e($search) ?>">
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if (!empty($search)): ?>
            <a href="/admin/pharmacies" class="btn btn-ghost">Clear</a>
        <?php endif; ?>
    </form>
</div>

<?php if (empty($pharmacies)): ?>
    <div class="empty-state">
        <div class="empty-state-icon"><?= icon('package') ?></div>
        <div class="empty-state-title">No pharmacies found</div>
    </div>
<?php else: ?>
    <div style="display:flex;flex-direction:column;gap:var(--space-4);">
        <?php foreach ($pharmacies as $pharmacy): ?>
            <div class="card card-status" data-status="<?= $pharmacy['is_active'] ? 'confirmed' : 'cancelled' ?>">
                <div class="card-body">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:var(--space-4);">
                        <div style="display:flex;align-items:center;gap:var(--space-3);">
                            <div class="avatar avatar-md"><?= getInitials($pharmacy['name']) ?></div>
                            <div>
                                <h3 style="font-size:var(--text-lg);"><?= e($pharmacy['name']) ?></h3>
                                <div style="font-size:var(--text-sm);color:var(--text-secondary);"><?= e($pharmacy['email']) ?></div>
                            </div>
                        </div>
                        <span class="badge badge-<?= $pharmacy['is_active'] ? 'confirmed' : 'cancelled' ?>">
                            <?= $pharmacy['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </div>
                    <div style="display:flex;flex-wrap:wrap;gap:var(--space-4);font-size:var(--text-sm);color:var(--text-secondary);">
                        <span><?= icon('phone') ?> <?= e($pharmacy['phone']) ?></span>
                        <span><?= icon('shield') ?> License: <?= e($pharmacy['license_number']) ?></span>
                        <?php if ($pharmacy['facility_name']): ?>
                            <span><?= icon('map-pin') ?> <?= e($pharmacy['facility_name']) ?></span>
                        <?php endif; ?>
                        <span><?= icon('calendar') ?> Joined <?= date('M j, Y', strtotime($pharmacy['created_at'])) ?></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
