<?php 
require_once 'db.php'; 
require_once 'includes/header.php'; 
require_once 'includes/sidebar.php'; 

// Fetch lookup data for the form dropdowns
$units = $pdo->query("SELECT unit_id, unit_name FROM tbl_unit ORDER BY unit_name")->fetchAll(PDO::FETCH_ASSOC);
$categories = $pdo->query("SELECT category_id, category_name, inventory_type_id FROM tbl_category ORDER BY category_name")->fetchAll(PDO::FETCH_ASSOC);

// We need to know which category belongs to which inventory type for the JS logic
$category_inventory_map = [];
foreach ($categories as $category) {
    $category_inventory_map[$category['category_id']] = $category['inventory_type_id'];
}
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Receive Items from Incoming ICS</h1>
    </div>

    <form id="incomingIcsForm">
        <!-- Card for ICS Header Information -->
        <div class="card mb-4">
            <div class="card-header">
                ICS Document Details
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="ics_number" class="form-label">ICS Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="ics_number" name="ics_number" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="source_office" class="form-label">Source Office / School <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="source_office" name="source_office" placeholder="e.g., Division Office" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="date_received" class="form-label">Date Received <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="date_received" name="date_received" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="issued_by_name" class="form-label">Issued By (Full Name) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="issued_by_name" name="issued_by_name" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="issued_by_position" class="form-label">Position of Issuer</label>
                        <input type="text" class="form-control" id="issued_by_position" name="issued_by_position">
                    </div>
                </div>
            </div>
        </div>

        <!-- Card for Items on the ICS -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                Items on ICS
                <button type="button" class="btn btn-sm btn-primary" id="addItemBtn"><i class="bi bi-plus-circle"></i> Add Item</button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="itemsTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 30%;">Description</th>
                                <th style="width: 15%;">Category</th>
                                <th style="width: 10%;">Quantity</th>
                                <th style="width: 12%;">Unit</th>
                                <th style="width: 12%;">Unit Cost</th>
                                <th style="width: 13%;">Inventory Type</th>
                                <th style="width: 8%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="itemsTbody">
                            <!-- Item rows will be appended here by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Card for Individual Asset Details -->
        <div class="card mb-4">
            <div class="card-header">
                Asset Details (Property Numbers, etc.)
            </div>
            <div class="card-body">
                <p class="text-muted" id="assetDetailsPlaceholder">Add items above to enter their individual details here. Property Number is required for all SEP/PPE items.</p>
                <div id="assetDetailsContainer">
                    <!-- Asset detail forms will be generated here by JavaScript -->
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="d-flex justify-content-end mb-4">
            <button type="reset" class="btn btn-secondary me-2">Clear Form</button>
            <button type="submit" class="btn btn-success"><i class="bi bi-check-circle"></i> Receive Items</button>
        </div>
    </form>
</main>

<!-- Pass PHP data to JavaScript -->
<script>
    const lookupData = {
        units: <?php echo json_encode($units); ?>,
        categories: <?php echo json_encode($categories); ?>,
        categoryInventoryMap: <?php echo json_encode($category_inventory_map); ?>
    };
</script>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Custom JS for this page -->
<script src="assets/js/incoming_ics.js"></script>

<?php require_once 'includes/footer.php'; ?>



