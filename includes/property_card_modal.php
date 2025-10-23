<!-- Property Card Modal -->
<div class="modal fade" id="propertyCardModal" tabindex="-1" aria-labelledby="propertyCardModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="propertyCardModalLabel">Property Card Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="property-card-modal-body">
                <!-- Content will be loaded here by JS -->
                <div class="text-center p-5"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" onclick="downloadDocumentAsPdf('property-card-content', 'Property_Card', '.property-card-number-data')"><i class="bi bi-file-earmark-pdf"></i> Download PDF</button>
                <button type="button" class="btn btn-primary" onclick="printDocument('property-card-content', 'Print Property Card')"><i class="bi bi-printer"></i> Print</button>
            </div>
        </div>
    </div>
</div>