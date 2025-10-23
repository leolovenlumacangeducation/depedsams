<?php 
require_once 'db.php'; 
require_once 'includes/header.php'; 
require_once 'includes/admin_guard.php';
require_once 'includes/sidebar.php'; 

// Fetch lookup data for modals
$units = $pdo->query("SELECT unit_id, unit_name FROM tbl_unit ORDER BY unit_name")->fetchAll();
$users = $pdo->query("SELECT user_id, full_name FROM tbl_user WHERE is_active = 1 ORDER BY full_name")->fetchAll();

?>

<!-- Page-specific CSS -->
<link rel="stylesheet" href="assets/css/consumables.css">

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h4"><strong>SUPPLIES AND MATERIALS</strong></h4>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" id="issue-selected-btn" class="btn btn-sm btn-dark" disabled>
                    <i class="bi bi-box-arrow-up-right"></i> Issue Selected
                </button>
            </div>
            <div class="btn-group" role="group" aria-label="View toggle">
                <button type="button" id="card-view-btn" class="btn btn-sm btn-outline-secondary active" title="Card View">
                    <i class="bi bi-grid-3x3-gap-fill"></i>
                </button>
                <button type="button" id="table-view-btn" class="btn btn-sm btn-outline-secondary" title="Table View">
                    <i class="bi bi-list-ul"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Main View Container -->
    <div id="consumables-view-container">
        <!-- Tabs for In Stock / Out of Stock -->
        <ul class="nav nav-tabs mb-3" id="stock-status-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="in-stock-tab" data-bs-toggle="tab" data-bs-target="#in-stock-panel" type="button" role="tab" aria-controls="in-stock-panel" aria-selected="true">In Stock</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="out-of-stock-tab" data-bs-toggle="tab" data-bs-target="#out-of-stock-panel" type="button" role="tab" aria-controls="out-of-stock-panel" aria-selected="false">Out of Stock <span class="badge bg-danger rounded-pill d-none" id="out-of-stock-count">0</span></button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="stock-status-tabs-content">
            <div class="tab-pane fade show active" id="in-stock-panel" role="tabpanel" aria-labelledby="in-stock-tab">
                <!-- Search Bar for Card View -->
                <div class="mb-3" id="card-view-search-bar">
                    <label for="consumable-card-search" class="form-label">Search Consumables</label>
                    <input type="text" id="consumable-card-search" name="consumable-card-search" class="form-control" placeholder="Search by description or stock number...">
                </div>
                <div id="consumables-card-container" class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-4"></div>
            </div>
            <div class="tab-pane fade" id="out-of-stock-panel" role="tabpanel" aria-labelledby="out-of-stock-tab">
                <div id="out-of-stock-card-container" class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-4"></div>
            </div>
        </div>

        <!-- Table View (hidden by default) -->
        <div id="consumables-table-container" class="d-none table-responsive">
            <table id="consumablesListTable" class="table table-striped table-bordered table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th>Stock No.</th>
                        <th>Description</th>
                        <th>Unit</th>
                        <th class="text-end">Current Stock</th>
                        <th>Date Received</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

</main>

<!-- Hidden file input for direct photo uploads -->
<input type="file" id="direct-photo-upload" class="d-none" accept="image/png, image/jpeg, image/gif, image/webp">

<!-- Include Modals -->
<?php require_once 'includes/consumable_view_modal.php'; ?>
<?php require_once 'includes/consumable_issue_modal.php'; ?>
<?php require_once 'includes/consumable_stock_card_modal.php'; ?>
<?php require_once 'includes/consumable_convert_modal.php'; ?>
<?php require_once 'includes/ris_modal.php'; ?>
<?php require_once 'includes/stock_card_pdf_modal.php'; ?>

<!-- jQuery must be loaded first -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- DataTables JS (depends on jQuery) -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<!-- Load Bootstrap bundle and other dependencies from footer -->
<?php require_once 'includes/footer.php'; ?>

<!-- Page-specific scripts - load after all dependencies -->
<script src="assets/js/consumables.cards.js"></script>
<script src="assets/js/consumables.modals.js"></script>
<script src="assets/js/consumables.js"></script>