<?php
/**
 * Bulk status check endpoint.
 * Returns all institution statuses for the current user as JSON.
 * Used by the dashboard for real-time updates.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
header('Content-Type: application/json');

$db = Database::getInstance();

if (isAdmin()) {
    $institutions = $db->fetchAll(
        "SELECT i.id, i.name, i.current_status, i.last_checked, u.full_name as vendor_name
         FROM institutions i
         JOIN users u ON u.id = i.vendor_id
         ORDER BY i.name"
    );
} else {
    $institutions = $db->fetchAll(
        "SELECT id, name, current_status, last_checked
         FROM institutions
         WHERE vendor_id = ?
         ORDER BY name",
        [getCurrentUserId()]
    );
}

echo json_encode(['success' => true, 'data' => $institutions]);
