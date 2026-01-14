<?php
session_start();

// Destroy all session data
$_SESSION = array();

// If you want to destroy the session completely, delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to landing page
header("Location: ../index.php");
exit();
?>