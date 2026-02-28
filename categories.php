<?php
// 1. استخدام ملف الإعدادات المركزي
require_once 'config.php';
if (!isset($_SESSION['user'])) { header('Location: login.php'); exit(); }

// 2. استخدم متغير الاتصال الموحد $mysqli
global $mysqli;

$website_title = $mysqli->query("SELECT setting_value FROM settings WHERE setting_key = 'website_title'")->fetch_assoc()['setting_value'] ?? 'Admin Panel';

// Handle Add Category Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name']);
    if (!empty($name)) {
        $stmt = $mysqli->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        if ($stmt->execute()) {
            $_SESSION['message'] = '<div class="alert alert-success alert-dismissible fade show" role="alert">تمت إضافة التصنيف بنجاح.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        } else {
            $_SESSION['message'] = '<div class="alert alert-danger alert-dismissible fade show" role="alert">حدث خطأ أثناء الإضافة.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        }
        $stmt->close();
    }
    header("Location: categories.php");
    exit();
}

// Fetch categories with channel count
$categories_result = $mysqli->query("
    SELECT cat.*, COUNT(c.id) as channel_count
    FROM categories cat
    LEFT JOIN channels c ON cat.id = c.category_id
    GROUP BY cat.id
    ORDER BY cat.name ASC
");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إدارة التصنيفات - <?= htmlspecialchars($website_title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="style.css">
     <style>
        .btn-action {
            width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include 'sidebar.php'; ?>
    <div id="content">
        <h1 class="h2 mb-4 fw-bold d-flex align-items-center"><i class="bi bi-bookmarks-fill me-3"></i>إدارة التصنيفات</h1>
        
        <?php if (isset($_SESSION['message'])) { echo $_SESSION['message']; unset($_SESSION['message']); } ?>

        <div class="row g-4">
            <!-- Categories List -->
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-light border-0 py-3">
                        <h5 class="mb-0">قائمة التصنيفات</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if($categories_result && $categories_result->num_rows > 0): ?>
                            <ul class="list-group list-group-flush">
                                <?php while($cat = $categories_result->fetch_assoc()): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-4 py-3">
                                    <div>
                                        <strong class="me-2"><?= htmlspecialchars($cat['name']) ?></strong>
                                        <span class="badge bg-secondary rounded-pill"><?= $cat['channel_count'] ?> قناة</span>
                                    </div>
                                    <div class="btn-group">
                                        <a href="category_edit.php?id=<?= $cat['id'] ?>" class="btn btn-sm btn-outline-primary btn-action" title="تعديل"><i class="bi bi-pencil-square"></i></a>
                                        <a href="category_delete.php?id=<?= $cat['id'] ?>" class="btn btn-sm btn-outline-danger btn-action" title="حذف" onclick="return confirm('هل أنت متأكد؟ سيتم حذف التصنيف فقط ولن يتم حذف القنوات.')"><i class="bi bi-trash3-fill"></i></a>
                                    </div>
                                </li>
                                <?php endwhile; ?>
                            </ul>
                        <?php else: ?>
                            <div class="text-center p-5">
                                <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 3rem;"></i>
                                <p class="mt-3 mb-0">لا توجد تصنيفات مضافة بعد.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Add New Category Form -->
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 h-100">
                     <div class="card-header bg-light border-0 py-3">
                        <h5 class="mb-0">إضافة تصنيف جديد</h5>
                    </div>
                    <div class="card-body d-flex flex-column justify-content-center">
                        <form method="post">
                            <div class="mb-3">
                                <label for="name" class="form-label">اسم التصنيف:</label>
                                <input type="text" name="name" id="name" class="form-control" required placeholder="مثال: قنوات رياضية">
                            </div>
                            <button type="submit" name="add_category" class="btn btn-success w-100"><i class="bi bi-plus-circle-fill me-2"></i>إضافة</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
