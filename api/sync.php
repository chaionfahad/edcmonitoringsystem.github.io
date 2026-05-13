<?php
/**
 * AJAX endpoint for manual sync trigger from admin dashboard.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();
header('Content-Type: application/json');

$result = syncAllInstitutions();
echo json_encode($result);
