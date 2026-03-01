<?php
require_once 'config.php';
session_start();
if (!isset($_SESSION['user'])) { header('Location: login.php'); exit(); }

$mysqli = new mysqli("localhost", "tv_admin", "TvPassword2026!", "tv_db");
$website_title = $mysqli->query("SELECT setting_value FROM settings WHERE setting_key = 'website_title'")
                         ->fetch_assoc()['setting_value'] ?? 'Admin Panel';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // استلام البيانات من الفورم المعدل
    $name = $_POST['name'] ?? '';
    $url = $_POST['url'] ?? ''; // رابط البث أصبح الآن هو رابط المصدر
    $category_id = $_POST['category_id'] ?? 0;
    $is_direct = $_POST['is_direct'] ?? 0; // الحقل الجديد
    $is_active = $_POST['active'] ?? 1;
    
    $upload_dir = "stream/stream/uploads/";
    $image_path = '';

    // معالجة رفع الصورة
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $filename = time() . '_' . basename($_FILES["image"]["name"]);
        $image_path = $filename;
        $full_path = $upload_dir . $filename;
        
        if (!move_uploaded_file($_FILES["image"]["tmp_name"], $full_path)) {
            $message = "<div class='alert alert-danger'>فشل رفع الصورة.</div>";
            $image_path = '';
        }
    }

    if (empty($message)) {
        // تم تحديث استعلام SQL ليشمل الحقول الجديدة
        // تم تغيير اسم العمود من url إلى url ليتوافق مع قاعدة البيانات
        $stmt = $mysqli->prepare("INSERT INTO channels (name, url, image_url, category_id, is_active, is_direct) VALUES (?, ?, ?, ?, ?, ?)");
        // تم تحديث أنواع المتغيرات
        $stmt->bind_param("sssiii", $name, $url, $image_path, $category_id, $is_active, $is_direct);

        if ($stmt->execute()) {
            // --- تم حذف كود تشغيل FFmpeg من هنا ---
            // سكريبت البايثون هو المسؤول الآن
            
            $_SESSION['message'] = "<div class='alert alert-success'>تمت إضافة القناة بنجاح.</div>";
            header("Location: dashboard.php#channels");
            exit();
        } else {
            $message = "<div class='alert alert-danger'>فشل إضافة القناة: " . $stmt->error . "</div>";
        }
        $stmt->close();
    }
}

$categories_result = $mysqli->query("SELECT * FROM categories ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إضافة قناة - <?= htmlspecialchars($website_title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="wrapper">
    <?php include 'sidebar.php'; ?>
    <div id="content" class="container-fluid p-4">
        <h1 class="mb-4">➕ إضافة قناة جديدة</h1>
        <div class="card shadow-sm">
            <div class="card-body">
                <?php if (!empty($message)) echo $message; ?>
                <form method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="name" class="form-label">اسم القناة:</label>
                        <input id="name" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="url" class="form-label">رابط المصدر (Source URL):</label>
                        <textarea id="url" name="url" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="image" class="form-label">صورة القناة:</label>
                        <input id="image" name="image" type="file" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="category_id" class="form-label">التصنيف:</label>
                        <select id="category_id" name="category_id" class="form-select" required>
                            <option value="" disabled selected>اختر تصنيف...</option>
                            <?php while($cat = $categories_result->fetch_assoc()): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <hr class="my-4">
                    <div class="mb-3">
                        <label for="is_direct" class="form-label">نوع القناة:</label>
                        <select id="is_direct" name="is_direct" class="form-select">
                            <option value="0" selected>ميرور (يمر عبر السيرفر)</option>
                            <option value="1">مباشر (لا يمر عبر السيرفر)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="active" class="form-label">الحالة:</label>
                        <select id="active" name="active" class="form-select">
                            <option value="1" selected>مفعلة</option>
                            <option value="0">غير مفعلة</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-success">💾 حفظ القناة</button>
                    <a href="dashboard.php" class="btn btn-secondary">↩️ إلغاء</a>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>