<!-- Stock Card Modal -->
<div class="modal fade" id="stockCardModal" tabindex="-1" aria-labelledby="stockCardModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="stockCardModalLabel">Stock Card: <span id="stock-card-item-name"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="stock-card-printable-area">
                <div id="stock-card-content" class="table-responsive">
                    <!-- Spinner for loading state -->
                    <div class="text-center p-5"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="print-stock-card-btn"><i class="bi bi-printer"></i> Print</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Template for the stock card table -->
<template id="stock-card-table-template">
    <table class="table table-striped table-bordered table-hover">
        <thead class="table-light">
            <tr>
                <th>Date</th><th>Transaction</th><th>Reference / Issued To</th><th>In</th><th>Out</th><th>Balance</th><th>Person-in-Charge</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</template>