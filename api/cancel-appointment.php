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

if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
    exit;
}

$appointmentId = (int)($_POST['appointment_id'] ?? 0);
$userId = (int)$_SESSION['user_id'];
$role = $_SESSION['role'] ?? '';

if (!$appointmentId) {
    echo json_encode(['success' => false, 'message' => 'Appointment ID required']);
    exit;
}

$db = Database::getConnection();

try {
    // Get appointment details
    if ($role === ROLE_PATIENT) {
        $patientId = getPatientId($userId);
        $stmt = $db->prepare("
            SELECT a.id, a.scheduled_at, a.status, a.doctor_id
            FROM appointments a
            WHERE a.id = ? AND a.patient_id = ?
        ");
        $stmt->execute([$appointmentId, $patientId]);
    } elseif ($role === ROLE_DOCTOR) {
        $doctorId = getDoctorId($userId);
        $stmt = $db->prepare("
            SELECT a.id, a.scheduled_at, a.status
            FROM appointments a
            WHERE a.id = ? AND a.doctor_id = ?
        ");
        $stmt->execute([$appointmentId, $doctorId]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Unauthorized role']);
        exit;
    }

    $appt = $stmt->fetch();

    if (!$appt) {
        echo json_encode(['success' => false, 'message' => 'Appointment not found']);
        exit;
    }

    // Check cancellation window (patients only)
    if ($role === ROLE_PATIENT) {
        $scheduledTime = strtotime($appt['scheduled_at']);
        $hoursUntil = ($scheduledTime - time()) / 3600;

        if ($hoursUntil < CANCEL_WINDOW_HOURS) {
            echo json_encode([
                'success' => false,
                'message' => 'Cannot cancel within ' . CANCEL_WINDOW_HOURS . ' hours of the appointment.'
            ]);
            exit;
        }
    }

    // Cancel the appointment
    $stmt = $db->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ?");
    $stmt->execute([$appointmentId]);

    // Notify the other party
    if ($role === ROLE_PATIENT && isset($appt['doctor_id'])) {
        $stmt = $db->prepare("SELECT user_id FROM doctors WHERE id = ?");
        $stmt->execute([$appt['doctor_id']]);
        $docUser = $stmt->fetch();
        if ($docUser) {
            createNotification(
                $docUser['user_id'],
                'Appointment Cancelled',
                'A patient has cancelled their appointment scheduled for ' . date('M j, Y g:i A', strtotime($appt['scheduled_at'])),
                NOTIF_APPOINTMENT
            );
        }
    }

    echo json_encode(['success' => true, 'message' => 'Appointment cancelled successfully']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to cancel appointment']);
}
