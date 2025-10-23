<!-- View RPCPPE Modal -->
<div class="modal fade" id="rpcppeViewModal" tabindex="-1" aria-labelledby="rpcppeViewModalLabel" aria-hidden="true">
    <style>
        #rpcppeViewModal { z-index: 1060; }
    </style>
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rpcppeViewModalLabel">View Report on the Physical Count of PPE</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="rpcppe-modal-body">
                <!-- RPCPPE content will be loaded here via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="printDocument('printable-rpcppe-report', 'Print RPCPPE')"><i class="bi bi-printer"></i> Print</button>
            </div>
        </div>
    </div>
</div>