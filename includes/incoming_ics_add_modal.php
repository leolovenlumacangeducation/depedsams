<!-- Add Incoming ICS Modal -->
<div class="modal fade" id="addIncomingIcsModal" tabindex="-1" aria-labelledby="addIncomingIcsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="max-width: 90%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addIncomingIcsModalLabel">Add New Incoming ICS</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addIncomingIcsForm">
                <div class="modal-body">
                    <!-- Card for ICS Header Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            ICS Document Details
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="ics_number" class="form-label">ICS Number <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="ics_number" name="ics_number" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="source_office" class="form-label">Source Office / School <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="source_office" name="source_office" placeholder="e.g., Division Office" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="date_received" class="form-label">Date Received <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="date_received" name="date_received" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="issued_by_name" class="form-label">Issued By (Full Name) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="issued_by_name" name="issued_by_name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="issued_by_position" class="form-label">Position of Issuer</label>
                                    <input type="text" class="form-control" id="issued_by_position" name="issued_by_position">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card for Items on the ICS -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            Items on ICS
                            <button type="button" class="btn btn-sm btn-primary" id="addItemBtn"><i class="bi bi-plus-circle"></i> Add Item</button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="itemsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 25%;">Description</th>
                                            <th style="width: 10%;">Quantity</th>
                                            <th style="width: 12%;">Unit</th>
                                            <th style="width: 12%;">Unit Cost</th>
                                            <th style="width: 15%;">Inventory Type</th>
                                            <th style="width: 15%;">Category</th>
                                            <th style="width: 8%;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="itemsTbody">
                                        <!-- Item rows will be appended here by JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Card for Individual Asset Details -->
                    <div class="card mb-4">
                        <div class="card-header">
                            Asset Details (Property Numbers, etc.)
                        </div>
                        <div class="card-body">
                            <p class="text-muted" id="assetDetailsPlaceholder">Add items above to enter their individual details here. Property Number is required for all SEP/PPE items.</p>
                            <div id="assetDetailsContainer">
                                <!-- Asset detail forms will be generated here by JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Receive Items</button>
                </div>
            </form>
        </div>
    </div>
</div>