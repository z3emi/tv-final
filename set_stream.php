<?php
require_once 'config.php';
session_start();

$id = $_POST['id'] ?? 0;
$response = ['success' => false];

if ($id > 0) {
    $mysqli = new mysqli("localhost", "root", "", "stream_db");
    $stmt = $mysqli->prepare("SELECT stream_url FROM channels WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($channel = $result->fetch_assoc()) {
        // زيادة عداد المشاهدات
        $mysqli->query("UPDATE channels SET view_count = view_count + 1 WHERE id = " . intval($id));

        // كتابة رابط القناة الجديدة فقط
        file_put_contents('stream_url.txt', $channel['stream_url']);
        $response['success'] = true;
    }
}

header('Content-Type: application/json');
echo json_encode($response);
exit();
?>