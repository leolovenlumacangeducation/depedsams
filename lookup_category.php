<?php 
require_once 'db.php'; 
require_once 'includes/header.php'; 
require_once 'includes/sidebar.php'; 

// Fetch lookup data for the modal dropdown
$inventory_types = $pdo->query("SELECT * FROM tbl_inventory_type ORDER BY inventory_type_name")->fetchAll();
?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Manage Categories</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#categoryModal" id="addCategoryBtn">
                <i class="bi bi-plus-circle"></i> Add New Category
            </button>
        </div>
    </div>

    <!-- Categories List Table -->
    <div class="table-responsive">
        <table id="categoriesTable" class="table table-striped table-bordered table-hover" style="width:100%">
            <thead>
                <tr>
                    <th>Category Name</th>
                    <th>UACS Object Code</th>
                    <th>Inventory Type</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- Data will be loaded here by DataTables -->
            </tbody>
        </table>
    </div>

</main>

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

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="assets/js/category.js"></script>

<?php require_once 'includes/footer.php'; ?>