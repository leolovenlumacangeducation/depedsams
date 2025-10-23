<?php 
require_once 'db.php'; 
require_once 'includes/header.php'; 
require_once 'includes/sidebar.php'; 
?>

<!-- Custom CSS for RSMI Report -->
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
        width: 150px !important;
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
            <i class="bi bi-file-text me-2"></i>
            Report of Supplies and Materials Issued (RSMI)
        </h1>
    </div>

    <!-- Compact Filters -->
    <div class="card filter-card mb-3">
        <div class="card-header bg-light d-flex align-items-center py-2">
            <form id="rsmiFilterForm" class="d-flex align-items-center gap-2 m-0">
                <label class="mb-0 me-1">Period:</label>
                <div class="d-flex align-items-center">
                    <input type="date" class="form-control form-control-sm date-input" id="start_date" name="start_date" required>
                    <span class="mx-1">to</span>
                    <input type="date" class="form-control form-control-sm date-input" id="end_date" name="end_date" required>
                </div>
                <button type="submit" class="btn btn-primary btn-sm ms-2">
                    <i class="bi bi-file-earmark-text"></i> Generate
                </button>
            </form>
        </div>
    </div>

    <!-- Report Display Area -->
    <div id="report-content-area">
        <div class="placeholder-area">
            <i class="bi bi-calendar3"></i>
            <h6 class="mb-2">No Report Generated Yet</h6>
            <small class="text-muted">Select a date range above to generate the RSMI report</small>
        </div>
    </div>

</main>

<!-- Template for the RSMI Report -->
<template id="rsmi-report-template">
    <div class="card">
        <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <small class="text-muted me-2">Appendix 64</small>
                <span class="badge bg-secondary">RSMI Report</span>
            </div>
            <button class="btn btn-sm btn-outline-success" id="print-rsmi-btn">
                <i class="bi bi-printer"></i>
            </button>
        </div>
        <div class="card-body p-3" id="printable-rsmi-report">
            <style>
                #printable-rsmi-report { 
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
                .recap-grid { 
                    display: grid; 
                    grid-template-columns: 1fr 1fr; 
                    gap: 1rem; 
                    margin-top: 15px;
                    font-size: 0.875rem;
                }
                .signature-block { 
                    margin-top: 20px;
                    font-size: 0.875rem;
                }
                .signature-line { 
                    border-top: 1px solid #dee2e6; 
                    margin-top: 30px;
                    padding-top: 5px;
                }
            </style>
            <div class="report-header">
                <h5>REPORT OF SUPPLIES AND MATERIALS ISSUED</h5>
                <h6 data-template-id="entity_name"></h6>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span data-template-id="fund_cluster">Fund Cluster: _______________</span>
                <span data-template-id="serial_no">Serial No.: _______________</span>
            </div>
            <div class="d-flex justify-content-between mb-3">
                <span></span>
                <span data-template-id="report_date">Date: _______________</span>
            </div>

            <table class="report-table">
                <thead>
                    <tr>
                        <th>RIS No.</th>
                        <th>Responsibility Center Code</th>
                        <th>Stock No.</th>
                        <th>Item</th>
                        <th>Unit</th>
                        <th>Qty Issued</th>
                        <th>Unit Cost</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody data-template-id="items_tbody">
                    <!-- Item rows will be inserted here -->
                </tbody>
            </table>

            <div class="recap-grid">
                <div>
                    <strong>Recapitulation:</strong>
                    <table class="report-table">
                        <thead><tr><th>Stock No.</th><th>Quantity</th></tr></thead>
                        <tbody data-template-id="recap_stock_tbody"></tbody>
                    </table>
                </div>
                <div>
                    <strong>Recapitulation:</strong>
                    <table class="report-table">
                        <thead><tr><th>UACS Object Code</th><th>Total Cost</th></tr></thead>
                        <tbody data-template-id="recap_uacs_tbody"></tbody>
                    </table>
                </div>
            </div>

            <div class="recap-grid">
                <div class="signature-block">
                    I hereby certify to the correctness of the above information.
                    <div class="signature-line text-center" data-template-id="custodian_name"></div>
                    <div class="text-center">Signature over Printed Name of Supply and/or Property Custodian</div>
                </div>
                <div class="signature-block">
                    Posted by:
                    <div class="signature-line text-center">__________________________</div>
                    <div class="text-center">Signature over Printed Name of Designated Accounting Staff</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<?php require_once 'includes/footer.php'; ?>
<script src="assets/js/rsmi_report.js"></script>

