<?php 
// The database connection must be included before the header,
// as the header performs session checks and we might need the DB for that.
require_once 'db.php'; 
require_once 'includes/header.php'; 
require_once 'includes/sidebar.php'; 

// Fetch lookup data for the 'Add PO' modal dropdowns
$suppliers = $pdo->query("SELECT supplier_id, supplier_name FROM tbl_supplier ORDER BY supplier_name")->fetchAll();
$purchase_modes = $pdo->query("SELECT * FROM tbl_purchase_mode")->fetchAll();
$delivery_places = $pdo->query("SELECT * FROM tbl_delivery_place")->fetchAll();
$delivery_terms = $pdo->query("SELECT * FROM tbl_delivery_term")->fetchAll();
$payment_terms = $pdo->query("SELECT * FROM tbl_payment_term")->fetchAll();
$units = $pdo->query("SELECT unit_id, unit_name FROM tbl_unit ORDER BY unit_name")->fetchAll();
$inventory_types = $pdo->query("SELECT * FROM tbl_inventory_type ORDER BY inventory_type_name")->fetchAll();
$categories = $pdo->query("SELECT category_id, category_name, inventory_type_id FROM tbl_category ORDER BY category_name")->fetchAll();

?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.min.css">


<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Purchase Orders</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addPoModal">
                    <i class="bi bi-plus-circle"></i> Add New PO
                </button>
            </div>
        </div>
    </div>

    <!-- Tabs for Pending / Delivered -->
    <ul class="nav nav-tabs mb-3" id="po-status-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="pending-po-tab" data-bs-toggle="tab" data-bs-target="#pending-po-panel" type="button" role="tab" aria-controls="pending-po-panel" aria-selected="true">Pending</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="delivered-po-tab" data-bs-toggle="tab" data-bs-target="#delivered-po-panel" type="button" role="tab" aria-controls="delivered-po-panel" aria-selected="false">Delivered</button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="po-status-tabs-content">
        <div class="tab-pane fade show active" id="pending-po-panel" role="tabpanel" aria-labelledby="pending-po-tab">
            <div class="table-responsive">
                <table id="pendingPoListTable" class="table table-striped table-bordered table-hover" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>No.</th><th>PO Number</th><th>Supplier</th><th>Order Date</th><th class="text-end">Total Amount</th><th>Status</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
        <div class="tab-pane fade" id="delivered-po-panel" role="tabpanel" aria-labelledby="delivered-po-tab">
            <div class="table-responsive">
                <table id="deliveredPoListTable" class="table table-striped table-bordered table-hover" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>No.</th><th>PO Number</th><th>Supplier</th><th>Order Date</th><th class="text-end">Total Amount</th><th>Status</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

</main>

<!-- Include Modals -->
<?php require_once 'includes/po_add_modal.php'; ?>
<?php require_once 'includes/po_view_modal.php'; ?>
<?php require_once 'includes/po_edit_modal.php'; ?>
<?php require_once 'includes/po_delivered_modal.php'; ?>
<?php require_once 'includes/iar_modal.php'; ?>
<?php require_once 'includes/po_receive_modal.php'; ?>

<!-- App data for client scripts -->
<div id="app-data" data-categories='<?= json_encode($categories, JSON_HEX_APOS|JSON_HEX_QUOT) ?>' data-inventory-types='<?= json_encode($inventory_types, JSON_HEX_APOS|JSON_HEX_QUOT) ?>' style="display:none"></div>

<!-- jQuery (required by DataTables) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- DataTables JS (Latest) -->
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.min.js"></script>
<!-- Custom JS for PO functionality -->
<script src="assets/js/po_list.js"></script>
<script src="assets/js/po_add.js"></script>
<script src="assets/js/po_edit.js"></script>
<script src="assets/js/po_delivered.js"></script>
<script src="assets/js/po_receive.js"></script>

<?php require_once 'includes/footer.php'; ?>