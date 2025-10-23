<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Ensure only admins may access pages that include this guard
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] !== 1) {
    // Non-admins get redirected to user dashboard or login
    if (isset($_SESSION['role_id']) && $_SESSION['role_id'] === 2) {
        header('Location: users/index.php');
        exit;
    }
    header('Location: login.php');
    exit;
}
