<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    echo json_encode(['success' => false, 'error' => 'No ID']);
    exit;
}

$db = Database::getInstance();
$inst = $db->fetch("SELECT * FROM institutions WHERE id = ?", [$id]);
if (!$inst) {
    echo json_encode(['success' => false, 'error' => 'Not found']);
    exit;
}

if (isVendor() && $inst['vendor_id'] !== getCurrentUserId()) {
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

// Get log counts for last 30 days
$total = $db->fetch("SELECT COUNT(*) as c FROM logs WHERE institution_id = ?", [$id])['c'];
$online = $db->fetch("SELECT COUNT(*) as c FROM logs WHERE institution_id = ? AND status = 'online'", [$id])['c'];
$offline = $db->fetch("SELECT COUNT(*) as c FROM logs WHERE institution_id = ? AND status = 'offline'", [$id])['c'];

// Get last 30 entries for recent trend
$recent = $db->fetchAll(
    "SELECT status, timestamp FROM logs WHERE institution_id = ? ORDER BY timestamp DESC LIMIT 30",
    [$id]
);

echo json_encode([
    'success' => true,
    'total_logs' => $total,
    'online' => $online,
    'offline' => $offline,
    'uptime_pct' => $total > 0 ? round($online / $total * 100) : 0,
    'recent' => array_reverse($recent),
]);
