<!-- Assign SEP Modal -->
<div class="modal fade" id="assignSepModal" tabindex="-1" aria-labelledby="assignSepModalLabel">
    <div class="modal-dialog" role="dialog">
        <div class="modal-content" tabindex="0">
            <form id="assignSepForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="assignSepModalLabel">Assign Semi-Expendable Property</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>You are assigning the following item(s):</p>
                    <ul class="list-group mb-3" id="assign-items-container">
                        <!-- Selected items will be populated here by JS -->
                    </ul>
                    <div class="mb-3">
                        <label for="assign_to_user_id" class="form-label">Assign To (Custodian)</label>
                        <input class="form-control" list="usersDatalist" id="assign_to_user_id" name="user_id" placeholder="Type to search or add a new user..." required>
                        <datalist id="usersDatalist">
                            <?php if (isset($users)): ?>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= htmlspecialchars($user['full_name']) ?>">
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </datalist>
                        <div class="form-text">You can assign to an existing user or type a new name to create a new (inactive) user record.</div>
                    </div>
                    <div class="mb-3">
                        <label for="location" class="form-label">Location / Office</label>
                        <input type="text" class="form-control" id="location" name="location" placeholder="e.g., Principal's Office, Grade 10 - Rizal" required>
                    </div>
                    <hr>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" role="switch" id="generate_ics" name="generate_ics" checked>
                        <label class="form-check-label" for="generate_ics">Generate Inventory Custodian Slip (ICS)</label>
                    </div>
                    <div id="ics-options-container">
                        <label for="ics_number_preview" class="form-label small text-muted">ICS Number</label>
                        <input type="text" class="form-control form-control-sm" id="ics_number_preview" readonly>
                        <div class="form-text">The next available ICS number will be used upon confirmation.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Confirm Assignment</button>
                </div>
            </form>
        </div>
    </div>
</div>