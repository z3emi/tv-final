<?php
// start_stream.php (API Version)

// No session check needed for viewers
// No redirection

require_once 'config.php';

// Set header to return a plain text response
header('Content-Type: text/plain');

// Get the channel ID from the request
$id = $_GET['id'] ?? 0;

if (!is_numeric($id) || $id <= 0) {
    http_response_code(400); // Bad Request
    echo "Error: Invalid Channel ID.";
    exit();
}

// Connect to the database
$mysqli = new mysqli("localhost", "root", "", "stream_db");
if ($mysqli->connect_errno) {
    http_response_code(500); // Internal Server Error
    echo "Error: Failed to connect to database.";
    exit();
}

// Prepare and execute the query
$stmt = $mysqli->prepare("SELECT stream_url FROM channels WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($channel = $result->fetch_assoc()) {
    // Write the new stream URL to the config file
    // The background process will read this file.
    file_put_contents('stream_url.txt', $channel['stream_url']);

    // Send a success response
    http_response_code(200); // OK
    echo "Success: Stream source updated for channel ID " . $id;
} else {
    // Send a "not found" response
    http_response_code(404); // Not Found
    echo "Error: Channel with ID " . $id . " not found.";
}

$stmt->close();
$mysqli->close();
exit();
?>