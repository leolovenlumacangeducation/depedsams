<!-- IAR View Modal -->
<div class="modal fade" id="iarModal" tabindex="-1" aria-labelledby="iarModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="iarModalLabel">Inspection and Acceptance Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- IAR Header Information -->
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>IAR Number:</strong> <span id="iar-number"></span></p>
                                <p><strong>PO Number:</strong> <span id="po-number"></span></p>
                                <p><strong>Supplier:</strong> <span id="supplier-name"></span></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Date Received:</strong> <span id="date-received"></span></p>
                                <p><strong>Delivery Receipt:</strong> <span id="dr-number"></span></p>
                                <p><strong>Invoice Number:</strong> <span id="invoice-number"></span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- IAR Items Table -->
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>Stock/Property No.</th>
                                <th>Description</th>
                                <th>Unit</th>
                                <th>Quantity</th>
                                <th>Unit Cost</th>
                                <th>Total Cost</th>
                            </tr>
                        </thead>
                        <tbody id="iar-items">
                            <!-- Items will be loaded here -->
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" class="text-end"><strong>Total Amount:</strong></td>
                                <td class="text-end" id="total-amount"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Inspection Details -->
                <div class="card mt-3">
                    <div class="card-body">
                        <h6 class="card-title">Inspection Details</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Inspected By:</strong> <span id="inspected-by"></span></p>
                                <p><strong>Date Inspected:</strong> <span id="date-inspected"></span></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Inspection Report No:</strong> <span id="inspection-report-no"></span></p>
                                <p><strong>Status:</strong> <span id="inspection-status"></span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="print-iar">Print IAR</button>
            </div>
        </div>
    </div>
</div>