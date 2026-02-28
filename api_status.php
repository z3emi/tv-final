<?php
// 1. استخدام ملف الإعدادات المركزي
require_once 'config.php';

// 2. المسار الجديد والمطلق لملف الحالة على قرص الرام
$status_file_path = LIVE_ROOT . '/status.json';

// 3. تعيين رأس المحتوى ليكون JSON
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

if (!file_exists($status_file_path)) {
    http_response_code(404);
    // رسالة خطأ أوضح للمستخدم
    echo json_encode(['error' => 'Status file not found at ' . $status_file_path . '. Make sure the Python script is running.']);
    exit();
}

// قراءة بيانات الحالة من ملف JSON
$status_json = file_get_contents($status_file_path);
$data = json_decode($status_json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode(['error' => 'Invalid JSON format in status file.']);
    exit();
}

// استخدام الاتصال $mysqli من config.php لجلب أسماء وصور القنوات
$channel_details = [];
$result = $mysqli->query("SELECT id, name, image_url FROM channels");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $channel_details[$row['id']] = [
            'name' => $row['name'],
            'image_url' => $row['image_url']
        ];
    }
    $result->free();
}
$mysqli->close();

// دمج أسماء وصور القنوات مع بيانات الحالة
if (isset($data['channels']) && is_array($data['channels'])) {
    foreach ($data['channels'] as &$channel) {
        $channel_id = $channel['id'];
        if (isset($channel_details[$channel_id])) {
            $channel['name'] = htmlspecialchars($channel_details[$channel_id]['name']);
            $channel['image_url'] = htmlspecialchars($channel_details[$channel_id]['image_url']);
        } else {
            $channel['name'] = 'قناة غير معروفة';
            $channel['image_url'] = 'default.png';
        }

        $links = build_local_stream_links((string)$channel_id);
        $channel['stream_link'] = $links['stream_link'];
        $channel['audio_link'] = $links['audio_link'];
    }
}

// إرسال البيانات المدمجة النهائية
echo json_encode($data);
?>