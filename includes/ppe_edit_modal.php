<!-- Edit PPE Details Modal -->
<div class="modal fade" id="editPpeModal" tabindex="-1" aria-labelledby="editPpeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editPpeForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPpeModalLabel">Edit PPE Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit_ppe_id" name="ppe_id">
                    
                    <div class="mb-3">
                        <label for="edit_ppe_description" class="form-label">Description</label>
                        <input type="text" class="form-control" id="edit_ppe_description" name="description" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_ppe_property_number" class="form-label">Property Number</label>
                        <input type="text" class="form-control" id="edit_ppe_property_number" name="property_number" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_ppe_model_number" class="form-label">Model Number</label>
                        <input type="text" class="form-control" id="edit_ppe_model_number" name="model_number">
                    </div>

                    <div class="mb-3">
                        <label for="edit_ppe_serial_number" class="form-label">Serial Number</label>
                        <input type="text" class="form-control" id="edit_ppe_serial_number" name="serial_number">
                    </div>

                    <div class="mb-3">
                        <label for="edit_ppe_date_acquired" class="form-label">Date Acquired</label>
                        <input type="date" class="form-control" id="edit_ppe_date_acquired" name="date_acquired" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_ppe_condition" class="form-label">Condition</label>
                        <select class="form-select" id="edit_ppe_condition" name="current_condition" required>
                            <option value="Serviceable" selected>Serviceable</option>
                            <option value="For Repair">For Repair</option>
                            <option value="Unserviceable">Unserviceable</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>