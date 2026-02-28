<?php
require_once 'config.php'; // Use the central connection file

header('Content-Type: application/json; charset=utf-8');

$channel_id = $_GET['id'] ?? 0;
$search_ip = trim($_GET['search_ip'] ?? '');

if (!filter_var($channel_id, FILTER_VALIDATE_INT) || $channel_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid Channel ID']);
    exit();
}

function parse_user_agent($ua) {
    if (empty($ua)) return ['device' => 'Unknown', 'icon' => 'bi-question-circle'];
    $os = 'Unknown OS'; $browser = 'Unknown Browser'; $icon = 'bi-display';
    if (preg_match('/windows nt 10/i', $ua)) $os = 'Windows';
    elseif (preg_match('/android/i', $ua)) $os = 'Android';
    elseif (preg_match('/iphone|ipad|ipod/i', $ua)) $os = 'iOS';
    elseif (preg_match('/mac os x/i', $ua)) $os = 'macOS';
    elseif (preg_match('/linux/i', $ua)) $os = 'Linux';
    if (preg_match('/firefox/i', $ua)) $browser = 'Firefox';
    elseif (preg_match('/edg/i', $ua)) $browser = 'Edge';
    elseif (preg_match('/chrome/i', $ua) && !preg_match('/edg/i', $ua)) $browser = 'Chrome';
    elseif (preg_match('/safari/i', $ua) && !preg_match('/chrome/i', $ua)) $browser = 'Safari';
    if ($os === 'Windows') $icon = 'bi-windows';
    elseif ($os === 'Android') $icon = 'bi-android2';
    elseif ($os === 'iOS' || $os === 'macOS') $icon = 'bi-apple';
    return ['device' => "$os / $browser", 'icon' => $icon];
}

$where_clauses = ["channel_id = ?"];
$params = [$channel_id];
$types = "i";
if (!empty($search_ip)) {
    $where_clauses[] = "ip_address LIKE ?";
    $params[] = "%" . $search_ip . "%";
    $types .= "s";
}
$where_sql = implode(" AND ", $where_clauses);

$stats_sql = "SELECT COUNT(*) as total_sessions, AVG(TIMESTAMPDIFF(SECOND, session_start, last_seen)) as avg_duration_seconds FROM viewing_history WHERE $where_sql";
$stats_stmt = $mysqli->prepare($stats_sql);
$stats_stmt->bind_param($types, ...$params);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result()->fetch_assoc();

$avg_seconds_total = (int)($stats_result['avg_duration_seconds'] ?? 0);
$avg_minutes = floor($avg_seconds_total / 60);
$avg_seconds = $avg_seconds_total % 60;
$stats = [
    'total_sessions' => (int)($stats_result['total_sessions'] ?? 0),
    'avg_duration_formatted' => "$avg_minutes دقيقة و $avg_seconds ثانية"
];

// --- بداية التعديل: جعل قاعدة البيانات تحدد الجلسات النشطة ---
$history_sql = "
    SELECT 
        viewer_uid, ip_address, user_agent, session_start, last_seen,
        (last_seen > NOW() - INTERVAL 20 SECOND) AS is_active 
    FROM viewing_history 
    WHERE $where_sql 
    ORDER BY id DESC 
    LIMIT 200
";
// --- نهاية التعديل ---

$history_stmt = $mysqli->prepare($history_sql);
$history_stmt->bind_param($types, ...$params);
$history_stmt->execute();
$history_result = $history_stmt->get_result();

$history = [];
while ($row = $history_result->fetch_assoc()) {
    $start = new DateTime($row['session_start']);
    $end = new DateTime($row['last_seen']);
    $diff = $start->diff($end);
    $device_info = parse_user_agent($row['user_agent']);
    
    $history[] = [
        'viewer_uid' => htmlspecialchars($row['viewer_uid']),
        'ip_address' => htmlspecialchars($row['ip_address']),
        'device' => $device_info['device'],
        'device_icon' => $device_info['icon'],
        'session_start' => date('Y-m-d H:i:s', strtotime($row['session_start'])),
        'last_seen' => date('Y-m-d H:i:s', strtotime($row['last_seen'])),
        'duration_formatted' => $diff->format('%h س, %i د, %s ث'),
        'is_active' => (bool)$row['is_active'] // استخدام القيمة مباشرة من قاعدة البيانات
    ];
}

echo json_encode([
    'stats' => $stats,
    'history' => $history
]);

$stats_stmt->close();
$history_stmt->close();
$mysqli->close();
?>
