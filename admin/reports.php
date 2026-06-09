<?php

require_once dirname(__DIR__) . '/includes/auth_guard.php';
requireRole(ROLE_ADMIN);

$db = Database::getConnection();

// Date range filter
$fromDate = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
$toDate = $_GET['to'] ?? date('Y-m-d');

// Total appointments in period
$stmt = $db->prepare("
    SELECT COUNT(*) AS total,
           SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
           SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled
    FROM appointments
    WHERE DATE(created_at) BETWEEN ? AND ?
");
$stmt->execute([$fromDate, $toDate]);
$apptStats = $stmt->fetch();

// Revenue in period
$stmt = $db->prepare("
    SELECT COALESCE(SUM(amount), 0) AS total_revenue
    FROM payments
    WHERE status = 'completed' AND DATE(paid_at) BETWEEN ? AND ?
");
$stmt->execute([$fromDate, $toDate]);
$revenue = $stmt->fetch();

// Top doctors
$stmt = $db->prepare("
    SELECT u.name, COUNT(a.id) AS appt_count
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    JOIN users u ON d.user_id = u.id
    WHERE DATE(a.created_at) BETWEEN ? AND ?
    GROUP BY a.doctor_id
    ORDER BY appt_count DESC
    LIMIT 10
");
$stmt->execute([$fromDate, $toDate]);
$topDoctors = $stmt->fetchAll();

// Registrations
$stmt = $db->prepare("
    SELECT role, COUNT(*) AS count
    FROM users
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY role
");
$stmt->execute([$fromDate, $toDate]);
$registrations = $stmt->fetchAll();

// Prescriptions issued
$stmt = $db->prepare("
    SELECT COUNT(*) AS total,
           SUM(CASE WHEN status = 'dispensed' THEN 1 ELSE 0 END) AS dispensed
    FROM prescriptions
    WHERE DATE(issued_at) BETWEEN ? AND ?
");
$stmt->execute([$fromDate, $toDate]);
$rxStats = $stmt->fetch();

$pageTitle = 'Reports';
?>
<div class="topbar">
    <div>
        <h1 class="topbar-title">Reports</h1>
    </div>
</div>

<div class="card" style="padding:var(--space-5);margin-bottom:var(--space-6);">
    <form method="GET" style="display:flex;gap:var(--space-4);align-items:flex-end;flex-wrap:wrap;">
        <div class="form-group" style="margin-bottom:0;">
            <label class="field-label" for="from">From date</label>
            <input class="field-input" type="date" id="from" name="from" value="<?= $fromDate ?>">
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <label class="field-label" for="to">To date</label>
            <input class="field-input" type="date" id="to" name="to" value="<?= $toDate ?>">
        </div>
        <button type="submit" class="btn btn-primary">Filter results</button>
    </form>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-info">
            <div class="stat-card-value"><?= $apptStats['total'] ?></div>
            <div class="stat-card-label">Total bookings</div>
        </div>
        <div class="stat-card-icon blue"><?= icon('calendar') ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-info">
            <div class="stat-card-value"><?= $apptStats['completed'] ?></div>
            <div class="stat-card-label">Consultations completed</div>
        </div>
        <div class="stat-card-icon green"><?= icon('check') ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-info">
            <div class="stat-card-value"><?= $apptStats['cancelled'] ?></div>
            <div class="stat-card-label">Cancellations</div>
        </div>
        <div class="stat-card-icon coral"><?= icon('x') ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-info">
            <div class="stat-card-value"><?= formatCurrency($revenue['total_revenue']) ?></div>
            <div class="stat-card-label">Total revenue</div>
        </div>
        <div class="stat-card-icon green"><?= icon('shield') ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-info">
            <div class="stat-card-value"><?= $rxStats['total'] ?></div>
            <div class="stat-card-label">Prescriptions issued</div>
        </div>
        <div class="stat-card-icon coral"><?= icon('file-text') ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-info">
            <div class="stat-card-value"><?= $rxStats['dispensed'] ?></div>
            <div class="stat-card-label">Prescriptions dispensed</div>
        </div>
        <div class="stat-card-icon blue"><?= icon('check') ?></div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="card">
        <div class="card-header">
            <h3>Top doctors</h3>
        </div>
        <?php if (empty($topDoctors)): ?>
            <div class="empty-state"><div class="empty-state-title">No data available</div></div>
        <?php else: ?>
            <?php foreach ($topDoctors as $i => $doc): ?>
                <div class="appointment-item">
                    <div class="appt-info">
                        <strong>#<?= $i + 1 ?> Dr. <?= e($doc['name']) ?></strong>
                        <span class="appt-date"><?= $doc['appt_count'] ?> appointments</span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>New registrations</h3>
        </div>
        <?php if (empty($registrations)): ?>
            <div class="empty-state"><div class="empty-state-title">No new registrations in this period</div></div>
        <?php else: ?>
            <?php foreach ($registrations as $reg): ?>
                <div class="appointment-item">
                    <div class="appt-info">
                        <strong><?= ucfirst($reg['role']) ?></strong>
                        <span class="appt-date"><?= $reg['count'] ?> registered</span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
