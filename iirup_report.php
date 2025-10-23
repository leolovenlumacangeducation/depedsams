<?php 
require_once 'db.php'; 
require_once 'includes/header.php'; 
require_once 'includes/sidebar.php'; 

// Get the IIRUP ID from the URL, if it exists.
$iirup_id = $_GET['iirup_id'] ?? null;
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2">
        <h1 class="page-title">
            <i class="bi bi-exclamation-triangle me-2"></i>
            Inventory & Inspection Report of Unserviceable Property (IIRUP)
        </h1>
    </div>

    <!-- Compact Controls -->
    <div class="card mb-3">
        <div class="card-header bg-light py-2 d-flex align-items-center justify-content-between">
            <?php if ($iirup_id): ?>
                <div class="d-flex align-items-center gap-2">
                    <small class="text-muted">Viewing IIRUP:</small>
                    <strong>#<?= htmlspecialchars($iirup_id) ?></strong>
                </div>
                <div class="btn-group">
                    <button type="button" id="printIirupReportBtn" class="btn btn-sm btn-outline-primary"><i class="bi bi-printer"></i></button>
                    <a href="disposal.php" class="btn btn-sm btn-outline-secondary">Back</a>
                </div>
            <?php else: ?>
                <form id="iirupFilterForm" class="d-flex align-items-center gap-2 m-0">
                    <label for="selectIirupDoc" class="mb-0 me-1">Select IIRUP:</label>
                    <select id="selectIirupDoc" class="form-select form-select-sm" style="width:260px">
                        <option value="">-- Select an IIRUP --</option>
                        <!-- Options will be loaded dynamically -->
                    </select>
                    <button type="button" id="viewSelectedIirupBtn" class="btn btn-sm btn-primary" disabled>View</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Report Display Area -->
    <div id="report-content-area">
        <div class="placeholder-area">
            <i class="bi bi-file-earmark-text"></i>
            <h6 class="mb-2">No Report Selected</h6>
            <small class="text-muted">Choose an IIRUP from the selector above or open a specific IIRUP document.</small>
        </div>
    </div>

    <?php if (!$iirup_id): ?>
    <div class="card mt-3">
        <div class="card-header">All IIRUP Documents</div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="allIirupDocsTable" class="table table-sm table-bordered" style="width:100%">
                    <thead><tr><th>IIRUP No.</th><th>As of Date</th><th>Disposal Method</th><th>Status</th><th>Created By</th><th>Actions</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

</main>

<!-- Template for the IIRUP Report -->
<template id="iirup-report-template">
    <div class="card">
        <div class="card-body p-3" id="printable-iirup-report">
            <style>
                #printable-iirup-report { 
                    font-family: Arial, sans-serif; 
                    font-size: 0.875rem; 
                }
                .report-header { 
                    text-align: center; 
                    margin-bottom: 12px;
                    padding: 6px 0;
                }
                .report-header h5 { 
                    font-size: 1rem;
                    font-weight: 600;
                    margin: 0 0 4px 0;
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
                    margin: 8px 0;
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
                .text-end { 
                    text-align: right; 
                }
                .signature-grid { 
                    display: grid; 
                    grid-template-columns: 1fr 1fr; 
                    gap: 1rem; 
                    margin-top: 12px;
                    font-size: 0.875rem;
                }
                .signature-line { 
                    border-top: 1px solid #dee2e6; 
                    margin-top: 24px;
                    padding-top: 4px;
                }
            </style>
            <div class="report-header">
                <h5>INVENTORY AND INSPECTION REPORT OF UNSERVICEABLE PROPERTY</h5>
                <h6 data-template-id="as_of_date">As of _______________</h6>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span>Entity Name: <span data-template-id="school_name" class="fw-bold">Pagadian City National Comprehensive High School</span></span>
                <span>Fund Cluster: _______________</span>
            </div>

            <table class="report-table">
                <thead>
                    <tr>
                        <th>Date Acquired</th>
                        <th>Article</th>
                        <th>Property No.</th>
                        <th>Qty.</th>
                        <th>Unit Cost</th>
                        <th>Total Cost</th>
                        <th>Accumulated Depreciation</th>
                        <th>Book Value</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody data-template-id="items_tbody">
                    <!-- Item rows will be inserted here -->
                </tbody>
                <tfoot data-template-id="report_tfoot">
                    <tr><th colspan="5" class="text-end">TOTAL:</th><th class="text-end" data-template-id="total_cost">0.00</th><th colspan="3"></th></tr>
                </tfoot>
            </table>

            <div class="signature-grid">
                <div class="signature-block">
                    I HEREBY request inspection and disposal of the foregoing property.
                    <div class="signature-line text-center" data-template-id="custodian_name"></div>
                    <div class="text-center">Signature over Printed Name of<br>Property and/or Supply Custodian</div>
                </div>
                <div class="signature-block">
                    I CERTIFY that I have inspected the property and that it is unserviceable.
                    <div class="signature-line text-center">__________________________</div>
                    <div class="text-center">Signature over Printed Name of<br>Inspection Officer</div>
                </div>
            </div>
            <div class="mt-3 signature-block">
                <p class="mb-1">Disposal Method: <strong data-template-id="disposal_method"></strong></p>
                <p class="mb-1">IIRUP Status: <strong data-template-id="iirup_status"></strong></p>
                <p class="mb-0">Generated By: <strong data-template-id="generated_by"></strong> on <span data-template-id="date_generated"></span></p>
            </div>
        </div>
    </div>
</template>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<?php require_once 'includes/footer.php'; ?>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.min.js"></script>
<!-- Document management scripts removed (feature disabled) -->
<script src="assets/js/iirup_report.js"></script>
