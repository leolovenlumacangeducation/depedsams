<!-- View Consumable Details Modal -->
<div class="modal fade" id="viewConsumableModal" tabindex="-1" aria-labelledby="viewConsumableModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewConsumableModalLabel">Consumable Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="view-consumable-content">
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

<!-- Template for the view modal content -->
<template id="consumable-view-template">
    <div class="row">
        <div class="col-md-4 text-center">
            <img src="" class="img-fluid rounded bg-light mb-3" alt="Consumable Photo" style="max-height: 250px; object-fit: contain;">
            <h5 class="mb-1" data-template-id="description"></h5>
            <p class="text-muted" data-template-id="stock_number"></p>
        </div>
        <div class="col-md-8">
            <h6>Inventory Details</h6>
            <ul class="list-group">
                <li class="list-group-item d-flex justify-content-between align-items-center">Unit Cost <span class="text-dark" data-template-id="unit_cost"></span></li>
                <li class="list-group-item d-flex justify-content-between align-items-center">Unit of Measure <span class="text-dark" data-template-id="unit_name"></span></li>
                <li class="list-group-item d-flex justify-content-between align-items-center">Date Received <span class="text-dark" data-template-id="date_received"></span></li>
                <li class="list-group-item d-flex justify-content-between align-items-center">Initial Quantity <span class="text-dark" data-template-id="quantity_received"></span></li>
                <li class="list-group-item d-flex justify-content-between align-items-center">Current Stock <span class="text-dark fw-bold" data-template-id="current_stock"></span></li>
            </ul>
        </div>
    </div>
</template>