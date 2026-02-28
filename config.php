<?php
// ابدأ الجلسة في مكان واحد مركزي
session_start();

// بيانات الاتصال بقاعدة البيانات
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "stream_db";

// أنشئ متغير اتصال واحد ومتناسق
$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);

// تحقق من وجود أخطاء في الاتصال
if ($mysqli->connect_error) {
    // استخدم die هنا لأن هذا خطأ فادح يمنع عمل أي شيء آخر
    die("❌ فشل الاتصال بقاعدة البيانات: " . $mysqli->connect_error);
}

// اضبط الترميز والمنطقة الزمنية لدعم اللغة العربية وشروط الوقت
$mysqli->set_charset("utf8mb4");
$mysqli->query("SET time_zone = '+03:00'");
?>
