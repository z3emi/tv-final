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


function format_bytes_value(float $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $index = 0;
    while ($bytes >= 1024 && $index < count($units) - 1) {
        $bytes /= 1024;
        $index++;
    }
    return number_format($bytes, 1) . ' ' . $units[$index];
}

function format_uptime_seconds(int $seconds): string {
    $days = intdiv($seconds, 86400);
    $hours = intdiv($seconds % 86400, 3600);
    $minutes = intdiv($seconds % 3600, 60);

    if ($days > 0) {
        return sprintf('%d يوم %02d:%02d', $days, $hours, $minutes);
    }

    return sprintf('%02d:%02d', $hours, $minutes);
}

function collect_server_stats(): array {
    $stats = [
        'cpu_load_1m' => null,
        'cpu_load_text' => 'غير متاح',
        'memory_used_percent' => null,
        'memory_used_text' => 'غير متاح',
        'disk_used_percent' => null,
        'disk_used_text' => 'غير متاح',
        'uptime_seconds' => null,
        'uptime_text' => 'غير متاح',
    ];

    if (function_exists('sys_getloadavg')) {
        $load = sys_getloadavg();
        if (isset($load[0])) {
            $stats['cpu_load_1m'] = (float)$load[0];
            $stats['cpu_load_text'] = number_format($load[0], 2);
        }
    }

    if (is_readable('/proc/meminfo')) {
        $meminfo = file('/proc/meminfo', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $memData = [];
        foreach ($meminfo as $line) {
            if (strpos($line, ':') !== false) {
                [$key, $value] = explode(':', $line, 2);
                $memData[trim($key)] = (int)filter_var($value, FILTER_SANITIZE_NUMBER_INT);
            }
        }
        if (!empty($memData['MemTotal']) && isset($memData['MemAvailable'])) {
            $totalBytes = $memData['MemTotal'] * 1024;
            $availableBytes = $memData['MemAvailable'] * 1024;
            $usedBytes = max(0, $totalBytes - $availableBytes);
            $usedPercent = $totalBytes > 0 ? ($usedBytes / $totalBytes) * 100 : null;

            if ($usedPercent !== null) {
                $stats['memory_used_percent'] = round($usedPercent, 2);
                $stats['memory_used_text'] = sprintf(
                    '%s / %s (%s%%)',
                    format_bytes_value($usedBytes),
                    format_bytes_value($totalBytes),
                    number_format($usedPercent, 1)
                );
            }
        }
    }

    $diskTotal = @disk_total_space(APP_BASE_DIR);
    $diskFree = @disk_free_space(APP_BASE_DIR);
    if ($diskTotal && $diskFree !== false) {
        $diskUsed = $diskTotal - $diskFree;
        $diskUsedPercent = $diskTotal > 0 ? ($diskUsed / $diskTotal) * 100 : null;
        if ($diskUsedPercent !== null) {
            $stats['disk_used_percent'] = round($diskUsedPercent, 2);
            $stats['disk_used_text'] = sprintf(
                '%s / %s (%s%%)',
                format_bytes_value($diskUsed),
                format_bytes_value($diskTotal),
                number_format($diskUsedPercent, 1)
            );
        }
    }

    if (is_readable('/proc/uptime')) {
        $raw = trim((string)@file_get_contents('/proc/uptime'));
        if ($raw !== '') {
            $parts = explode(' ', $raw);
            $uptimeSeconds = (int)floor((float)$parts[0]);
            $stats['uptime_seconds'] = $uptimeSeconds;
            $stats['uptime_text'] = format_uptime_seconds($uptimeSeconds);
        }
    }

    return $stats;
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

$data['server_stats'] = collect_server_stats();

// إرسال البيانات المدمجة النهائية
echo json_encode($data);
?>