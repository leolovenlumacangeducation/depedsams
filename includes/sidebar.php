<?php
// A simple function to check if the current page matches a given link
function isActive($link) {
    // This gets the script name (e.g., 'index.php') from the full path
    $currentPage = basename($_SERVER['PHP_SELF']);
    if ($currentPage == $link) {
        return 'active';
    }
    return 'link-dark'; // Return link-dark if not active
}

// A function to check if any link within a submenu is active
function isSubmenuActive($links) {
    $currentPage = basename($_SERVER['PHP_SELF']);
    foreach ($links as $link) {
        if ($currentPage == $link) {
            return 'show';
        }
    }
    return '';
}

// Group pages for submenu logic (updated for disposal)
$inventory_pages = ['consumables.php', 'sep.php', 'ppe.php']; // Kept for active state logic
$acquisition_pages = ['po_list.php', 'incoming_ics_list.php', 'receive.php']; // Kept for active state logic
$generate_reports_pages = ['rsmi_report.php', 'rpci_report.php', 'rpcppe_report.php', 'iirup_report.php', 'qr_generator.php'];
$processed_docs_pages = ['po_reprint_list.php', 'ris_list.php', 'ics_list.php', 'par_list.php', 'rpcppe_list.php', 'rpci_list.php', 'iar_list.php', 'iirup_list.php'];
$reports_pages = array_merge($generate_reports_pages, $processed_docs_pages);
$management_pages = ['users.php', 'suppliers.php', 'school_settings.php', 'lookups.php', 'category.php'];
$system_pages = ['reset_data.php'];
$numbering_pages = ['po_no.php', 'ris_no.php', 'sn_no.php', 'pn_no.php', 'ics_no.php', 'par_no.php', 'rpci_no.php', 'rpcppe_no.php'];

// Combine management and numbering for the main settings dropdown
$setup_admin_pages = array_merge($management_pages, $numbering_pages, $system_pages);


?>

<?php
// Compute a quick badge count for 'My Inventory' (PPE + SEP + Consumables) for the logged-in user.
$my_inventory_count = 0;
if (isset($_SESSION['user_id'])) {
    // include db only when needed
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

        // Consumables - only if custodian_user_id column exists
        $cols = $pdo->query("SHOW COLUMNS FROM tbl_consumable LIKE 'custodian_user_id'")->fetchAll();
        if (count($cols) > 0) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_consumable WHERE custodian_user_id = ?");
            $stmt->execute([$userId]);
            $my_inventory_count += (int) $stmt->fetchColumn();
        }
    } catch (Exception $e) {
        // If DB fails, just leave count at 0
        $my_inventory_count = 0;
    }
}


    ?>

    <style>
    #sidebarMenu .nav-link {
        font-size: 0.9rem;
        padding: 0.6rem 1rem;
        display: flex;
        align-items: center;
        position: relative;
    }
    #sidebarMenu .nav-link .bi {
        font-size: 0.9rem;
        line-height: 1;
    }
    #sidebarMenu .badge {
        margin-left: auto;
    }
</style>

<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3" style="height: calc(100vh - 48px); overflow-y: auto;">
        <?php
        // If the logged-in user is a plain 'User', show only Dashboard and My Inventory
        $is_plain_user = isset($_SESSION['role_name']) && $_SESSION['role_name'] === 'User';
        ?>
        <ul class="nav flex-column" style="padding-bottom: 60px;">
            <?php if ($is_plain_user): ?>
                <li class="nav-item">
                    <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'index.php' || basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : 'link-dark' ?>" href="index.php">
                        <i class="bi bi-grid-1x2-fill me-2"></i>
                        Dashboard
                        <span id="low-stock-badge" class="badge bg-danger rounded-pill float-end d-none"></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= isActive('my_inventory.php') ?>" href="my_inventory.php">
                        <i class="bi bi-person-lines-fill me-2"></i>
                        My Inventory
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <span class="badge bg-primary rounded-pill float-end"><?= (int) $my_inventory_count ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php else: ?>
                <!-- Full menu for admins and other roles -->
                <ul class="nav flex-column" style="padding-bottom: 60px;">
                    <li class="nav-item">
                        <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'index.php' || basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : 'link-dark' ?>" href="index.php">
                            <i class="bi bi-grid-1x2-fill me-2"></i>
                            Dashboard
                            <span id="low-stock-badge" class="badge bg-danger rounded-pill float-end d-none"></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= isActive('my_inventory.php') ?>" href="my_inventory.php">
                            <i class="bi bi-person-lines-fill me-2"></i>
                            My Inventory
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <span class="badge bg-primary rounded-pill float-end"><?= (int) $my_inventory_count ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <hr>

                    <!-- Acquisition -->
                    <li class="nav-item">
                        <a class="nav-link" href="#acquisition-submenu" data-bs-toggle="collapse" aria-expanded="true">
                            <i class="bi bi-cart-plus-fill"></i> Acquisition
                        </a>
                        <div class="collapse show" id="acquisition-submenu">
                            <ul class="nav flex-column ms-4">
                                <li class="nav-item"><a class="nav-link <?= isActive('po_list.php') ?>" href="po_list.php"><i class="bi bi-receipt"></i> Purchase Order</a></li>
                                <li class="nav-item"><a class="nav-link <?= isActive('incoming_ics_list.php') ?>" href="incoming_ics_list.php"><i class="bi bi-box-arrow-in-down"></i> via ICS</a></li>
                            </ul>
                        </div>
                    </li>

                    <hr>

                    <!-- Inventory Management (hidden for role_id = 2 => User) -->
                    <?php if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] !== 2): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="#inventory-submenu" data-bs-toggle="collapse" aria-expanded="true">
                            <i class="bi bi-box-seam-fill"></i> Inventory Management
                        </a>
                        <div class="collapse show" id="inventory-submenu">
                            <ul class="nav flex-column ms-4">
                                <li class="nav-item"><a class="nav-link <?= isActive('consumables.php') ?>" href="consumables.php"><i class="bi bi-droplet-half"></i> Supplies & Materials</a></li>
                                <li class="nav-item"><a class="nav-link <?= isActive('sep.php') ?>" href="sep.php"><i class="bi bi-pc-display"></i> Semi-Expendable</a></li>
                                <li class="nav-item"><a class="nav-link <?= isActive('ppe.php') ?>" href="ppe.php"><i class="bi bi-building"></i> PPE</a></li>
                                <?php /* Disposed Items removed - use Disposal (IIRUP) page instead */ ?>
                            </ul>
                        </div>
                    </li>
                    <?php endif; ?>
                    <?php if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] !== 2): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= isActive('disposal.php') ?>" href="disposal.php">
                            <i class="bi bi-trash"></i>
                            Disposal (IIRUP)
                        </a>
                    </li>
                    <?php endif; ?>
                    <hr>
                    <?php if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] !== 2): ?>
                    <!-- Reports -->
                    <li class="nav-item">
                        <a class="nav-link collapsed" href="#reports-submenu" data-bs-toggle="collapse" aria-expanded="false">
                            <i class="bi bi-bar-chart-line-fill"></i> REPORTS
                        </a>
                        <div class="collapse <?= isSubmenuActive($reports_pages) ?>" id="reports-submenu">
                            <ul class="nav flex-column ms-4">
                                <li class="nav-item">
                                    <a class="nav-link collapsed ps-0" href="#generate-reports-submenu" data-bs-toggle="collapse" aria-expanded="false">Generate</a>
                                    <div class="collapse <?= isSubmenuActive($generate_reports_pages) ?>" id="generate-reports-submenu">
                                        <ul class="nav flex-column ms-4">
                                            <li class="nav-item"><a class="nav-link <?= isActive('rsmi_report.php') ?>" href="rsmi_report.php">RSMI</a></li>
                                            <li class="nav-item"><a class="nav-link <?= isActive('rpci_report.php') ?>" href="rpci_report.php">RPCI</a></li>
                                            <li class="nav-item"><a class="nav-link <?= isActive('rpcppe_report.php') ?>" href="rpcppe_report.php">RPCPPE</a></li>
                                            <li class="nav-item"><a class="nav-link <?= isActive('iirup_report.php') ?>" href="iirup_report.php">IIRUP</a></li>
                                            <?php if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1): ?>
                                                <li class="nav-item"><a class="nav-link <?= isActive('qr_generator.php') ?>" href="qr_generator.php">QR Generator</a></li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link collapsed ps-0" href="#documents-submenu" data-bs-toggle="collapse" aria-expanded="false">Documents</a>
                                    <div class="collapse <?= isSubmenuActive($processed_docs_pages) ?>" id="documents-submenu">
                                        <ul class="nav flex-column ms-4">
                                            <li class="nav-item"><a class="nav-link <?= isActive('po_reprint_list.php') ?>" href="po_reprint_list.php">Purchase Orders</a></li>
                                            <li class="nav-item"><a class="nav-link <?= isActive('ris_list.php') ?>" href="ris_list.php">RIS</a></li>
                                            <li class="nav-item"><a class="nav-link <?= isActive('ics_list.php') ?>" href="ics_list.php">ICS</a></li>
                                            <li class="nav-item"><a class="nav-link <?= isActive('par_list.php') ?>" href="par_list.php">PAR</a></li>
                                            <li class="nav-item"><a class="nav-link <?= isActive('iar_list.php') ?>" href="iar_list.php">IAR</a></li>
                                            <li class="nav-item"><a class="nav-link <?= isActive('rpcppe_list.php') ?>" href="rpcppe_list.php">RPCPPE</a></li>
                                            <li class="nav-item"><a class="nav-link <?= isActive('rpci_list.php') ?>" href="rpci_list.php">RPCI</a></li>
                                            <li class="nav-item"><a class="nav-link <?= isActive('iirup_list.php') ?>" href="iirup_list.php">IIRUP</a></li>
                                        </ul>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </li>
                    <?php endif; ?>
                    <hr>
                     <?php if (isset($_SESSION['role_name']) && $_SESSION['role_name'] == 'Admin'): ?>
                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted text-uppercase">
                        <a class="nav-link collapsed p-0 text-muted text-uppercase" href="#setup-submenu" data-bs-toggle="collapse" aria-expanded="false">
                            <i class="bi bi-gear-fill"></i> SETUP
                        </a>
                    </h6>
                    <div class="collapse <?= isSubmenuActive($setup_admin_pages) ?>" id="setup-submenu">
                        <ul class="nav flex-column ms-4">
                            <li class="nav-item">
                                <a class="nav-link collapsed ps-0" href="#management-submenu" data-bs-toggle="collapse" aria-expanded="false">Management</a>
                                <div class="collapse <?= isSubmenuActive($management_pages) ?>" id="management-submenu">
                                    <ul class="nav flex-column ms-4">
                                        <li class="nav-item"><a class="nav-link <?= isActive('users.php') ?>" href="users.php"><i class="bi bi-people"></i> Users</a></li>
                                        <li class="nav-item"><a class="nav-link <?= isActive('suppliers.php') ?>" href="suppliers.php"><i class="bi bi-truck-front"></i> Suppliers</a></li>
                                        <li class="nav-item"><a class="nav-link <?= isActive('school_settings.php') ?>" href="school_settings.php"><i class="bi bi-building-gear"></i> School Info</a></li>
                                        <li class="nav-item"><a class="nav-link <?= isActive('lookups.php') ?>" href="lookups.php">Categories</a></li>
                                    </ul>
                                </div>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link collapsed ps-0" href="#numbering-submenu" data-bs-toggle="collapse" aria-expanded="false">Numbering</a>
                                <div class="collapse <?= isSubmenuActive($numbering_pages) ?>" id="numbering-submenu">
                                    <ul class="nav flex-column ms-4">
                                        <li class="nav-item"><a class="nav-link <?= isActive('po_no.php') ?>" href="po_no.php">PO Series</a></li>
                                        <li class="nav-item"><a class="nav-link <?= isActive('ris_no.php') ?>" href="ris_no.php">RIS Series</a></li>
                                        <li class="nav-item"><a class="nav-link <?= isActive('sn_no.php') ?>" href="sn_no.php">Stock No. Series</a></li>
                                        <li class="nav-item"><a class="nav-link <?= isActive('pn_no.php') ?>" href="pn_no.php">Property No. Series</a></li>
                                        <li class="nav-item"><a class="nav-link <?= isActive('ics_no.php') ?>" href="ics_no.php">ICS Series</a></li>
                                        <li class="nav-item"><a class="nav-link <?= isActive('par_no.php') ?>" href="par_no.php">PAR Series</a></li>
                                        <li class="nav-item"><a class="nav-link <?= isActive('rpci_no.php') ?>" href="rpci_no.php">RPCI Series</a></li>
                                        <li class="nav-item"><a class="nav-link <?= isActive('rpcppe_no.php') ?>" href="rpcppe_no.php">RPCPPE Series</a></li>
                                    </ul>
                                </div>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link collapsed ps-0" href="#system-submenu" data-bs-toggle="collapse" aria-expanded="false">System</a>
                                <div class="collapse <?= isSubmenuActive($system_pages) ?>" id="system-submenu">
                                    <ul class="nav flex-column ms-4">
                                        <li class="nav-item"><a class="nav-link <?= isActive('reset_data.php') ?>" href="reset_data.php"><i class="bi bi-trash3-fill"></i> Reset Data</a></li>
                                    </ul>
                                </div>
                            </li>
                        </ul>
                    </div>
                <?php endif; ?>
                </ul>
            <?php endif; ?>
        </ul>
    </div>
</nav>
