<?php
require_once 'config.php';
// استدعاء ملف التحقق من الصلاحيات
include 'auth_check.php';
// طلب صلاحية "مدير" لهذه الصفحة
require_admin();

$mysqli = new mysqli("localhost", "tv_admin", "TvPassword2026!", "tv_db");
$website_title = $mysqli->query("SELECT setting_value FROM settings WHERE setting_key = 'website_title'")->fetch_assoc()['setting_value'] ?? 'Admin Panel';
$id = $_GET['id'] ?? 0;
$message = '';

if ($id == 0) {
    header("Location: users.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $is_active = $_POST['is_active'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    // منع تغيير دور المدير الرئيسي (صاحب ID 1)
    if ($id == 1 && $role !== 'admin') {
        $role = 'admin'; // إعادة الدور إلى مدير بالقوة
    }

    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare("UPDATE users SET password = ?, is_active = ?, role = ? WHERE id = ?");
        $stmt->bind_param("sisi", $hashed_password, $is_active, $role, $id);
    } else {
        $stmt = $mysqli->prepare("UPDATE users SET is_active = ?, role = ? WHERE id = ?");
        $stmt->bind_param("isi", $is_active, $role, $id);
    }
    
    if($stmt->execute()){
        $message = "<div class='alert alert-success'>تم تحديث بيانات المستخدم بنجاح.</div>";
    } else {
        $message = "<div class='alert alert-danger'>فشل تحديث البيانات.</div>";
    }
}

$user_result = $mysqli->query("SELECT * FROM users WHERE id = $id");
if($user_result->num_rows == 0){
    header("Location: users.php");
    exit();
}
$user = $user_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تعديل مستخدم - <?= htmlspecialchars($website_title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="wrapper">
    <?php include 'sidebar.php'; ?>
    <div id="content">
        <h1 class="mb-4">✏️ تعديل المستخدم: <?= htmlspecialchars($user['username']) ?></h1>
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="post">
                    <?php if($message): ?><?= $message ?><?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">كلمة المرور الجديدة:</label>
                        <input type="password" name="password" class="form-control" placeholder="اتركه فارغاً لعدم التغيير">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الحالة:</label>
                        <select name="is_active" class="form-select">
                            <option value="1" <?= $user['is_active'] ? 'selected' : '' ?>>مفعل</option>
                            <option value="0" <?= !$user['is_active'] ? 'selected' : '' ?>>غير مفعل</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الدور (الصلاحية):</label>
                        <select name="role" class="form-select" <?= ($user['id'] == 1) ? 'disabled' : '' ?>>
                            <option value="editor" <?= ($user['role'] === 'editor') ? 'selected' : '' ?>>محرر (Editor)</option>
                            <option value="admin" <?= ($user['role'] === 'admin') ? 'selected' : '' ?>>مدير (Admin)</option>
                        </select>
                        <?php if ($user['id'] == 1): ?>
                            <div class="form-text text-danger">لا يمكن تغيير دور المدير الرئيسي.</div>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                    <a href="users.php" class="btn btn-secondary">العودة للمستخدمين</a>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>