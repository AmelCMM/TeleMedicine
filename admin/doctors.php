<?php

require_once dirname(__DIR__) . '/includes/auth_guard.php';
requireRole(ROLE_ADMIN);

$db = Database::getConnection();
$filter = $_GET['filter'] ?? 'all';

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
        exit;
    }

    $doctorId = (int)($_POST['doctor_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'approve') {
        $stmt = $db->prepare("UPDATE doctors SET is_approved = 1 WHERE id = ?");
        $stmt->execute([$doctorId]);

        // Notify doctor
        $stmt = $db->prepare("
            SELECT u.id FROM users u
            JOIN doctors d ON d.user_id = u.id
            WHERE d.id = ?
        ");
        $stmt->execute([$doctorId]);
        $docUser = $stmt->fetch();
        if ($docUser) {
            createNotification(
                $docUser['id'],
                'Account Approved',
                'Your doctor account has been approved. You can now start accepting patients.',
                NOTIF_SYSTEM
            );
        }

        echo json_encode(['success' => true, 'message' => 'Doctor approved successfully.']);
    } elseif ($action === 'reject') {
        $stmt = $db->prepare("UPDATE users SET is_active = 0 WHERE id = (SELECT user_id FROM doctors WHERE id = ?)");
        $stmt->execute([$doctorId]);
        echo json_encode(['success' => true, 'message' => 'Doctor rejected.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    }
    exit;
}

$query = "
    SELECT d.id, d.hpcz_number, d.specialization, d.consultation_fee, d.is_approved,
           u.name, u.email, u.phone, u.created_at
    FROM doctors d
    JOIN users u ON d.user_id = u.id
";

if ($filter === 'pending') {
    $query .= ' WHERE d.is_approved = 0';
} elseif ($filter === 'approved') {
    $query .= ' WHERE d.is_approved = 1';
}

$query .= ' ORDER BY u.created_at DESC';

$stmt = $db->query($query);
$doctors = $stmt->fetchAll();

$pageTitle = 'Manage Doctors';
?>
<div class="topbar">
    <div>
        <h1 class="topbar-title">Manage doctors</h1>
    </div>
</div>

<div class="filter-tabs">
    <a href="?filter=all" class="filter-tab <?= $filter === 'all' ? 'active' : '' ?>">All</a>
    <a href="?filter=pending" class="filter-tab <?= $filter === 'pending' ? 'active' : '' ?>">Pending</a>
    <a href="?filter=approved" class="filter-tab <?= $filter === 'approved' ? 'active' : '' ?>">Approved</a>
</div>

<?php if (empty($doctors)): ?>
    <div class="empty-state">
        <div class="empty-state-icon"><?= icon('user-check') ?></div>
        <div class="empty-state-title">No doctors found</div>
    </div>
<?php else: ?>
    <div style="display:flex;flex-direction:column;gap:var(--space-4);">
        <?php foreach ($doctors as $doctor): ?>
            <div class="card card-status" data-status="<?= $doctor['is_approved'] ? 'confirmed' : 'pending' ?>">
                <div class="card-body">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:var(--space-3);">
                        <div>
                            <h3 style="font-size:var(--text-lg);">Dr. <?= e($doctor['name']) ?></h3>
                            <span style="font-size:var(--text-sm);color:var(--text-secondary);">
                                <?= e($doctor['specialization']) ?> &mdash; HPCZ: <?= e($doctor['hpcz_number']) ?>
                            </span>
                        </div>
                        <span class="badge badge-<?= $doctor['is_approved'] ? 'confirmed' : 'pending' ?>">
                            <?= $doctor['is_approved'] ? 'Approved' : 'Pending' ?>
                        </span>
                    </div>

                    <div style="display:flex;flex-wrap:wrap;gap:var(--space-4);font-size:var(--text-sm);color:var(--text-secondary);margin-bottom:var(--space-3);">
                        <span><?= icon('mail') ?> <?= e($doctor['email']) ?></span>
                        <span><?= icon('phone') ?> <?= e($doctor['phone']) ?></span>
                        <span><?= icon('dollar-sign') ?> <?= formatCurrency($doctor['consultation_fee']) ?></span>
                        <span><?= icon('calendar') ?> Joined <?= date('M j, Y', strtotime($doctor['created_at'])) ?></span>
                    </div>

                    <?php if (!$doctor['is_approved']): ?>
                        <div style="display:flex;gap:var(--space-3);">
                            <button class="btn btn-success btn-sm approve-btn" data-doctor-id="<?= $doctor['id'] ?>">Approve</button>
                            <button class="btn btn-danger btn-sm reject-btn" data-doctor-id="<?= $doctor['id'] ?>">Reject</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
(function() {
    var csrfToken = '<?= generateCsrfToken() ?>';

    function handleAction(btn, action) {
        var doctorId = btn.dataset.doctorId;
        btn.disabled = true;

        var formData = new FormData();
        formData.append('doctor_id', doctorId);
        formData.append('action', action);
        formData.append('csrf_token', csrfToken);

        fetch('/admin/doctors', { method: 'POST', body: formData })
            .then(function(resp) { return resp.json(); })
            .then(function(data) {
                if (data.success) {
                    showToast('success', data.message);
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    showToast('error', data.message);
                    btn.disabled = false;
                }
            })
            .catch(function() {
                showToast('error', 'An error occurred.');
                btn.disabled = false;
            });
    }

    document.querySelectorAll('.approve-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('Approve this doctor?')) return;
            handleAction(this, 'approve');
        });
    });

    document.querySelectorAll('.reject-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('Reject this doctor? This will deactivate their account.')) return;
            handleAction(this, 'reject');
        });
    });
})();
</script>
