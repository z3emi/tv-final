<?php
require_once 'config.php';
if (!isset($_SESSION['user'])) { header('Location: login.php'); exit(); }
global $mysqli;

$website_title = $mysqli->query("SELECT setting_value FROM settings WHERE setting_key = 'website_title'")
    ->fetch_assoc()['setting_value'] ?? 'Admin Panel';

$id = $_GET['id'] ?? 0;
$message = '';

if (!filter_var($id, FILTER_VALIDATE_INT) || $id <= 0) {
    die("Invalid Channel ID");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_image = $_POST['current_image'];
    $image_path = $current_image;
    $upload_dir = "stream/stream/uploads/";

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $filename = time() . '_' . basename($_FILES["image"]["name"]);
        $image_path = $filename;
        $full_path = $upload_dir . $filename;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $full_path)) {
            if (!empty($current_image) && file_exists($upload_dir . $current_image)) {
                @unlink($upload_dir . $current_image);
            }
        } else {
            $message = "<div class='alert alert-danger'>فشل رفع الصورة الجديدة.</div>";
            $image_path = $current_image;
        }
    }

    if (empty($message)) {
        $name = $_POST['name'];
        $url = $_POST['url'];
        $category_id = $_POST['category_id'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $is_direct = isset($_POST['is_direct']) ? (int) $_POST['is_direct'] : 0;
        $channel_id = $_POST['id'];

        $stmt = $mysqli->prepare("UPDATE channels SET name=?, url=?, image_url=?, category_id=?, is_active=?, is_direct=? WHERE id=?");
        $stmt->bind_param("sssiiii", $name, $url, $image_path, $category_id, $is_active, $is_direct, $channel_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "<div class='alert alert-success'>تم تحديث القناة بنجاح.</div>";
            header("Location: dashboard.php#channels");
            exit();
        } else {
            $message = "<div class='alert alert-danger'>فشل تحديث القناة: " . $stmt->error . "</div>";
        }
        $stmt->close();
    }
}

$stmt = $mysqli->prepare("SELECT * FROM channels WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$channel_result = $stmt->get_result();

if($channel_result->num_rows == 0) die("Channel not found");
$channel = $channel_result->fetch_assoc();
$stmt->close();

$categories_result = $mysqli->query("SELECT * FROM categories ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تعديل قناة - <?= htmlspecialchars($website_title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #f9f5f1;
        }
        .card {
            border-radius: 12px;
            overflow: hidden;
            border: none;
        }
        .card-header {
            background: #212529; /* اللون الجديد */
            color: white;
            font-weight: bold;
            font-size: 1rem;
        }
        .form-label {
            font-weight: 600;
            color: #444;
        }
        .image-preview {
            width: 150px;
            height: 150px;
            border-radius: 12px;
            object-fit: contain;
            background-color: #fff;
            border: 3px solid #eadbcd;
            box-shadow: 0px 2px 6px rgba(0,0,0,0.1);
        }
        .btn-primary {
            background-color: #212529; /* اللون الجديد */
            border: none;
        }
        .btn-primary:hover {
            background-color: #000; /* أغمق عند المرور */
        }
        .form-check-label {
            font-weight: 500;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include 'sidebar.php'; ?>
    <div id="content" class="p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h4 fw-bold mb-0"><i class="bi bi-pencil-square me-2 text-primary"></i>تعديل قناة: <?= htmlspecialchars($channel['name']) ?></h1>
            <a href="dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> العودة</a>
        </div>

        <?php if (!empty($message)) echo $message; ?>

        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $channel['id'] ?>">
            <input type="hidden" name="current_image" value="<?= htmlspecialchars($channel['image_url']) ?>">

            <div class="row g-4">
                <!-- القسم الأيسر -->
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header py-3">البيانات الأساسية</div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="name" class="form-label">اسم القناة:</label>
                                <input id="name" name="name" class="form-control" value="<?= htmlspecialchars($channel['name']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="url" class="form-label">رابط المصدر (Source URL):</label>
                                <textarea id="url" name="url" class="form-control" rows="4" required><?= htmlspecialchars($channel['url']) ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="category_id" class="form-label">التصنيف:</label>
                                <select id="category_id" name="category_id" class="form-select" required>
                                    <?php while($cat = $categories_result->fetch_assoc()): ?>
                                    <option value="<?= $cat['id'] ?>" <?= ($cat['id'] == $channel['category_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- القسم الأيمن -->
                <div class="col-lg-4">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header py-3">صورة القناة</div>
                        <div class="card-body text-center">
                            <img src="stream/stream/uploads/<?= htmlspecialchars($channel['image_url'] ?? 'default.png') ?>" class="image-preview mb-3" onerror="this.src='https://placehold.co/150/eee/313435?text=No+Image';">
                            <input id="image" name="image" type="file" class="form-control mt-2">
                        </div>
                    </div>
                    <div class="card shadow-sm">
                        <div class="card-header py-3">الإعدادات</div>
                        <div class="card-body">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?= $channel['is_active'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_active">حالة القناة (مفعلة)</label>
                            </div>
                            <div class="mb-3">
                                <label for="is_direct" class="form-label">بث مباشر:</label>
                                <select id="is_direct" name="is_direct" class="form-select">
                                    <option value="0" <?= !$channel['is_direct'] ? 'selected' : '' ?>>غير مباشر (يمر عبر السيرفر)</option>
                                    <option value="1" <?= $channel['is_direct'] ? 'selected' : '' ?>>مباشر (لا يمر عبر السيرفر)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-check-circle-fill me-2"></i>حفظ التعديلات</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
