<?php
require_once 'config.php';
// استدعاء ملف التحقق من الصلاحيات
include 'auth_check.php';
// طلب صلاحية "مدير" لهذه الصفحة، سيتم طرد المحررين
require_admin();

$mysqli = new mysqli("localhost", "tv_admin", "TvPassword2026!", "tv_db");
$website_title = $mysqli->query("SELECT setting_value FROM settings WHERE setting_key = 'website_title'")->fetch_assoc()['setting_value'] ?? 'Admin Panel';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role']; // الحصول على الدور من الفورم

    if (empty($username) || empty($password)) {
        $error = "الرجاء ملء جميع الحقول.";
    } elseif ($role !== 'admin' && $role !== 'editor') {
        $error = "الدور المحدد غير صالح.";
    } else {
        // تشفير كلمة المرور للأمان
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // إضافة المستخدم الجديد مع دوره
        $stmt = $mysqli->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $hashed_password, $role);
        
        if ($stmt->execute()) {
            header("Location: users.php");
            exit();
        } else {
            $error = "اسم المستخدم موجود بالفعل.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إضافة مستخدم - <?= htmlspecialchars($website_title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="wrapper">
    <?php include 'sidebar.php'; ?>
    <div id="content">
        <h1 class="mb-4">➕ إضافة مستخدم جديد</h1>
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="post">
                    <?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">اسم المستخدم:</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">كلمة المرور:</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الدور (الصلاحية):</label>
                        <select name="role" class="form-select" required>
                            <option value="editor">محرر (Editor)</option>
                            <option value="admin">مدير (Admin)</option>
                        </select>
                        <div class="form-text">المحرر يمكنه إدارة القنوات والتصنيفات فقط. المدير يمكنه التحكم بكل شيء.</div>
                    </div>
                    <button type="submit" class="btn btn-success">إضافة المستخدم</button>
                    <a href="users.php" class="btn btn-secondary">إلغاء</a>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>