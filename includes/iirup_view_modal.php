<!-- IIRUP View Modal -->
<div class="modal fade" id="iirupViewModal" tabindex="-1" aria-labelledby="iirupViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="iirupViewModalLabel">View IIRUP Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="iirup-modal-body">
                <!-- IIRUP content will be loaded here via AJAX -->
                <div class="text-center p-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading IIRUP details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="print-iirup-modal-btn"><i class="bi bi-printer"></i> Print</button>
                <!-- Add other actions like Finalize/Approve if needed -->
            </div>
        </div>
    </div>
</div>

<!-- Template for IIRUP View -->
<template id="iirup-view-template">
    <div id="iirup-view-content">
        <!-- Content from API will be inserted here -->
    </div>
</template>