<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Only allow role_id 2 (users) to access user pages
if ($_SESSION['role_id'] != 2) {
    header('Location: index.php');
    exit();
}

// Include the database connection to fetch user info
require_once 'db.php';

// Fetch school settings and user photo
try {
    // Fetch school logo for favicon
    $school_stmt = $pdo->query("SELECT logo FROM tbl_school LIMIT 1");
    $school_settings = $school_stmt->fetch();
    $favicon_path = 'assets/uploads/school/' . ($school_settings['logo'] ?? 'default_logo.png');

    // Fetch current user's photo
    $user_stmt = $pdo->prepare("SELECT photo FROM tbl_user WHERE user_id = ?");
    $user_stmt->execute([$_SESSION['user_id']]);
    $user_photo_filename = $user_stmt->fetchColumn();
    $user_photo_path = 'assets/uploads/users/' . ($user_photo_filename ?: 'default_user.png');
} catch (PDOException $e) {
    $favicon_path = 'assets/uploads/school/default_logo.png';
    $user_photo_path = 'assets/uploads/users/default_user.png';
    error_log("Header User DB Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="<?= $favicon_path ?>" type="image/png">
    <link rel="apple-touch-icon" href="<?= $favicon_path ?>">
    <title>SAMS - User Dashboard</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.1/dist/sweetalert2.min.css" rel="stylesheet">
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/topbar.css">
    <!-- User-specific styles -->
    <link href="assets/css/user/main.css" rel="stylesheet">
    <link href="assets/css/user/sidebar.css" rel="stylesheet">
</head>
<body data-user-id="<?= htmlspecialchars($_SESSION['user_id'] ?? '') ?>" data-user-role="user">
    <header class="navbar navbar-dark bg-dark flex-md-nowrap p-0 shadow" style="position:fixed;top:0;left:0;right:0;z-index:1030;">
    <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3 fs-6 d-flex align-items-center" href="index.php">
            <img src="<?= htmlspecialchars($favicon_path) ?>" alt="Logo" class="me-2 rounded" style="width:28px;height:28px;object-fit:cover;">
            <span class="d-none d-md-inline sams-brand-full">DEPED ASSET MANAGEMENT</span>
            <span class="d-md-none">SAMS</span>
        </a>

        <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="navbar-nav ms-auto">
            <div class="d-flex align-items-center gap-2">
                <div class="nav-item dropdown text-nowrap">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="<?= htmlspecialchars($user_photo_path) ?>" alt="User Photo" class="rounded-circle me-2" style="width: 28px; height: 28px; object-fit: cover;">
                        <span class="d-none d-sm-inline"><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                        <li><a class="dropdown-item" href="my_profile.php"><i class="bi bi-person me-2"></i> My Profile</a></li>
                        <li><a class="dropdown-item" href="my_inventory.php"><i class="bi bi-person-workspace me-2"></i> My Inventory</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Sign Out</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <!-- spacer to prevent content from being hidden under the fixed header -->
    <div style="height:56px;flex-shrink:0;"></div>

<div class="container-fluid mt-0" style="padding-top:0;">
    <div class="row">