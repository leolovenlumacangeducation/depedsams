<?php 
require_once 'db.php';
require_once 'includes/header.php'; 
require_once 'includes/sidebar.php'; 

// Fetch lookup data for the category modal dropdown
$inventory_types = $pdo->query("SELECT * FROM tbl_inventory_type ORDER BY inventory_type_name")->fetchAll();
?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Manage Lookups</h1>
    </div>

    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="lookupsTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="units-tab" data-bs-toggle="tab" data-bs-target="#units-panel" type="button" role="tab">Units</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="positions-tab" data-bs-toggle="tab" data-bs-target="#positions-panel" type="button" role="tab">Positions</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="roles-tab" data-bs-toggle="tab" data-bs-target="#roles-panel" type="button" role="tab">Roles</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="categories-tab" data-bs-toggle="tab" data-bs-target="#categories-panel" type="button" role="tab">Categories</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="inventory-types-tab" data-bs-toggle="tab" data-bs-target="#inventory-types-panel" type="button" role="tab">Inventory Types</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="purchase-modes-tab" data-bs-toggle="tab" data-bs-target="#purchase-modes-panel" type="button" role="tab">Purchase Modes</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="delivery-places-tab" data-bs-toggle="tab" data-bs-target="#delivery-places-panel" type="button" role="tab">Delivery Places</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="delivery-terms-tab" data-bs-toggle="tab" data-bs-target="#delivery-terms-panel" type="button" role="tab">Delivery Terms</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="payment-terms-tab" data-bs-toggle="tab" data-bs-target="#payment-terms-panel" type="button" role="tab">Payment Terms</button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="lookupsTabContent">
                <!-- Units Panel -->
                <div class="tab-pane fade show active" id="units-panel" role="tabpanel">
                    <div class="d-flex justify-content-end mb-3">
                        <button class="btn btn-sm btn-success add-lookup-btn" data-type="unit" data-modal-title="Add New Unit"><i class="bi bi-plus-circle"></i> Add New Unit</button>
                    </div>
                    <table id="unitsTable" class="table table-striped table-bordered lookup-table" style="width:100%;">
                        <thead><tr><th>Unit Name</th><th>Actions</th></tr></thead>
                    </table>
                </div>
                <!-- Positions Panel -->
                <div class="tab-pane fade" id="positions-panel" role="tabpanel">
                     <div class="d-flex justify-content-end mb-3">
                        <button class="btn btn-sm btn-success add-lookup-btn" data-type="position" data-modal-title="Add New Position"><i class="bi bi-plus-circle"></i> Add New Position</button>
                    </div>
                    <table id="positionsTable" class="table table-striped table-bordered lookup-table" style="width:100%;">
                        <thead><tr><th>Position Name</th><th>Actions</th></tr></thead>
                    </table>
                </div>
                <!-- Roles Panel -->
                <div class="tab-pane fade" id="roles-panel" role="tabpanel">
                     <div class="d-flex justify-content-end mb-3">
                        <button class="btn btn-sm btn-success add-lookup-btn" data-type="role" data-modal-title="Add New Role"><i class="bi bi-plus-circle"></i> Add New Role</button>
                    </div>
                    <table id="rolesTable" class="table table-striped table-bordered lookup-table" style="width:100%;">
                        <thead><tr><th>Role Name</th><th>Actions</th></tr></thead>
                    </table>
                </div>
                <!-- Categories Panel -->
                <div class="tab-pane fade" id="categories-panel" role="tabpanel">
                    <div class="d-flex justify-content-end mb-3">
                        <button class="btn btn-sm btn-success" id="addCategoryBtn" data-bs-toggle="modal" data-bs-target="#categoryModal"><i class="bi bi-plus-circle"></i> Add New Category</button>
                    </div>
                    <table id="categoriesTable" class="table table-striped table-bordered" style="width:100%;">
                        <thead>
                            <tr>
                                <th>Category Name</th>
                                <th>UACS Object Code</th>
                                <th>Inventory Type</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                <!-- Inventory Types Panel -->
                <div class="tab-pane fade" id="inventory-types-panel" role="tabpanel">
                     <div class="d-flex justify-content-end mb-3">
                        <button class="btn btn-sm btn-success add-lookup-btn" data-type="inventory_type" data-modal-title="Add New Inventory Type"><i class="bi bi-plus-circle"></i> Add New Inventory Type</button>
                    </div>
                    <table id="inventoryTypesTable" class="table table-striped table-bordered lookup-table" style="width:100%;">
                        <thead><tr><th>Inventory Type Name</th><th>Actions</th></tr></thead>
                    </table>
                </div>
                <!-- Purchase Modes Panel -->
                <div class="tab-pane fade" id="purchase-modes-panel" role="tabpanel">
                     <div class="d-flex justify-content-end mb-3">
                        <button class="btn btn-sm btn-success add-lookup-btn" data-type="purchase_mode" data-modal-title="Add New Purchase Mode"><i class="bi bi-plus-circle"></i> Add New Purchase Mode</button>
                    </div>
                    <table id="purchaseModesTable" class="table table-striped table-bordered lookup-table" style="width:100%;">
                        <thead><tr><th>Mode Name</th><th>Actions</th></tr></thead>
                    </table>
                </div>
                <!-- Delivery Places Panel -->
                <div class="tab-pane fade" id="delivery-places-panel" role="tabpanel">
                     <div class="d-flex justify-content-end mb-3">
                        <button class="btn btn-sm btn-success add-lookup-btn" data-type="delivery_place" data-modal-title="Add New Delivery Place"><i class="bi bi-plus-circle"></i> Add New Delivery Place</button>
                    </div>
                    <table id="deliveryPlacesTable" class="table table-striped table-bordered lookup-table" style="width:100%;">
                        <thead><tr><th>Place Name</th><th>Actions</th></tr></thead>
                    </table>
                </div>
                <!-- Delivery Terms Panel -->
                <div class="tab-pane fade" id="delivery-terms-panel" role="tabpanel">
                     <div class="d-flex justify-content-end mb-3">
                        <button class="btn btn-sm btn-success add-lookup-btn" data-type="delivery_term" data-modal-title="Add New Delivery Term"><i class="bi bi-plus-circle"></i> Add New Delivery Term</button>
                    </div>
                    <table id="deliveryTermsTable" class="table table-striped table-bordered lookup-table" style="width:100%;">
                        <thead><tr><th>Term Description</th><th>Actions</th></tr></thead>
                    </table>
                </div>
                <!-- Payment Terms Panel -->
                <div class="tab-pane fade" id="payment-terms-panel" role="tabpanel">
                     <div class="d-flex justify-content-end mb-3">
                        <button class="btn btn-sm btn-success add-lookup-btn" data-type="payment_term" data-modal-title="Add New Payment Term"><i class="bi bi-plus-circle"></i> Add New Payment Term</button>
                    </div>
                    <table id="paymentTermsTable" class="table table-striped table-bordered lookup-table" style="width:100%;">
                        <thead><tr><th>Term Description</th><th>Actions</th></tr></thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Generic Modal for Add/Edit Lookup -->
<div class="modal fade" id="lookupModal" tabindex="-1" aria-labelledby="lookupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="lookupForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="lookupModalLabel">Add/Edit Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="lookup_id" name="id">
                    <input type="hidden" id="lookup_type" name="type">
                    <div class="mb-3">
                        <label for="lookup_name" class="form-label">Name / Description</label>
                        <input type="text" class="form-control" id="lookup_name" name="name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add/Edit Category Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="categoryModalLabel">Add New Category</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="categoryForm">
          <div class="modal-body">
            <input type="hidden" id="category_id" name="category_id">
            <div class="mb-3">
                <label for="category_name" class="form-label">Category Name</label>
                <input type="text" class="form-control" id="category_name" name="category_name" required>
            </div>
            <div class="mb-3">
                <label for="uacs_object_code" class="form-label">UACS Object Code</label>
                <input type="text" class="form-control" id="uacs_object_code" name="uacs_object_code" placeholder="e.g., 50203010">
            </div>
            <div class="mb-3">
                <label for="inventory_type_id" class="form-label">Inventory Type</label>
                <select class="form-select" id="inventory_type_id" name="inventory_type_id" required>
                    <option value="" disabled selected>Select an inventory type...</option>
                    <?php foreach ($inventory_types as $type): ?>
                        <option value="<?= $type['inventory_type_id'] ?>"><?= htmlspecialchars($type['inventory_type_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Save Category</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- jQuery (required by DataTables) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<!-- Custom JS for this page -->
<script src="assets/js/lookups.js"></script>

<?php require_once 'includes/footer.php'; ?>