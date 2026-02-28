<?php
require_once 'config.php';
session_start();
if (!isset($_SESSION['user'])) { header('Location: login.php'); exit(); }

$mysqli = new mysqli("localhost", "tv_admin", "TvPassword2026!", "tv_db");

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=viewers_export.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['Channel', 'IP Address', 'Sessions', 'Total Traffic (MB)', 'First Seen', 'Last Seen']);

$query = "
    SELECT v.channel_id, c.name AS channel_name, v.ip_address, COUNT(*) as session_count,
           SUM(v.data_transferred) as total_data,
           MIN(v.created_at) AS first_seen,
           MAX(v.last_active) AS last_seen
    FROM viewers v
    LEFT JOIN channels c ON c.id = v.channel_id
    GROUP BY v.channel_id, v.ip_address
    ORDER BY last_seen DESC
";

$result = $mysqli->query($query);
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['channel_name'],
        $row['ip_address'],
        $row['session_count'],
        number_format($row['total_data'] / 1024 / 1024, 2),
        $row['first_seen'],
        $row['last_seen']
    ]);
}

fclose($output);
exit;
?>
