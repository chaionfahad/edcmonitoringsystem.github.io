<?php
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: vendor/dashboard.php');
    }
} else {
    header('Location: login.php');
}
exit;
