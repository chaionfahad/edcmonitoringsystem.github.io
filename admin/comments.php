<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$_SESSION['last_comment_view'] = date('Y-m-d H:i:s');

$db = Database::getInstance();

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

// Handle delete all via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_all'])) {
    $instId = (int)($_POST['institution_id'] ?? 0);
    if ($instId) {
        $db->query("DELETE FROM comments WHERE institution_id = ?", [$instId]);
        $_SESSION['flash'] = 'All comments deleted.';
        header('Location: comments.php');
        exit;
    }
}

$institutions = $db->fetchAll(
    "SELECT i.id, i.name, i.thana, i.union_name,
       (SELECT COUNT(*) FROM comments WHERE institution_id = i.id) as comment_count
     FROM institutions i
     HAVING comment_count > 0
     ORDER BY i.name"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comments - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- <script src="../assets/js/custom-select.js?v=2" defer></script> -->
    <style>
        @keyframes slideUp { from { opacity:0;transform:translateY(20px) } to { opacity:1;transform:translateY(0) } }
        .comment-item { padding: 1rem; border-radius: 12px; margin-bottom: 0.75rem; font-size: 0.9rem; }
        .comment-item.vendor { background: #eef2ff; }
        .comment-item.admin { background: #f0fdf4; margin-left: 1.5rem; }
        .comment-meta { display: flex; justify-content: space-between; margin-bottom: 0.35rem; font-size: 0.8rem; }
        .comment-role { font-weight: 700; }
        .comment-role.vendor { color: #2563eb; }
        .comment-role.admin { color: #16a34a; }
        .comment-time { color: #94a3b8; }
        .reply-input { display: flex; gap: 0.5rem; margin-top: 0.5rem; }
        .reply-input input { flex: 1; padding: 0.5rem 0.75rem; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 0.85rem; outline: none; }
        .reply-input input:focus { border-color: #2563eb; }
        .reply-input button { padding: 0.5rem 1rem; background: #2563eb; color: #fff; border: none; border-radius: 10px; cursor: pointer; font-weight: 600; font-size: 0.85rem; }
        .inst-header { cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
        .inst-header:hover { color: #2563eb; }
        .admin-tag { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 6px; font-size: 0.7rem; font-weight: 700; background: rgba(22,163,74,0.15); color: #16a34a; }
        .vendor-tag { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 6px; font-size: 0.7rem; font-weight: 700; background: rgba(37,99,235,0.15); color: #2563eb; }
    </style>
</head>
<body data-theme="<?= currentTheme() ?>">
    <div class="layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="main-content">
            <div class="page-header">
                <h2><?= __('Comments') ?></h2>
            </div>

            <?php if ($flash): ?><div class="alert alert-success"><?= h($flash) ?></div><?php endif; ?>

            <?php if (empty($institutions)): ?>
            <div class="card"><div class="card-body"><p style="text-align:center;color:#94a3b8">No comments from vendors yet.</p></div></div>
            <?php endif; ?>

            <?php foreach ($institutions as $inst): ?>
            <div class="card" style="animation:slideUp 0.4s ease-out">
                <div class="card-header">
                    <div><strong><?= h($inst['name']) ?></strong> <span style="color:#94a3b8;font-size:0.8rem">— <?= h($inst['thana'] ?: '') ?> <?= h($inst['union_name'] ?: '') ?></span></div>
                    <div style="display:flex;gap:0.5rem;align-items:center">
                        <span style="font-size:0.8rem;color:#94a3b8"><?= $inst['comment_count'] ?> comment<?= $inst['comment_count'] > 1 ? 's' : '' ?></span>
                        <form method="POST" onsubmit="return confirm('Delete all comments for this institution?')" style="display:inline">
                            <input type="hidden" name="delete_all" value="1">
                            <input type="hidden" name="institution_id" value="<?= $inst['id'] ?>">
                            <button type="submit" style="background:none;border:none;color:#dc2626;cursor:pointer;font-size:0.8rem;text-decoration:underline">Delete All</button>
                        </form>
                    </div>
                </div>
                <div class="card-body" id="comments-<?= $inst['id'] ?>">
                    <div style="text-align:center;color:#94a3b8;padding:0.5rem">Loading...</div>
                </div>
                <div class="card-body" style="border-top:1px solid #f1f5f9;padding-top:0.75rem">
                    <div class="reply-input">
                        <input type="text" id="replyInput-<?= $inst['id'] ?>" placeholder="Write a reply as Admin...">
                        <button onclick="replyAsAdmin(<?= $inst['id'] ?>)">Reply</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </main>
    </div>

    <script>
    function escHtml(str) {
        var d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    function loadComments(instId) {
        var container = document.getElementById('comments-' + instId);
        container.innerHTML = '<div style="text-align:center;color:#94a3b8;padding:0.5rem">Loading...</div>';
        fetch('../api/comments.php?action=list&institution_id=' + instId)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success || data.data.length === 0) {
                    container.innerHTML = '<div style="text-align:center;color:#94a3b8;padding:0.5rem;font-size:0.85rem">No comments</div>';
                    return;
                }
                container.innerHTML = '';
                data.data.forEach(function(c) {
                    var div = document.createElement('div');
                    var isAdmin = c.role === 'admin';
                    div.className = 'comment-item ' + (isAdmin ? 'admin' : 'vendor');
                    var roleLabel = isAdmin ? 'Admin <span class="admin-tag">Admin</span>' : 'Vendor <span class="vendor-tag">Vendor</span>';
                    div.innerHTML = '<div class="comment-meta"><span class="comment-role ' + (isAdmin ? 'admin' : 'vendor') + '">' + roleLabel + '</span><span class="comment-time">' + c.created_at + '</span></div><div>' + escHtml(c.message) + '</div>';
                    container.appendChild(div);
                });
            });
    }

    function replyAsAdmin(instId) {
        var input = document.getElementById('replyInput-' + instId);
        var msg = input.value.trim();
        if (!msg) return;
        var form = new FormData();
        form.append('action', 'add');
        form.append('institution_id', instId);
        form.append('message', msg);
        fetch('../api/comments.php', { method: 'POST', body: form })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    input.value = '';
                    loadComments(instId);
                }
            });
    }

    <?php foreach ($institutions as $inst): ?>
    loadComments(<?= $inst['id'] ?>);
    <?php endforeach; ?>
    </script>
</body>
</html>