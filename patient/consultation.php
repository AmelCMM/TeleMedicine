<?php

require_once dirname(__DIR__) . '/includes/auth_guard.php';
requireRole(ROLE_PATIENT);

$userId = getCurrentUserId();
$patientId = getPatientId($userId);
$apptId = (int)($_GET['appt'] ?? 0);

if (!$patientId || !$apptId) {
    setFlashMessage('error', 'Invalid request.');
    redirect('/patient/dashboard');
}

$db = Database::getConnection();

// Verify this appointment belongs to this patient
$stmt = $db->prepare("
    SELECT a.id, a.status, a.type, a.jitsi_room, a.scheduled_at,
           u.name AS doctor_name, d.specialization, d.id AS doctor_id
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    JOIN users u ON d.user_id = u.id
    WHERE a.id = ? AND a.patient_id = ?
");
$stmt->execute([$apptId, $patientId]);
$appt = $stmt->fetch();

if (!$appt) {
    setFlashMessage('error', 'Appointment not found.');
    redirect('/patient/dashboard');
}

// Get or create consultation
$stmt = $db->prepare('SELECT id FROM consultations WHERE appointment_id = ?');
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
    $consultationId = (int)$consultation['id'];
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
                    <p class="empty-state-text" style="color:rgba(255,255,255,0.6);">Use the panel on the right to message Dr. <?= e($appt['doctor_name']) ?>.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="consultation-chat">
        <div class="chat-header">
            <span>Dr. <?= e($appt['doctor_name']) ?></span>
            <span class="tag"><?= e($appt['specialization']) ?></span>
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

<script src="https://meet.jit.si/external_api.js"></script>
<script>
const consultationId = <?= $consultationId ?>;
const userId = <?= $userId ?>;
const currentUserName = '<?= e(getCurrentUserName()) ?>';

<?php if ($appt['type'] === 'video' || $appt['type'] === 'voice'): ?>
const jitsiRoom = '<?= e($appt['jitsi_room']) ?>';
const domain = 'meet.jit.si';
const options = {
    roomName: jitsiRoom,
    parentNode: document.getElementById('jitsi-container'),
    userInfo: { displayName: currentUserName },
    configOverwrite: {
        startWithAudioMuted: <?= $appt['type'] === 'video' ? 'false' : 'false' ?>,
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

// Scroll to bottom on load
chatMessages.scrollTop = chatMessages.scrollHeight;
</script>
