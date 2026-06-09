<?php

require_once dirname(__DIR__) . '/includes/auth_guard.php';
requireRole(ROLE_PHARMACY);

$db = Database::getConnection();
$userId = getCurrentUserId();
$prescription = null;
$error = null;

// Handle POST verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qrHash = trim($_POST['qr_hash'] ?? '');

    if (empty($qrHash)) {
        $error = 'Please enter or scan a prescription QR code.';
    } else {
        $stmt = $db->prepare("
            SELECT p.*, u.name AS patient_name, d2.name AS doctor_name,
                   d2.specialization
            FROM prescriptions p
            JOIN patients pt ON p.patient_id = pt.id
            JOIN users u ON pt.user_id = u.id
            JOIN doctors d ON p.doctor_id = d.id
            JOIN users d2 ON d.user_id = d2.id
            WHERE p.qr_code_hash = ?
        ");
        $stmt->execute([$qrHash]);
        $prescription = $stmt->fetch();

        if (!$prescription) {
            $error = 'Invalid prescription code. No prescription found.';
        } elseif ($prescription['status'] !== 'active') {
            $error = 'This prescription has already been ' . $prescription['status'] . '.';
        } elseif (strtotime($prescription['expires_at']) < time()) {
            $error = 'This prescription has expired.';
        }
    }

    // Handle dispense confirmation
    if (isset($_POST['dispense']) && $prescription) {
        try {
            $stmt = $db->prepare("
                UPDATE prescriptions
                SET status = 'dispensed', dispensed_by = ?, dispensed_at = NOW()
                WHERE id = ? AND status = 'active'
            ");
            $stmt->execute([$userId, $prescription['id']]);

            // Notify patient
            $stmt2 = $db->prepare("SELECT user_id FROM patients WHERE id = ?");
            $stmt2->execute([$prescription['patient_id']]);
            $pt = $stmt2->fetch();
            if ($pt) {
                createNotification(
                    $pt['user_id'],
                    'Prescription Dispensed',
                    'Your prescription has been dispensed by the pharmacy.',
                    NOTIF_PRESCRIPTION
                );
            }

            setFlashMessage('success', 'Prescription dispensed successfully!');
            redirect('/pharmacy/dashboard');
        } catch (PDOException $e) {
            $error = 'Failed to dispense prescription. Please try again.';
        }
    }
}

$pageTitle = 'Verify Prescription';
?>
<div class="topbar">
    <div>
        <h1 class="topbar-title">Verify prescription</h1>
        <p class="topbar-subtitle">Scan or enter the prescription QR code to verify and dispense.</p>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-6);margin-bottom:var(--space-6);">
    <div class="card">
        <div class="card-header"><h3>Scan QR code</h3></div>
        <div class="card-body" style="text-align:center;">
            <div id="qr-reader" style="width:250px;margin:0 auto;"></div>
            <p style="font-size:var(--text-sm);color:var(--text-muted);margin-top:var(--space-3);">
                Point your camera at the patient's prescription QR code.
            </p>
            <div id="qr-result" style="display:none;margin-top:var(--space-4);">
                <p>Scanned code: <strong id="scannedCode"></strong></p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>Or enter code manually</h3></div>
        <div class="card-body">
            <form method="POST" id="verifyForm">
                <?= csrfField() ?>
                <div class="form-group">
                    <label class="field-label" for="qr_hash">Prescription code</label>
                    <input class="field-input" type="text" id="qr_hash" name="qr_hash" required
                           placeholder="Enter or scan the QR code hash"
                           value="<?= e($_POST['qr_hash'] ?? '') ?>">
                </div>
                <button type="submit" class="btn btn-primary btn-full">Verify</button>
            </form>
        </div>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger" style="margin-bottom:var(--space-6);">
        <?= icon('alert-triangle') ?>
        <div class="alert-text">
            <div class="alert-title">Verification failed</div>
            <?= e($error) ?>
        </div>
    </div>
<?php endif; ?>

<?php if ($prescription): ?>
    <div class="card" style="margin-bottom:var(--space-6);">
        <div class="card-header"><h3>Prescription details</h3></div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-4);margin-bottom:var(--space-4);">
                <div>
                    <div style="font-size:var(--text-xs);color:var(--text-muted);text-transform:uppercase;font-weight:600;">Patient</div>
                    <div><?= e($prescription['patient_name']) ?></div>
                </div>
                <div>
                    <div style="font-size:var(--text-xs);color:var(--text-muted);text-transform:uppercase;font-weight:600;">Doctor</div>
                    <div>Dr. <?= e($prescription['doctor_name']) ?> (<?= e($prescription['specialization']) ?>)</div>
                </div>
                <div>
                    <div style="font-size:var(--text-xs);color:var(--text-muted);text-transform:uppercase;font-weight:600;">Issued</div>
                    <div><?= date('M j, Y g:i A', strtotime($prescription['issued_at'])) ?></div>
                </div>
                <div>
                    <div style="font-size:var(--text-xs);color:var(--text-muted);text-transform:uppercase;font-weight:600;">Expires</div>
                    <div><?= date('M j, Y', strtotime($prescription['expires_at'])) ?></div>
                </div>
                <div>
                    <div style="font-size:var(--text-xs);color:var(--text-muted);text-transform:uppercase;font-weight:600;">Status</div>
                    <div><span class="badge badge-<?= $prescription['status'] ?>"><?= $prescription['status'] ?></span></div>
                </div>
            </div>

            <h4 style="margin-bottom:var(--space-3);">Medications</h4>
            <?php
            $stmt = $db->prepare("
                SELECT medication_name, dosage, frequency, duration, instructions
                FROM prescription_items WHERE prescription_id = ?
            ");
            $stmt->execute([$prescription['id']]);
            $items = $stmt->fetchAll();
            ?>
            <div style="overflow-x:auto;">
                <table class="rx-table" style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="background:var(--surface-alt);">
                            <th style="padding:var(--space-2) var(--space-3);text-align:left;font-size:var(--text-sm);">Medication</th>
                            <th style="padding:var(--space-2) var(--space-3);text-align:left;font-size:var(--text-sm);">Dosage</th>
                            <th style="padding:var(--space-2) var(--space-3);text-align:left;font-size:var(--text-sm);">Frequency</th>
                            <th style="padding:var(--space-2) var(--space-3);text-align:left;font-size:var(--text-sm);">Duration</th>
                            <th style="padding:var(--space-2) var(--space-3);text-align:left;font-size:var(--text-sm);">Instructions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr style="border-bottom:1px solid var(--border);">
                                <td style="padding:var(--space-2) var(--space-3);"><?= e($item['medication_name']) ?></td>
                                <td style="padding:var(--space-2) var(--space-3);"><?= e($item['dosage']) ?></td>
                                <td style="padding:var(--space-2) var(--space-3);"><?= e($item['frequency']) ?></td>
                                <td style="padding:var(--space-2) var(--space-3);"><?= e($item['duration']) ?></td>
                                <td style="padding:var(--space-2) var(--space-3);"><?= e($item['instructions']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <form method="POST" style="margin-top:var(--space-5);">
                <?= csrfField() ?>
                <input type="hidden" name="qr_hash" value="<?= e($prescription['qr_code_hash']) ?>">
                <button type="submit" name="dispense" class="btn btn-success btn-lg"
                        onclick="return confirm('Confirm dispense of this prescription?')">
                    <?= icon('check') ?> Confirm dispense
                </button>
            </form>
        </div>
    </div>
<?php endif; ?>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(function() {
    const qrReader = document.getElementById('qr-reader');
    const qrResult = document.getElementById('qr-result');
    const scannedCode = document.getElementById('scannedCode');
    const qrInput = document.getElementById('qr_hash');

    if (qrReader && typeof Html5Qrcode !== 'undefined') {
        const html5QrCode = new Html5Qrcode("qr-reader");

        html5QrCode.start(
            { facingMode: "environment" },
            {
                fps: 10,
                qrbox: { width: 200, height: 200 }
            },
            function(decodedText) {
                qrResult.style.display = 'block';
                scannedCode.textContent = decodedText;
                qrInput.value = decodedText;
                html5QrCode.stop();
                document.getElementById('verifyForm').submit();
            },
            function(error) {
                // Scanning in progress
            }
        ).catch(function(err) {
            qrReader.innerHTML = '<p class="text-muted">Camera not available. Please enter the code manually.</p>';
        });
    }
})();
</script>
