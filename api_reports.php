<?php
require_once 'config.php'; // يستخدم ملف الاتصال المركزي مع إعدادات التوقيت

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

$response = [];

// --- 1. الإحصائيات المباشرة (Live Stats) ---
$live_stats_query = $mysqli->query("
    SELECT
        COUNT(DISTINCT viewer_uid) AS total_viewers,
        COUNT(DISTINCT channel_id) AS active_channels
    FROM viewers
    WHERE last_active > NOW() - INTERVAL 20 SECOND
");
$live_stats_data = $live_stats_query->fetch_assoc();
$total_traffic_kbps = ($live_stats_data['total_viewers'] ?? 0) * 410;
$live_stats_data['total_traffic'] = ($total_traffic_kbps < 1000) ? number_format($total_traffic_kbps) . ' Kbps' : number_format($total_traffic_kbps / 1000, 2) . ' Mbps';
$response['live_stats'] = $live_stats_data;

$top_channels_query = $mysqli->query("
    SELECT c.name, COUNT(DISTINCT v.viewer_uid) AS view_count
    FROM viewers v JOIN channels c ON v.channel_id = c.id
    WHERE v.last_active > NOW() - INTERVAL 20 SECOND
    GROUP BY v.channel_id ORDER BY view_count DESC LIMIT 10
");
$response['top_channels'] = $top_channels_query->fetch_all(MYSQLI_ASSOC);

$top_categories_query = $mysqli->query("
    SELECT cat.name, COUNT(DISTINCT v.viewer_uid) AS view_count
    FROM viewers v JOIN channels c ON v.channel_id = c.id JOIN categories cat ON c.category_id = cat.id
    WHERE v.last_active > NOW() - INTERVAL 20 SECOND
    GROUP BY c.category_id ORDER BY view_count DESC LIMIT 5
");
$response['top_categories'] = $top_categories_query->fetch_all(MYSQLI_ASSOC);

// --- 2. بيانات الرسم البياني لآخر 24 ساعة (مع مراعاة التوقيت) ---
$history_query = $mysqli->query("
    SELECT 
        DATE_FORMAT(last_active, '%Y-%m-%d %H:00:00') AS hour_slot,
        COUNT(DISTINCT viewer_uid) AS historical_view_count
    FROM viewers 
    WHERE last_active > NOW() - INTERVAL 24 HOUR
    GROUP BY hour_slot 
    ORDER BY hour_slot ASC
");
$chart_labels = []; $chart_data_points = []; $temp_history = [];
while($row = $history_query->fetch_assoc()){ $temp_history[$row['hour_slot']] = $row['historical_view_count']; }
// إنشاء تسميات للساعات بتوقيت GMT+3
$now = new DateTime("now", new DateTimeZone('Asia/Baghdad')); // <--- استخدام توقيت بغداد
for ($i = 23; $i >= 0; $i--) {
    $hour_datetime = (clone $now)->modify("-$i hour");
    $hour_key = $hour_datetime->format('Y-m-d H:00:00');
    $chart_labels[] = $hour_datetime->format('g A');
    $chart_data_points[] = $temp_history[$hour_key] ?? 0;
}
$response['chart_data'] = ['labels' => $chart_labels, 'data' => $chart_data_points];


// --- 3. الإحصائيات التاريخية حسب الفترة ---
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$end_date_for_query = $end_date . ' 23:59:59';

$historical_stmt = $mysqli->prepare("
    SELECT c.name, COUNT(h.id) AS total_sessions
    FROM viewing_history h
    JOIN channels c ON h.channel_id = c.id
    WHERE h.session_start BETWEEN ? AND ?
    GROUP BY h.channel_id
    ORDER BY total_sessions DESC
    LIMIT 10
");
$historical_stmt->bind_param("ss", $start_date, $end_date_for_query);
$historical_stmt->execute();
$historical_result = $historical_stmt->get_result();
$response['historical_top_channels'] = $historical_result->fetch_all(MYSQLI_ASSOC);
$historical_stmt->close();

$mysqli->close();
echo json_encode($response);
?>