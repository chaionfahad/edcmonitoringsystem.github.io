<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$unreadCount = 0;
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    try {
        require_once __DIR__ . '/db.php';
        $db = Database::getInstance();
        if (!isset($_SESSION['last_comment_view'])) {
            $_SESSION['last_comment_view'] = date('Y-m-d H:i:s');
        }
        $since = $_SESSION['last_comment_view'];
        $unreadCount = (int)$db->fetch("SELECT COUNT(*) as c FROM comments WHERE parent_id IS NULL AND created_at > ?", [$since])['c'];
    } catch (Exception $e) {}
}
?>
<aside class="sidebar" id="mainSidebar">
    <div class="sidebar-brand" style="display:flex;align-items:center;justify-content:space-between">
        <h2 style="white-space:nowrap;background:var(--sidebar-brand);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text"><?= APP_NAME ?></h2>
        <button class="sidebar-close" onclick="toggleSidebar()">&times;</button>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <a href="../admin/comments.php" style="position:relative;display:flex;align-items:center;justify-content:center;width:32px;height:32px;background:var(--sidebar-hover-bg);border-radius:50%;text-decoration:none;flex-shrink:0">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--sidebar-text)"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            <?php if ($unreadCount > 0): ?>
            <span style="position:absolute;top:-4px;right:-4px;background:#dc2626;color:#fff;font-size:0.65rem;font-weight:700;width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 6px rgba(220,38,38,0.4)"><?= $unreadCount > 99 ? '99+' : $unreadCount ?></span>
            <?php endif; ?>
        </a>
        <?php endif; ?>
    </div>
    <div class="sidebar-user">
        <strong><?= h($_SESSION['full_name'] ?? '') ?></strong>
        <small><?= ucfirst($_SESSION['role'] ?? '') ?></small>
    </div>
    <nav class="sidebar-nav">
        <?php if (isAdmin()): ?>
        <a href="../admin/dashboard.php" class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
            <?= __('dashboard') ?>
        </a>
        <a href="../admin/vendors.php" class="nav-link <?= $currentPage === 'vendors.php' ? 'active' : '' ?>">
            <?= __('nav_vendors') ?>
        </a>
        <a href="../admin/institutions.php" class="nav-link <?= $currentPage === 'institutions.php' ? 'active' : '' ?>">
            <?= __('nav_institutions') ?>
        </a>
        <a href="../admin/logs.php" class="nav-link <?= $currentPage === 'logs.php' ? 'active' : '' ?>">
            <?= __('nav_logs') ?>
        </a>
        <a href="../admin/settings.php" class="nav-link <?= $currentPage === 'settings.php' ? 'active' : '' ?>">
            <?= __('nav_mt_settings') ?>
        </a>
        <a href="../admin/comments.php" class="nav-link <?= $currentPage === 'comments.php' ? 'active' : '' ?>">
            Comments <?= $unreadCount > 0 ? '<span style="background:#dc2626;color:#fff;padding:1px 7px;border-radius:10px;font-size:0.7rem;margin-left:4px">'.$unreadCount.'</span>' : '' ?>
        </a>
        <?php if (isSuperAdmin()): ?>
        <a href="../admin/admins.php" class="nav-link <?= $currentPage === 'admins.php' ? 'active' : '' ?>">
            Admins
        </a>
        <?php endif; ?>
        <?php elseif (isVendor()): ?>
        <a href="../vendor/dashboard.php" class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
            <?= __('dashboard') ?>
        </a>
        <?php endif; ?>
        <a href="../logout.php" class="nav-link logout-link"><?= __('logout') ?></a>
        <div class="sidebar-section-label"><?= __('theme') ?></div>
        <div class="theme-slider">
            <a href="?theme=light" class="ts-option <?= currentTheme()=='light'?'active':'' ?>" data-theme="light">Light</a>
            <a href="?theme=dark" class="ts-option <?= currentTheme()=='dark'?'active':'' ?>" data-theme="dark">Dark</a>
            <a href="?theme=purple" class="ts-option <?= currentTheme()=='purple'?'active':'' ?>" data-theme="purple">Purple</a>
            <div class="ts-indicator" style="transform:translateX(<?= currentTheme()=='light'?'0':(currentTheme()=='dark'?'100%':'200%') ?>)"></div>
        </div>
    </nav>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
<button class="hamburger" id="hamburgerBtn" onclick="toggleSidebar()">
    <span></span><span></span><span></span>
</button>
<script>
(function(){
    var m = document.cookie.match(/(?:^|;\s*)edc_theme=([^;]*)/);
    if (m) document.body.dataset.theme = m[1];
})();

function toggleSidebar() {
    document.getElementById('mainSidebar').classList.toggle('show');
    document.getElementById('sidebarOverlay').classList.toggle('show');
}
</script>