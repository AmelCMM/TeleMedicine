<?php
require_once dirname(__DIR__) . '/includes/auth_guard.php';
requireRole(ROLE_ADMIN);

$db = Database::getConnection();

// Stats
$patientCount = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'patient'")->fetchColumn();
$doctorCount = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'doctor'")->fetchColumn();
$pendingDoctors = (int)$db->query("SELECT COUNT(*) FROM doctors WHERE is_approved = 0")->fetchColumn();
$pharmacyCount = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'pharmacy'")->fetchColumn();
$todayAppts = (int)$db->query("SELECT COUNT(*) FROM appointments WHERE DATE(scheduled_at) = CURDATE()")->fetchColumn();
$facilityCount = (int)$db->query("SELECT COUNT(*) FROM facilities")->fetchColumn();

$pageTitle = 'Administration Panel';
?>
<div class="page-header animate-fade">
    <div>
        <h1 class="topbar-title">Platform Overview</h1>
        <p class="topbar-subtitle">System metrics and management tools.</p>
    </div>
    <div class="topbar-actions">
        <a href="/admin/reports" class="btn btn-secondary"><?= icon('file-text') ?> System Reports</a>
    </div>
</div>

<div class="stats-grid animate-slide">
    <div class="stat-card">
        <div class="stat-card-label">Total Patients</div>
        <div class="stat-card-value"><?= number_format($patientCount) ?></div>
        <div class="stat-card-icon blue"><?= icon('users') ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-label">Verified Providers</div>
        <div class="stat-card-value"><?= number_format($doctorCount) ?></div>
        <div class="stat-card-icon green"><?= icon('user-check') ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-label">Pending Reviews</div>
        <div class="stat-card-value"><?= $pendingDoctors ?></div>
        <div class="stat-card-icon amber"><?= icon('clock') ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-label">Registered Pharmacies</div>
        <div class="stat-card-value"><?= $pharmacyCount ?></div>
        <div class="stat-card-icon coral"><?= icon('shield') ?></div>
    </div>
</div>

<div class="dashboard-grid animate-slide" style="animation-delay: 100ms;">
    <div class="card">
        <div class="card-header">
            <h3>Administrative Actions</h3>
        </div>
        <div class="card-body">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:var(--space-4);">
                <a href="/admin/doctors" class="btn btn-secondary btn-lg" style="justify-content: flex-start; height:auto; padding: var(--space-4);">
                    <div style="text-align: left;">
                        <div style="font-weight:600; color:var(--text);">Manage Providers</div>
                        <div style="font-size:var(--text-xs); color:var(--text-muted); font-weight:400; margin-top:4px;">Verify credentials and manage accounts.</div>
                    </div>
                </a>
                <a href="/admin/pharmacies" class="btn btn-secondary btn-lg" style="justify-content: flex-start; height:auto; padding: var(--space-4);">
                    <div style="text-align: left;">
                        <div style="font-weight:600; color:var(--text);">Manage Pharmacies</div>
                        <div style="font-size:var(--text-xs); color:var(--text-muted); font-weight:400; margin-top:4px;">Network oversight and compliance.</div>
                    </div>
                </a>
                <a href="/admin/facilities" class="btn btn-secondary btn-lg" style="justify-content: flex-start; height:auto; padding: var(--space-4);">
                    <div style="text-align: left;">
                        <div style="font-weight:600; color:var(--text);">Healthcare Facilities</div>
                        <div style="font-size:var(--text-xs); color:var(--text-muted); font-weight:400; margin-top:4px;">Directory and geolocation management.</div>
                    </div>
                </a>
                <a href="/admin/reports" class="btn btn-secondary btn-lg" style="justify-content: flex-start; height:auto; padding: var(--space-4);">
                    <div style="text-align: left;">
                        <div style="font-weight:600; color:var(--text);">System Analytics</div>
                        <div style="font-size:var(--text-xs); color:var(--text-muted); font-weight:400; margin-top:4px;">Platform growth and usage tracking.</div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>System Status</h3>
        </div>
        <div class="card-body">
            <?php if ($pendingDoctors > 0): ?>
                <div class="alert alert-warning" style="margin-bottom: var(--space-4);">
                    <?= icon('clock') ?>
                    <div class="alert-text">
                        <strong>Review Required</strong>
                        <p style="font-size: var(--text-xs); margin:0;"><?= $pendingDoctors ?> providers awaiting verification.</p>
                    </div>
                </div>
                <a href="/admin/doctors?filter=pending" class="btn btn-primary btn-full">Review Now</a>
            <?php else: ?>
                <div class="empty-state" style="padding: var(--space-6);">
                    <div class="empty-state-icon" style="background: var(--success-bg); color: var(--success); width:48px; height:48px;">
                        <?= icon('check') ?>
                    </div>
                    <div class="empty-state-title" style="font-size: var(--text-base);">All caught up</div>
                    <p class="empty-state-text" style="font-size: var(--text-xs);">No pending provider reviews.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
