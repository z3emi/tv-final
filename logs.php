<?php
require_once 'config.php';
session_start();
if (!isset($_SESSION['user'])) { header('Location: login.php'); exit(); }
$mysqli = new mysqli("localhost", "root", "", "stream_db");
$logs = $mysqli->query("SELECT logs.*, users.username FROM logs LEFT JOIN users ON users.id = logs.user_id ORDER BY logs.id DESC");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>سجل العمليات</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <h2 class="mb-4">📄 سجل العمليات</h2>
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>المستخدم</th>
                <th>العملية</th>
                <th>الوقت</th>
            </tr>
        </thead>
        <tbody>
        <?php while($l = $logs->fetch_assoc()): ?>
            <tr>
                <td><?= $l['username'] ?? 'مجهول' ?></td>
                <td><?= htmlspecialchars($l['action']) ?></td>
                <td><?= $l['created_at'] ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <a href="dashboard.php" class="btn btn-secondary mt-3">↩️ رجوع</a>
</div>
</body>
</html>
