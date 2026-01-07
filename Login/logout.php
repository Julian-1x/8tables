<?php
session_start();

// Add audit log before destroying session
try {
    require_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if (isset($_SESSION['user_id'])) {
        $username = $_SESSION['username'] ?? 'User';
        addAuditLog($db, $_SESSION['user_id'], 'USER_LOGOUT', "User {$username} logged out");
    }
} catch (Exception $e) {
    error_log("Audit log error: " . $e->getMessage());
}

// Destroy the session
session_destroy();

// Redirect to login page with success message
header("Location: login.php?message=logged_out");
exit();
?>