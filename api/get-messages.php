<?php

if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$consultationId = (int)($_GET['consultation_id'] ?? 0);
$lastId = (int)($_GET['last_id'] ?? 0);

if (!$consultationId) {
    echo json_encode(['success' => false, 'message' => 'Consultation ID required']);
    exit;
}

$db = Database::getConnection();

try {
    $stmt = $db->prepare("
        SELECT m.id, m.content, m.type, m.file_path, m.sent_at, m.sender_id, u.name AS sender_name
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.consultation_id = ? AND m.id > ?
        ORDER BY m.sent_at ASC
    ");
    $stmt->execute([$consultationId, $lastId]);
    $messages = $stmt->fetchAll();

    foreach ($messages as &$msg) {
        $msg['time_ago'] = timeAgo($msg['sent_at']);
    }

    echo json_encode(['success' => true, 'messages' => $messages]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to fetch messages']);
}
