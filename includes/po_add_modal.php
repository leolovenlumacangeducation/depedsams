<!-- Add Purchase Order Modal -->
<div class="modal fade" id="addPoModal" tabindex="-1" aria-labelledby="addPoModalLabel" aria-hidden="true">
    <style>
        /* Custom style to make the PO modals wider */
        #addPoModal .modal-dialog.modal-wide {
            max-width: 90%; /* Adjust this percentage as needed */
        }
    </style>
    <div class="modal-dialog modal-dialog-centered modal-xl modal-wide">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPoModalLabel">Add New Purchase Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addPoForm">
                    <!-- PO Header Information -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="po_number" class="form-label">PO Number</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="po_number" name="po_number" placeholder="Enter or generate PO Number">
                                <button class="btn btn-outline-secondary" type="button" id="generate-po-btn" title="Get Next PO Number">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </button>
                            </div>
                            <div class="form-text">Enter a custom PO number or click the button to generate one.</div>
                        </div>
                        <div class="col-md-4">
                            <label for="supplier_id" class="form-label">Supplier</label>
                            <select id="supplier_id" name="supplier_id" class="form-select" required>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?= $supplier['supplier_id'] ?>"><?= htmlspecialchars($supplier['supplier_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="order_date" class="form-label">Order Date</label>
                            <input type="date" class="form-control" id="order_date" name="order_date" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="purchase_mode_id" class="form-label">Mode of Purchase</label>
                            <select id="purchase_mode_id" name="purchase_mode_id" class="form-select" required>
                                <?php foreach ($purchase_modes as $mode): ?>
                                    <option value="<?= $mode['purchase_mode_id'] ?>"><?= htmlspecialchars($mode['mode_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="delivery_place_id" class="form-label">Place of Delivery</label>
                            <select id="delivery_place_id" name="delivery_place_id" class="form-select" required>
                                <?php foreach ($delivery_places as $place): ?>
                                    <option value="<?= $place['delivery_place_id'] ?>"><?= htmlspecialchars($place['place_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="delivery_term_id" class="form-label">Delivery Term</label>
                            <select id="delivery_term_id" name="delivery_term_id" class="form-select" required>
                                <?php foreach ($delivery_terms as $term): ?>
                                    <option value="<?= $term['delivery_term_id'] ?>"><?= htmlspecialchars($term['term_description']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="payment_term_id" class="form-label">Payment Term</label>
                            <select id="payment_term_id" name="payment_term_id" class="form-select" required>
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
                                    <th style="width: 20%;">Description</th>
                                    <th style="width: 5%;">Qty</th>
                                    <th style="width: 10%;">Unit</th>
                                    <th style="width: 15%;">Unit Cost</th>
                                    <th style="width: 10%;">Inventory Type</th>
                                    <th style="width: 20%;">Category</th>
                                    <th style="width: 15%;">Total</th>
                                    <th style="width: 5%;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="po-item-rows">
                                <!-- Item rows will be added here by JavaScript -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="6" class="text-end"><strong>Grand Total:</strong></td>
                                    <td id="po-grand-total" class="text-end fw-bold">₱ 0.00</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <button type="button" id="add-item-row" class="btn btn-sm btn-secondary"><i class="bi bi-plus"></i> Add Item</button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" form="addPoForm" class="btn btn-primary">Save Purchase Order</button>
            </div>
        </div>
    </div>
</div>

<!-- Template for new item rows (hidden) -->
<template id="po-item-row-template">
    <tr class="po-item-row">
        <td><textarea name="description" class="form-control form-control-sm" rows="1" required></textarea></td>
        <td><input type="number" name="quantity" class="form-control form-control-sm text-end" min="1" value="1" required></td>
        <td>
            <select name="unit_id" class="form-select form-select-sm" required>
                <?php foreach ($units as $unit): ?>
                    <option value="<?= $unit['unit_id'] ?>"><?= htmlspecialchars($unit['unit_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td><input type="number" name="unit_cost" class="form-control form-control-sm text-end" step="0.01" min="0" value="0.00" required></td>
        <td>
            <select name="inventory_type_id" class="form-select form-select-sm inventory-type-select" required>
                <option value="" selected disabled>Select...</option>
                <?php foreach ($inventory_types as $type): ?>
                    <option value="<?= $type['inventory_type_id'] ?>"><?= htmlspecialchars($type['inventory_type_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td>
            <select name="category_id" class="form-select form-select-sm category-select" required disabled>
                <option value="" selected disabled>Select...</option>
            </select>
        </td>
        <td class="text-end item-total">₱ 0.00</td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-danger remove-item-row"><i class="bi bi-trash"></i></button>
        </td>
    </tr>
</template>