<?php
require_once 'config.php';
include 'auth_check.php'; // Includes session_start()
require_admin(); // Only admins can access this page

$mysqli = new mysqli("localhost", "root", "", "stream_db");
$website_title = $mysqli->query("SELECT setting_value FROM settings WHERE setting_key = 'website_title'")->fetch_assoc()['setting_value'] ?? 'Admin Panel';

// --- بداية التعديل: منطق عرض المستخدمين الجديد ---

$current_user_data = null;
if (isset($_SESSION['user'])) {
    $username_to_find = is_array($_SESSION['user']) ? $_SESSION['user']['username'] : $_SESSION['user'];
    $stmt = $mysqli->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username_to_find);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $current_user_data = $result->fetch_assoc();
    }
    $stmt->close();
}

if (!$current_user_data) {
    die("خطأ فادح: لا يمكن التحقق من هوية المستخدم الحالي. قد تحتاج لتسجيل الخروج والدخول مرة أخرى.");
}

$current_user_id = $current_user_data['id'];
$current_username = $current_user_data['username'];

// --- إعداد استعلام جلب قائمة المستخدمين بناءً على اسم المستخدم ---
$sql = "SELECT * FROM users";

// إذا كان المستخدم الحالي ليس هو "admin" الرئيسي، قم بإخفاء المستخدم "admin" عنه.
if ($current_username !== 'admin') {
    $sql .= " WHERE username != 'admin'";
}

$sql .= " ORDER BY id ASC";
$users_result = $mysqli->query($sql);

// --- نهاية التعديل ---
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إدارة المستخدمين - <?= htmlspecialchars($website_title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .table-hover tbody tr:hover {
            background-color: rgba(0,0,0,0.05);
        }
        .btn-action {
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        /* --- بداية الإصلاح النهائي: استهداف خلايا الرأس مباشرة --- */
        .table-dark th {
            color: #fff !important;
            background-color: #212529 !important; /* لون Bootstrap الداكن القياسي */
        }
        /* --- نهاية الإصلاح --- */
    </style>
</head>
<body>
<div class="wrapper">
    <?php include 'sidebar.php'; ?>
    <div id="content">
        <h1 class="mb-4 fw-bold d-flex align-items-center"><i class="bi bi-people-fill me-3"></i>إدارة المستخدمين</h1>
        <div class="card shadow-sm border-0">
            <div class="card-header bg-light border-0 py-3">
                <a href="user_add.php" class="btn btn-success"><i class="bi bi-plus-circle-fill me-2"></i>إضافة مستخدم جديد</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle text-center">
                        <thead class="table-dark">
                            <tr>
                                <th>اسم المستخدم</th>
                                <th>الدور</th>
                                <th>الحالة</th>
                                <th>تاريخ الإنشاء</th>
                                <th style="width: 150px;">التحكم</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($users_result && $users_result->num_rows > 0): ?>
                                <?php while($user = $users_result->fetch_assoc()): ?>
                                <tr>
                                    <td class="fw-medium"><?= htmlspecialchars($user['username']) ?></td>
                                    <td>
                                        <span class="badge fs-6 rounded-pill <?= $user['role'] == 'admin' ? 'bg-danger-subtle text-danger-emphasis' : 'bg-info-subtle text-info-emphasis' ?>">
                                            <i class="bi <?= $user['role'] == 'admin' ? 'bi-shield-lock-fill' : 'bi-person-fill' ?> me-1"></i>
                                            <?= htmlspecialchars($user['role']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge fs-6 rounded-pill <?= $user['is_active'] ? 'bg-success-subtle text-success-emphasis' : 'bg-secondary-subtle text-secondary-emphasis' ?>">
                                            <?= $user['is_active'] ? 'مفعل' : 'غير مفعل' ?>
                                        </span>
                                    </td>
                                    <td><?= date("Y-m-d", strtotime($user['created_at'])) ?></td>
                                    <td>
                                        <a href="user_edit.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-primary btn-action" title="تعديل">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <?php 
                                        if ($user['username'] !== 'admin' && $user['id'] != $current_user_id): 
                                        ?>
                                        <a href="user_delete.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-danger btn-action" title="حذف" onclick="return confirm('هل أنت متأكد من حذف هذا المستخدم؟')">
                                            <i class="bi bi-trash3-fill"></i>
                                        </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center p-5">
                                        <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 3rem;"></i>
                                        <p class="mt-3 mb-0">لا يوجد مستخدمون لعرضهم حالياً.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
