<?php
require_once 'config.php';
session_start();
if (!isset($_SESSION['user'])) { header('Location: login.php'); exit(); }
$mysqli = new mysqli("localhost", "root", "", "stream_db");
$website_title = $mysqli->query("SELECT setting_value FROM settings WHERE setting_key = 'website_title'")->fetch_assoc()['setting_value'] ?? 'Admin Panel';
$id = $_GET['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $stmt = $mysqli->prepare("UPDATE categories SET name = ? WHERE id = ?");
    $stmt->bind_param("si", $name, $id);
    $stmt->execute();
    header("Location: categories.php");
    exit();
}

$category = $mysqli->query("SELECT * FROM categories WHERE id = $id")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تعديل التصنيف - <?= htmlspecialchars($website_title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="wrapper">
    <?php include 'sidebar.php'; ?>
    <div id="content">
        <h1 class="mb-4">✏️ تعديل التصنيف</h1>
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label for="name" class="form-label">اسم التصنيف:</label>
                        <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars($category['name']) ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
                    <a href="categories.php" class="btn btn-secondary">إلغاء</a>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>