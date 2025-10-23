<!-- View ICS Modal -->
<div class="modal fade" id="icsViewModal" tabindex="-1" aria-labelledby="icsViewModalLabel" aria-hidden="true">
    <style>
        #icsViewModal {
            z-index: 1060; /* Higher than the default 1055 */
        }
    </style>
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="icsViewModalLabel">View Inventory Custodian Slip</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="ics-modal-body">
                <!-- ICS content will be loaded here via AJAX -->
                <div class="text-center p-5">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" onclick="downloadDocumentAsPdf('ics-view-content', 'ICS_Document', '[data-template-id=\'ics_number\']')"><i class="bi bi-file-earmark-arrow-down"></i> Download PDF</button>
                <button type="button" class="btn btn-primary" onclick="printDocument('ics-view-content', 'Print ICS')"><i class="bi bi-printer"></i> Print ICS</button>
            </div>
        </div>
    </div>
</div>