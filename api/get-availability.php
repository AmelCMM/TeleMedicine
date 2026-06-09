<?php

if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');

$doctorId = (int)($_GET['doctor_id'] ?? 0);
$date = $_GET['date'] ?? '';

if (!$doctorId || empty($date)) {
    echo json_encode(['success' => false, 'message' => 'Doctor ID and date required']);
    exit;
}

// Validate date
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format']);
    exit;
}

$dayOfWeek = (int)date('w', strtotime($date));

$db = Database::getConnection();

try {
    // Get doctor's availability for this day of week
    $stmt = $db->prepare("
        SELECT start_time, end_time
        FROM doctor_availability
        WHERE doctor_id = ? AND day_of_week = ?
        ORDER BY start_time
    ");
    $stmt->execute([$doctorId, $dayOfWeek]);
    $slots = $stmt->fetchAll();

    if (empty($slots)) {
        echo json_encode(['success' => true, 'slots' => [], 'message' => 'Doctor is not available on this day']);
        exit;
    }

    // Get already booked appointments for this date
    $stmt = $db->prepare("
        SELECT scheduled_at
        FROM appointments
        WHERE doctor_id = ? AND DATE(scheduled_at) = ?
        AND status IN ('confirmed', 'in_progress', 'pending')
    ");
    $stmt->execute([$doctorId, $date]);
    $bookedAppts = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Generate time slots (30-minute intervals)
    $availableSlots = [];
    foreach ($slots as $slot) {
        $start = strtotime($slot['start_time']);
        $end = strtotime($slot['end_time']);

        while ($start < $end) {
            $timeStr = date('H:i:s', $start);
            $slotDateTime = $date . ' ' . $timeStr;

            // Check if slot is free
            $isBooked = false;
            foreach ($bookedAppts as $booked) {
                $bookedTime = date('H:i:s', strtotime($booked));
                $bookedEnd = date('H:i:s', strtotime($booked) + 1800);
                if ($timeStr >= $bookedTime && $timeStr < $bookedEnd) {
                    $isBooked = true;
                    break;
                }
            }

            if (!$isBooked) {
                // Don't show past slots for today
                if ($date !== date('Y-m-d') || $start > time()) {
                    $availableSlots[] = [
                        'time' => $timeStr,
                        'display' => date('g:i A', $start),
                    ];
                }
            }

            $start += 1800; // 30 minutes
        }
    }

    echo json_encode(['success' => true, 'slots' => $availableSlots]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to fetch availability']);
}
