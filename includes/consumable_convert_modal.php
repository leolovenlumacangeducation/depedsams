<!-- Consumable Conversion Modal -->
<div class="modal fade" id="convertConsumableModal" tabindex="-1" aria-labelledby="convertConsumableModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="convertConsumableModalLabel">Convert Item to Smaller Units</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="convertConsumableForm">
        <div class="modal-body">
            <input type="hidden" id="from_consumable_id" name="from_consumable_id">

            <div class="alert alert-info">
                <h6 class="alert-heading" id="convert-item-name">Item Name</h6>
                <p class="mb-0">
                    Current Stock: <strong id="convert-current-stock">0</strong> <span id="convert-unit-name">units</span>
                </p>
            </div>

            <div class="mb-3">
                <label for="quantity_to_convert" class="form-label">Quantity to Convert</label>
                <input type="number" class="form-control" id="quantity_to_convert" name="quantity_to_convert" min="1" required>
                <div class="form-text">
                    How many of the larger units are you converting? (e.g., 1 box)
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">New Unit Details</label>
                <div class="input-group">
                    <label class="input-group-text" for="conversion_factor">Creates</label>
                    <input type="number" class="form-control" id="conversion_factor" name="conversion_factor" min="1" placeholder="e.g., 100" required aria-label="Conversion factor">
                    <label class="visually-hidden" for="to_unit_id">Select New Unit</label>
                    <select class="form-select" id="to_unit_id" name="to_unit_id" required aria-label="New unit type">
                        <option value="" selected disabled>Select New Unit...</option>
                        <?php foreach ($units as $unit): ?>
                            <option value="<?= $unit['unit_id'] ?>"><?= htmlspecialchars($unit['unit_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                 <div class="form-text">
                    How many new, smaller units are created from each larger one? (e.g., 100 pieces). The cost per new unit will be calculated automatically.
                </div>
            </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-arrow-repeat"></i> Confirm Conversion</button>
        </div>
      </form>
    </div>
  </div>
</div>