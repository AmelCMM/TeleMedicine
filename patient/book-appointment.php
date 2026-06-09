<?php

require_once dirname(__DIR__) . '/includes/auth_guard.php';
requireRole(ROLE_PATIENT);

$db = Database::getConnection();
$userId = getCurrentUserId();
$patientId = getPatientId($userId);

if (!$patientId) {
    setFlashMessage('error', 'Patient profile not found.');
    redirect('/patient/dashboard');
}

$doctorId = (int)($_GET['doctor_id'] ?? 0);

// Get doctor info
$stmt = $db->prepare("
    SELECT d.id, u.name, d.specialization, d.consultation_fee
    FROM doctors d
    JOIN users u ON d.user_id = u.id
    WHERE d.id = ? AND d.is_approved = 1
");
$stmt->execute([$doctorId]);
$doctor = $stmt->fetch();

if (!$doctor) {
    setFlashMessage('error', 'Doctor not found or not approved.');
    redirect('/patient/find-doctor');
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
        exit;
    }

    $scheduledAt = $_POST['scheduled_at'] ?? '';
    $type = $_POST['type'] ?? 'chat';
    $chiefComplaint = trim($_POST['chief_complaint'] ?? '');

    if (empty($scheduledAt) || strtotime($scheduledAt) <= time()) {
        echo json_encode(['success' => false, 'message' => 'Please select a future date and time.']);
        exit;
    }

    if (!in_array($type, ['chat', 'voice', 'video'])) {
        $type = 'chat';
    }

    try {
        $db->beginTransaction();

        // Create appointment
        $stmt = $db->prepare("
            INSERT INTO appointments (patient_id, doctor_id, scheduled_at, type, status, chief_complaint, jitsi_room)
            VALUES (?, ?, ?, ?, 'pending', ?, ?)
        ");
        $jitsiRoom = generateJitsiRoom(0);
        $stmt->execute([$patientId, $doctorId, $scheduledAt, $type, $chiefComplaint, $jitsiRoom]);
        $apptId = (int)$db->lastInsertId();

        // Update jitsi room with real ID
        $jitsiRoom = generateJitsiRoom($apptId);
        $stmt = $db->prepare('UPDATE appointments SET jitsi_room = ? WHERE id = ?');
        $stmt->execute([$jitsiRoom, $apptId]);

        // Create payment record (pending)
        $stmt = $db->prepare("
            INSERT INTO payments (appointment_id, amount, method, status)
            VALUES (?, ?, 'mtn_momo', 'pending')
        ");
        $stmt->execute([$apptId, $doctor['consultation_fee']]);

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
                'New Appointment Booking',
                'A patient has booked an appointment for ' . date('M j, Y g:i A', strtotime($scheduledAt)),
                NOTIF_APPOINTMENT
            );
        }

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Appointment booked successfully! You will be redirected to payment.',
            'redirect' => '/patient/appointments',
        ]);
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Booking failed. Please try again.']);
        exit;
    }
}

$pageTitle = 'Book Appointment';
?>
<div class="topbar">
    <div>
        <h1 class="topbar-title">Book appointment</h1>
        <p class="topbar-subtitle">Schedule a consultation with Dr. <?= e($doctor['name']) ?></p>
    </div>
</div>

<div class="wizard-steps">
    <div class="wizard-step active">
        <div class="wizard-step-dot">1</div>
        <div class="wizard-step-label">Details</div>
    </div>
    <div class="wizard-step-line"></div>
    <div class="wizard-step" id="step2-indicator">
        <div class="wizard-step-dot">2</div>
        <div class="wizard-step-label">Payment</div>
    </div>
    <div class="wizard-step-line"></div>
    <div class="wizard-step">
        <div class="wizard-step-dot">3</div>
        <div class="wizard-step-label">Confirm</div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="card">
        <div class="card-header">
            <h3>Consultation details</h3>
        </div>
        <form id="bookingForm" method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="doctor_id" value="<?= $doctorId ?>">
            <div class="card-body">
                <div class="form-group">
                    <label class="field-label" for="scheduled_at">Preferred date & time</label>
                    <input class="field-input" type="datetime-local" id="scheduled_at" name="scheduled_at" required
                           min="<?= date('Y-m-d\TH:i', strtotime('+1 hour')) ?>">
                </div>

                <div class="form-group">
                    <label class="field-label">Consultation type</label>
                    <div class="radio-card-group">
                        <label class="radio-card">
                            <input type="radio" name="type" value="chat" checked>
                            <span class="radio-card-label">
                                <?= icon('message-square') ?>
                                <span>Chat</span>
                            </span>
                        </label>
                        <label class="radio-card">
                            <input type="radio" name="type" value="voice">
                            <span class="radio-card-label">
                                <?= icon('phone') ?>
                                <span>Voice</span>
                            </span>
                        </label>
                        <label class="radio-card">
                            <input type="radio" name="type" value="video">
                            <span class="radio-card-label">
                                <?= icon('video') ?>
                                <span>Video</span>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="field-label" for="chief_complaint">What is the reason for this visit?</label>
                    <textarea class="field-textarea" id="chief_complaint" name="chief_complaint"
                              placeholder="Describe your symptoms or concerns..."></textarea>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary btn-lg">Book & proceed to payment</button>
            </div>
        </form>
    </div>

    <aside>
        <div class="card" style="margin-bottom:var(--space-6);">
            <div class="card-header">
                <h3>Doctor info</h3>
            </div>
            <div class="card-body">
                <div style="display:flex;align-items:center;gap:var(--space-3);margin-bottom:var(--space-4);">
                    <div class="avatar avatar-lg"><?= getInitials($doctor['name']) ?></div>
                    <div>
                        <div style="font-weight:700;">Dr. <?= e($doctor['name']) ?></div>
                        <div style="font-size:var(--text-sm);color:var(--primary-500);"><?= e($doctor['specialization']) ?></div>
                    </div>
                </div>
                <div style="padding-top:var(--space-4);border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                    <span class="text-muted">Consultation fee</span>
                    <span style="font-weight:700;font-size:var(--text-lg);"><?= formatCurrency($doctor['consultation_fee']) ?></span>
                </div>
            </div>
        </div>

        <div class="alert alert-info">
            <?= icon('info') ?>
            <div class="alert-text">
                Your appointment will be confirmed once payment is received.
            </div>
        </div>
    </aside>
</div>

<script>
document.getElementById('bookingForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.textContent = 'Processing...';

    try {
        const formData = new FormData(this);
        const resp = await fetch('/patient/book-appointment?doctor_id=<?= $doctorId ?>', {
            method: 'POST',
            body: formData
        });
        const data = await resp.json();

        if (data.success) {
            window.location.href = data.redirect;
        } else {
            alert(data.message);
            btn.disabled = false;
            btn.textContent = 'Proceed to Payment';
        }
    } catch (err) {
        alert('An error occurred. Please try again.');
        btn.disabled = false;
        btn.textContent = 'Proceed to Payment';
    }
});
</script>
