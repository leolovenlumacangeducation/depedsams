<!-- RIS View Modal -->
<div class="modal fade" id="risViewModal" tabindex="-1" aria-labelledby="risViewModalLabel" aria-hidden="true">
    <style>
        #risViewModal {
            z-index: 1060; /* Higher than the default 1055 */
        }
    </style>
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="risViewModalLabel">Requisition and Issue Slip (RIS)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="ris-modal-body">
                <!-- Content will be loaded here via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="printRisView()"><i class="bi bi-printer"></i> Print</button>
            </div>
        </div>
    </div>
</div>