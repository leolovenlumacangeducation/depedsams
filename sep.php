<?php 
require_once 'db.php'; 
require_once 'includes/header.php'; 
require_once 'includes/admin_guard.php';
require_once 'includes/sidebar.php'; 

// Fetch lookup data for modals
$users_stmt = $pdo->query("SELECT user_id, full_name FROM tbl_user WHERE is_active = 1 ORDER BY full_name");
$users = $users_stmt ? $users_stmt->fetchAll() : [];
if (!$users_stmt) {
    // Handle potential query error, though unlikely here.
}

?>

<!-- Custom CSS for this page -->
<link rel="stylesheet" href="assets/css/sep.css">

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h4"><strong>SEMI-EXPENDABLE PROPERTY</strong></h4>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" id="assign-selected-btn" class="btn btn-sm btn-dark" disabled>
                    <i class="bi bi-person-check-fill"></i> Assign Selected
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
    <div id="sep-view-container">
        <!-- Search Bar for Card View -->
        <div class="mb-3" id="card-view-search-bar">
            <label for="sep-card-search" class="form-label">Search Semi-Expendable Property</label>
            <input type="text" id="sep-card-search" class="form-control" placeholder="Search by description, property number, or serial number...">
        </div>

        <!-- Tabs for Unassigned / Assigned -->
        <ul class="nav nav-tabs mb-3" id="assignment-status-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="unassigned-tab" data-bs-toggle="tab" data-bs-target="#unassigned-panel" type="button" role="tab">Available Stocks</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="assigned-tab" data-bs-toggle="tab" data-bs-target="#assigned-panel" type="button" role="tab">Assigned Stocks</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="unserviceable-tab" data-bs-toggle="tab" data-bs-target="#unserviceable-panel" type="button" role="tab">Unserviceable</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="for-repair-tab" data-bs-toggle="tab" data-bs-target="#for-repair-panel" type="button" role="tab">For Repair</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="disposed-tab" data-bs-toggle="tab" data-bs-target="#disposed-panel" type="button" role="tab">Disposed</button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="assignment-status-tabs-content">
            <div class="tab-pane fade show active" id="unassigned-panel" role="tabpanel" aria-labelledby="unassigned-tab">
                <div id="unassigned-card-container" class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-4"></div>
            </div>
            <div class="tab-pane fade" id="assigned-panel" role="tabpanel" aria-labelledby="assigned-tab">
                <div id="assigned-card-container" class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-4"></div>
            </div>
            <div class="tab-pane fade" id="unserviceable-panel" role="tabpanel" aria-labelledby="unserviceable-tab">
                <div id="unserviceable-card-container" class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-4"></div>
            </div>
            <div class="tab-pane fade" id="for-repair-panel" role="tabpanel" aria-labelledby="for-repair-tab">
                <div id="for-repair-card-container" class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-4"></div>
            </div>
            <div class="tab-pane fade" id="disposed-panel" role="tabpanel" aria-labelledby="disposed-tab">
                <div id="disposed-card-container" class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-4"></div>
            </div>
        </div>

        <!-- Table View (hidden by default) -->
        <div id="sep-table-container" class="d-none table-responsive">
            <table id="sepListTable" class="table table-striped table-bordered table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Property No.</th>
                        <th>Description</th>
                        <th>Serial No.</th>
                        <th>Date Acquired</th>
                        <th>Assigned To</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

</main>

<!-- Include Modals -->
<?php 
require_once 'includes/sep_assign_modal.php'; 
require_once 'includes/sep_view_modal.php';
require_once 'includes/ics_view_modal.php';
require_once 'includes/sep_edit_modal.php';
?> 
<input type="file" id="direct-sep-photo-upload" class="d-none" accept="image/png, image/jpeg, image/gif, image/webp"> 

<!-- jQuery (required by DataTables) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<!-- Custom JS for this page -->
<?php require_once 'includes/footer.php'; ?>
<!-- Document management scripts removed (feature disabled) -->
<script src="assets/js/sep.cards.js"></script>
<script src="assets/js/sep.js"></script> <!-- This must be last -->