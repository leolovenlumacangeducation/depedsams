<?php
// Function to check if the current page matches a given link
function isUserPageActive($link) {
    $currentPage = basename($_SERVER['PHP_SELF']);
    return $currentPage == $link ? 'active' : 'link-dark';
}

// Get inventory count for the logged-in user
$my_inventory_count = 0;
if (isset($_SESSION['user_id'])) {
    if (!isset($pdo)) {
        require_once dirname(__DIR__) . '/db.php';
    }
    try {
        $userId = (int) $_SESSION['user_id'];
        // PPE
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_ppe WHERE assigned_to_user_id = ?");
        $stmt->execute([$userId]);
        $my_inventory_count += (int) $stmt->fetchColumn();

        // SEP
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_sep WHERE assigned_to_user_id = ?");
        $stmt->execute([$userId]);
        $my_inventory_count += (int) $stmt->fetchColumn();

        // Consumables
        $cols = $pdo->query("SHOW COLUMNS FROM tbl_consumable LIKE 'custodian_user_id'")->fetchAll();
        if (count($cols) > 0) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_consumable WHERE custodian_user_id = ?");
            $stmt->execute([$userId]);
            $my_inventory_count += (int) $stmt->fetchColumn();
        }
    } catch (Exception $e) {
        $my_inventory_count = 0;
    }
}
?>

<!-- Sidebar -->
<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
    <div class="position-sticky sidebar-sticky">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link text-white <?php echo isUserPageActive('index.php'); ?>" href="index.php">
                    <i class="bi bi-speedometer2 me-2"></i>
                    <span class="align-middle">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo isUserPageActive('my_inventory.php'); ?>" href="my_inventory.php">
                    <i class="bi bi-person-lines-fill me-2"></i>
                    <span class="align-middle">My Inventory</span>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <span class="badge bg-primary rounded-pill ms-auto"><?php echo (int) $my_inventory_count; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo isUserPageActive('my_profile.php'); ?>" href="my_profile.php">
                    <i class="bi bi-person me-2"></i>
                    <span class="align-middle">My Profile</span>
                </a>
            </li>
        </ul>

    </div>
</nav>