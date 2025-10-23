<?php 
require_once 'db.php'; 
require_once 'includes/header.php'; 
require_once 'includes/sidebar.php'; 
?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.min.css">

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h4"><strong>Inspection and Acceptance Reports (IAR)</strong></h4>
    </div>

    <!-- IAR List Table -->
    <div class="table-responsive">
        <table id="iarListTable" class="table table-striped table-bordered table-hover" style="width:100%">
            <thead>
                <tr>
                    <th>PO Number</th>
                    <th>Supplier</th>
                    <th>Order Date</th>
                    <th class="text-end">Total Amount</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- Data will be loaded here by DataTables -->
            </tbody>
        </table>
    </div>

</main>

<?php require_once 'includes/iar_modal.php'; ?>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.min.js"></script>
<?php require_once 'includes/footer.php'; ?>
<script src="assets/js/iar_list.js"></script>