<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
// ابدأ الجلسة في مكان واحد مركزي
session_start();

// بيانات الاتصال بقاعدة البيانات
$db_host = "localhost";
$db_user = 'tv_admin';
$db_pass = "TvPassword2026!";
$db_name = "tv_db";

// أنشئ متغير اتصال واحد ومتناسق
$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);

// تحقق من وجود أخطاء في الاتصال
if ($mysqli->connect_error) {
    die("❌ فشل الاتصال بقاعدة البيانات: " . $mysqli->connect_error);
}

$mysqli->set_charset("utf8mb4");
$mysqli->query("SET time_zone = '+03:00'");

if (!defined('APP_BASE_DIR')) {
    define('APP_BASE_DIR', __DIR__);
}
if (!defined('LIVE_ROOT')) {
    $liveRoot = getenv('TV_LIVE_ROOT') ?: APP_BASE_DIR . '/live';
    define('LIVE_ROOT', rtrim($liveRoot, '/\\'));
}
if (!defined('COMMANDS_DIR')) {
    define('COMMANDS_DIR', LIVE_ROOT . '/commands');
}
if (!defined('CHANNELS_FILE_PATH')) {
    define('CHANNELS_FILE_PATH', APP_BASE_DIR . '/channels.txt');
}

function build_local_stream_links(string $channelId): array {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $safeId = rawurlencode($channelId);
    $base = "{$scheme}://{$host}/live/channel_{$safeId}";

    return [
        'stream_link' => $base . '/stream.m3u8',
        'audio_link' => $base . '/audio.m3u8',
    ];
}
?>
