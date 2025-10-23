<?php 
require_once 'db.php'; 
require_once 'includes/header.php'; 
require_once 'includes/sidebar.php'; 
?>

<!-- Custom CSS for RPCI Report -->
<style>
    .page-title {
        color: #2c3e50;
        font-size: 1.2rem;
        margin-bottom: 1rem;
    }
    .filter-card {
        background: #fff;
        border: 1px solid #dee2e6;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .date-input {
        width: 200px !important;
        margin-right: 10px;
    }
    .placeholder-area {
        background: #f8f9fa;
        border-radius: 4px;
        padding: 2rem;
        text-align: center;
        color: #6c757d;
    }
    .placeholder-area i {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
        color: #95a5a6;
    }
</style>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2">
        <h1 class="page-title">
            <i class="bi bi-clipboard-data me-2"></i>
            Report on Physical Count of Inventories (RPCI)
        </h1>
    </div>

    <!-- Compact Filters -->
    <div class="card filter-card mb-3">
        <div class="card-header bg-light d-flex align-items-center py-2">
            <form id="rpciFilterForm" class="d-flex align-items-center m-0">
                <label for="as_of_date" class="me-2 mb-0">Physical Count Date:</label>
                <input type="date" class="form-control form-control-sm date-input" id="as_of_date" name="as_of_date" required>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-file-earmark-text"></i> Generate
                </button>
            </form>
        </div>
    </div>

    <!-- Enhanced Report Display Area -->
    <div id="report-content-area">
        <div class="placeholder-area">
            <i class="bi bi-file-earmark-text"></i>
            <h5 class="mb-3">No Report Generated Yet</h5>
            <p class="text-muted mb-0">Select a date above and click "Generate Report" to view the RPCI</p>
        </div>
    </div>

</main>

<!-- Template for the RPCI Report -->
<template id="rpci-report-template">
    <div class="card">
        <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <small class="text-muted me-2">Appendix 66</small>
                <span class="badge bg-secondary">Physical Count Report</span>
            </div>
            <div class="btn-group">
                <button class="btn btn-sm btn-outline-primary" id="save-adjustments-btn">
                    <i class="bi bi-save"></i>
                </button>
                <button class="btn btn-sm btn-outline-success" id="print-rpci-btn">
                    <i class="bi bi-printer"></i>
                </button>
            </div>
        </div>
        <div class="card-body p-4" id="printable-rpci-report">
            <style>
                #printable-rpci-report { 
                    font-family: 'Segoe UI', Arial, sans-serif; 
                    font-size: 10pt;
                    line-height: 1.4;
                }
                .report-header { 
                    text-align: center; 
                    margin-bottom: 30px;
                    padding: 20px;
                    background: #f8f9fa;
                    border-radius: 8px;
                }
                .report-header h5 { 
                    font-size: 1.25rem;
                    font-weight: 600;
                    color: #2c3e50;
                    margin: 0 0 10px 0;
                }
                .report-header h6 { 
                    font-size: 1rem;
                    color: #6c757d;
                    margin: 0;
                }
                .report-table { 
                    width: 100%; 
                    border-collapse: collapse;
                    font-size: 0.875rem;
                    margin: 10px 0;
                }
                .report-table th, .report-table td { 
                    border: 1px solid #dee2e6;
                    padding: 4px 6px;
                    vertical-align: middle;
                }
                .report-table th { 
                    background: white;
                    text-align: center;
                    font-weight: 500;
                    font-size: 0.875rem;
                    color: #212529;
                }
                .report-table td {
                    background: white;
                }
                .text-end { text-align: right; }
                .signature-grid { 
                    display: grid; 
                    grid-template-columns: 1fr 1fr; 
                    gap: 3rem; 
                    margin-top: 50px;
                    padding: 20px;
                    background: #fff;
                    border-radius: 8px;
                }
                .signature-line { 
                    border-top: 2px solid #dee2e6; 
                    margin-top: 50px;
                    padding-top: 10px;
                }
                @media print {
                    .report-header {
                        background: none !important;
                    }
                    .report-table th {
                        background: none !important;
                        border: 1px solid #000 !important;
                    }
                    .report-table td {
                        border: 1px solid #000 !important;
                    }
                    .signature-line {
                        border-top: 1px solid #000 !important;
                    }
                }
            </style>
            <div class="report-header">
                <h5>REPORT ON THE PHYSICAL COUNT OF INVENTORIES</h5>
                <h6 data-template-id="as_of_date">As of _______________</h6>
            </div>
            <div class="d-flex justify-content-between mb-3">
                <span>Fund Cluster: _______________</span>
                <span>For which <span data-template-id="custodian_name" class="fw-bold"></span>, <span data-template-id="school_name" class="fw-bold"></span> is accountable, having assumed such accountability on _______________.</span>
            </div>

            <table class="report-table">
                <thead>
                    <tr>
                        <th rowspan="2">Article</th>
                        <th rowspan="2">Description</th>
                        <th rowspan="2">Stock Number</th>
                        <th rowspan="2">Unit of Measurement</th>
                        <th rowspan="2">Unit Value</th>
                        <th>Balance Per Card</th>
                        <th>On Hand Per Count</th>
                        <th colspan="2">Shortage/Overage</th>
                        <th rowspan="2">Remarks</th>
                    </tr>
                    <tr>
                        <th>(Quantity)</th>
                        <th>(Quantity)</th>
                        <th>(Quantity)</th>
                        <th>(Value)</th>
                    </tr>
                </thead>
                <tbody data-template-id="items_tbody">
                    <!-- Item rows will be inserted here -->
                </tbody>
            </table>

            <div class="signature-grid">
                <div>
                    Certified Correct by:
                    <div class="signature-line text-center" data-template-id="approving_officer_name"></div>
                    <div class="text-center">Signature Over Printed Name of<br>Inventory Committee Chair and Members</div>
                </div>
                <div>
                    Approved by:
                    <div class="signature-line text-center">__________________________</div>
                    <div class="text-center">Signature Over Printed Name of Head of Agency/Entity or His/Her Authorized Representative</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<?php require_once 'includes/footer.php'; ?>
<script src="assets/js/rpci_report.js"></script>
