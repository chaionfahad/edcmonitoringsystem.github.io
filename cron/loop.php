<?php
/**
 * Continuous loop script for Windows environments
 * where cron isn't available.
 *
 * Run this in the background:
 *   start /B php loop.php
 *
 * It will run the sync every N seconds (configurable in settings).
 */

echo "[INFO] Continuous sync loop started. Press Ctrl+C to stop.\n";

while (true) {
    // Run the sync
    ob_start();
    include __DIR__ . '/sync.php';
    $output = ob_get_clean();
    echo $output;

    // Get interval from DB
    try {
        require_once __DIR__ . '/../includes/db.php';
        $db = Database::getInstance();
        $settings = $db->fetch("SELECT check_interval FROM settings WHERE id = 1");
        $interval = $settings ? (int)$settings['check_interval'] : 60;
    } catch (Exception $e) {
        $interval = 60;
    }

    echo "[INFO] Next check in {$interval} seconds.\n";
    sleep($interval);
}
