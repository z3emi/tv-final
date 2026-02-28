<?php
// 1. استخدام ملف الإعدادات المركزي لضمان توحيد الاتصال
require_once 'config.php';

// 2. وضع الهيدرز في الأعلى
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// 3. الاستعلام المحسّن باستخدام LEFT JOIN و GROUP BY
$result = $mysqli->query("
    SELECT
        c.id,
        COUNT(v.viewer_uid) AS view_count
    FROM
        channels c
    LEFT JOIN
        viewers v ON c.id = v.channel_id AND v.last_active > NOW() - INTERVAL 20 SECOND
    GROUP BY
        c.id
");

$stats = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $channel_id_str = (string)$row['id'];
        $view_count = (int)$row['view_count'];

        // يمكنك تعديل هذا الرقم ليعكس متوسط البت ريت لقنواتك
        $bitrate_kbps = 410;
        $traffic_kbps = $view_count * $bitrate_kbps;

        $traffic_display = '0 Kbps';
        if ($traffic_kbps > 0) {
            $traffic_display = ($traffic_kbps < 1000) ? number_format($traffic_kbps) . ' Kbps' : number_format($traffic_kbps / 1000, 2) . ' Mbps';
        }

        $stats[$channel_id_str] = [
            'view_count' => $view_count,
            'traffic' => $traffic_display
        ];
    }
    $result->free();
} else {
    // إرسال خطأ إذا فشل الاستعلام
    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode(['error' => 'Query failed: ' . $mysqli->error]);
    exit();
}

$mysqli->close();

echo json_encode(['stats' => $stats]);
?>