<!-- View PPE Details Modal -->
<div class="modal fade" id="viewPpeModal" tabindex="-1" aria-labelledby="viewPpeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewPpeModalLabel">Property, Plant & Equipment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="view-ppe-content">
                    <!-- Spinner for loading state -->
                    <div class="text-center p-5"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Template for the PPE view modal content -->
<template id="ppe-view-template">
    <div class="row">
        <div class="col-md-4 text-center">
            <img src="" class="img-fluid rounded bg-light mb-3" alt="PPE Photo" style="max-height: 250px; object-fit: contain;">
            <h5 class="mb-1" data-template-id="description"></h5>
            <p class="text-muted" data-template-id="property_number"></p>
        </div>
        <div class="col-md-8">
            <h6>Asset Details</h6>
            <ul class="list-group">
                <li class="list-group-item d-flex justify-content-between align-items-center">Model Number <span class="text-dark" data-template-id="model_number"></span></li>
                <li class="list-group-item d-flex justify-content-between align-items-center">Serial Number <span class="text-dark" data-template-id="serial_number"></span></li>
                <li class="list-group-item d-flex justify-content-between align-items-center">Unit Cost <span class="text-dark" data-template-id="unit_cost"></span></li>
                <li class="list-group-item d-flex justify-content-between align-items-center">Date Acquired <span class="text-dark" data-template-id="date_acquired"></span></li>
                <li class="list-group-item d-flex justify-content-between align-items-center">Assigned To <span class="text-dark fw-bold" data-template-id="assigned_to"></span></li>
            </ul>
        </div>
    </div>
</template>