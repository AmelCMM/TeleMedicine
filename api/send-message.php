<?php

if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$consultationId = (int)($_POST['consultation_id'] ?? 0);
$content = trim($_POST['content'] ?? '');
$senderId = (int)($_POST['sender_id'] ?? 0);

if (!$consultationId || empty($content) || !$senderId) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Verify sender matches session
if ($senderId !== (int)$_SESSION['user_id']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Sender mismatch']);
    exit;
}

$db = Database::getConnection();

try {
    $stmt = $db->prepare("
        INSERT INTO messages (consultation_id, sender_id, content)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$consultationId, $senderId, $content]);

    echo json_encode(['success' => true, 'message' => 'Message sent']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to send message']);
}
