<?php

require_once dirname(__DIR__) . '/includes/auth_guard.php';
requireRole(ROLE_DOCTOR);

$userId = getCurrentUserId();
$doctorId = getDoctorId($userId);
$apptId = (int)($_GET['appt'] ?? 0);

if (!$doctorId || !$apptId) {
    setFlashMessage('error', 'Invalid request.');
    redirect('/doctor/dashboard');
}

$db = Database::getConnection();

// Verify this appointment belongs to this doctor
$stmt = $db->prepare("
    SELECT a.id, a.status, a.type, a.jitsi_room, a.scheduled_at,
           u.name AS patient_name, p.id AS patient_id
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    JOIN users u ON p.user_id = u.id
    WHERE a.id = ? AND a.doctor_id = ?
");
$stmt->execute([$apptId, $doctorId]);
$appt = $stmt->fetch();

if (!$appt) {
    setFlashMessage('error', 'Appointment not found.');
    redirect('/doctor/dashboard');
}

// Get or create consultation
$stmt = $db->prepare('SELECT id, id AS consultation_id, ended_at FROM consultations WHERE appointment_id = ?');
$stmt->execute([$apptId]);
$consultation = $stmt->fetch();

if (!$consultation) {
    $stmt = $db->prepare("
        INSERT INTO consultations (appointment_id, started_at)
        VALUES (?, NOW())
    ");
    $stmt->execute([$apptId]);

    // Update appointment status
    $stmt = $db->prepare("UPDATE appointments SET status = 'in_progress' WHERE id = ?");
    $stmt->execute([$apptId]);

    $consultationId = (int)$db->lastInsertId();
} else {
    $consultationId = (int)$consultation['consultation_id'];
}

// Mark appointment as in_progress if not already
if ($appt['status'] !== 'in_progress' && $appt['status'] !== 'completed') {
    $stmt = $db->prepare("UPDATE appointments SET status = 'in_progress' WHERE id = ?");
    $stmt->execute([$apptId]);
}

// Get messages
$stmt = $db->prepare("
    SELECT m.id, m.content, m.type, m.file_path, m.sent_at, m.sender_id, u.name AS sender_name
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.consultation_id = ?
    ORDER BY m.sent_at ASC
");
$stmt->execute([$consultationId]);
$messages = $stmt->fetchAll();

// Handle end consultation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['end_consultation'])) {
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $followUpDate = !empty($_POST['follow_up_date']) ? $_POST['follow_up_date'] : null;

    $stmt = $db->prepare("
        UPDATE consultations
        SET ended_at = NOW(), diagnosis = ?, notes = ?, follow_up_date = ?
        WHERE id = ?
    ");
    $stmt->execute([$diagnosis, $notes, $followUpDate, $consultationId]);

    // Update appointment status
    $stmt = $db->prepare("UPDATE appointments SET status = 'completed' WHERE id = ?");
    $stmt->execute([$apptId]);

    // Create health record
    $stmt = $db->prepare("
        INSERT INTO health_records (patient_id, consultation_id, title, record_type, description, created_by)
        VALUES (?, ?, ?, 'diagnosis', ?, ?)
    ");
    $title = 'Consultation - ' . date('M j, Y');
    $stmt->execute([$appt['patient_id'], $consultationId, $title, $diagnosis, $userId]);

    // Notify patient
    $stmt = $db->prepare("SELECT user_id FROM patients WHERE id = ?");
    $stmt->execute([$appt['patient_id']]);
    $p = $stmt->fetch();
    if ($p) {
        createNotification(
            $p['user_id'],
            'Consultation Completed',
            'Your consultation has been completed. A prescription may have been issued.',
            NOTIF_CONSULTATION
        );
    }

    setFlashMessage('success', 'Consultation ended successfully.');
    redirect('/doctor/write-prescription?consultation=' . $apptId);
}

$pageTitle = 'Consultation';
?>
<div class="consultation-layout">
    <div class="consultation-video">
        <div class="consultation-type-label">
            <?php if ($appt['type'] === 'video'): ?>
                Video Consultation
            <?php elseif ($appt['type'] === 'voice'): ?>
                Voice Consultation
            <?php else: ?>
                Chat Consultation
            <?php endif; ?>
        </div>
        <div id="jitsi-container" style="flex:1;">
            <?php if ($appt['type'] === 'chat'): ?>
                <div class="empty-state" style="height:100%;color:white;">
                    <div class="empty-state-icon" style="background-color:rgba(255,255,255,0.1);"><?= icon('message-square') ?></div>
                    <div class="empty-state-title" style="color:white;">Chat only mode</div>
                    <p class="empty-state-text" style="color:rgba(255,255,255,0.6);">Use the panel on the right to message the patient.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="consultation-chat">
        <div class="chat-header">
            <span>Patient: <?= e($appt['patient_name']) ?></span>
            <button class="btn btn-sm btn-danger" id="endConsultBtn"><?= icon('log-out') ?> End</button>
        </div>

        <div class="chat-messages" id="chatMessages">
            <?php foreach ($messages as $msg): ?>
                <div class="message-bubble <?= $msg['sender_id'] === $userId ? 'outgoing' : 'incoming' ?>">
                    <div class="bubble-body">
                        <?= e($msg['content']) ?>
                        <div class="bubble-time"><?= timeAgo($msg['sent_at']) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <form id="messageForm" class="chat-input-area">
            <input type="text" id="messageInput" class="chat-input" placeholder="Type your message..." required autocomplete="off">
            <button type="submit" class="chat-send-btn"><?= icon('check') ?></button>
        </form>
    </div>
</div>

<!-- End Consultation Modal -->
<div class="modal-backdrop" id="endConsultModal">
    <div class="modal">
        <div class="modal-header">
            <h3>End consultation</h3>
            <button class="modal-close"><?= icon('x') ?></button>
        </div>
        <div class="modal-body">
            <form id="endConsultForm" method="POST">
                <input type="hidden" name="end_consultation" value="1">
                <div class="form-group">
                    <label class="field-label" for="diagnosis">Diagnosis <span class="required">*</span></label>
                    <textarea class="field-textarea" id="diagnosis" name="diagnosis" required placeholder="What is your diagnosis for this patient?"></textarea>
                </div>
                <div class="form-group">
                    <label class="field-label" for="notes">Clinical notes</label>
                    <textarea class="field-textarea" id="notes" name="notes" placeholder="Private notes about this visit..."></textarea>
                </div>
                <div class="form-group">
                    <label class="field-label" for="follow_up_date">Recommended follow-up date</label>
                    <input class="field-input" type="date" id="follow_up_date" name="follow_up_date" min="<?= date('Y-m-d') ?>">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost modal-close">Continue consultation</button>
            <button type="submit" form="endConsultForm" class="btn btn-danger">End & issue prescription</button>
        </div>
    </div>
</div>

<script src="https://meet.jit.si/external_api.js"></script>
<script>
// Modal handling
const modal = document.getElementById('endConsultModal');
document.getElementById('endConsultBtn').addEventListener('click', () => modal.classList.add('open'));
document.querySelectorAll('.modal-close').forEach(btn => btn.addEventListener('click', () => modal.classList.remove('open')));
modal.addEventListener('click', (e) => { if(e.target === modal) modal.classList.remove('open'); });

const consultationId = <?= $consultationId ?>;
const userId = <?= $userId ?>;
const currentUserName = 'Dr. <?= e(getCurrentUserName()) ?>';

<?php if ($appt['type'] === 'video' || $appt['type'] === 'voice'): ?>
const jitsiRoom = '<?= e($appt['jitsi_room']) ?>';
const domain = 'meet.jit.si';
const options = {
    roomName: jitsiRoom,
    parentNode: document.getElementById('jitsi-container'),
    userInfo: { displayName: currentUserName },
    configOverwrite: {
        startWithAudioMuted: false,
        startWithVideoMuted: <?= $appt['type'] === 'voice' ? 'true' : 'false' ?>
    },
    interfaceConfigOverwrite: {
        TOOLBAR_ALWAYS_VISIBLE: false,
        DISABLE_JOIN_LEAVE_NOTIFICATIONS: true
    }
};
const jitsiApi = new JitsiMeetExternalAPI(domain, options);
<?php endif; ?>

// Chat polling
const chatMessages = document.getElementById('chatMessages');
const messageForm = document.getElementById('messageForm');
const messageInput = document.getElementById('messageInput');
let lastMessageId = <?= !empty($messages) ? end($messages)['id'] : 0 ?>;

async function pollMessages() {
    try {
        const resp = await fetch('/api/get-messages?consultation_id=' + consultationId + '&last_id=' + lastMessageId);
        const data = await resp.json();
        if (data.success && data.messages) {
            data.messages.forEach(function(msg) {
                const div = document.createElement('div');
                div.className = 'message-bubble ' + (msg.sender_id === userId ? 'outgoing' : 'incoming');
                div.innerHTML = `<div class="bubble-body">${escapeHtml(msg.content)}<div class="bubble-time">${msg.time_ago}</div></div>`;
                chatMessages.appendChild(div);
                if (msg.id > lastMessageId) lastMessageId = msg.id;
            });
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    } catch (err) {
        console.warn('Poll failed:', err);
    }
}

setInterval(pollMessages, 3000);

messageForm.addEventListener('submit', async function(e) {
    e.preventDefault();
    const content = messageInput.value.trim();
    if (!content) return;

    messageInput.value = '';
    try {
        const resp = await fetch('/api/send-message', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'consultation_id=' + consultationId + '&content=' + encodeURIComponent(content) + '&sender_id=' + userId
        });
        const data = await resp.json();
        if (data.success) {
            pollMessages();
        }
    } catch (err) {
        console.warn('Send failed:', err);
    }
});

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

chatMessages.scrollTop = chatMessages.scrollHeight;
</script>
