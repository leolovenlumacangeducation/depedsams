<!-- ICS Sequence Modal -->
<div class="modal fade" id="icsSequenceModal" tabindex="-1" aria-labelledby="icsSequenceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="icsSequenceForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="icsSequenceModalLabel">Add New Sequence</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="ics_number_id" name="ics_number_id">
                    <div class="mb-3">
                        <label for="year" class="form-label">Year</label>
                        <input type="number" class="form-control" id="year" name="year" required>
                    </div>
                    <div class="mb-3">
                        <label for="ics_number_format" class="form-label">Format</label>
                        <input type="text" class="form-control" id="ics_number_format" name="ics_number_format" placeholder="e.g., ICS-{YYYY}-{NNNN}" required>
                        <div class="form-text">Use {YYYY} for year and {NNNN} for the padded number.</div>
                    </div>
                    <div class="mb-3">
                        <label for="start_count" class="form-label">Next Number</label>
                        <input type="number" class="form-control" id="start_count" name="start_count" min="1" required>
                        <div class="form-text">Set the number that will be used for the next generated ICS.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Sequence</button>
                </div>
            </form>
        </div>
    </div>
</div>