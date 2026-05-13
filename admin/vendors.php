<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$db = Database::getInstance();
$error = '';

// Handle create / update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($action === 'create') {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $db->insert(
                "INSERT INTO users (username, password, full_name, email, role) VALUES (?, ?, ?, ?, 'vendor')",
                [$username, $hash, $fullName, $email]
            );
            $_SESSION['flash'] = __('vendor_created');
            header('Location: vendors.php');
            exit;
        } catch (Exception $e) {
            $error = __('username_exists');
        }
    } elseif ($action === 'update' && $id > 0) {
        if ($password) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $db->query(
                "UPDATE users SET username = ?, password = ?, full_name = ?, email = ? WHERE id = ? AND role = 'vendor'",
                [$username, $hash, $fullName, $email, $id]
            );
        } else {
            $db->query(
                "UPDATE users SET username = ?, full_name = ?, email = ? WHERE id = ? AND role = 'vendor'",
                [$username, $fullName, $email, $id]
            );
        }
        $_SESSION['flash'] = __('vendor_updated');
        header('Location: vendors.php');
        exit;
    } elseif ($action === 'toggle_status' && $id > 0) {
        $vendor = $db->fetch("SELECT status FROM users WHERE id = ? AND role = 'vendor'", [$id]);
        if ($vendor) {
            $newStatus = $vendor['status'] ? 0 : 1;
            $db->query("UPDATE users SET status = ? WHERE id = ?", [$newStatus, $id]);
            $_SESSION['flash'] = __('vendor_toggled');
            header('Location: vendors.php');
            exit;
        }
    } elseif ($action === 'delete' && $id > 0) {
        $db->query("DELETE FROM users WHERE id = ? AND role = 'vendor'", [$id]);
        $_SESSION['flash'] = 'Vendor deleted.';
        header('Location: vendors.php');
        exit;
    }
}

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

$vendors = $db->fetchAll(
    "SELECT u.*, (SELECT COUNT(*) FROM institutions WHERE vendor_id = u.id) as institution_count
     FROM users u WHERE u.role = 'vendor' ORDER BY u.full_name ASC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Vendors - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- <script src="../assets/js/custom-select.js?v=2" defer></script> -->
    <style>
        .form-group input {
            padding: 0.75rem 1rem 0.75rem 2.8rem;
            border: 2px solid #e5e7eb;
            border-radius: 14px;
            font-size: 0.95rem;
            background: #fff;
            color: #1f2937;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            width: 100%;
            box-sizing: border-box;
        }
        .form-group input:hover {
            border-color: #c7d2fe;
            box-shadow: 0 4px 16px rgba(37, 99, 235, 0.08);
        }
        .form-group input:focus {
            outline: none;
            transform: translateY(-1px);
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12), 0 8px 24px rgba(37, 99, 235, 0.1);
        }
        .form-group input::placeholder {
            color: #9ca3af;
        }
        .input-wrap {
            position: relative;
        }
        .input-icon {
            position: absolute;
            left: 0.85rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            pointer-events: none;
            transition: color 0.3s ease;
            z-index: 1;
        }
        .input-wrap:focus-within .input-icon {
            color: #2563eb;
        }
    </style>
</head>
<body data-theme="<?= currentTheme() ?>">
    <div class="layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="main-content">
            <div class="page-header">
                <h2><?= __('manage_vendors') ?></h2>
                <button onclick="openModal('vendorModal')" class="btn btn-primary"><?= __('new_vendor') ?></button>
            </div>

            <?php if ($flash): ?><div class="alert alert-success"><?= h($flash) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th><?= __('username') ?></th>
                                <th><?= __('full_name') ?></th>
                                <th><?= __('email') ?></th>
                                <th><?= __('institutions_count') ?></th>
                                <th><?= __('status') ?></th>
                                <th><?= __('actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vendors as $v): ?>
                            <tr>
                                <td><?= h($v['username']) ?></td>
                                <td><?= h($v['full_name']) ?></td>
                                <td><?= h($v['email'] ?: '--') ?></td>
                                <td><?= $v['institution_count'] ?></td>
                                <td>
                                    <span class="status-badge <?= $v['status'] ? 'status-active' : 'status-inactive' ?>">
                                        <?= $v['status'] ? __('active') : __('inactive') ?>
                                    </span>
                                </td>
                                <td class="action-cell" style="white-space:nowrap">
                                    <a href="institutions.php?vendor_id=<?= $v['id'] ?>" class="btn-action btn-view"><?= __('view_institutions') ?></a>
                                    <button onclick="editVendor(<?= $v['id'] ?>, '<?= h($v['username']) ?>', '<?= h($v['full_name']) ?>', '<?= h($v['email']) ?>')" class="btn-action btn-edit"><?= __('edit') ?></button>
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="id" value="<?= $v['id'] ?>">
                                        <button type="submit" class="btn-action btn-toggle" onclick="return confirm('Toggle status?')">
                                            <?= $v['status'] ? __('deactivate') : __('activate') ?>
                                        </button>
                                    </form>
                                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this vendor? All associated data will be removed.')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $v['id'] ?>">
                                        <button type="submit" class="btn-action btn-delete"><?= __('delete') ?></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($vendors)): ?>
                            <tr><td colspan="6" class="text-center"><?= __('no_vendors') ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Create Vendor Modal -->
    <div id="vendorModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal('vendorModal')">&times;</span>
            <h3 id="vendorModalTitle"><?= __('new_vendor') ?></h3>
            <form method="POST">
                <input type="hidden" name="action" id="vendorAction" value="create">
                <input type="hidden" name="id" id="vendorId" value="0">
                <div class="form-group">
                    <label><?= __('username') ?></label>
                    <div class="input-wrap">
                        <span class="input-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </span>
                        <input type="text" name="username" id="vUsername" required placeholder="<?= __('username') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label><?= __('full_name') ?></label>
                    <div class="input-wrap">
                        <span class="input-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </span>
                        <input type="text" name="full_name" id="vFullName" required placeholder="<?= __('full_name') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label><?= __('email') ?></label>
                    <div class="input-wrap">
                        <span class="input-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        </span>
                        <input type="email" name="email" id="vEmail" placeholder="<?= __('email') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label><?= __('password') ?> <small id="pwHint"><?= __('pw_required') ?></small></label>
                    <div class="input-wrap">
                        <span class="input-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </span>
                        <input type="password" name="password" id="vPassword" <?= isset($editMode) ? '' : 'required' ?> placeholder="••••••••">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary"><?= __('save') ?></button>
            </form>
        </div>
    </div>

    <script>
    function openModal(id) {
        document.getElementById(id).style.display = 'flex';
    }
    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }
    function editVendor(id, username, fullName, email) {
        document.getElementById('vendorAction').value = 'update';
        document.getElementById('vendorId').value = id;
        document.getElementById('vUsername').value = username;
        document.getElementById('vFullName').value = fullName;
        document.getElementById('vEmail').value = email;
        document.getElementById('vPassword').required = false;
        document.getElementById('pwHint').textContent = '<?= __('pw_keep') ?>';
        document.getElementById('vendorModalTitle').textContent = '<?= __('edit') ?>';
        openModal('vendorModal');
    }
    // Reset modal form on close
    document.querySelectorAll('.modal').forEach(m => {
        m.addEventListener('click', function(e) {
            if (e.target === this) this.style.display = 'none';
        });
    });
    </script>
</body>
</html>
