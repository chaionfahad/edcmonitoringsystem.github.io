<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$db = Database::getInstance();
$counts = getStatusCounts();
$recentLogs = getRecentLogs(10);
$institutions = $db->fetchAll(
    "SELECT i.*, u.full_name as vendor_name
     FROM institutions i
     JOIN users u ON u.id = i.vendor_id
     ORDER BY i.type, i.thana, i.union_name, i.current_status DESC, i.name ASC"
);
$thanas = $db->fetchAll("SELECT DISTINCT thana FROM institutions WHERE thana IS NOT NULL AND thana != '' ORDER BY thana");

$vendorStats = $db->fetchAll(
    "SELECT u.id, u.full_name,
        COUNT(i.id) as total,
        SUM(i.current_status = 'online') as online,
        SUM(i.current_status = 'offline') as offline
     FROM users u
     JOIN institutions i ON i.vendor_id = u.id
     WHERE u.role = 'vendor'
     GROUP BY u.id, u.full_name
     ORDER BY u.full_name"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/filter-dropdown.js?v=1" defer></script>
</head>
<body data-theme="<?= currentTheme() ?>">
    <div class="layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="main-content">
            <div class="page-header">
                <h2><?= __('admin_dashboard') ?></h2>
                <div class="header-actions">
                    <span class="last-checked"><?= __('last_checked') ?>: <span id="lastCheckTime">--</span></span>
                    <button onclick="manualSync()" class="btn btn-sm btn-outline"><?= __('sync_now') ?></button>
                    <a href="../logout.php" class="btn btn-sm btn-outline"><?= __('logout') ?></a>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card stat-total">
                    <div class="stat-value"><?= $counts['total'] ?></div>
                    <div class="stat-label"><?= __('total_institutions') ?></div>
                </div>
                <div class="stat-card stat-online">
                    <div class="stat-value"><?= $counts['online'] ?></div>
                    <div class="stat-label"><?= __('online') ?></div>
                </div>
                <div class="stat-card stat-offline">
                    <div class="stat-value"><?= $counts['offline'] ?></div>
                    <div class="stat-label"><?= __('offline') ?></div>
                </div>
            </div>

            <?php $onlinePct = $counts['total'] > 0 ? round($counts['online'] / $counts['total'] * 100) : 0; ?>
            <?php $offlinePct = $counts['total'] > 0 ? round($counts['offline'] / $counts['total'] * 100) : 0; ?>
            <div class="uptime-bar">
                <div class="uptime-bar-track">
                    <div class="uptime-bar-segment uptime-online" style="width:<?= $onlinePct ?>%"></div>
                    <div class="uptime-bar-segment uptime-offline" style="width:<?= $offlinePct ?>%"></div>
                </div>
                <div class="uptime-labels">
                    <span><span class="uptime-dot dot-online"></span> <?= $onlinePct ?>% <?= __('online') ?></span>
                    <span><span class="uptime-dot dot-offline"></span> <?= $offlinePct ?>% <?= __('offline') ?></span>
                </div>
            </div>

            <div class="vendor-stats-grid">
                <?php if (!empty($vendorStats)): ?>
                <?php foreach ($vendorStats as $vs):
                    $vpct = $vs['total'] > 0 ? round($vs['online'] / $vs['total'] * 100) : 0;
                ?>
                <a href="institutions.php?vendor_id=<?= $vs['id'] ?>" class="vendor-stat-card" style="text-decoration:none;color:inherit;display:block">
                    <div class="vendor-stat-name"><?= h($vs['full_name']) ?></div>
                    <div class="vendor-stat-bar">
                        <div class="vendor-stat-fill" style="width:<?= $vpct ?>%"></div>
                    </div>
                    <div class="vendor-stat-pct"><?= $vpct ?>%</div>
                    <div class="vendor-stat-nums"><?= $vs['online'] ?>/<?= $vs['total'] ?></div>
                </a>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><?= __('all_institutions') ?></h3>
                    <span id="filterCount" class="text-muted"></span>
                </div>
                <div class="card-body">
                    <div class="filter-bar">
                        <input type="text" id="searchInput" placeholder="<?= __('filter_search') ?>" class="form-control filter-input">
                        <div class="filter-dropdown">
                            <select id="filterThana">
                                <option value=""><?= __('thana') ?></option>
                                <?php foreach ($thanas as $t): ?>
                                <option value="<?= h($t['thana']) ?>"><?= h($t['thana']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-dropdown">
                            <select id="filterStatus">
                                <option value=""><?= __('status') ?></option>
                                <option value="online"><?= __('online') ?></option>
                                <option value="offline"><?= __('offline') ?></option>
                                <option value="unknown"><?= __('unknown') ?></option>
                            </select>
                        </div>
                        <button onclick="clearFilters()" class="btn btn-sm btn-outline"><?= __('clear') ?></button>
                    </div>

                    <table class="table" id="institutionTable">
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
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($institutions as $inst): ?>
                            <tr class="inst-row"
                                data-name="<?= h(strtolower($inst['name'])) ?>"
                                data-pppoe="<?= h(strtolower($inst['pppoe_user'])) ?>"
                                data-vendor="<?= h(strtolower($inst['vendor_name'])) ?>"
                                data-type="<?= $inst['type'] ?>"
                                data-thana="<?= h(strtolower($inst['thana'] ?: '')) ?>"
                                data-union="<?= h(strtolower($inst['union_name'] ?: '')) ?>"
                                data-status="<?= $inst['current_status'] ?>">
                                <td>
                                    <span class="status-indicator status-<?= $inst['current_status'] ?>"
                                          data-id="<?= $inst['id'] ?>">
                                        <?= ucfirst($inst['current_status']) ?>
                                    </span>
                                </td>
                                <td class="inst-name"><?= h($inst['name']) ?></td>
                                <td class="inst-thana"><?= h($inst['thana'] ?: '--') ?></td>
                                <td class="inst-union"><?= h($inst['union_name'] ?: '--') ?></td>
                                <td class="inst-pppoe"><?= h($inst['pppoe_user']) ?></td>
                                <td><?= h($inst['ip_address'] ?: '--') ?></td>
                                <td class="inst-vendor"><?= h($inst['vendor_name']) ?></td>
                                <td><?= $inst['last_checked'] ? date('M d, H:i:s', strtotime($inst['last_checked'])) : __('never') ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($institutions)): ?>
                            <tr><td colspan="8" class="text-center"><?= __('no_institutions') ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><?= __('recent_activity') ?></h3>
                    <a href="logs.php" class="btn btn-sm btn-outline"><?= __('view_all') ?></a>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th><?= __('institution') ?></th>
                                <th><?= __('status') ?></th>
                                <th><?= __('timestamp') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentLogs as $log): ?>
                            <tr>
                                <td><?= h($log['institution_name']) ?></td>
                                <td>
                                    <span class="status-indicator status-<?= $log['status'] ?>">
                                        <?= ucfirst($log['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y H:i:s', strtotime($log['timestamp'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recentLogs)): ?>
                            <tr><td colspan="3" class="text-center"><?= __('no_logs_yet') ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
    function manualSync() {
        const btn = document.querySelector('.header-actions .btn-outline');
        btn.textContent = '<?= __('syncing') ?>';
        btn.disabled = true;
        fetch('../api/sync.php')
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('lastCheckTime').textContent = new Date().toLocaleTimeString();
                    location.reload();
                } else {
                    alert('Sync failed: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(err => alert('Request failed: ' + err.message))
            .finally(() => {
                btn.textContent = '<?= __('sync_now') ?>';
                btn.disabled = false;
            });
    }

    function filterTable() {
        const q = document.getElementById('searchInput').value.toLowerCase();
        const thana = document.getElementById('filterThana').value.toLowerCase();
        const status = document.getElementById('filterStatus').value;
        const rows = document.querySelectorAll('.inst-row');
        let visible = 0;

        rows.forEach(row => {
            const name = row.dataset.name;
            const pppoe = row.dataset.pppoe;
            const vendor = row.dataset.vendor;
            const rowThana = row.dataset.thana;
            const rowUnion = row.dataset.union;
            const rowStatus = row.dataset.status;

            const matchSearch = !q || name.includes(q) || pppoe.includes(q) || vendor.includes(q) || rowUnion.includes(q);
            const matchThana = !thana || rowThana === thana;
            const matchStatus = !status || rowStatus === status;

            if (matchSearch && matchThana && matchStatus) {
                row.style.display = '';
                visible++;
            } else {
                row.style.display = 'none';
            }
        });

        document.getElementById('filterCount').textContent = visible + ' of ' + rows.length + ' shown';
    }

    function clearFilters() {
        document.getElementById('searchInput').value = '';
        document.getElementById('filterThana').value = '';
        document.getElementById('filterThana').dispatchEvent(new Event('change'));
        document.getElementById('filterStatus').value = '';
        document.getElementById('filterStatus').dispatchEvent(new Event('change'));
    }

    document.getElementById('searchInput').addEventListener('input', filterTable);
    document.getElementById('filterThana').addEventListener('change', filterTable);
    document.getElementById('filterStatus').addEventListener('change', filterTable);

    filterTable();
    document.getElementById('lastCheckTime').textContent = new Date().toLocaleTimeString();

    // Auto-refresh status every 60 seconds (read-only, no router sync)
    setInterval(function() {
        fetch('../api/status_bulk.php')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    document.getElementById('lastCheckTime').textContent = new Date().toLocaleTimeString();
                    // Update status indicators without full page reload
                    data.data.forEach(function(inst) {
                        var cells = document.querySelectorAll('.status-indicator[data-id="' + inst.id + '"]');
                        cells.forEach(function(cell) {
                            cell.className = 'status-indicator status-' + inst.current_status;
                            cell.textContent = inst.current_status.charAt(0).toUpperCase() + inst.current_status.slice(1);
                        });
                    });
                }
            })
            .catch(function() {});
    }, 60000);
    </script>
</body>
</html>
