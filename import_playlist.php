<?php
require_once 'config.php';
session_start();
if (!isset($_SESSION['user'])) { header('Location: login.php'); exit(); }
$mysqli = new mysqli("localhost", "tv_admin", "TvPassword2026!", "tv_db");
$website_title = $mysqli->query("SELECT setting_value FROM settings WHERE setting_key = 'website_title'")->fetch_assoc()['setting_value'] ?? 'Admin Panel';

// The rest of your PHP logic for importing...
$step = 1;
$parsed_channels = [];
$import_message = '';
// ... (Your existing PHP code for this file)
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>استيراد M3U - <?= htmlspecialchars($website_title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="wrapper">
    <?php include 'sidebar.php'; ?>
    <div id="content">
        <h1 class="mb-4">📥 استيراد قنوات من M3U</h1>
        <?php if(!empty($import_message)) echo $import_message; ?>

        <?php if ($step === 1): ?>
        <div class="card p-4 shadow-sm">
            <p>ادخل رابط ملف M3U ليتم تحليله واستخراج القنوات منه.</p>
            <form method="post">
                <div class="mb-3">
                    <label for="playlist_url" class="form-label">رابط قائمة التشغيل:</label>
                    <input type="url" name="playlist_url" id="playlist_url" class="form-control" dir="ltr" required>
                </div>
                <button type="submit" class="btn btn-primary">🔍 تحليل الرابط</button>
            </form>
        </div>
        <?php endif; ?>

        <?php if ($step === 2): ?>
        <div class="card p-4 shadow-sm">
             </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>