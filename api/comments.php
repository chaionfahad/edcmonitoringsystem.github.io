<?php
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// GET comments for an institution
if ($action === 'list' && isset($_GET['institution_id'])) {
    $instId = (int)$_GET['institution_id'];
    $db = Database::getInstance();
    $comments = $db->fetchAll(
        "SELECT c.*, u.full_name, u.role FROM comments c JOIN users u ON u.id = c.user_id WHERE c.institution_id = ? ORDER BY c.created_at ASC",
        [$instId]
    );
    echo json_encode(['success' => true, 'data' => $comments]);
    exit;
}

// ADD a comment
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $instId = (int)($_POST['institution_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

    if (!$instId || !$message) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }

    $db = Database::getInstance();
    $db->insert(
        "INSERT INTO comments (institution_id, user_id, parent_id, message) VALUES (?, ?, ?, ?)",
        [$instId, getCurrentUserId(), $parentId, $message]
    );

    echo json_encode(['success' => true]);
    exit;
}

// DELETE all comments for an institution (admin only)
if ($action === 'delete_all' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isAdmin()) {
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit;
    }
    $instId = (int)($_POST['institution_id'] ?? 0);
    if (!$instId) {
        echo json_encode(['success' => false, 'error' => 'Missing institution_id']);
        exit;
    }
    $db = Database::getInstance();
    $db->query("DELETE FROM comments WHERE institution_id = ?", [$instId]);
    echo json_encode(['success' => true]);
    exit;
}

// GET unread comment count (admin)
if ($action === 'unread_count') {
    if (!isAdmin()) {
        echo json_encode(['success' => false, 'count' => 0]);
        exit;
    }
    $db = Database::getInstance();
    $count = $db->fetch("SELECT COUNT(*) as c FROM comments WHERE parent_id IS NULL")['c'];
    echo json_encode(['success' => true, 'count' => $count]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
