<?php
require_once 'db.php';
require_once 'includes/header.php';
require_once 'includes/admin_guard.php';
require_once 'includes/sidebar.php';

// Fetch lookup data for modals and other components
$users = $pdo->query("SELECT user_id, full_name FROM tbl_user WHERE is_active = 1 ORDER BY full_name")->fetchAll();
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Disposal Management (IIRUP)</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <button type="button" id="create-iirup-btn" class="btn btn-sm btn-success" disabled>
                <i class="bi bi-file-earmark-plus"></i> Create IIRUP
            </button>
        </div>
    </div>

    <!-- Tabs for Unserviceable Items / IIRUP Documents -->
    <ul class="nav nav-tabs mb-3" id="disposal-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="unserviceable-tab" data-bs-toggle="tab" data-bs-target="#unserviceable-panel" type="button" role="tab" aria-controls="unserviceable-panel" aria-selected="true">Unserviceable Items</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="iirup-docs-tab" data-bs-toggle="tab" data-bs-target="#iirup-docs-panel" type="button" role="tab" aria-controls="iirup-docs-panel" aria-selected="false">IIRUP Documents</button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="disposal-tabs-content">
        <div class="tab-pane fade show active" id="unserviceable-panel" role="tabpanel" aria-labelledby="unserviceable-tab">
            <div class="mb-3">
                <input type="text" id="unserviceable-search" class="form-control" placeholder="Search unserviceable items...">
            </div>
            <div class="table-responsive">
                <table id="unserviceableItemsTable" class="table table-striped table-bordered table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="select-all-unserviceable"></th>
                            <th>Property No.</th>
                            <th>Description</th>
                            <th>Serial No.</th>
                            <th>Asset Type</th>
                            <th>Condition</th>
                            <th>Date Acquired</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded here by DataTables -->
                    </tbody>
                </table>
            </div>
        </div>
        <div class="tab-pane fade" id="iirup-docs-panel" role="tabpanel" aria-labelledby="iirup-docs-tab">
            <div class="mb-3">
                <input type="text" id="iirup-docs-search" class="form-control" placeholder="Search IIRUP documents...">
            </div>
            <div class="table-responsive">
                <table id="iirupDocsTable" class="table table-striped table-bordered table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th>IIRUP No.</th>
                            <th>As of Date</th>
                            <th>Disposal Method</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Date Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded here by DataTables -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Create IIRUP Modal -->
<div class="modal fade" id="createIirupModal" tabindex="-1" aria-labelledby="createIirupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createIirupModalLabel">Create New IIRUP</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createIirupForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="iirup_number_preview" class="form-label">IIRUP Number</label>
                        <input type="text" class="form-control" id="iirup_number_preview" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="as_of_date" class="form-label">As of Date</label>
                        <input type="date" class="form-control" id="as_of_date" name="as_of_date" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="disposal_method" class="form-label">Disposal Method</label>
                        <input type="text" class="form-control" id="disposal_method" name="disposal_method" placeholder="e.g., Sold, Destroyed, Transferred">
                    </div>
                    <h6>Items to be included:</h6>
                    <ul id="selected-disposal-items" class="list-group mb-3">
                        <!-- Selected items will be listed here -->
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Generate IIRUP</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Include Modals (for viewing IIRUPs) -->
<?php require_once 'includes/iirup_view_modal.php'; ?>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.min.js"></script>

<?php require_once 'includes/footer.php'; ?>
<!-- Document management scripts removed (feature disabled) -->
<script src="assets/js/disposal.js"></script>