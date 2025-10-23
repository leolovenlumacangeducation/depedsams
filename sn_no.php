<?php 
require_once 'includes/header.php'; 
require_once 'includes/sidebar.php'; 
?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.min.css">

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Stock Number Sequences</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#snSequenceModal" id="addSnSequenceBtn">
                <i class="bi bi-plus-circle"></i> Add New Sequence
            </button>
        </div>
    </div>

    <div class="alert alert-info">
        Manage the automatic generation of Stock Numbers (SN) for Consumable items. The format uses placeholders: <code>{YYYY}</code> for the year and <code>{NNNN}</code> for the auto-incrementing number.
    </div>

    <!-- SN Sequences List Table -->
    <div class="table-responsive">
        <table id="snSequenceTable" class="table table-striped table-bordered table-hover" style="width:100%">
            <thead>
                <tr>
                    <th>Year</th>
                    <th>Format</th>
                    <th>Next Number</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- Data will be loaded here by DataTables -->
            </tbody>
        </table>
    </div>

</main>

<!-- Add/Edit SN Sequence Modal -->
<div class="modal fade" id="snSequenceModal" tabindex="-1" aria-labelledby="snSequenceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="snSequenceModalLabel">Add New Sequence</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="snSequenceForm">
          <div class="modal-body">
            <input type="hidden" id="item_number_id" name="item_number_id">
            <div class="mb-3">
                <label for="year" class="form-label">Year</label>
                <input type="number" class="form-control" id="year" name="year" placeholder="e.g., <?= date('Y') ?>" required>
            </div>
            <div class="mb-3">
                <label for="item_number_format" class="form-label">Number Format</label>
                <input type="text" class="form-control" id="item_number_format" name="item_number_format" placeholder="e.g., SN-{YYYY}-{NNNN}" required>
            </div>
            <div class="mb-3">
                <label for="start_count" class="form-label">Next Number</label>
                <input type="number" class="form-control" id="start_count" name="start_count" min="1" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Save Sequence</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- jQuery (required by DataTables) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- DataTables JS (Latest) -->
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.min.js"></script>
<!-- Custom JS for this page -->
<script src="assets/js/sn_no.js"></script>

<?php require_once 'includes/footer.php'; ?>