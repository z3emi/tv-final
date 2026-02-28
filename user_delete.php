<?php
require_once 'config.php';
include 'auth_check.php'; // استدعاء ملف التحقق
require_admin(); // طلب صلاحية "مدير" لهذه الصفحة
session_start();
if (!isset($_SESSION['user'])) { header('Location: login.php'); exit(); }

$id = $_GET['id'] ?? 0;
// Prevent deleting the main admin user (ID 1)
if ($id > 1) {
    $mysqli = new mysqli("localhost", "root", "", "stream_db");
    $stmt = $mysqli->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}
header("Location: users.php");
exit();
?>