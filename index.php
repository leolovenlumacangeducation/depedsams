<?php 
require_once 'includes/header.php'; 
require_once 'includes/sidebar.php'; 
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Dashboard</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="refreshDashboardBtn">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Loading Spinner -->
    <div id="dashboard-loader" class="text-center p-5">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2">Loading Dashboard Data...</p>
    </div>

    <!-- Dashboard Content (hidden until data is loaded) -->
    <div id="dashboard-content" class="d-none">
        <!-- Summary Cards -->
        <!-- Value Summary Row -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-primary">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">Total Asset Value</h6>
                        <h2 class="card-title mb-0" id="total-value">₱0.00</h2>
                        <small class="text-muted">Combined value of all assets</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-success">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">PPE Value</h6>
                        <h2 class="card-title mb-0" id="ppe-value">₱0.00</h2>
                        <small class="text-muted">Total PPE value</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-info">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">SEP Value</h6>
                        <h2 class="card-title mb-0" id="sep-value">₱0.00</h2>
                        <small class="text-muted">Total SEP value</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-warning">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">Consumables Value</h6>
                        <h2 class="card-title mb-0" id="consumables-value">₱0.00</h2>
                        <small class="text-muted">Total inventory value</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Count Summary Row -->
        <div class="row">
            <div class="col-md-4">
                <div class="card text-white bg-primary mb-3">
                    <div class="card-header">Consumables</div>
                    <div class="card-body">
                        <h5 class="card-title display-6" id="consumable-count">0</h5>
                        <p class="card-text">Total distinct consumable items</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-info mb-3">
                    <div class="card-header">Semi-Expendable (SEP)</div>
                    <div class="card-body">
                        <h5 class="card-title display-6" id="sep-count">0</h5>
                        <p class="card-text">Total semi-expendable properties.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success mb-3">
                    <div class="card-header">Equipment (PPE)</div>
                    <div class="card-body">
                        <h5 class="card-title display-6" id="ppe-count">0</h5>
                        <p class="card-text">Total property, plant, and equipment.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts and Low Stock -->
        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <i class="bi bi-exclamation-triangle-fill text-danger"></i> Low Stock Items (<= 10)
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Stock No.</th>
                                        <th>Description</th>
                                        <th class="text-end">Current Stock</th>
                                    </tr>
                                </thead>
                                <tbody id="low-stock-table-body">
                                    <!-- Rows will be inserted here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-4">
                <div class="row g-3">
                    <div class="col-12">
                        <div class="card h-100">
                            <div class="card-header">Category Distribution</div>
                            <div class="card-body">
                                <div style="height:260px;">
                                    <canvas id="categoryDistributionChart" style="width:100%; height:100%;"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="card h-100">
                            <div class="card-header">Assets by Condition</div>
                            <div class="card-body">
                                <div style="height:260px;">
                                    <canvas id="assetsByConditionChart" style="width:100%; height:100%;"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header"><i class="bi bi-clock-history"></i> Recent Activity</div>
                            <div class="list-group list-group-flush" id="recent-activity-feed" style="max-height: 350px; overflow-y: auto;">
                                <!-- Activity items will be inserted here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require_once 'includes/iirup_view_modal.php'; ?>
</main>

<!-- Chart.js for the doughnut chart -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Document management scripts removed (feature disabled) -->
<script src="assets/js/dashboard.js"></script>

<?php require_once 'includes/footer.php'; ?>