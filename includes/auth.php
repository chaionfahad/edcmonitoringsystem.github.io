<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/lang.php';

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isSuperAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin' && !empty($_SESSION['is_super_admin']);
}

function isVendor() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'vendor';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        die("Access denied: Admin only.");
    }
}

function requireSuperAdmin() {
    requireLogin();
    if (!isSuperAdmin()) {
        die("Access denied: Super Admin only.");
    }
}

function requireVendor() {
    requireLogin();
    if (!isVendor()) {
        die("Access denied: Vendor only.");
    }
}

function login($username, $password) {
    $db = Database::getInstance();
    $user = $db->fetch("SELECT * FROM users WHERE username = ? AND status = 1", [$username]);

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['is_super_admin'] = !empty($user['is_super_admin']);
        return true;
    }
    return false;
}

function logout() {
    session_destroy();
    header('Location: login.php');
    exit;
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? 0;
}

function getCurrentRole() {
    return $_SESSION['role'] ?? '';
}
