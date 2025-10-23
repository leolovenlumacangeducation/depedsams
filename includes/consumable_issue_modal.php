<!-- Issue Consumables Modal -->
<div class="modal fade" id="issueConsumableModal" tabindex="-1" aria-labelledby="issueConsumableModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form id="issueConsumableForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="issueConsumableModalLabel">Issue Supplies/Materials</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Form for issuance details -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <label for="issued_to" class="form-label">Issued To (Person/Office)</label>
                            <input class="form-control" list="issued-to-options" id="issued_to" name="issued_to" placeholder="Type or select a name..." required>
                            <datalist id="issued-to-options">
                                <?php if (isset($users)): ?>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?= htmlspecialchars($user['full_name']) ?>">
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </datalist>
                        </div>
                        <div class="col-md-4">
                            <label for="date_issued" class="form-label">Date Issued</label>
                            <input type="date" class="form-control" id="date_issued" name="date_issued" required>
                        </div>
                    </div>

                    <!-- Container for items to be issued -->
                    <h6>Items to Issue</h6>
                    <div id="issue-items-container">
                        <!-- Items will be dynamically added here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-circle"></i> Confirm Issuance</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Template for a single item row in the issue modal -->
<template id="issue-item-template">
    <div class="issue-item-row input-group mb-2" data-consumable-id="">
        <label class="input-group-text flex-grow-1" for="qty_input" title="">Item Description</label>
        <span class="input-group-text">Stock: <strong class="ms-1" data-template-id="current_stock">0</strong></span>
        <input type="number" id="qty_input" class="form-control" name="quantity_issued" placeholder="Qty" min="1" max="" required style="max-width: 100px;" aria-label="Quantity to issue">
        <button class="btn btn-outline-danger" type="button" title="Remove Item" aria-label="Remove Item"><i class="bi bi-x-lg"></i></button>
    </div>
</template>