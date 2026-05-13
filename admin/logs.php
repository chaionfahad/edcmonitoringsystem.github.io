<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$db = Database::getInstance();
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Optional filter by institution
$filterInst = isset($_GET['institution_id']) ? (int)$_GET['institution_id'] : 0;

if ($filterInst) {
    $logs = $db->fetchAll(
        "SELECT l.*, i.name as institution_name
         FROM logs l
         JOIN institutions i ON i.id = l.institution_id
         WHERE l.institution_id = ?
         ORDER BY l.timestamp DESC LIMIT ? OFFSET ?",
        [$filterInst, $limit, $offset]
    );
    $total = $db->fetch("SELECT COUNT(*) as c FROM logs WHERE institution_id = ?", [$filterInst])['c'];
} else {
    $logs = $db->fetchAll(
        "SELECT l.*, i.name as institution_name
         FROM logs l
         JOIN institutions i ON i.id = l.institution_id
         ORDER BY l.timestamp DESC LIMIT ? OFFSET ?",
        [$limit, $offset]
    );
    $total = $db->fetch("SELECT COUNT(*) as c FROM logs")['c'];
}

$totalPages = ceil($total / $limit);
$institutions = $db->fetchAll("SELECT id, name FROM institutions ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/filter-dropdown.js?v=1" defer></script>
</head>
<body data-theme="<?= currentTheme() ?>">
    <div class="layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="main-content">
            <div class="page-header">
                <h2><?= __('activity_logs') ?></h2>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><?= __('status_change_history') ?></h3>
                    <div>
                        <div class="filter-dropdown" style="display:inline-block;min-width:180px">
                            <select onchange="location.href=this.value">
                                <option value="logs.php"><?= __('all_institutions') ?></option>
                                <?php foreach ($institutions as $inst): ?>
                                <option value="logs.php?institution_id=<?= $inst['id'] ?>" <?= $filterInst === $inst['id'] ? 'selected' : '' ?>>
                                    <?= h($inst['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th><?= __('institution') ?></th>
                                <th><?= __('status') ?></th>
                                <th><?= __('timestamp') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?= $log['id'] ?></td>
                                <td><?= h($log['institution_name']) ?></td>
                                <td>
                                    <span class="status-indicator status-<?= $log['status'] ?>">
                                        <?= ucfirst($log['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y H:i:s', strtotime($log['timestamp'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($logs)): ?>
                            <tr><td colspan="4" class="text-center"><?= __('no_logs') ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="logs.php?page=<?= $i ?><?= $filterInst ? '&institution_id=' . $filterInst : '' ?>"
                           class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
