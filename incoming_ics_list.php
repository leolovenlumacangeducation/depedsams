<?php 
require_once 'db.php'; 
require_once 'includes/header.php'; 
require_once 'includes/sidebar.php'; 

// Fetch lookup data for the 'Add ICS' modal dropdowns
$inventory_types = $pdo->query("SELECT inventory_type_id, inventory_type_name FROM tbl_inventory_type ORDER BY inventory_type_name")->fetchAll(PDO::FETCH_ASSOC);
$units = $pdo->query("SELECT unit_id, unit_name FROM tbl_unit ORDER BY unit_name")->fetchAll(PDO::FETCH_ASSOC);
$categories = $pdo->query("SELECT category_id, category_name, inventory_type_id FROM tbl_category ORDER BY category_name")->fetchAll(PDO::FETCH_ASSOC);

// We need to know which category belongs to which inventory type for the JS logic
$category_inventory_map = [];
foreach ($categories as $category) {
    $category_inventory_map[$category['category_id']] = $category['inventory_type_id'];
}
?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.min.css">

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Incoming ICS Documents</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addIncomingIcsModal">
                    <i class="bi bi-plus-circle"></i> Add New Incoming ICS
                </button>
            </div>
        </div>
    </div>

    <!-- ICS List Table -->
    <div class="table-responsive">
        <table id="incomingIcsListTable" class="table table-striped table-bordered table-hover" style="width:100%">
            <thead class="table-light">
                <tr>
                    <th>ICS Number</th>
                    <th>Source Office</th>
                    <th>Date Received</th>
                    <th>Issued By</th>
                    <th>Received By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- Data will be loaded here by DataTables -->
            </tbody>
        </table>
    </div>

</main>

<?php 
require_once 'includes/incoming_ics_add_modal.php'; 
require_once 'includes/incoming_ics_view_modal.php'; 
?>

<!-- Pass PHP data to JavaScript -->
<script>
    const lookupData = {
        inventoryTypes: <?php echo json_encode($inventory_types); ?>,
        units: <?php echo json_encode($units); ?>,
        categories: <?php echo json_encode($categories); ?>,
        categoryInventoryMap: <?php echo json_encode($category_inventory_map); ?>
    };
</script>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php require_once 'includes/footer.php'; ?>
<script src="assets/js/incoming_ics_list.js"></script>
<script src="assets/js/incoming_ics_add.js"></script>