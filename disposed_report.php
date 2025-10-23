<?php 
require_once 'db.php'; 
require_once 'includes/header.php'; 
require_once 'includes/sidebar.php'; 
?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.0.2/css/buttons.bootstrap5.min.css">

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h4"><strong>Disposed Items Report</strong></h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <!-- Print button will be managed by DataTables -->
        </div>
    </div>

    <!-- Disposed Items Table -->
    <div class="table-responsive">
        <table id="disposedItemsTable" class="table table-striped table-bordered table-hover" style="width:100%">
            <thead>
                <tr>
                    <th>Property Number</th>
                    <th>Description</th>
                    <th>Serial Number</th>
                    <th>Item Type</th>
                    <th>Date Acquired</th>
                </tr>
            </thead>
            <tbody>
                <!-- Data will be loaded here by DataTables -->
            </tbody>
        </table>
    </div>

</main>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/3.0.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.print.min.js"></script>

<?php require_once 'includes/footer.php'; ?>
<script src="assets/js/disposed_report.js"></script>