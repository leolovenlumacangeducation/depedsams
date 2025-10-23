<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_logged_in = isset($_SESSION['user_id']);

// If the page is not public and the user is not logged in, redirect to login.
if (!isset($is_public_page) && !$is_logged_in) {
    header("Location: login.php");
    exit;
}

// Include the database connection to fetch the favicon
require_once 'db.php';

// Fetch school settings and user photo
try {
    // Fetch school logo for favicon
    $school_stmt = $pdo->query("SELECT logo FROM tbl_school LIMIT 1");
    $school_settings = $school_stmt->fetch();
    $favicon_path = 'assets/uploads/school/' . ($school_settings['logo'] ?? 'default_logo.png');

    $user_photo_path = 'assets/uploads/users/default_user.png';
    if ($is_logged_in) {
        // Fetch current user's photo only if logged in
        $user_stmt = $pdo->prepare("SELECT photo FROM tbl_user WHERE user_id = ?");
        $user_stmt->execute([$_SESSION['user_id']]);
        $user_photo_filename = $user_stmt->fetchColumn();
        $user_photo_path = 'assets/uploads/users/' . ($user_photo_filename ?: 'default_user.png');
    }

} catch (PDOException $e) {
    // If DB fails, use a default. Don't break the page.
    $favicon_path = 'assets/uploads/school/default_logo.png';
    $user_photo_path = 'assets/uploads/users/default_user.png';
    error_log("Header DB Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="<?= $favicon_path ?>" type="image/png">
    <link rel="apple-touch-icon" href="<?= $favicon_path ?>">
    <title>SAMS - DASHBOARD</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <!-- Custom Styles -->
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/topbar.css">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.1/dist/sweetalert2.min.css" rel="stylesheet">
</head>
<body <?php if ($is_logged_in): ?>data-user-id="<?= htmlspecialchars($_SESSION['user_id'] ?? '') ?>" data-user-role="<?= htmlspecialchars($_SESSION['role'] ?? '') ?>"<?php endif; ?>>

<?php if (!isset($is_public_page) || $is_public_page !== true): // Only render header on non-public pages ?>
    <header class="navbar navbar-dark bg-dark flex-md-nowrap p-0 shadow" style="position:fixed;top:0;left:0;right:0;z-index:1030;">
        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3 fs-6 d-flex align-items-center" href="index.php">
            <img src="<?= htmlspecialchars($favicon_path) ?>" alt="Logo" class="me-2 rounded" style="width:28px;height:28px;object-fit:cover;">
            <span class="d-none d-md-inline sams-brand-full">DEPED ASSET MANAGEMENT</span>
            <span class="d-md-none">SAMS</span>
        </a>
        <?php if ($is_logged_in): // Only show the sidebar toggle if logged in ?>
            <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
        <?php endif; ?>
        
        <?php if ($is_logged_in): // Only show user profile if logged in ?>
            <!-- Topbar Search (hidden on small screens) -->
            <form id="topSearchForm" class="d-none d-lg-flex ms-3" role="search" aria-label="Quick asset search">
                <input id="topSearchInput" class="form-control form-control-sm" type="search" placeholder="" aria-label="Search">
                <div id="topSearchResults" class="list-group position-absolute mt-1 d-none" style="z-index:1200; min-width:280px;"></div>
            </form>
            <div class="navbar-nav ms-auto">
                    <div class="d-flex align-items-center gap-2">
                    <div class="nav-item dropdown text-nowrap">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="<?= htmlspecialchars($user_photo_path) ?>" alt="User Photo" class="rounded-circle me-2" style="width: 28px; height: 28px; object-fit: cover;">
                            <span class="d-none d-sm-inline"><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                            <li class="px-3 py-2 small text-muted">Notifications</li>
                            <li id="userDropdownNotifications" class="px-3 py-1 small">No alerts</li>
                            <li><hr class="dropdown-divider"></li>
                            <li class="dropdown-header">Theme</li>
                            <li><button class="dropdown-item theme-option" data-theme="dark" type="button">Dark</button></li>
                            <li><button class="dropdown-item theme-option" data-theme="light" type="button">Light</button></li>
                            <li><button class="dropdown-item theme-option" data-theme="grey" type="button">Grey (Minimal)</button></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="my_profile.php"><i class="bi bi-person me-2"></i> My Profile</a></li>
                            <li><a class="dropdown-item" href="receive.php"><i class="bi bi-box-arrow-in-down me-2"></i> Receive Items</a></li>
                            <li><a class="dropdown-item" href="scan.php"><i class="bi bi-qr-code-scan me-2"></i> Scan Items</a></li>
                            <li><a class="dropdown-item" href="my_inventory.php"><i class="bi bi-person-workspace me-2"></i> My Inventory</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Sign Out</a></li>
                        </ul>
                    </div>
                    <!-- mobile consolidated controls: visible only on very small screens -->
                    <div class="nav-item d-block d-sm-none">
                        <div class="dropdown">
                            <button id="mobileControlsBtn" class="btn btn-link text-white p-2" data-bs-toggle="dropdown" aria-expanded="false" aria-label="More">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul id="mobileControlsMenu" class="dropdown-menu dropdown-menu-end" aria-labelledby="mobileControlsBtn">
                                <!-- populated by JS: notifications, theme options, profile links -->
                                <li class="px-3 py-2 small text-muted">Loading...</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </header>
<?php endif; ?>

    <!-- spacer to prevent content from being hidden under the fixed header -->
    <div style="height:56px;flex-shrink:0;"></div>

<div class="container-fluid mt-0" style="padding-top:0;">
    <div class="row">