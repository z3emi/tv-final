<?php
$mysqli = new mysqli("localhost", "root", "", "stream_db");
$id = $_GET["id"];
$mysqli->query("DELETE FROM channels WHERE id = $id");

// إعادة إنشاء ملف القنوات
$channels = [];
$res = $mysqli->query("SELECT id, stream_url FROM channels WHERE is_active = 1");
while ($row = $res->fetch_assoc()) {
    $channels[] = $row["id"] . "|" . $row["stream_url"];
}
file_put_contents("channels.txt", implode(PHP_EOL, $channels) . PHP_EOL);

header("Location: channels.php");
exit();
?>