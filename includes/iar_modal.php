
<!-- IAR View Modal -->
<div class="modal fade" id="iarModal" tabindex="-1" aria-labelledby="iarModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="iarModalLabel">Inspection and Acceptance Report (IAR)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="iar-modal-body">
        <!-- Content from api/iar_view.php will be loaded here. A spinner is shown by default. -->
        <div class="text-center p-5">
            <div class="spinner-border" style="width: 3rem; height: 3rem;" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <input type="hidden" id="iar_po_id">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-info" onclick="printIarView()">Print IAR</button>
        <button type="button" id="confirm-acceptance-btn" class="btn btn-success">Confirm Acceptance & Mark Delivered</button>
      </div>
    </div>
  </div>
</div>