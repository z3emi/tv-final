<?php
require_once 'config.php'; // يستخدم ملف الاتصال المركزي

// استلام البيانات من المشغل
$channel_id = $_POST['channel_id'] ?? 0;
$viewer_uid = $_POST['viewer_id'] ?? '';
$ip_address = $_SERVER['REMOTE_ADDR'];
// --- بداية الإضافة: التقاط معلومات المتصفح والجهاز ---
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
// --- نهاية الإضافة ---

// التحقق من صحة البيانات
if (!filter_var($channel_id, FILTER_VALIDATE_INT) || $channel_id <= 0 || empty($viewer_uid)) {
    http_response_code(400);
    exit();
}

// استخدم متغير الاتصال العام $mysqli من config.php
global $mysqli;

// 1. تحديث جدول المشاهدين النشطين
$stmt = $mysqli->prepare("
    INSERT INTO viewers (channel_id, viewer_uid, ip_address, last_active)
    VALUES (?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE
    last_active = NOW(), ip_address = VALUES(ip_address)
");
$stmt->bind_param("iss", $channel_id, $viewer_uid, $ip_address);
$stmt->execute();
$stmt->close();


// 2. تحديث سجل المشاهدات
$update_stmt = $mysqli->prepare("
    UPDATE viewing_history 
    SET last_seen = NOW() 
    WHERE channel_id = ? AND viewer_uid = ? AND last_seen > NOW() - INTERVAL 2 MINUTE
");
$update_stmt->bind_param("is", $channel_id, $viewer_uid);
$update_stmt->execute();

$affected_rows = $update_stmt->affected_rows;
$update_stmt->close();

// --- بداية التعديل: إضافة user_agent عند إنشاء سجل جديد ---
if ($affected_rows === 0) {
    $insert_stmt = $mysqli->prepare("
        INSERT INTO viewing_history (channel_id, viewer_uid, ip_address, user_agent, session_start, last_seen) 
        VALUES (?, ?, ?, ?, NOW(), NOW())
    ");
    // تم تغيير "iss" إلى "isss" لإضافة المتغير الجديد
    $insert_stmt->bind_param("isss", $channel_id, $viewer_uid, $ip_address, $user_agent);
    $insert_stmt->execute();
    $insert_stmt->close();
}
// --- نهاية التعديل ---

http_response_code(200);
echo "OK";
?>