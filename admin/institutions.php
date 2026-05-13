<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$db = Database::getInstance();
$error = '';

// Add new columns if not exist (one-time migration)
try { $db->query("ALTER TABLE institutions ADD COLUMN IF NOT EXISTS head_name varchar(200) DEFAULT NULL AFTER name"); } catch (Exception $e) {}
try { $db->query("ALTER TABLE institutions ADD COLUMN IF NOT EXISTS mobile varchar(11) DEFAULT NULL AFTER head_name"); } catch (Exception $e) {}
try { $db->query("ALTER TABLE institutions ADD COLUMN IF NOT EXISTS address text DEFAULT NULL AFTER mobile"); } catch (Exception $e) {}
try { $db->query("CREATE TABLE IF NOT EXISTS comments (id int(11) NOT NULL AUTO_INCREMENT, institution_id int(11) NOT NULL, user_id int(11) NOT NULL, parent_id int(11) DEFAULT NULL, message text NOT NULL, created_at timestamp NOT NULL DEFAULT current_timestamp(), PRIMARY KEY (id), KEY institution_id (institution_id), KEY user_id (user_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch (Exception $e) {}
try { $db->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_super_admin tinyint(1) NOT NULL DEFAULT 0 AFTER role"); } catch (Exception $e) {}
// Set first admin as super admin if none exists
try {
    $hasSuper = $db->fetch("SELECT id FROM users WHERE is_super_admin = 1");
    if (!$hasSuper) {
        $firstAdmin = $db->fetch("SELECT id FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1");
        if ($firstAdmin) {
            $db->query("UPDATE users SET is_super_admin = 1 WHERE id = ?", [$firstAdmin['id']]);
        }
    }
} catch (Exception $e) {}

$filterVendor = isset($_GET['vendor_id']) ? (int)$_GET['vendor_id'] : 0;

// Handle CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $headName = trim($_POST['head_name'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $pppoeUser = trim($_POST['pppoe_user'] ?? '');
    $ipAddress = trim($_POST['ip_address'] ?? '');
    $type = $_POST['type'] ?? 'others';
    $thana = trim($_POST['thana'] ?? '');
    $unionName = trim($_POST['union_name'] ?? '');
    $vendorId = (int)($_POST['vendor_id'] ?? 0);

    if ($action === 'create') {
        $db->insert(
            "INSERT INTO institutions (name, head_name, mobile, pppoe_user, ip_address, type, thana, union_name, vendor_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$name, $headName ?: null, $mobile ?: null, $pppoeUser, $ipAddress ?: null, $type, $thana ?: null, $unionName ?: null, $vendorId]
        );
        $_SESSION['flash'] = __('institution_created');
        header('Location: institutions.php' . ($filterVendor ? '?vendor_id=' . $filterVendor : ''));
        exit;
    } elseif ($action === 'update' && $id > 0) {
        $db->query(
            "UPDATE institutions SET name = ?, head_name = ?, mobile = ?, pppoe_user = ?, ip_address = ?, type = ?, thana = ?, union_name = ?, vendor_id = ? WHERE id = ?",
            [$name, $headName ?: null, $mobile ?: null, $pppoeUser, $ipAddress ?: null, $type, $thana ?: null, $unionName ?: null, $vendorId, $id]
        );
        $_SESSION['flash'] = __('institution_updated');
        header('Location: institutions.php' . ($filterVendor ? '?vendor_id=' . $filterVendor : ''));
        exit;
    } elseif ($action === 'delete' && $id > 0) {
        $db->query("DELETE FROM institutions WHERE id = ?", [$id]);
        $_SESSION['flash'] = __('institution_deleted');
        header('Location: institutions.php' . ($filterVendor ? '?vendor_id=' . $filterVendor : ''));
        exit;
    } elseif ($action === 'move' && $id > 0) {
        $newVendor = (int)($_POST['new_vendor_id'] ?? 0);
        if ($newVendor) {
            $db->query("UPDATE institutions SET vendor_id = ? WHERE id = ?", [$newVendor, $id]);
            $_SESSION['flash'] = 'Institution moved to new vendor.';
            header('Location: institutions.php' . ($filterVendor ? '?vendor_id=' . $filterVendor : ''));
            exit;
        }
    } elseif ($action === 'sync_one' && $id > 0) {
        $inst = $db->fetch("SELECT * FROM institutions WHERE id = ?", [$id]);
        if ($inst) {
            $result = checkInstitutionStatus($inst['pppoe_user'], $inst['ip_address']);
            $newStatus = $result['online'] ? 'online' : 'offline';
            $oldStatus = $inst['current_status'];
            $db->query("UPDATE institutions SET current_status = ?, last_checked = NOW() WHERE id = ?", [$newStatus, $id]);
            if ($oldStatus !== $newStatus && $oldStatus !== 'unknown') {
                $db->query("INSERT INTO logs (institution_id, status, timestamp) VALUES (?, ?, NOW())", [$id, $newStatus]);
            }
            $_SESSION['flash'] = "Checked: {$inst['name']} is " . strtoupper($newStatus);
            header('Location: institutions.php' . ($filterVendor ? '?vendor_id=' . $filterVendor : ''));
            exit;
        }
    }
}

// Show flash message
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

$vendors = $db->fetchAll("SELECT id, full_name FROM users WHERE role = 'vendor' AND status = 1 ORDER BY full_name");

if ($filterVendor) {
            $institutions = $db->fetchAll(
                "SELECT i.*, u.full_name as vendor_name FROM institutions i JOIN users u ON u.id = i.vendor_id WHERE i.vendor_id = ? ORDER BY i.type, i.name",
                [$filterVendor]
            );
        } else {
            $institutions = $db->fetchAll(
                "SELECT i.*, u.full_name as vendor_name FROM institutions i JOIN users u ON u.id = i.vendor_id ORDER BY i.type, i.thana, i.union_name, i.name"
            );
        }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Institutions - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/filter-dropdown.js?v=1" defer></script>
    <style>
        .form-group input {
            padding: 0.55rem 0.75rem 0.55rem 2.4rem;
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
        .form-group select {
            padding: 0.55rem 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 14px;
            font-size: 0.95rem;
            background: #fff;
            color: #1f2937;
            width: 100%;
            box-sizing: border-box;
        }
        .form-group select:focus {
            outline: none;
            border-color: #2563eb;
        }
        .form-group input::placeholder {
            color: #9ca3af;
        }
        .input-wrap {
            position: relative;
        }
        .input-icon {
            position: absolute;
            left: 0.7rem;
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
                <h2><?= __('manage_institutions') ?></h2>
                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
                    <div class="c-dropdown-filter">
                        <input type="hidden" id="vendorFilterValue" value="">
                        <button type="button" class="c-dropdown-filter-trigger" id="vendorFilterTrigger"><?= __('all_vendors') ?></button>
                        <div class="c-dropdown-filter-menu" id="vendorFilterMenu">
                            <div class="c-dropdown-filter-item" data-url="institutions.php"><?= __('all_vendors') ?></div>
                            <?php foreach ($vendors as $v): ?>
                            <div class="c-dropdown-filter-item" data-url="institutions.php?vendor_id=<?= $v['id'] ?>"><?= h($v['full_name']) ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <button onclick="openModal('instModal')" class="btn btn-primary" style="height:42px;border-radius:8px;background:#8b5cf6;border:none;font-weight:600;display:inline-flex;align-items:center;white-space:nowrap"><?= __('new_institution') ?></button>
                </div>
            </div>

            <?php if ($flash): ?><div class="alert alert-success"><?= h($flash) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th><?= __('status') ?></th>
                                <th><?= __('name') ?></th>
                                <th><?= __('thana') ?></th>
                                <th><?= __('union') ?></th>
                                <th><?= __('pppoe_user') ?></th>
                                <th><?= __('ip_address') ?></th>
                                <th><?= __('vendor') ?></th>
                                <th><?= __('last_checked') ?></th>
                                <th><?= __('actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($institutions as $inst): ?>
                            <tr>
                                <td>
                                    <span class="status-indicator status-<?= $inst['current_status'] ?>"
                                          data-id="<?= $inst['id'] ?>">
                                        <?= ucfirst($inst['current_status']) ?>
                                    </span>
                                </td>
                                <td><?= h($inst['name']) ?></td>
                                <td><?= h($inst['thana'] ?: '--') ?></td>
                                <td><?= h($inst['union_name'] ?: '--') ?></td>
                                <td><?= h($inst['pppoe_user']) ?></td>
                                <td><?= h($inst['ip_address'] ?: '--') ?></td>
                                <td><?= h($inst['vendor_name']) ?></td>
                                <td><?= $inst['last_checked'] ? date('M d, H:i:s', strtotime($inst['last_checked'])) : __('never') ?></td>
                                <td class="action-cell" style="white-space:nowrap">
                                    <button onclick="editInst(this)" class="btn-action btn-edit" data-id="<?= $inst['id'] ?>" data-name="<?= h($inst['name']) ?>" data-head="<?= h($inst['head_name'] ?? '') ?>" data-mobile="<?= h($inst['mobile'] ?? '') ?>" data-pppoe="<?= h($inst['pppoe_user']) ?>" data-ip="<?= h($inst['ip_address'] ?? '') ?>" data-type="<?= $inst['type'] ?>" data-thana="<?= h($inst['thana'] ?? '') ?>" data-union="<?= h($inst['union_name'] ?? '') ?>" data-vendor="<?= $inst['vendor_id'] ?>"><?= __('edit') ?></button>
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="action" value="sync_one">
                                        <input type="hidden" name="id" value="<?= $inst['id'] ?>">
                                        <button type="submit" class="btn-action btn-check"><?= __('check') ?></button>
                                    </form>
                                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this institution?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $inst['id'] ?>">
                                        <button type="submit" class="btn-action btn-delete"><?= __('delete') ?></button>
                                    </form>
                                    <button onclick="openMoveModal(<?= $inst['id'] ?>, <?= $inst['vendor_id'] ?>)" class="btn-action btn-move">Move</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($institutions)): ?>
                            <tr><td colspan="9" class="text-center"><?= __('no_institutions') ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Institution Modal -->
    <div id="instModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal('instModal')">&times;</span>
            <h3 id="instModalTitle"><?= __('new_institution') ?></h3>
            <form method="POST">
                <input type="hidden" name="action" id="instAction" value="create">
                <input type="hidden" name="id" id="instId" value="0">
                <div class="form-group">
                    <label><?= __('institution_name') ?></label>
                    <div class="input-wrap">
                        <span class="input-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        </span>
                        <input type="text" name="name" id="instName" required placeholder="<?= __('institution_name') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label><?= __('Head Of this Institutions') ?></label>
                    <div class="input-wrap">
                        <span class="input-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </span>
                        <input type="text" name="head_name" id="instHead" placeholder="Head Of this Institutions">
                    </div>
                </div>
                <div class="form-group">
                    <label><?= __('Mobile Number 11 Digit') ?></label>
                    <div class="input-wrap">
                        <span class="input-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                        </span>
                        <input type="tel" name="mobile" id="instMobile" pattern="[0-9]{11}" maxlength="11" placeholder="01XXXXXXXXX">
                    </div>
                </div>
                <div class="form-group">
                    <label><?= __('pppoe_user') ?></label>
                    <div class="input-wrap">
                        <span class="input-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </span>
                        <input type="text" name="pppoe_user" id="instPppoe" required placeholder="<?= __('pppoe_user') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label><?= __('ip_address') ?> (optional)</label>
                    <div class="input-wrap">
                        <span class="input-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        </span>
                        <input type="text" name="ip_address" id="instIp" placeholder="e.g. 10.0.0.5">
                    </div>
                </div>
                <div class="form-group">
                    <label><?= __('thana') ?></label>
                    <div class="input-wrap">
                        <span class="input-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        </span>
                        <input type="text" name="thana" id="instThana" placeholder="e.g. Sadar, Kaliganj, Tala" list="thanaList">
                        <datalist id="thanaList">
                            <option value="Sadar">
                            <option value="Kaliganj">
                            <option value="Tala">
                            <option value="Assasuni">
                            <option value="Debhata">
                            <option value="Shyamnagar">
                            <option value="Kolaroa">
                        </datalist>
                    </div>
                </div>
                <div class="form-group">
                    <label><?= __('union') ?></label>
                    <div class="input-wrap">
                        <span class="input-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </span>
                        <input type="text" name="union_name" id="instUnion" placeholder="e.g. Brahmarajpur, Raruli" list="unionList">
                        <datalist id="unionList">
                            <option value="Brahmarajpur">
                            <option value="Raruli">
                            <option value="Kushadanga">
                            <option value="Labsha">
                            <option value="Gopalpur">
                            <option value="Jhaudanga">
                        </datalist>
                    </div>
                </div>
                <div class="form-group">
                    <label><?= __('assigned_vendor') ?></label>
                    <div class="c-dropdown" data-field="vendor_id">
                        <input type="hidden" name="vendor_id" id="instVendor" value="">
                        <button type="button" class="c-dropdown-trigger" id="instVendorTrigger"><?= __('select_vendor') ?></button>
                        <div class="c-dropdown-menu" id="instVendorMenu">
                            <div class="c-dropdown-item" data-value=""><?= __('select_vendor') ?></div>
                            <?php foreach ($vendors as $v): ?>
                            <div class="c-dropdown-item" data-value="<?= $v['id'] ?>"><?= h($v['full_name']) ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary"><?= __('save') ?></button>
            </form>
        </div>
    </div>

    <script>
    // ── Custom Dropdown Init ──
    function initCustomDropdown(container) {
        var trigger = container.querySelector('.c-dropdown-trigger');
        var menu = container.querySelector('.c-dropdown-menu');
        var hidden = container.querySelector('input[type="hidden"]');
        var items = container.querySelectorAll('.c-dropdown-item');

        trigger.addEventListener('click', function(e) {
            e.stopPropagation();
            closeAllDropdowns();
            menu.classList.toggle('open');
            trigger.classList.toggle('open');
        });

        items.forEach(function(item) {
            item.addEventListener('click', function(e) {
                e.stopPropagation();
                var val = this.dataset.value;
                var text = this.textContent;
                hidden.value = val;
                trigger.textContent = text;
                items.forEach(function(o) { o.classList.remove('selected', 'active'); });
                this.classList.add('selected');
                menu.classList.remove('open');
                trigger.classList.remove('open');
                hidden.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
    }

    function closeAllDropdowns() {
        document.querySelectorAll('.c-dropdown-menu.open, .c-dropdown-filter-menu.open').forEach(function(m) {
            m.classList.remove('open');
        });
        document.querySelectorAll('.c-dropdown-trigger.open, .c-dropdown-filter-trigger.open').forEach(function(t) {
            t.classList.remove('open');
        });
    }

    document.addEventListener('click', closeAllDropdowns);

    // Init all custom dropdowns
    document.querySelectorAll('.c-dropdown').forEach(initCustomDropdown);

    // ── Filter Dropdown (All Vendors) ──
    (function() {
        var trigger = document.getElementById('vendorFilterTrigger');
        var menu = document.getElementById('vendorFilterMenu');
        var items = menu.querySelectorAll('.c-dropdown-filter-item');

        trigger.addEventListener('click', function(e) {
            e.stopPropagation();
            closeAllDropdowns();
            menu.classList.toggle('open');
            trigger.classList.toggle('open');
        });

        items.forEach(function(item) {
            item.addEventListener('click', function(e) {
                e.stopPropagation();
                var url = this.dataset.url;
                trigger.textContent = this.textContent;
                menu.classList.remove('open');
                trigger.classList.remove('open');
                window.location.href = url;
            });
        });
    })();

    // ── Modal Functions ──
    function openModal(id) {
        document.getElementById(id).style.display = 'flex';
    }
    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }
    function editInst(btn) {
        document.getElementById('instAction').value = 'update';
        document.getElementById('instId').value = btn.dataset.id;
        document.getElementById('instName').value = btn.dataset.name;
        document.getElementById('instHead').value = btn.dataset.head;
        document.getElementById('instMobile').value = btn.dataset.mobile;
        document.getElementById('instPppoe').value = btn.dataset.pppoe;
        document.getElementById('instIp').value = btn.dataset.ip;
        document.getElementById('instThana').value = btn.dataset.thana;
        document.getElementById('instUnion').value = btn.dataset.union;
        var v = document.getElementById('instVendor');
        v.value = btn.dataset.vendor;
        var t = document.getElementById('instVendorTrigger');
        var sel = document.querySelector('#instVendorMenu .c-dropdown-item[data-value="' + btn.dataset.vendor + '"]');
        if (sel) t.textContent = sel.textContent;
        document.getElementById('instModalTitle').textContent = '<?= __('edit') ?>';
        openModal('instModal');
    }
    function openMoveModal(id, currentVendor) {
        document.getElementById('moveInstId').value = id;
        document.getElementById('moveVendor').value = currentVendor;
        document.getElementById('moveModal').style.display = 'flex';
    }

    document.querySelectorAll('.modal').forEach(function(m) {
        m.addEventListener('click', function(e) {
            if (e.target === this) this.style.display = 'none';
        });
    });
    </script>

    <!-- Move Institution Modal -->
    <div id="moveModal" class="modal" onclick="if(event.target===this)this.style.display='none'">
        <div class="modal-content" style="max-width:400px">
            <span class="modal-close" onclick="document.getElementById('moveModal').style.display='none'">&times;</span>
            <h3 style="margin-bottom:1.25rem">Move Institution</h3>
            <form method="POST">
                <input type="hidden" name="action" value="move">
                <input type="hidden" name="id" id="moveInstId">
                <div class="form-group">
                    <label>Select New Vendor</label>
                    <select name="new_vendor_id" id="moveVendor" class="form-select" required>
                        <option value=""><?= __('select_vendor') ?></option>
                        <?php foreach ($vendors as $v): ?>
                        <option value="<?= $v['id'] ?>"><?= h($v['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Move</button>
            </form>
        </div>
    </div>
</body>
</html>
