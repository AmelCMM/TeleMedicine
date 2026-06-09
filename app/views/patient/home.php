<div class="patient-dashboard">
    <div class="welcome-card">
        <div style="display:flex;align-items:center;gap:var(--space-4);">
            <span class="avatar avatar-lg"><?= strtoupper(substr(getCurrentUserName(), 0, 1)) ?></span>
            <div>
                <h2>Welcome, <?= e(getCurrentUserName()) ?></h2>
                <p>Manage your health from anywhere.</p>
            </div>
        </div>
    </div>

    <div class="quick-actions-grid">
        <a href="/patient/find-doctor" class="action-card">
            <?= icon('search') ?>
            <span>Find a doctor</span>
        </a>
        <a href="/patient/consultation" class="action-card">
            <?= icon('message-square') ?>
            <span>Chat with a doctor</span>
        </a>
        <a href="/patient/consultation" class="action-card">
            <?= icon('video') ?>
            <span>Video consultation</span>
        </a>
        <a href="/patient/prescriptions" class="action-card">
            <?= icon('file-text') ?>
            <span>Get e-prescriptions</span>
        </a>
        <a href="/patient/appointments" class="action-card">
            <?= icon('calendar') ?>
            <span>Book appointments</span>
        </a>
        <a href="/patient/health-records" class="action-card">
            <?= icon('heart') ?>
            <span>Access health records</span>
        </a>
        <a href="/emergency/nearest" class="action-card emergency">
            <?= icon('alert-triangle') ?>
            <span>Emergency guidance</span>
        </a>
    </div>
</div>
