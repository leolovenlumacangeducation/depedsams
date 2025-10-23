
<!-- Edit Purchase Order Modal -->
<div class="modal fade" id="editPoModal" tabindex="-1" aria-labelledby="editPoModalLabel" aria-hidden="true">
    <style>
        /* Custom style to make the PO modals wider */
        #editPoModal .modal-dialog.modal-wide {
            max-width: 90%; /* Adjust this percentage as needed */
        }
    </style>
    <div class="modal-dialog modal-dialog-centered modal-xl modal-wide">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPoModalLabel">Edit Purchase Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="edit-po-form-container">
                    <!-- A loading spinner will be shown here initially by default -->
                    <div class="text-center p-5">
                        <div class="spinner-border" style="width: 3rem; height: 3rem;" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" form="editPoForm" class="btn btn-primary">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Template for the entire Edit Form (loaded via JS) -->
<template id="edit-po-form-template">
    <form id="editPoForm">
        <input type="hidden" id="edit_po_id" name="po_id">
        <!-- PO Header Information -->
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label for="edit_supplier_id" class="form-label">Supplier</label>
                <select id="edit_supplier_id" name="supplier_id" class="form-select" required>
                    <?php foreach ($suppliers as $supplier): ?>
                        <option value="<?= $supplier['supplier_id'] ?>"><?= htmlspecialchars($supplier['supplier_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="edit_order_date" class="form-label">Order Date</label>
                <input type="date" class="form-control" id="edit_order_date" name="order_date" required>
            </div>
            <div class="col-md-4">
                <label for="edit_purchase_mode_id" class="form-label">Mode of Purchase</label>
                <select id="edit_purchase_mode_id" name="purchase_mode_id" class="form-select" required>
                    <?php foreach ($purchase_modes as $mode): ?>
                        <option value="<?= $mode['purchase_mode_id'] ?>"><?= htmlspecialchars($mode['mode_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="edit_delivery_place_id" class="form-label">Place of Delivery</label>
                <select id="edit_delivery_place_id" name="delivery_place_id" class="form-select" required>
                    <?php foreach ($delivery_places as $place): ?>
                        <option value="<?= $place['delivery_place_id'] ?>"><?= htmlspecialchars($place['place_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="edit_delivery_term_id" class="form-label">Delivery Term</label>
                <select id="edit_delivery_term_id" name="delivery_term_id" class="form-select" required>
                    <?php foreach ($delivery_terms as $term): ?>
                        <option value="<?= $term['delivery_term_id'] ?>"><?= htmlspecialchars($term['term_description']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="edit_payment_term_id" class="form-label">Payment Term</label>
                <select id="edit_payment_term_id" name="payment_term_id" class="form-select" required>
                    <?php foreach ($payment_terms as $term): ?>
                        <option value="<?= $term['payment_term_id'] ?>"><?= htmlspecialchars($term['term_description']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- PO Items Table -->
        <h5 class="mt-4">Items</h5>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th style="width: 25%;">Description</th>
                        <th style="width: 10%;">Qty</th>
                        <th style="width: 15%;">Unit</th>
                        <th style="width: 15%;">Unit Cost</th>
                        <th style="width: 15%;">Inventory Type</th>
                        <th style="width: 15%;">Category</th>
                        <th style="width: 10%;">Total</th>
                        <th style="width: 5%;">Action</th>
                    </tr>
                </thead>
                <tbody id="edit-po-item-rows">
                    <!-- Existing and new item rows will be populated here by JavaScript -->
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="6" class="text-end"><strong>Grand Total:</strong></td>
                        <td id="edit-po-grand-total" class="text-end fw-bold">₱ 0.00</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <button type="button" id="edit-add-item-row" class="btn btn-sm btn-secondary"><i class="bi bi-plus"></i> Add New Item</button>
    </form>
</template>

<!-- Template for new item rows in Edit Modal (hidden) -->
<template id="edit-item-row-template">
    <tr class="po-item-row new-item">
        <td><textarea name="new_description" class="form-control form-control-sm" rows="1" required></textarea></td>
        <td><input type="number" name="new_quantity" class="form-control form-control-sm text-end" min="1" value="1" required></td>
        <td>
            <select name="new_unit_id" class="form-select form-select-sm" required>
                <?php foreach ($units as $unit): ?><option value="<?= $unit['unit_id'] ?>"><?= htmlspecialchars($unit['unit_name']) ?></option><?php endforeach; ?>
            </select>
        </td>
        <td><input type="number" name="new_unit_cost" class="form-control form-control-sm text-end" step="0.01" min="0" value="0.00" required></td>
        <td>
            <select name="new_inventory_type_id" class="form-select form-select-sm inventory-type-select" required>
                <option value="" selected disabled>Select...</option>
                <?php foreach ($inventory_types as $type): ?><option value="<?= $type['inventory_type_id'] ?>"><?= htmlspecialchars($type['inventory_type_name']) ?></option><?php endforeach; ?>
            </select>
        </td>
        <td>
            <select name="new_category_id" class="form-select form-select-sm category-select" required disabled><option value="" selected disabled>Select...</option></select>
        </td>
        <td class="item-total text-end">₱ 0.00</td>
        <td class="text-center align-middle"><button type="button" class="btn btn-sm btn-danger remove-item-row"><i class="bi bi-trash"></i></button></td>
    </tr>
</template>
