<?php
require_once 'config.php';
include 'auth_check.php';
require_admin();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit();
}

$channel_id = (int)($_POST['channel_id'] ?? 0);
$channel_url = trim($_POST['channel_url'] ?? '');
$audio_url = trim($_POST['audio_url'] ?? '');

// إذا لم يتم وضع رابط صوت، استخدم رابط القناة
if (empty($audio_url) && !empty($channel_url)) {
    $audio_url = $channel_url;
}

// التحقق من البيانات
if ($channel_id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'معرف القناة غير صحيح']);
    exit();
}

if (empty($channel_url)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'رابط القناة مطلوب']);
    exit();
}

// التحقق من وجود القناة
$stmt = $mysqli->prepare("SELECT id FROM channels WHERE id = ?");
$stmt->bind_param("i", $channel_id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    $stmt->close();
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'القناة غير موجودة']);
    exit();
}
$stmt->close();

// تحديث قاعدة البيانات
$stmt = $mysqli->prepare("UPDATE channels SET url = ?, audio_url = ? WHERE id = ?");
$stmt->bind_param("ssi", $channel_url, $audio_url, $channel_id);
if (!$stmt->execute()) {
    $stmt->close();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'فشل تحديث القاعدة']);
    exit();
}
$stmt->close();

// إعادة كتابة ملف channels.txt
$channels = [];
$res = $mysqli->query("SELECT id, url FROM channels WHERE is_active = 1");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $channels[] = $row["id"] . "|" . $row["url"];
    }
    $res->free();
}

$channelsTxtPath = __DIR__ . '/channels.txt';
if (!file_put_contents($channelsTxtPath, implode(PHP_EOL, $channels) . PHP_EOL)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'فشل تحديث ملف channels.txt']);
    exit();
}

echo json_encode([
    'status' => 'success',
    'message' => 'تم تحديث رابط البث بنجاح',
    'channel_id' => $channel_id,
    'channel_url' => $channel_url,
    'audio_url' => $audio_url
]);
?>
