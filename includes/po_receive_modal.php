<!-- Receive PO Items Modal -->
<div class="modal fade" id="receivePoModal" tabindex="-1" aria-labelledby="receivePoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="receivePoModalLabel">Receive Items for Purchase Order</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="receivePoForm">
            <input type="hidden" name="po_id" id="receive_po_id">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="delivery_receipt_no" class="form-label">Delivery Receipt No.</label>
                    <input type="text" class="form-control" id="delivery_receipt_no" name="delivery_receipt_no" required>
                </div>
                <div class="col-md-6">
                    <label for="date_received" class="form-label">Date Received</label>
                    <input type="date" class="form-control" id="date_received" name="date_received" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>
            <div id="receive-items-container">
                <!-- Item details will be loaded here by JavaScript. A spinner is shown by default. -->
                <div class="text-center p-5">
                    <div class="spinner-border" style="width: 3rem; height: 3rem;" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" form="receivePoForm" class="btn btn-primary">Receive Items</button>
      </div>
    </div>
  </div>
</div>