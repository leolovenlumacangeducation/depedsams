<!-- User Documents List Modal -->
<div class="modal fade" id="userDocsModal" tabindex="-1" aria-labelledby="userDocsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userDocsModalLabel">User Documents</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Document list will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="printUserAccountability()"><i class="bi bi-printer"></i> Print Accountability</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const userDocsModalEl = document.getElementById('userDocsModal');
    if (userDocsModalEl) {
        userDocsModalEl.addEventListener('click', function(e) {
            const viewBtn = e.target.closest('.view-doc-btn');
            if (viewBtn) {
                const docId = viewBtn.dataset.docId;
                const docType = viewBtn.dataset.docType;

                // These functions are globally available from ppe.js and ics_list.js
                if (docType === 'par' && typeof showParModal === 'function') {
                    showParModal(docId);
                } else if (docType === 'ics' && typeof showIcsModal === 'function') {
                    showIcsModal(docId);
                } else if (docType === 'ris' && typeof showRisModal === 'function') {
                    showRisModal(docId);
                }
            }
        });
    }
});
</script>