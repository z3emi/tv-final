<?php
// Start the session only once here for all admin pages.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Basic session check for all admin pages
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

// Function to check for admin-only pages
function require_admin() {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        // Redirect non-admins to the dashboard with an error message
        header('Location: dashboard.php?error=unauthorized');
        exit();
    }
}
?>