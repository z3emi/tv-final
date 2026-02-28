<?php
require_once 'config.php';
if (!isset($_SESSION['user'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$commands_dir = 'R:/live/commands';
$channels_file = 'C:/nginx/html/channels.txt'; // <--- !! تأكد أن هذا المسار صحيح

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$channel_id = $_POST['id'] ?? '';

if (empty($action) || empty($channel_id) || !in_array($action, ['restart', 'stop', 'start'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters.']);
    exit();
}

// المنطق الجديد للتعامل مع start/stop
if ($action === 'start' || $action === 'stop') {
    if (!file_exists($channels_file) || !is_writable($channels_file)) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'channels.txt not found or not writable.']);
        exit();
    }
    
    $lines = file($channels_file);
    $output = '';
    $found = false;
    foreach ($lines as $line) {
        // نزيل # من بداية السطر المؤقتًا للمقارنة
        $clean_line = ltrim(trim($line), '#');
        if (strpos($clean_line, $channel_id . '|') === 0) {
            $found = true;
            if ($action === 'stop') {
                // أضف # فقط إذا لم تكن موجودة بالفعل
                $output .= str_starts_with(trim($line), '#') ? $line : '#' . $line;
            } else { // action is 'start'
                // أزل # من بداية السطر
                $output .= ltrim($line, '#');
            }
        } else {
            $output .= $line;
        }
    }

    if ($found) {
        file_put_contents($channels_file, $output);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Channel ID not found in channels.txt.']);
        exit();
    }
}

// إنشاء ملف الأمر لسكربت البايثون (فقط للإيقاف أو إعادة التشغيل)
if ($action === 'restart' || $action === 'stop'){
    if (!is_dir($commands_dir)) {
        mkdir($commands_dir, 0777, true);
    }
    $command_file = $commands_dir . '/' . $action . '_' . $channel_id . '.cmd';
    file_put_contents($command_file, '');
}

echo json_encode(['status' => 'success', 'message' => 'Command processed successfully.']);
?>