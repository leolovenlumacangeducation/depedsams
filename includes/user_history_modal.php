<!-- User Full History Modal -->
<div class="modal fade" id="userHistoryModal" tabindex="-1" aria-labelledby="userHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userHistoryModalLabel">Full Accountability History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="user-history-modal-body">
                <div class="mb-3">
                    <input type="text" id="user-history-search" class="form-control" placeholder="Filter history by item, number, or reference...">
                </div>
                <div id="user-history-table-container">
                    <!-- History timeline will be loaded here -->
                </div>
                <div class="alert alert-warning text-center no-results-message" style="display: none;">No history records match your search.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="printUserHistory()"><i class="bi bi-printer"></i> Print History</button>
            </div>
        </div>
    </div>
</div>