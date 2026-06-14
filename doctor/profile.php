<?php

require_once dirname(__DIR__) . '/includes/auth_guard.php';
requireRole(ROLE_DOCTOR);

$userId = getCurrentUserId();
$doctorId = getDoctorId($userId);

if (!$doctorId) {
    setFlashMessage('error', 'Doctor profile not found.');
    redirect('/login');
}

$db = Database::getConnection();

// Get current profile
$stmt = $db->prepare("
    SELECT d.*, u.name, u.email, u.phone, u.avatar
    FROM doctors d
    JOIN users u ON d.user_id = u.id
    WHERE d.id = ?
");
$stmt->execute([$doctorId]);
$doctor = $stmt->fetch();

// Get availability
$stmt = $db->prepare("SELECT * FROM doctor_availability WHERE doctor_id = ? ORDER BY day_of_week, start_time");
$stmt->execute([$doctorId]);
$availability = $stmt->fetchAll();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
        exit;
    }

    $bio = trim($_POST['bio'] ?? '');
    $fee = (float)($_POST['consultation_fee'] ?? 0);
    $specialization = trim($_POST['specialization'] ?? '');

    try {
        $stmt = $db->prepare("
            UPDATE doctors SET bio = ?, consultation_fee = ?, specialization = ? WHERE id = ?
        ");
        $stmt->execute([$bio, $fee, $specialization, $doctorId]);

        // Update availability
        $stmt = $db->prepare("DELETE FROM doctor_availability WHERE doctor_id = ?");
        $stmt->execute([$doctorId]);

        $days = $_POST['days'] ?? [];
        $startTimes = $_POST['start_time'] ?? [];
        $endTimes = $_POST['end_time'] ?? [];

        $stmt = $db->prepare("
            INSERT INTO doctor_availability (doctor_id, day_of_week, start_time, end_time)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($days as $i => $day) {
            if (isset($startTimes[$i], $endTimes[$i]) && !empty($startTimes[$i]) && !empty($endTimes[$i])) {
                $stmt->execute([$doctorId, (int)$day, $startTimes[$i], $endTimes[$i]]);
            }
        }

        echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Update failed. Please try again.']);
        exit;
    }
}

$daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

$pageTitle = 'My Profile';
?>
<div class="topbar">
    <div>
        <h1 class="topbar-title">My profile</h1>
    </div>
</div>

<form id="profileForm" method="POST">
    <?= csrfField() ?>

    <div class="card" style="margin-bottom:var(--space-6);">
        <div class="card-header"><h3>Professional information</h3></div>
        <div class="card-body">
            <div class="form-group">
                <label class="field-label">Name</label>
                <input class="field-input" type="text" value="<?= e($doctor['name']) ?>" disabled>
            </div>
            <div class="form-group">
                <label class="field-label">Email</label>
                <input class="field-input" type="email" value="<?= e($doctor['email']) ?>" disabled>
            </div>
            <div class="form-group">
                <label class="field-label">HPCZ registration number</label>
                <input class="field-input" type="text" value="<?= e($doctor['hpcz_number']) ?>" disabled>
            </div>
            <div class="form-group">
                <label class="field-label" for="specialization">Specialization</label>
                <input class="field-input" type="text" id="specialization" name="specialization" value="<?= e($doctor['specialization']) ?>" required>
            </div>
            <div class="form-group">
                <label class="field-label" for="consultation_fee">Consultation fee (ZMW)</label>
                <input class="field-input" type="number" id="consultation_fee" name="consultation_fee" step="0.50" min="0"
                       value="<?= e($doctor['consultation_fee']) ?>" required>
            </div>
            <div class="form-group">
                <label class="field-label" for="bio">Bio</label>
                <textarea class="field-textarea" id="bio" name="bio" placeholder="Tell patients about your experience..."><?= e($doctor['bio']) ?></textarea>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:var(--space-6);">
        <div class="card-header"><h3>Weekly availability</h3></div>
        <div class="card-body">
            <p style="font-size:var(--text-sm);color:var(--text-secondary);margin-bottom:var(--space-4);">Set your available time slots for each day of the week.</p>
            <div id="availabilityContainer">
                <?php $availByDay = []; foreach ($availability as $a) $availByDay[$a['day_of_week']][] = $a; ?>
                <?php for ($d = 0; $d <= 6; $d++): ?>
                    <?php $daySlots = $availByDay[$d] ?? []; ?>
                    <div class="avail-day" style="margin-bottom:var(--space-4);padding-bottom:var(--space-4);border-bottom:1px solid var(--border);">
                        <label class="field-check">
                            <input type="checkbox" class="day-checkbox" data-day="<?= $d ?>"
                                   <?= !empty($daySlots) ? 'checked' : '' ?>>
                            <span style="font-weight:600;"><?= $daysOfWeek[$d] ?></span>
                        </label>
                        <div class="avail-slots" id="slots-<?= $d ?>" style="display: <?= !empty($daySlots) ? 'flex' : 'none' ?>; flex-direction:column; gap:var(--space-2); margin-top:var(--space-3); padding-left:var(--space-8);">
                            <?php if (!empty($daySlots)): ?>
                                <?php foreach ($daySlots as $slot): ?>
                                    <div class="avail-slot" style="display:flex;align-items:center;gap:var(--space-3);">
                                        <input type="hidden" name="days[]" value="<?= $d ?>">
                                        <input class="field-input" style="width:140px;height:36px;" type="time" name="start_time[]" value="<?= $slot['start_time'] ?>" required>
                                        <span class="text-muted">to</span>
                                        <input class="field-input" style="width:140px;height:36px;" type="time" name="end_time[]" value="<?= $slot['end_time'] ?>" required>
                                        <button type="button" class="btn btn-sm btn-ghost remove-slot"><?= icon('x') ?></button>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <button type="button" class="btn btn-secondary btn-sm add-slot" data-day="<?= $d ?>" style="width:fit-content;"><?= icon('plus') ?> Add slot</button>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary btn-lg">Save changes</button>
</form>

<script>
document.querySelectorAll('.day-checkbox').forEach(function(cb) {
    cb.addEventListener('change', function() {
        const slots = document.getElementById('slots-' + this.dataset.day);
        slots.style.display = this.checked ? 'flex' : 'none';
        if (this.checked && slots.querySelectorAll('.avail-slot').length === 0) {
            addSlot(this.dataset.day);
        }
    });
});

function addSlot(day) {
    const slotsDiv = document.getElementById('slots-' + day);
    const slot = document.createElement('div');
    slot.className = 'avail-slot';
    slot.style.display = 'flex';
    slot.style.alignItems = 'center';
    slot.style.gap = 'var(--space-3)';
    slot.innerHTML = `
        <input type="hidden" name="days[]" value="${day}">
        <input class="field-input" style="width:140px;height:36px;" type="time" name="start_time[]" required>
        <span class="text-muted">to</span>
        <input class="field-input" style="width:140px;height:36px;" type="time" name="end_time[]" required>
        <button type="button" class="btn btn-sm btn-ghost remove-slot"><?= icon('x') ?></button>
    `;
    slotsDiv.insertBefore(slot, slotsDiv.querySelector('.add-slot'));
    slot.querySelector('.remove-slot').addEventListener('click', function() {
        slot.remove();
    });
}

document.querySelectorAll('.add-slot').forEach(function(btn) {
    btn.addEventListener('click', function() {
        addSlot(this.dataset.day);
    });
});

document.querySelectorAll('.remove-slot').forEach(function(btn) {
    btn.addEventListener('click', function() {
        this.closest('.avail-slot').remove();
    });
});

document.getElementById('profileForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = 'Saving...';

    try {
        const formData = new FormData(this);
        const resp = await fetch('/doctor/profile', { method: 'POST', body: formData });
        const data = await resp.json();

        if (data.success) {
            showToast('success', 'Profile updated', data.message);
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast('error', 'Update failed', data.message);
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    } catch (err) {
        showToast('error', 'Update failed', 'An error occurred.');
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
});
</script>
