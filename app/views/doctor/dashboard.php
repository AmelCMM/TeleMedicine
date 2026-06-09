<div class="doctor-dashboard">
    <div class="welcome-card">
        <h2>Welcome, Dr. <?= e(getCurrentUserName()) ?></h2>
        <p>Manage your practice and patient consultations.</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div>
                <div class="stat-card-value">0</div>
                <div class="stat-card-label">Today's appointments</div>
            </div>
            <div class="stat-card-icon blue"><?= icon('calendar') ?></div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-card-value">0</div>
                <div class="stat-card-label">Total patients</div>
            </div>
            <div class="stat-card-icon green"><?= icon('users') ?></div>
        </div>
    </div>
</div>
