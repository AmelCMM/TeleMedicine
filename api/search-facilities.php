<?php

if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');

$lat = (float)($_GET['lat'] ?? 0);
$lng = (float)($_GET['lng'] ?? 0);
$emergencyOnly = isset($_GET['emergency']) && $_GET['emergency'] === '1';

if (!$lat || !$lng) {
    // Return all facilities sorted by name
    $db = Database::getConnection();
    $query = "SELECT id, name, type, address, phone, latitude, longitude, is_emergency FROM facilities WHERE is_active = 1";
    if ($emergencyOnly) {
        $query .= ' AND is_emergency = 1';
    }
    $query .= ' ORDER BY name LIMIT 50';

    $stmt = $db->query($query);
    echo json_encode(['success' => true, 'facilities' => $stmt->fetchAll()]);
    exit;
}

$db = Database::getConnection();

// Haversine formula for distance calculation
$query = "
    SELECT id, name, type, address, phone, latitude, longitude, is_emergency,
           (6371 * acos(cos(radians(?)) * cos(radians(latitude))
           * cos(radians(longitude) - radians(?)) + sin(radians(?))
           * sin(radians(latitude)))) AS distance
    FROM facilities
    WHERE is_active = 1
";

$params = [$lat, $lng, $lat];

if ($emergencyOnly) {
    $query .= ' AND is_emergency = 1';
}

$query .= ' HAVING distance < 100 ORDER BY distance LIMIT 20';

try {
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $facilities = $stmt->fetchAll();

    foreach ($facilities as &$facility) {
        $facility['distance'] = round($facility['distance'], 1);
    }

    echo json_encode(['success' => true, 'facilities' => $facilities]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to search facilities']);
}
