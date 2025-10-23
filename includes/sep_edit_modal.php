<!-- Edit SEP Details Modal -->
<div class="modal fade" id="editSepModal" tabindex="-1" aria-labelledby="editSepModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editSepForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="editSepModalLabel">Edit SEP Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit_sep_id" name="sep_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <input type="text" class="form-control" id="edit_sep_description" readonly disabled>
                        <div class="form-text">Description is linked to the Purchase Order and cannot be edited here.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Property Number</label>
                        <input type="text" class="form-control" id="edit_sep_property_number" readonly disabled>
                    </div>

                    <div class="mb-3">
                        <label for="edit_sep_brand_name" class="form-label">Brand</label>
                        <input type="text" class="form-control" id="edit_sep_brand_name" name="brand_name">
                    </div>

                    <div class="mb-3">
                        <label for="edit_sep_serial_number" class="form-label">Serial Number</label>
                        <input type="text" class="form-control" id="edit_sep_serial_number" name="serial_number">
                    </div>

                    <div class="mb-3">
                        <label for="edit_sep_useful_life" class="form-label">Estimated Useful Life (Years)</label>
                        <input type="number" class="form-control" id="edit_sep_useful_life" name="estimated_useful_life" min="0">
                    </div>

                    <div class="mb-3">
                        <label for="edit_sep_date_acquired" class="form-label">Date Acquired</label>
                        <input type="date" class="form-control" id="edit_sep_date_acquired" name="date_acquired" required>
                    </div>
                </div>
                <div class="modal-body pt-0">
                    <div class="mb-3">
                        <label for="edit_sep_condition" class="form-label">Condition</label>
                        <select class="form-select" id="edit_sep_condition" name="current_condition" required>
                            <option value="Serviceable">Serviceable</option>
                            <option value="For Repair">For Repair</option>
                            <option value="Unserviceable">Unserviceable</option>
                            <option value="Disposed">Disposed</option>
                        </select>
                        <div class="form-text mt-2">
                            <small class="text-muted">
                                <i class="bi bi-info-circle"></i> Status effects:
                                <ul class="small mt-1">
                                    <li>"Serviceable" - Item can be assigned to users</li>
                                    <li>"For Repair" - Item will appear in For Repair tab</li>
                                    <li>"Unserviceable" - Item will move to Unserviceable tab</li>
                                    <li>"Disposed" - Item will be archived in Disposed tab</li>
                                </ul>
                            </small>
                        </div>
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