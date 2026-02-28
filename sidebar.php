<?php
require_once 'config.php';

// مهم: تأكد تشغّل السيشن في مكان واحد فقط (يفضل داخل config.php) بالشرط التالي:
// if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($mysqli)) {
    $mysqli = new mysqli("localhost", "root", "", "stream_db");
}

$website_title_sidebar = $mysqli->query("SELECT setting_value FROM settings WHERE setting_key = 'website_title'")
    ->fetch_assoc()['setting_value'] ?? 'Admin Panel';

$user_role = $_SESSION['user_role'] ?? 'editor';
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Admin Header (Fixed Top) -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm fixed-top" style="padding: 0.75rem 1.5rem; z-index: 1050;">
    <a class="navbar-brand fw-bold" href="dashboard.php">
        <i class="bi bi-tv-fill me-1"></i> <?= htmlspecialchars($website_title_sidebar) ?>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNavbar">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">

            <li class="nav-item">
                <a class="nav-link <?= $current_page == 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                    <i class="bi bi-grid-1x2-fill me-1"></i> لوحة التحكم
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= in_array($current_page, ['channels.php', 'channel_add.php', 'channel_edit.php', 'import_playlist.php']) ? 'active' : '' ?>" href="channels.php">
                    <i class="bi bi-film me-1"></i> إدارة القنوات
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= in_array($current_page, ['categories.php', 'category_edit.php']) ? 'active' : '' ?>" href="categories.php">
                    <i class="bi bi-bookmarks-fill me-1"></i> التصنيفات
                </a>
            </li>

            <?php if ($user_role === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link <?= in_array($current_page, ['users.php', 'user_add.php', 'user_edit.php']) ? 'active' : '' ?>" href="users.php">
                        <i class="bi bi-people-fill me-1"></i> المستخدمين
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'settings.php' ? 'active' : '' ?>" href="settings.php">
                        <i class="bi bi-gear-fill me-1"></i> الإعدادات
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= in_array($current_page, ['report_viewers.php', 'export_active_viewers.php']) ? 'active' : '' ?>" href="report_viewers.php">
                        <i class="bi bi-bar-chart-line-fill me-1"></i> التقارير
                    </a>
                </li>
            <?php endif; ?>
        </ul>

        <ul class="navbar-nav d-flex align-items-center gap-2">
            <li class="nav-item">
                <a class="nav-link" href="../index.php" target="_blank">
                    <i class="bi bi-display-fill me-1"></i> عرض الموقع
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link text-danger" href="logout.php">
                    <i class="bi bi-box-arrow-right me-1"></i> خروج
                </a>
            </li>
        </ul>
    </div>
</nav>

<!-- Custom CSS -->
<style>
    /* فراغ علوي للمحتوى حتى ما يغطيه الهيدر المثبّت */
    body {
        padding-top: 72px; /* يناسب ارتفاع النافبار الديسكتوب */
    }
    @media (max-width: 991.98px) {
        body { padding-top: 64px; } /* أجهزة لوحية */
    }
    @media (max-width: 767.98px) {
        body { padding-top: 58px; } /* موبايل */
    }

    .navbar-nav .nav-link {
        transition: all 0.2s ease-in-out;
        font-weight: 500;
    }
    .navbar-nav .nav-link:hover {
        background-color: #343a40;
        border-radius: 6px;
    }
    .navbar-nav .nav-link.active {
        background-color: #495057;
        border-radius: 6px;
    }
    .navbar-brand {
        font-size: 1.25rem;
    }
    @media (max-width: 767.98px) {
        .navbar-brand { font-size: 1rem; }
    }
</style>
