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

$userId = (int)$_SESSION['user_id'];
$db = Database::getConnection();

try {
    $stmt = $db->prepare("
        SELECT id, title, message, type, is_read, created_at
        FROM notifications
        WHERE user_id = ? AND is_read = 0
        ORDER BY created_at DESC
        LIMIT 20
    ");
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll();

    echo json_encode(['success' => true, 'notifications' => $notifications, 'count' => count($notifications)]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to fetch notifications']);
}
