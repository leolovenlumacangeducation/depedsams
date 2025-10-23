<!-- Delivery View Modal -->
<div class="modal fade" id="deliveryModal" tabindex="-1" aria-labelledby="deliveryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deliveryModalLabel">Delivery Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Delivery Header Information -->
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>DR Number:</strong> <span id="delivery-number"></span></p>
                                <p><strong>PO Number:</strong> <span id="po-number"></span></p>
                                <p><strong>Supplier:</strong> <span id="supplier-name"></span></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Date Received:</strong> <span id="date-received"></span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Delivery Items Table -->
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
                        <tbody id="delivery-items">
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
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="print-delivery">Print</button>
            </div>
        </div>
    </div>
</div>