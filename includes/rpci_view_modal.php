<!-- View RPCI Modal -->
<div class="modal fade" id="rpciViewModal" tabindex="-1" aria-labelledby="rpciViewModalLabel" aria-hidden="true">
    <style>
        #rpciViewModal { z-index: 1060; }
    </style>
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rpciViewModalLabel">View Report on the Physical Count of Inventories</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="rpci-modal-body">
                <!-- RPCI content will be loaded here via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="printDocument('printable-rpci-report', 'Print RPCI')"><i class="bi bi-printer"></i> Print</button>
            </div>
        </div>
    </div>
</div>