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

$stmt = $db->prepare("
    SELECT p.id, p.issued_at, p.expires_at, p.status, p.qr_code_hash,
           u.name AS doctor_name, d.specialization,
           GROUP_CONCAT(pi.medication_name SEPARATOR ', ') AS medications
    FROM prescriptions p
    JOIN doctors d ON p.doctor_id = d.id
    JOIN users u ON d.user_id = u.id
    LEFT JOIN prescription_items pi ON pi.prescription_id = p.id
    WHERE p.patient_id = ?
    GROUP BY p.id
    ORDER BY p.issued_at DESC
");
$stmt->execute([$patientId]);
$prescriptions = $stmt->fetchAll();

$pageTitle = 'My Prescriptions';
?>
<div class="topbar">
    <div>
        <h1 class="topbar-title">My prescriptions</h1>
        <p class="topbar-subtitle">View your e-prescriptions from consultations</p>
    </div>
</div>

<?php if (empty($prescriptions)): ?>
    <div class="empty-state">
        <div class="empty-state-icon"><?= icon('file-text') ?></div>
        <div class="empty-state-title">No prescriptions yet</div>
        <p class="empty-state-text">Your prescriptions will appear here after a doctor issues one.</p>
    </div>
<?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(320px, 1fr));gap:var(--space-6);">
        <?php foreach ($prescriptions as $rx): ?>
            <div class="prescription-card">
                <div class="prescription-header">
                    <div>
                        <div class="prescription-id">RX-<?= str_pad($rx['id'], 6, '0', STR_PAD_LEFT) ?></div>
                        <div style="font-weight:700;font-size:var(--text-lg);margin-top:2px;">Dr. <?= e($rx['doctor_name']) ?></div>
                        <div style="font-size:var(--text-sm);opacity:0.85;"><?= e($rx['specialization']) ?></div>
                    </div>
                    <span class="badge badge-<?= $rx['status'] ?>"><?= $rx['status'] ?></span>
                </div>
                <div class="prescription-body">
                    <?php if ($rx['medications']): ?>
                        <div class="rx-item">
                            <div class="rx-medication">Prescribed medications</div>
                            <div class="rx-details"><?= e($rx['medications']) ?></div>
                        </div>
                    <?php endif; ?>

                    <div style="display:flex;gap:var(--space-6);margin-top:var(--space-4);">
                        <div>
                            <div style="font-size:var(--text-xs);color:var(--text-muted);text-transform:uppercase;font-weight:600;">Issued</div>
                            <div style="font-size:var(--text-sm);"><?= date('M j, Y', strtotime($rx['issued_at'])) ?></div>
                        </div>
                        <div>
                            <div style="font-size:var(--text-xs);color:var(--text-muted);text-transform:uppercase;font-weight:600;">Expires</div>
                            <div style="font-size:var(--text-sm);"><?= date('M j, Y', strtotime($rx['expires_at'])) ?></div>
                        </div>
                    </div>

                    <button class="btn btn-secondary btn-sm btn-full view-rx-btn" style="margin-top:var(--space-6);"
                            data-rx-id="<?= $rx['id'] ?>" data-rx-hash="<?= e($rx['qr_code_hash']) ?>">
                        <?= icon('qr-code') ?> View digital prescription
                    </button>

                    <div class="prescription-qr" id="qrPanel-<?= $rx['id'] ?>" style="display:none;flex-direction:column;align-items:center;">
                        <img id="qrImg-<?= $rx['id'] ?>" src="" alt="Prescription QR code">
                        <p style="font-size:var(--text-xs);color:var(--text-muted);margin-top:var(--space-3);text-align:center;">
                            Scan at any registered pharmacy to verify and dispense medication.
                        </p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
document.querySelectorAll('.view-rx-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const rxId = this.dataset.rxId;
        const panel = document.getElementById('qrPanel-' + rxId);
        const img = document.getElementById('qrImg-' + rxId);

        if (panel.style.display === 'none' || !panel.style.display) {
            panel.style.display = 'flex';
            this.innerHTML = '<?= icon('x') ?> Hide digital prescription';

            if (!img.src) {
                img.src = 'https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=' + encodeURIComponent(this.dataset.rxHash);
            }
        } else {
            panel.style.display = 'none';
            this.innerHTML = '<?= icon('qr-code') ?> View digital prescription';
        }
    });
});
</script>
