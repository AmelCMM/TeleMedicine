<?php

require_once dirname(__DIR__) . '/includes/auth_guard.php';
requireRole(ROLE_DOCTOR);

$userId = getCurrentUserId();
$doctorId = getDoctorId($userId);

if (!$doctorId) {
    setFlashMessage('error', 'Doctor profile not found.');
    redirect('/login');
}

// Get consultation ID from various sources
$consultationApptId = (int)($_GET['consultation'] ?? 0);

if (!$consultationApptId) {
    setFlashMessage('error', 'No consultation specified.');
    redirect('/doctor/dashboard');
}

$db = Database::getConnection();

// Get consultation details
$stmt = $db->prepare("
    SELECT c.id, c.diagnosis, a.patient_id, a.id AS appointment_id, a.scheduled_at,
           u.name AS patient_name
    FROM consultations c
    JOIN appointments a ON c.appointment_id = a.id
    JOIN patients p ON a.patient_id = p.id
    JOIN users u ON p.user_id = u.id
    WHERE a.id = ? AND a.doctor_id = ?
");
$stmt->execute([$consultationApptId, $doctorId]);
$consultation = $stmt->fetch();

if (!$consultation) {
    setFlashMessage('error', 'Consultation not found.');
    redirect('/doctor/dashboard');
}

$consultationId = (int)$consultation['id'];
$patientId = (int)$consultation['patient_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
        exit;
    }

    $medications = $_POST['medications'] ?? [];

    if (empty($medications)) {
        echo json_encode(['success' => false, 'message' => 'Please add at least one medication.']);
        exit;
    }

    try {
        $db->beginTransaction();

        $issuedAt = date('Y-m-d H:i:s');
        $expiresAt = date('Y-m-d', strtotime('+' . RX_EXPIRY_DAYS . ' days'));

        // Create prescription (placeholder qr hash)
        $stmt = $db->prepare("
            INSERT INTO prescriptions (consultation_id, doctor_id, patient_id, qr_code_hash, expires_at)
            VALUES (?, ?, ?, 'placeholder', ?)
        ");
        $stmt->execute([$consultationId, $doctorId, $patientId, $expiresAt]);
        $prescriptionId = (int)$db->lastInsertId();

        // Update QR hash
        $qrHash = generateQrHash($prescriptionId, $patientId, $issuedAt);
        $stmt = $db->prepare("UPDATE prescriptions SET qr_code_hash = ? WHERE id = ?");
        $stmt->execute([$qrHash, $prescriptionId]);

        // Insert prescription items
        $stmt = $db->prepare("
            INSERT INTO prescription_items (prescription_id, medication_name, dosage, frequency, duration, instructions)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        foreach ($medications as $med) {
            $stmt->execute([
                $prescriptionId,
                trim($med['name'] ?? ''),
                trim($med['dosage'] ?? ''),
                trim($med['frequency'] ?? ''),
                trim($med['duration'] ?? ''),
                trim($med['instructions'] ?? ''),
            ]);
        }

        // Mark appointment as completed if not already
        $stmt = $db->prepare("
            UPDATE appointments SET status = 'completed'
            WHERE id = ? AND status != 'completed'
        ");
        $stmt->execute([$consultationApptId]);

        // Update consultation ended_at if not set
        $stmt = $db->prepare("
            UPDATE consultations SET ended_at = NOW()
            WHERE id = ? AND ended_at IS NULL
        ");
        $stmt->execute([$consultationId]);

        // Notify patient
        $stmt = $db->prepare("SELECT user_id FROM patients WHERE id = ?");
        $stmt->execute([$patientId]);
        $p = $stmt->fetch();
        if ($p) {
            createNotification(
                $p['user_id'],
                'Prescription Issued',
                'Your doctor has issued a new prescription. View it in your prescriptions.',
                NOTIF_PRESCRIPTION
            );
        }

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Prescription issued successfully!',
            'redirect' => '/doctor/dashboard',
        ]);
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Failed to issue prescription. Please try again.']);
        exit;
    }
}

$pageTitle = 'Write Prescription';
?>
<div class="topbar">
    <div>
        <h1 class="topbar-title">Write prescription</h1>
        <p class="topbar-subtitle">Patient: <?= e($consultation['patient_name']) ?></p>
    </div>
</div>

<div class="card" style="margin-bottom:var(--space-6);">
    <?php if ($consultation['diagnosis']): ?>
        <div class="alert alert-info">
            <?= icon('info') ?>
            <div class="alert-text">
                <div class="alert-title">Diagnosis recorded</div>
                <?= e($consultation['diagnosis']) ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<form id="rxForm" method="POST">
    <?= csrfField() ?>

    <div class="card" style="margin-bottom:var(--space-6);">
        <div class="card-header">
            <h3>Medications</h3>
        </div>
        <div class="card-body">
            <div id="medicationsContainer">
                <div class="medication-item" style="padding-bottom:var(--space-6);margin-bottom:var(--space-6);border-bottom:1px solid var(--border);">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-4);">
                        <div class="form-group">
                            <label class="field-label">Medication name</label>
                            <input class="field-input" type="text" name="medications[0][name]" required placeholder="e.g. Amoxicillin">
                        </div>
                        <div class="form-group">
                            <label class="field-label">Dosage</label>
                            <input class="field-input" type="text" name="medications[0][dosage]" required placeholder="e.g. 500mg">
                        </div>
                        <div class="form-group">
                            <label class="field-label">Frequency</label>
                            <input class="field-input" type="text" name="medications[0][frequency]" required placeholder="e.g. 3 times daily">
                        </div>
                        <div class="form-group">
                            <label class="field-label">Duration</label>
                            <input class="field-input" type="text" name="medications[0][duration]" required placeholder="e.g. 7 days">
                        </div>
                        <div class="form-group" style="grid-column:1/-1;">
                            <label class="field-label">Instructions</label>
                            <input class="field-input" type="text" name="medications[0][instructions]" placeholder="e.g. Take after meals">
                        </div>
                    </div>
                </div>
            </div>
            <button type="button" id="addMedication" class="btn btn-secondary btn-sm"><?= icon('plus') ?> Add medication</button>
        </div>
    </div>

    <button type="submit" class="btn btn-primary btn-lg btn-full">Issue prescription</button>
</form>

<script>
let medIndex = 1;

document.getElementById('addMedication').addEventListener('click', function() {
    const container = document.getElementById('medicationsContainer');
    const template = `
        <div class="medication-item" style="padding-bottom:var(--space-6);margin-bottom:var(--space-6);border-bottom:1px solid var(--border);">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-4);">
                <div class="form-group">
                    <label class="field-label">Medication name</label>
                    <input class="field-input" type="text" name="medications[${medIndex}][name]" required placeholder="e.g. Amoxicillin">
                </div>
                <div class="form-group">
                    <label class="field-label">Dosage</label>
                    <input class="field-input" type="text" name="medications[${medIndex}][dosage]" required placeholder="e.g. 500mg">
                </div>
                <div class="form-group">
                    <label class="field-label">Frequency</label>
                    <input class="field-input" type="text" name="medications[${medIndex}][frequency]" required placeholder="e.g. 3 times daily">
                </div>
                <div class="form-group">
                    <label class="field-label">Duration</label>
                    <input class="field-input" type="text" name="medications[${medIndex}][duration]" required placeholder="e.g. 7 days">
                </div>
                <div class="form-group" style="grid-column:1/-1;">
                    <label class="field-label">Instructions</label>
                    <input class="field-input" type="text" name="medications[${medIndex}][instructions]" placeholder="e.g. Take after meals">
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-ghost remove-med" style="margin-top:var(--space-2);color:var(--danger);"><?= icon('x') ?> Remove medication</button>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', template);
    medIndex++;

    document.querySelectorAll('.remove-med').forEach(function(btn) {
        btn.removeEventListener('click', removeMedication);
        btn.addEventListener('click', removeMedication);
    });
});

function removeMedication() {
    this.closest('.medication-item').remove();
}

document.getElementById('rxForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = 'Issuing prescription...';

    try {
        const formData = new FormData(this);
        const resp = await fetch('/doctor/write-prescription?consultation=<?= $consultationApptId ?>', {
            method: 'POST',
            body: formData
        });
        const data = await resp.json();

        if (data.success) {
            showToast('success', 'Prescription issued', data.message);
            setTimeout(() => window.location.href = data.redirect, 1500);
        } else {
            showToast('error', 'Error', data.message);
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    } catch (err) {
        showToast('error', 'Error', 'An error occurred. Please try again.');
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
});
</script>
