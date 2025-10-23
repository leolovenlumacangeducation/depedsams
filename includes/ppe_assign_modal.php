<?php
/**
 * Renders the Assign PPE Modal.
 * @param array $users An array of users to populate the dropdown.
 */
function render_assign_ppe_modal(array $users = []) {
?>
<!-- Assign PPE Modal -->
<div class="modal fade" id="assignPpeModal" tabindex="-1" aria-labelledby="assignPpeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="assignPpeForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="assignPpeModalLabel">Assign Property, Plant & Equipment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>You are assigning the following item(s):</p>
                    <ul class="list-group mb-3" id="assign-items-container">
                        <!-- Selected items will be populated here by JS -->
                    </ul>
                    <div class="mb-3">
                        <label for="assign_to_user_id" class="form-label">Assign To (Custodian)</label>
                        <select class="form-select" id="assign_to_user_id" name="user_id" required>
                            <option value="" selected disabled>Select a user...</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= htmlspecialchars($user['user_id']) ?>"><?= htmlspecialchars($user['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="location" class="form-label">Location / Office</label>
                        <input type="text" class="form-control" id="location" name="location" placeholder="e.g., Principal's Office, Grade 10 - Rizal" required>
                    </div>
                    <hr>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" role="switch" id="generate_par" name="generate_par" checked>
                        <label class="form-check-label" for="generate_par">Generate Property Acknowledgment Receipt (PAR)</label>
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
<?php
}
?>