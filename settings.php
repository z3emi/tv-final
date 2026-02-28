<?php
require_once 'config.php';
include 'auth_check.php'; // Includes session_start()
require_admin(); // Only admins can access this page

$mysqli = new mysqli("localhost", "tv_admin", "TvPassword2026!", "tv_db");
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $website_title = $_POST['website_title'];
    $stmt = $mysqli->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('website_title', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->bind_param("ss", $website_title, $website_title);
    if($stmt->execute()){
        $message = '<div class="alert alert-success">تم حفظ الإعدادات بنجاح.</div>';
    }
}

$settings_result = $mysqli->query("SELECT * FROM settings");
$settings = [];
while($row = $settings_result->fetch_assoc()){ $settings[$row['setting_key']] = $row['setting_value']; }
$website_title = $settings['website_title'] ?? 'My Stream System';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>الإعدادات العامة - <?= htmlspecialchars($website_title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="wrapper">
    <?php include 'sidebar.php'; ?>
    <div id="content">
        <h1 class="mb-4">⚙️ الإعدادات العامة</h1>
        <?= $message ?>
        <div class="card shadow-sm">
            <div class="card-header">إعدادات الموقع</div>
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label for="website_title" class="form-label">اسم الموقع:</label>
                        <input type="text" name="website_title" id="website_title" class="form-control" value="<?= htmlspecialchars($website_title) ?>">
                        <div class="form-text">هذا الاسم سيظهر في عنوان تبويب المتصفح وفي رأس لوحة التحكم.</div>
                    </div>
                    <button type="submit" class="btn btn-primary">حفظ الإعدادات</button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>