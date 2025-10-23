<?php 
require_once 'db.php'; 
require_once 'includes/header.php'; 
require_once 'includes/sidebar.php'; 
?>

<!-- Custom CSS for RPCPPE Report -->
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
            <i class="bi bi-clipboard-check me-2"></i>
            Report on Physical Count of PPE (RPCPPE)
        </h1>
    </div>

    <!-- Compact Filters -->
    <div class="card filter-card mb-3">
        <div class="card-header bg-light d-flex align-items-center py-2">
            <form id="rpcppeFilterForm" class="d-flex align-items-center m-0">
                <label for="as_of_date_rpcppe" class="me-2 mb-0">Physical Count Date:</label>
                <input type="date" class="form-control form-control-sm date-input" id="as_of_date_rpcppe" name="as_of_date" required>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-file-earmark-text"></i> Generate
                </button>
            </form>
        </div>
    </div>

    <!-- Report Display Area -->
    <div id="report-content-area">
        <div class="placeholder-area">
            <i class="bi bi-clipboard-check"></i>
            <h6 class="mb-2">No Report Generated Yet</h6>
            <small class="text-muted">Select a date above to generate the RPCPPE report</small>
        </div>
    </div>

</main>

<!-- Template for the RPCPPE Report -->
<template id="rpcppe-report-template">
    <div class="card">
        <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <small class="text-muted me-2">Appendix 73</small>
                <span class="badge bg-secondary">RPCPPE Report</span>
            </div>
            <div class="btn-group">
                <button class="btn btn-sm btn-outline-primary" id="save-rpcppe-btn">
                    <i class="bi bi-save"></i>
                </button>
                <button class="btn btn-sm btn-outline-success" id="print-rpcppe-btn">
                    <i class="bi bi-printer"></i>
                </button>
            </div>
        </div>
        <div class="card-body p-3" id="printable-rpcppe-report">
            <style>
                #printable-rpcppe-report { 
                    font-family: Arial, sans-serif; 
                    font-size: 0.875rem;
                }
                .report-header { 
                    text-align: center; 
                    margin-bottom: 15px;
                    padding: 10px;
                    background: #fff;
                }
                .report-header h5 { 
                    font-size: 1rem;
                    font-weight: 500;
                    margin: 0 0 5px 0;
                }
                .report-header h6 { 
                    font-size: 0.875rem;
                    margin: 0;
                    color: #666;
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
                }
                .report-table td {
                    background: white;
                }
                .text-end { 
                    text-align: right; 
                }
                .signature-grid { 
                    display: grid; 
                    grid-template-columns: 1fr 1fr; 
                    gap: 1rem; 
                    margin-top: 15px;
                    font-size: 0.875rem;
                }
                .signature-line { 
                    border-top: 1px solid #dee2e6; 
                    margin-top: 30px;
                    padding-top: 5px;
                }
            </style>
            <div class="report-header">
                <h5>REPORT ON THE PHYSICAL COUNT OF PROPERTY, PLANT AND EQUIPMENT</h5>
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
                        <th rowspan="2">Property Number</th>
                        <th rowspan="2">Unit of Measure</th>
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
<script src="assets/js/rpcppe_report.js"></script>
