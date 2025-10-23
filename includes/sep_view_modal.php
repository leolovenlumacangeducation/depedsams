<!-- View SEP Details Modal -->
<div class="modal fade" id="viewSepModal" tabindex="-1" aria-labelledby="viewSepModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewSepModalLabel">Semi-Expendable Property Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="view-sep-content">
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
<template id="sep-view-template">
    <div class="row">
        <div class="col-md-4 text-center">
            <img src="" class="img-fluid rounded bg-light mb-3" alt="SEP Photo" style="max-height: 250px; object-fit: contain;">
            <h5 class="mb-1" data-template-id="description"></h5>
            <p class="text-muted" data-template-id="property_number"></p>
        </div>
            <div class="col-md-8">
            <div class="mt-3">
                <!-- Details only (Documents removed) -->
                <div>
                    <ul class="list-group" id="sepDetailsList">
                        <li class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col-4">Serial Number</div>
                                <div class="col-8">
                                    <span class="view-mode text-dark" data-template-id="serial_number"></span>
                                    <input type="text" class="form-control edit-mode d-none" data-field="serial_number">
                                </div>
                            </div>
                        </li>
                        <li class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col-4">Brand</div>
                                <div class="col-8">
                                    <span class="view-mode text-dark" data-template-id="brand_name"></span>
                                    <input type="text" class="form-control edit-mode d-none" data-field="brand_name">
                                </div>
                            </div>
                        </li>
                        <li class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col-4">Unit Cost</div>
                                <div class="col-8">
                                    <span class="view-mode text-dark" data-template-id="unit_cost"></span>
                                    <input type="number" step="0.01" class="form-control edit-mode d-none" data-field="unit_cost">
                                </div>
                            </div>
                        </li>
                        <li class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col-4">Date Acquired</div>
                                <div class="col-8">
                                    <span class="view-mode text-dark" data-template-id="date_acquired"></span>
                                    <input type="date" class="form-control edit-mode d-none" data-field="date_acquired">
                                </div>
                            </div>
                        </li>
                        <li class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col-4">Estimated Useful Life</div>
                                <div class="col-8">
                                    <span class="view-mode text-dark" data-template-id="estimated_useful_life"></span>
                                    <input type="number" class="form-control edit-mode d-none" data-field="estimated_useful_life">
                                </div>
                            </div>
                        </li>
                        <li class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col-4">Condition</div>
                                <div class="col-8">
                                    <span class="view-mode text-dark" data-template-id="current_condition"></span>
                                    <select class="form-select edit-mode d-none" data-field="current_condition">
                                        <option value="Serviceable">Serviceable</option>
                                        <option value="For Repair">For Repair</option>
                                        <option value="Unserviceable">Unserviceable</option>
                                        <option value="Disposed">Disposed</option>
                                    </select>
                                </div>
                            </div>
                        </li>
                        <li class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col-4">Assigned To</div>
                                <div class="col-8">
                                    <span class="view-mode text-dark fw-bold" data-template-id="assigned_to"></span>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>
