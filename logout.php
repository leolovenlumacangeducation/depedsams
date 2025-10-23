<?php
/**
 * Logout Script
 *
 * This script handles the user logout process. It destroys the current session
 * and redirects the user to the login page.
 */

// 1. Start the session to access it.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Unset all of the session variables.
$_SESSION = [];

// 3. Destroy the session cookie.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}

// 4. Finally, destroy the session.
session_destroy();

// 5. Redirect to the login page.
header("Location: login.php");
exit;
?>