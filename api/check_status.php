<?php
/**
 * Real-time AJAX endpoint for checking a single institution's status.
 * Returns JSON with current online/offline status.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    echo json_encode(['success' => false, 'error' => 'No institution ID provided']);
    exit;
}

$db = Database::getInstance();
$inst = $db->fetch("SELECT * FROM institutions WHERE id = ?", [$id]);

if (!$inst) {
    echo json_encode(['success' => false, 'error' => 'Institution not found']);
    exit;
}

// Vendor users can only check their own institutions
if (isVendor() && $inst['vendor_id'] !== getCurrentUserId()) {
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

$result = checkInstitutionStatus($inst['pppoe_user'], $inst['ip_address']);
$newStatus = $result['online'] ? 'online' : 'offline';

// Update database
$oldStatus = $inst['current_status'];
$db->query(
    "UPDATE institutions SET current_status = ?, last_checked = NOW() WHERE id = ?",
    [$newStatus, $id]
);

// Log if changed
if ($oldStatus !== $newStatus && $oldStatus !== 'unknown') {
    $db->query(
        "INSERT INTO logs (institution_id, status, timestamp) VALUES (?, ?, NOW())",
        [$id, $newStatus]
    );
}

echo json_encode([
    'success' => true,
    'id' => $id,
    'name' => $inst['name'],
    'status' => $newStatus,
    'changed' => ($oldStatus !== $newStatus),
    'error' => $result['error'],
]);
