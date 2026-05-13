<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireVendor();

$vendorId = getCurrentUserId();
$counts = getStatusCounts($vendorId);

$db = Database::getInstance();
$institutions = $db->fetchAll(
    "SELECT * FROM institutions WHERE vendor_id = ? ORDER BY type, thana, union_name, current_status DESC, name ASC",
    [$vendorId]
);

$recentLogs = $db->fetchAll(
    "SELECT l.*, i.name as institution_name
     FROM logs l
     JOIN institutions i ON i.id = l.institution_id
     WHERE i.vendor_id = ?
     ORDER BY l.timestamp DESC LIMIT 10",
    [$vendorId]
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Dashboard - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/filter-dropdown.js?v=1" defer></script>
</head>
<body data-theme="<?= currentTheme() ?>">
    <div class="layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="main-content">
            <div class="page-header">
                <h2><?= __('vendor_dashboard') ?></h2>
                <div class="header-actions">
                    <span><?= __('welcome') ?>, <?= h($_SESSION['full_name']) ?></span>
                    <a href="../logout.php" class="btn btn-sm btn-outline"><?= __('logout') ?></a>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card stat-total">
                    <div class="stat-value"><?= $counts['total'] ?></div>
                    <div class="stat-label"><?= __('my_institutions') ?></div>
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

            <div class="card">
                <div class="card-header">
                    <h3><?= __('my_institutions') ?></h3>
                    <span id="filterCount" class="text-muted"></span>
                </div>
                <div class="card-body">
                    <div class="filter-bar">
                        <input type="text" id="searchInput" placeholder="<?= __('filter_search_vendor') ?>" class="form-control filter-input">
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
                                <th><?= __('last_checked') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($institutions as $inst): ?>
                            <tr class="inst-row" data-id="<?= $inst['id'] ?>"
                                data-name="<?= h(strtolower($inst['name'])) ?>"
                                data-pppoe="<?= h(strtolower($inst['pppoe_user'])) ?>"
                                data-type="<?= $inst['type'] ?>"
                                data-thana="<?= h(strtolower($inst['thana'] ?: '')) ?>"
                                data-union="<?= h(strtolower($inst['union_name'] ?: '')) ?>"
                                data-status="<?= $inst['current_status'] ?>">
                                <td>
                                    <span class="status-indicator status-<?= $inst['current_status'] ?> status-pulse-<?= $inst['current_status'] ?>"
                                          data-id="<?= $inst['id'] ?>">
                                        <?= ucfirst($inst['current_status']) ?>
                                    </span>
                                </td>
                                <td><a href="javascript:void(0)" onclick="showInstInfo(this)" data-name="<?= h($inst['name']) ?>" data-thana="<?= h($inst['thana'] ?? '') ?>" data-union="<?= h($inst['union_name'] ?? '') ?>" data-type="<?= ucfirst($inst['type']) ?>" data-status="<?= ucfirst($inst['current_status']) ?>" data-last="<?= $inst['last_checked'] ? date('M d, H:i:s', strtotime($inst['last_checked'])) : 'Never' ?>" style="color:var(--primary);text-decoration:none;font-weight:600;cursor:pointer"><?= h($inst['name']) ?></a></td>
                                <td><?= h($inst['thana'] ?: '--') ?></td>
                                <td><?= h($inst['union_name'] ?: '--') ?></td>
                                <td><?= $inst['last_checked'] ? date('M d, H:i:s', strtotime($inst['last_checked'])) : __('never') ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($institutions)): ?>
                            <tr><td colspan="5" class="text-center"><?= __('no_inst_assigned') ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><?= __('recent_activity') ?></h3>
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
                            <tr><td colspan="3" class="text-center"><?= __('no_activity') ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
    function escHtml(str) {
        var d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    function filterTable() {
        const q = document.getElementById('searchInput').value.toLowerCase();
        const status = document.getElementById('filterStatus').value;
        const rows = document.querySelectorAll('.inst-row');
        let visible = 0;

        rows.forEach(row => {
            const name = row.dataset.name;
            const pppoe = row.dataset.pppoe;
            const rowThana = row.dataset.thana;
            const rowUnion = row.dataset.union;
            const rowStatus = row.dataset.status;

            const matchSearch = !q || name.includes(q) || pppoe.includes(q) || rowThana.includes(q) || rowUnion.includes(q);
            const matchStatus = !status || rowStatus === status;

            if (matchSearch && matchStatus) {
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
        document.getElementById('filterStatus').value = '';
        document.getElementById('filterStatus').dispatchEvent(new Event('change'));
    }

    document.getElementById('searchInput').addEventListener('input', filterTable);
    document.getElementById('filterStatus').addEventListener('change', filterTable);

    filterTable();

    // Auto-refresh every 60 seconds
    setTimeout(() => location.reload(), 60000);

    function showInstInfo(el) {
        document.getElementById('infoName').textContent = el.dataset.name;
        document.getElementById('infoType').textContent = el.dataset.type;
        document.getElementById('infoThana').textContent = el.dataset.thana;
        document.getElementById('infoUnion').textContent = el.dataset.union;
        document.getElementById('infoStatus').textContent = el.dataset.status;
        document.getElementById('infoLast').textContent = el.dataset.last;
        var instId = el.closest('tr').querySelector('.status-indicator').dataset.id;
        document.getElementById('infoInstId').value = instId;
        document.getElementById('infoModal').style.display = 'flex';
        loadComments(instId);
        loadUptime(instId);
    }

    function loadUptime(instId) {
        var container = document.getElementById('uptimeContainer');
        var fill = document.getElementById('uptimeFill');
        var rest = document.getElementById('uptimeRest');
        var pct = document.getElementById('uptimePct');
        var counts = document.getElementById('uptimeCounts');
        container.style.display = 'none';
        fetch('../api/uptime.php?id=' + instId)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) return;
                container.style.display = 'block';
                fill.style.width = data.uptime_pct + '%';
                rest.style.width = (100 - data.uptime_pct) + '%';
                pct.textContent = data.uptime_pct + '% online';
                counts.textContent = data.online + ' online / ' + data.offline + ' offline';
            });
    }

    function closeInfoModal() {
        document.getElementById('infoModal').style.display = 'none';
    }

    function loadComments(instId) {
        var container = document.getElementById('commentsContainer');
        container.innerHTML = '<div style="text-align:center;color:#94a3b8;padding:1rem">Loading...</div>';
        fetch('../api/comments.php?action=list&institution_id=' + instId)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) { container.innerHTML = ''; return; }
                container.innerHTML = '';
                if (data.data.length === 0) {
                    container.innerHTML = '<div style="text-align:center;color:#94a3b8;padding:1rem;font-size:0.85rem">No comments yet</div>';
                    return;
                }
                data.data.forEach(function(c) {
                    var div = document.createElement('div');
                    div.style.cssText = 'padding:0.75rem;margin-bottom:0.5rem;border-radius:10px;background:' + (c.parent_id ? '#f8fafc' : '#eef2ff') + ';font-size:0.85rem';
                    var role = c.role === 'admin' ? 'Admin' : 'Vendor';
                    div.innerHTML = '<div style="display:flex;justify-content:space-between;margin-bottom:0.25rem"><strong>' + role + '</strong> <span style="color:#94a3b8;font-size:0.75rem">' + c.created_at + '</span></div><div>' + escHtml(c.message) + '</div>';
                    container.appendChild(div);
                });
            });
    }

    function addComment() {
        var instId = document.getElementById('infoInstId').value;
        var msg = document.getElementById('commentInput').value.trim();
        if (!msg) return;
        var form = new FormData();
        form.append('action', 'add');
        form.append('institution_id', instId);
        form.append('message', msg);
        fetch('../api/comments.php', { method: 'POST', body: form })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    document.getElementById('commentInput').value = '';
                    loadComments(instId);
                }
            });
    }
    </script>

    <!-- Institution Info Modal -->
    <div id="infoModal" class="modal" onclick="if(event.target===this)closeInfoModal()">
        <div class="modal-content" style="max-width:480px">
            <span class="modal-close" onclick="closeInfoModal()">&times;</span>
            <h3 id="infoName" style="margin-bottom:1.5rem;font-size:1.2rem;color:#1e293b"></h3>
            <input type="hidden" id="infoInstId">
            <table style="width:100%;border-collapse:collapse">
                <tr><td style="padding:0.6rem 0;color:#64748b;font-size:0.9rem;border-bottom:1px solid #f1f5f9">Type</td><td style="padding:0.6rem 0;font-weight:600;font-size:0.9rem;border-bottom:1px solid #f1f5f9;text-align:right"><span id="infoType" class="badge badge-govt"></span></td></tr>
                <tr><td style="padding:0.6rem 0;color:#64748b;font-size:0.9rem;border-bottom:1px solid #f1f5f9">Thana</td><td id="infoThana" style="padding:0.6rem 0;font-weight:600;font-size:0.9rem;border-bottom:1px solid #f1f5f9;text-align:right"></td></tr>
                <tr><td style="padding:0.6rem 0;color:#64748b;font-size:0.9rem;border-bottom:1px solid #f1f5f9">Union</td><td id="infoUnion" style="padding:0.6rem 0;font-weight:600;font-size:0.9rem;border-bottom:1px solid #f1f5f9;text-align:right"></td></tr>
                <tr><td style="padding:0.6rem 0;color:#64748b;font-size:0.9rem;border-bottom:1px solid #f1f5f9">Status</td><td id="infoStatus" style="padding:0.6rem 0;font-weight:600;font-size:0.9rem;border-bottom:1px solid #f1f5f9;text-align:right"></td></tr>
                <tr><td style="padding:0.6rem 0;color:#64748b;font-size:0.9rem">Last Checked</td><td id="infoLast" style="padding:0.6rem 0;font-weight:600;font-size:0.9rem;text-align:right"></td></tr>
            </table>
            <div id="uptimeContainer" style="margin-top:0.75rem;display:none">
                <div style="font-size:0.8rem;font-weight:600;color:#64748b;margin-bottom:0.4rem">Uptime</div>
                <div class="uptime-bar-track" style="height:8px">
                    <div class="uptime-bar-segment uptime-online" id="uptimeFill" style="width:0%"></div>
                    <div class="uptime-bar-segment uptime-offline" id="uptimeRest" style="width:0%"></div>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:0.75rem;color:#94a3b8;margin-top:0.3rem">
                    <span id="uptimePct">0% online</span>
                    <span id="uptimeCounts">0 total</span>
                </div>
            </div>
            <hr style="margin:1.25rem 0;border:none;border-top:1px solid #f1f5f9">
            <div style="font-size:0.95rem;font-weight:600;margin-bottom:0.75rem;color:#1e293b">Comments</div>
            <div id="commentsContainer" style="max-height:200px;overflow-y:auto;margin-bottom:0.75rem"></div>
            <div style="display:flex;gap:0.5rem">
                <input type="text" id="commentInput" placeholder="Write a comment..." style="flex:1;padding:0.4rem 0.6rem;border:2px solid #e5e7eb;border-radius:8px;font-size:0.8rem;outline:none">
                <button onclick="addComment()" style="padding:0.6rem 1rem;background:#2563eb;color:#fff;border:none;border-radius:10px;cursor:pointer;font-weight:600;font-size:0.85rem">Send</button>
            </div>
        </div>
    </div>
</body>
</html>
