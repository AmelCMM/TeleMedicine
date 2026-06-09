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

$notificationId = (int)($_POST['notification_id'] ?? 0);
$userId = (int)$_SESSION['user_id'];

if (!$notificationId) {
    echo json_encode(['success' => false, 'message' => 'Notification ID required']);
    exit;
}

$db = Database::getConnection();

try {
    $stmt = $db->prepare("
        UPDATE notifications SET is_read = 1
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$notificationId, $userId]);

    echo json_encode(['success' => true, 'message' => 'Notification marked as read']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to mark notification']);
}
