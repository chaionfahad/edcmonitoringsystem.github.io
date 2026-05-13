<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireSuperAdmin();

$db = Database::getInstance();

$error = '';
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'vendor';

    if ($action === 'create') {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            if ($role === 'admin') {
                $db->insert(
                    "INSERT INTO users (username, password, full_name, email, role, is_super_admin) VALUES (?, ?, ?, ?, 'admin', 0)",
                    [$username, $hash, $fullName, $email]
                );
                $_SESSION['flash'] = 'Admin created successfully.';
            } else {
                $db->insert(
                    "INSERT INTO users (username, password, full_name, email, role) VALUES (?, ?, ?, ?, 'vendor')",
                    [$username, $hash, $fullName, $email]
                );
                $_SESSION['flash'] = 'Vendor created successfully.';
            }
            header('Location: admins.php');
            exit;
        } catch (Exception $e) {
            $error = 'Username already exists.';
        }
    } elseif ($action === 'delete' && $id > 0) {
        if ($id == getCurrentUserId()) {
            $error = 'You cannot delete yourself.';
        } else {
            $db->query("DELETE FROM users WHERE id = ?", [$id]);
            $_SESSION['flash'] = 'User deleted.';
            header('Location: admins.php');
            exit;
        }
    }
}

$users = $db->fetchAll("SELECT id, username, full_name, email, role, is_super_admin, status FROM users ORDER BY FIELD(role,'admin','vendor'), is_super_admin DESC, full_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- <script src="../assets/js/custom-select.js?v=2" defer></script> -->
</head>
<body data-theme="<?= currentTheme() ?>">
    <div class="layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="main-content">
            <div class="page-header">
                <h2>Manage Users</h2>
                <button onclick="openModal('userModal')" class="btn btn-primary">+ New User</button>
            </div>

            <?php if ($flash): ?><div class="alert alert-success"><?= h($flash) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?= h($u['username']) ?></td>
                                <td><?= h($u['full_name']) ?></td>
                                <td><?= h($u['email'] ?: '--') ?></td>
                                <td><span class="badge badge-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
                                <td><span class="status-indicator status-<?= $u['status'] ? 'online' : 'offline' ?>"><?= $u['status'] ? 'Active' : 'Inactive' ?></span></td>
                                <td>
                                    <?php if ($u['is_super_admin'] && $u['id'] == getCurrentUserId()): ?>
                                        <span class="action-label">You</span>
                                    <?php elseif ($u['role'] === 'vendor'): ?>
                                        <span class="action-label">—</span>
                                    <?php elseif (!$u['is_super_admin']): ?>
                                        <form method="POST" style="display:inline" onsubmit="return confirm('Delete this <?= $u['role'] ?>?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Create User Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content" style="max-width:460px">
            <span class="modal-close" onclick="closeModal('userModal')">&times;</span>
            <h3>New User</h3>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="form-group">
                    <label>Account Type</label>
                    <select name="role" class="form-control" required>
                        <option value="admin">Admin</option>
                        <option value="vendor">Vendor</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary">Create User</button>
            </form>
        </div>
    </div>

    <script>
    function openModal(id) { document.getElementById(id).style.display = 'flex'; }
    function closeModal(id) { document.getElementById(id).style.display = 'none'; }
    document.querySelectorAll('.modal').forEach(function(m) {
        m.addEventListener('click', function(e) { if (e.target === this) this.style.display = 'none'; });
    });
    </script>
</body>
</html>