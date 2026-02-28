<?php
require_once 'config.php';
session_start();
if (!isset($_SESSION['user'])) { header('Location: login.php'); exit(); }
$mysqli = new mysqli("localhost", "tv_admin", "TvPassword2026!", "tv_db");
$id = $_GET['id'] ?? 0;
if ($id > 0) {
    $stmt = $mysqli->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}
header("Location: categories.php");
exit();
?>