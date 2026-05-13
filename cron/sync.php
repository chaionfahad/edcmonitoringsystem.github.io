<?php
/**
 * MikroTik Status Sync Cron Job
 *
 * Run this every minute via cron (Linux) or Task Scheduler (Windows):
 *   * * * * * php /path/to/cron/sync.php >/dev/null 2>&1
 *
 * This script:
 * 1. Loads MikroTik connection settings from the database
 * 2. Connects to the router
 * 3. Checks each institution's PPPoE status
 * 4. Updates the database and creates logs on status changes
 */

// Increase execution time for large deployments
set_time_limit(300);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/routeros_api.class.php';

$db = Database::getInstance();

// 1. Get settings
$settings = $db->fetch("SELECT * FROM settings WHERE id = 1");
if (!$settings) {
    echo "[ERROR] MikroTik settings not found.\n";
    exit(1);
}

echo "[" . date('Y-m-d H:i:s') . "] Starting sync...\n";

// 2. Connect to MikroTik
$api = new RouterOSAPI(
    $settings['mt_ip'],
    $settings['mt_username'],
    $settings['mt_password'],
    $settings['mt_api_port']
);

if (!$api->connect()) {
    echo "[ERROR] Cannot connect to MikroTik: " . $api->getError() . "\n";
    exit(1);
}

echo "[INFO] Connected to MikroTik at {$settings['mt_ip']}\n";

// 3. Get all institutions
$institutions = $db->fetchAll("SELECT * FROM institutions");
echo "[INFO] Checking " . count($institutions) . " institutions...\n";

$checked = 0;
$changed = 0;

foreach ($institutions as $inst) {
    // Query PPPoE active list for this user
    $isActive = $api->checkPPPoEUser($inst['pppoe_user']);

    $newStatus = $isActive ? 'online' : 'offline';
    $oldStatus = $inst['current_status'];

    // Update status in database
    $db->query(
        "UPDATE institutions SET current_status = ?, last_checked = NOW() WHERE id = ?",
        [$newStatus, $inst['id']]
    );

    // Log status transitions (skip initial 'unknown' to avoid flooding)
    if ($oldStatus !== $newStatus && $oldStatus !== 'unknown') {
        $db->query(
            "INSERT INTO logs (institution_id, status, timestamp) VALUES (?, ?, NOW())",
            [$inst['id'], $newStatus]
        );
        echo "[CHANGE] {$inst['name']}: {$oldStatus} -> {$newStatus}\n";
        $changed++;
    }

    $checked++;
}

// 4. Disconnect
$api->disconnect();

echo "[DONE] Checked {$checked} institutions, {$changed} status changes recorded.\n";
