<?php 
require_once 'db.php'; 
require_once 'includes/header.php'; 
require_once 'includes/sidebar.php'; 
?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.min.css">

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h4"><strong>IIRUP Documents</strong></h1>
    </div>

    <!-- IIRUP List Table -->
    <div class="table-responsive">
        <table id="iirupListTable" class="table table-striped table-bordered table-hover" style="width:100%">
            <thead class="table-light">
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

</main>

<?php 
// Include the view modal which is also used by other pages
require_once 'includes/iirup_view_modal.php'; 
?>

<!-- jQuery (required by DataTables) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- DataTables JS (Latest) -->
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.min.js"></script>

<?php require_once 'includes/footer.php'; ?>

<!-- Document Modal Functionality -->
<script src="assets/js/document.modals.js"></script>
<!-- Custom JS for this page -->
<script src="assets/js/iirup_list.js"></script>