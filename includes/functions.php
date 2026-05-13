<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/routeros_api.class.php';

/**
 * Get MikroTik settings from database
 */
function getMikrotikSettings() {
    $db = Database::getInstance();
    return $db->fetch("SELECT * FROM settings WHERE id = 1");
}

/**
 * Check institution status via MikroTik
 */
function checkInstitutionStatus($pppoeUser, $ipAddress = null) {
    $settings = getMikrotikSettings();
    if (!$settings) {
        return ['online' => false, 'error' => 'MikroTik not configured'];
    }

    $api = new RouterOSAPI(
        $settings['mt_ip'],
        $settings['mt_username'],
        $settings['mt_password'],
        $settings['mt_api_port']
    );

    if (!$api->connect()) {
        return ['online' => false, 'error' => $api->getError()];
    }

    // Check via PPPoE Active list
    $active = $api->checkPPPoEUser($pppoeUser);

    $api->disconnect();

    return ['online' => $active, 'error' => null];
}

/**
 * Check all institutions and update their status
 */
function syncAllInstitutions() {
    $db = Database::getInstance();
    $institutions = $db->fetchAll("SELECT * FROM institutions");

    $settings = getMikrotikSettings();
    if (!$settings) {
        return ['success' => false, 'error' => 'MikroTik not configured'];
    }

    $api = new RouterOSAPI(
        $settings['mt_ip'],
        $settings['mt_username'],
        $settings['mt_password'],
        $settings['mt_api_port']
    );

    if (!$api->connect()) {
        return ['success' => false, 'error' => $api->getError()];
    }

    $results = [];

    foreach ($institutions as $inst) {
        $online = $api->checkPPPoEUser($inst['pppoe_user']);
        $newStatus = $online ? 'online' : 'offline';
        $oldStatus = $inst['current_status'];

        // Update institution status
        $db->query(
            "UPDATE institutions SET current_status = ?, last_checked = NOW() WHERE id = ?",
            [$newStatus, $inst['id']]
        );

        // Log if status changed (only log online->offline and offline->online transitions)
        if ($oldStatus !== $newStatus && $oldStatus !== 'unknown') {
            $db->query(
                "INSERT INTO logs (institution_id, status, timestamp) VALUES (?, ?, NOW())",
                [$inst['id'], $newStatus]
            );
        }

        $results[] = [
            'id' => $inst['id'],
            'name' => $inst['name'],
            'status' => $newStatus,
            'changed' => ($oldStatus !== $newStatus)
        ];
    }

    $api->disconnect();

    return ['success' => true, 'results' => $results];
}

/**
 * Get status counts for dashboard
 */
function getStatusCounts($vendorId = null) {
    $db = Database::getInstance();

    if ($vendorId) {
        $online = $db->fetch(
            "SELECT COUNT(*) as count FROM institutions WHERE vendor_id = ? AND current_status = 'online'",
            [$vendorId]
        )['count'];
        $offline = $db->fetch(
            "SELECT COUNT(*) as count FROM institutions WHERE vendor_id = ? AND current_status = 'offline'",
            [$vendorId]
        )['count'];
        $total = $db->fetch(
            "SELECT COUNT(*) as count FROM institutions WHERE vendor_id = ?",
            [$vendorId]
        )['count'];
    } else {
        $online = $db->fetch("SELECT COUNT(*) as count FROM institutions WHERE current_status = 'online'")['count'];
        $offline = $db->fetch("SELECT COUNT(*) as count FROM institutions WHERE current_status = 'offline'")['count'];
        $total = $db->fetch("SELECT COUNT(*) as count FROM institutions")['count'];
    }

    return [
        'total' => $total,
        'online' => $online,
        'offline' => $offline,
    ];
}

/**
 * Get recent logs
 */
function getRecentLogs($limit = 20, $institutionId = null) {
    $db = Database::getInstance();

    if ($institutionId) {
        return $db->fetchAll(
            "SELECT l.*, i.name as institution_name
             FROM logs l
             JOIN institutions i ON i.id = l.institution_id
             WHERE l.institution_id = ?
             ORDER BY l.timestamp DESC
             LIMIT ?",
            [$institutionId, $limit]
        );
    }

    return $db->fetchAll(
        "SELECT l.*, i.name as institution_name
         FROM logs l
         JOIN institutions i ON i.id = l.institution_id
         ORDER BY l.timestamp DESC
         LIMIT ?",
        [$limit]
    );
}

/**
 * Safely escape output
 */
function h($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}
