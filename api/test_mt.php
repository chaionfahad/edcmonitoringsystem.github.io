<?php
/**
 * AJAX endpoint for testing MikroTik connection without page hang.
 */
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../includes/routeros_api.class.php';

header('Content-Type: application/json');

// Check session manually - return JSON error instead of HTML redirect
session_start();
if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Session expired. Please re-login.']);
    exit;
}

$ip = $_GET['mt_ip'] ?? '';
$port = (int)($_GET['mt_api_port'] ?? 8728);
$user = $_GET['mt_username'] ?? '';
$pass = $_GET['mt_password'] ?? '';

if (!$ip || !$user) {
    echo json_encode(['success' => false, 'error' => 'IP and Username are required']);
    exit;
}

$api = new RouterOSAPI($ip, $user, $pass, $port);

$start = microtime(true);
if ($api->connect()) {
    $elapsed = round((microtime(true) - $start) * 1000);
    $api->disconnect();
    echo json_encode(['success' => true, 'ms' => $elapsed]);
} else {
    echo json_encode(['success' => false, 'error' => $api->getError()]);
}
