<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$db = Database::getInstance();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mtIp = trim($_POST['mt_ip'] ?? '');
    $mtPort = (int)($_POST['mt_api_port'] ?? 8728);
    $mtUser = trim($_POST['mt_username'] ?? '');
    $mtPass = trim($_POST['mt_password'] ?? '');
    $interval = (int)($_POST['check_interval'] ?? 60);

    if ($mtIp && $mtUser) {
        $db->query(
            "UPDATE settings SET mt_ip = ?, mt_api_port = ?, mt_username = ?, mt_password = ?, check_interval = ? WHERE id = 1",
            [$mtIp, $mtPort, $mtUser, $mtPass, $interval]
        );
        $message = __('settings_saved');
    } else {
        $error = __('ip_user_required');
    }
}

$settings = getMikrotikSettings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- <script src="../assets/js/custom-select.js?v=2" defer></script> -->
    <style>
        .btn-group { display: flex; gap: 0.75rem; flex-wrap: wrap; }

        .code-block {
            border-radius: 12px !important;
            background: #1e293b !important;
            border: 1px solid rgba(255,255,255,0.08) !important;
        }

        .alert { border-radius: 12px; }
    </style>
</head>
<body data-theme="<?= currentTheme() ?>">
    <div class="layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="main-content">
            <div class="page-header">
                <h2><?= __('mt_settings') ?></h2>
            </div>

            <?php if ($message): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>

            <div id="statusBadge" class="status-badge">
                <span class="status-icon"></span>
                <span class="status-text"></span>
            </div>

            <div class="card settings-card">
                <div class="card-header">
                    <h3><?= __('mt_connection') ?></h3>
                </div>
                <div class="card-body">
                    <form method="POST" id="settingsForm" style="max-width:420px">
                        <div class="form-group">
                            <label class="form-label"><?= __('router_ip') ?></label>
                            <input type="text" name="mt_ip" value="<?= h($settings['mt_ip']) ?>" class="form-control" required placeholder="e.g. 192.168.88.1">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?= __('api_port') ?></label>
                            <input type="number" name="mt_api_port" value="<?= h($settings['mt_api_port']) ?>" class="form-control" placeholder="8728">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?= __('api_username') ?></label>
                            <input type="text" name="mt_username" value="<?= h($settings['mt_username']) ?>" class="form-control" required>
                            <small>Create an API user in MikroTik: /user add name=api_user group=read password=xxx</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?= __('api_password') ?></label>
                            <input type="password" name="mt_password" value="<?= h($settings['mt_password']) ?>" class="form-control" required placeholder="••••••••">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?= __('check_interval') ?></label>
                            <input type="number" name="check_interval" value="<?= h($settings['check_interval']) ?>" class="form-control" min="10" max="3600">
                            <small>How often the cron job checks institution status (default: 60s)</small>
                        </div>
                        <div class="btn-group" style="margin-top:1.5rem">
                            <button type="submit" class="btn-action btn-view"><?= __('save') ?></button>
                            <button type="button" onclick="testConnection()" class="btn-action btn-edit" id="testBtn">
                                <?= __('test_connection') ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </main>
    </div>

    <script>
    function showStatus(success, msg) {
        const badge = document.getElementById('statusBadge');
        badge.className = 'status-badge show ' + (success ? 'success' : 'error');
        badge.querySelector('.status-icon').textContent = success ? '✓' : '✕';
        badge.querySelector('.status-text').textContent = msg;
        if (success) {
            setTimeout(() => { badge.className = 'status-badge'; }, 4000);
        }
    }

    function testConnection() {
        const btn = document.getElementById('testBtn');
        btn.classList.add('loading');

        const form = document.getElementById('settingsForm');
        const data = new FormData(form);
        const params = new URLSearchParams();
        for (const [key, val] of data) {
            if (key !== 'action') params.append(key, val);
        }

        showStatus(false, 'Connecting to ' + document.querySelector('input[name="mt_ip"]').value + '...');

        const API_TEST_URL = '<?= rtrim((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['SCRIPT_NAME']))) ?>/api/test_mt.php';

        fetch(API_TEST_URL + '?' + params.toString(), { credentials: 'same-origin' })
            .then(r => r.json())
            .then(data => {
                showStatus(data.success, data.success
                    ? '<?= __('connection_ok') ?>'
                    : '<?= __('connection_fail') ?>: ' + (data.error || '<?= __('unknown') ?>'));
            })
            .catch(err => {
                showStatus(false, 'Request failed: ' + err.message);
            })
            .finally(() => {
                btn.classList.remove('loading');
            });
    }
    </script>
</body>
</html>