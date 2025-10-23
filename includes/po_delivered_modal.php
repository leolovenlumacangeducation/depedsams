<!-- Mark as Delivered Confirmation Modal -->
<div class="modal fade" id="deliveredPoModal" tabindex="-1" aria-labelledby="deliveredPoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deliveredPoModalLabel">Confirm Delivery</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="d-flex align-items-center">
          <i class="bi bi-exclamation-triangle-fill text-warning me-3" style="font-size: 2rem;"></i>
          <div>
        <p>Are you sure you want to mark Purchase Order #<strong id="delivered-po-number"></strong> as 'Delivered'?</p>
            <p class="text-muted small mb-0">This will generate the Inspection and Acceptance Report (IAR). This action should only be performed after all items have been processed via the "Receive Items" function.</p>
          </div>
        </div>
        <input type="hidden" id="delivered_po_id">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="confirm-delivered-btn" class="btn btn-success">Yes, Mark as Delivered</button>
      </div>
    </div>
  </div>
</div>