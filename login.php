<?php
// 1. استدعاء ملف الإعدادات المركزي أولاً
require_once 'config.php'; // هذا الملف يبدأ الجلسة ويتصل بقاعدة البيانات

// 2. إذا كان المستخدم مسجلاً دخوله بالفعل، انقله إلى لوحة التحكم
if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 3. استخدم متغير الاتصال الموحد $mysqli من config.php
    global $mysqli;

    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $mysqli->prepare("SELECT id, username, password, is_active, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            if ($user['is_active']) {
                session_regenerate_id(true);
                
                // --- بداية الإصلاح: العودة إلى طريقة تخزين الجلسة الأصلية ---
                // هذا يضمن التوافق مع بقية ملفاتك
                $_SESSION['user'] = $user['username']; 
                // يمكنك إضافة بيانات أخرى إذا احتجت إليها في ملفات أخرى
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                // --- نهاية الإصلاح ---
                
                header('Location: dashboard.php');
                exit();
            } else {
                $error = "هذا الحساب غير مفعل.";
            }
        } else {
            $error = "بيانات الدخول غير صحيحة.";
        }
    } else {
        $error = "بيانات الدخول غير صحيحة.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تسجيل الدخول</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: linear-gradient(-45deg, #0d1117, #161b22, #0d1117);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .login-card {
            background: rgba(22, 27, 34, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            color: #c9d1d9;
            width: 100%;
            max-width: 420px;
        }
        .form-control {
            background-color: rgba(13, 17, 23, 0.7);
            border: 1px solid #30363d;
            color: #c9d1d9;
        }
        .form-control:focus {
            background-color: rgba(13, 17, 23, 0.9);
            border-color: #58a6ff;
            box-shadow: 0 0 0 0.25rem rgba(88, 166, 255, 0.25);
            color: #c9d1d9;
        }
        .input-group-text {
            background-color: #30363d;
            border: 1px solid #30363d;
            color: #8b949e;
        }
    </style>
</head>
<body>
<div class="card shadow-lg p-4 p-md-5 login-card">
    <h3 class="mb-4 text-center fw-bold">🔐 تسجيل الدخول</h3>
    <?php if (!empty($error)): ?>
        <div class='alert alert-danger bg-danger-subtle text-danger-emphasis border-danger'>
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error ?>
        </div>
    <?php endif; ?>

    <form method="post" action="login.php">
        <div class="mb-3">
            <label class="form-label">اسم المستخدم</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                <input type="text" name="username" class="form-control" required>
            </div>
        </div>
        <div class="mb-4">
            <label class="form-label">كلمة المرور</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                <input type="password" name="password" class="form-control" required>
            </div>
        </div>
        <button type="submit" class="btn btn-primary w-100 btn-lg">دخول</button>
    </form>
</div>
</body>
</html>
