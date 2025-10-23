<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function is_admin() {
    return isset($_SESSION['role_id']) && $_SESSION['role_id'] === 1;
}

// Check if user is regular user
function is_user() {
    return isset($_SESSION['role_id']) && $_SESSION['role_id'] === 2;
}
?>