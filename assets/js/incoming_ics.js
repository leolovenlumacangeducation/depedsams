$(document).ready(function () {
    let itemIndex = 0;

    // --- Event Handlers ---

    // Add a new item row to the items table
    $('#addItemBtn').on('click', function () {
        const unitOptions = lookupData.units.map(u => `<option value="${u.unit_id}">${u.unit_name}</option>`).join('');
        const categoryOptions = lookupData.categories.map(c => `<option value="${c.category_id}">${c.category_name}</option>`).join('');

        const itemRow = `
            <tr data-index="${itemIndex}">
                <td><textarea class="form-control item-description" rows="1" required></textarea></td>
                <td>
                    <select class="form-select item-category" required>
                        <option value="" selected disabled>Choose...</option>
                        ${categoryOptions}
                    </select>
                </td>
                <td><input type="number" class="form-control item-quantity" min="1" value="1" required></td>
                <td>
                    <select class="form-select item-unit" required>
                        <option value="" selected disabled>Choose...</option>
                        ${unitOptions}
                    </select>
                </td>
                <td><input type="number" class="form-control item-unit-cost" min="0" step="0.01" required></td>
                <td><input type="text" class="form-control item-inventory-type" readonly></td>
                <td><button type="button" class="btn btn-sm btn-danger removeItemBtn"><i class="bi bi-trash"></i></button></td>
            </tr>
        `;
        $('#itemsTbody').append(itemRow);
        itemIndex++;
        updateAssetDetails();
    });

    // Remove an item row
    $('#itemsTbody').on('click', '.removeItemBtn', function () {
        $(this).closest('tr').remove();
        updateAssetDetails();
    });

    // Update inventory type and asset details when category, quantity, or cost changes
    $('#itemsTbody').on('change input', '.item-category, .item-quantity, .item-unit-cost', function () {
        updateAssetDetails();
    });

    // Handle form submission
    $('#incomingIcsForm').on('submit', function (e) {
        e.preventDefault();
        submitIncomingIcs();
    });

    // --- Core Functions ---

    /**
     * Updates the inventory type field and regenerates the asset detail forms.
     */
    function updateAssetDetails() {
        $('#assetDetailsContainer').empty();
        let hasAssets = false;

        $('#itemsTbody tr').each(function () {
            const row = $(this);
            const index = row.data('index');
            const unitCost = parseFloat(row.find('.item-unit-cost').val()) || 0;
            const quantity = parseInt(row.find('.item-quantity').val()) || 0;
            const inventoryTypeSelect = row.find('.item-inventory-type');
            let inventoryTypeId = inventoryTypeSelect.val();

            // Auto-determine inventory type based on unit cost, if not Consumable
            if (inventoryTypeId !== '3') { // Assuming '3' is Consumable
                if (unitCost >= 50000) {
                    inventoryTypeId = '1'; // PPE
                } else if (unitCost >= 5000) {
                    inventoryTypeId = '2'; // SEP
                }
                inventoryTypeSelect.val(inventoryTypeId);
            }

            const categoryId = row.find('.item-category').val();

            let inventoryTypeName = '';
            if (inventoryTypeId == 1) inventoryTypeName = 'PPE';
            else if (inventoryTypeId == 2) inventoryTypeName = 'SEP';
            else inventoryTypeName = 'Consumable';

            // The readonly input is not present in this form, so we skip updating it.

            // Generate asset detail inputs only for SEP and PPE
            if ((inventoryTypeName === 'SEP' || inventoryTypeName === 'PPE') && quantity > 0) {
                hasAssets = true;
                let detailsHtml = `<div class="mb-4 p-3 border rounded" id="asset-group-${index}"><h5>Details for: <small class="text-muted">${row.find('.item-description').val() || `Item #${index + 1}`}</small></h5>`;
                for (let i = 0; i < quantity; i++) {
                    detailsHtml += `
                        <div class="row g-2 mb-2 border-bottom pb-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label small">Property Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm asset-property-number" data-item-index="${index}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Serial Number</label>
                                <input type="text" class="form-control form-control-sm asset-serial-number" data-item-index="${index}">
                            </div>
                    `;
                    if (inventoryTypeName === 'SEP') {
                        detailsHtml += `
                            <div class="col-md-3">
                                <label class="form-label small">Brand</label>
                                <input type="text" class="form-control form-control-sm asset-brand-name" data-item-index="${index}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Useful Life (yrs)</label>
                                <input type="number" class="form-control form-control-sm asset-useful-life" data-item-index="${index}" min="0">
                            </div>
                        `;
                    } else { // PPE
                        detailsHtml += `
                            <div class="col-md-3">
                                <label class="form-label small">Model Number</label>
                                <input type="text" class="form-control form-control-sm asset-model-number" data-item-index="${index}">
                            </div>
                        `;
                    }
                    detailsHtml += `
                            <div class="col-md-1">
                                <label class="form-label small">Condition</label>
                                <select class="form-select form-select-sm asset-condition" data-item-index="${index}">
                                    <option>Serviceable</option>
                                    <option>Unserviceable</option>
                                    <option>For Repair</option>
                                </select>
                            </div>
                        </div>
                    `;
                }
                detailsHtml += `</div>`;
                $('#assetDetailsContainer').append(detailsHtml);
            }
        });

        // Show/hide placeholder text
        $('#assetDetailsPlaceholder').toggle(!hasAssets);
    }

    /**
     * Gathers data from the form and submits it to the API.
     */
    function submitIncomingIcs() {
        const header = {
            ics_number: $('#ics_number').val(),
            source_office: $('#source_office').val(),
            date_received: $('#date_received').val(),
            issued_by_name: $('#issued_by_name').val(),
            issued_by_position: $('#issued_by_position').val()
        };

        const items = [];
        let isValid = true;

        $('#itemsTbody tr').each(function () {
            const row = $(this);
            const index = row.data('index');
            const inventoryType = row.find('.item-inventory-type').val();
            const item = {
                description: row.find('.item-description').val(),
                category_id: row.find('.item-category').val(),
                quantity: row.find('.item-quantity').val(),
                unit_id: row.find('.item-unit').val(),
                unit_cost: row.find('.item-unit-cost').val(),
                inventory_type: inventoryType,
                details: []
            };

            if (inventoryType === 'SEP' || inventoryType === 'PPE') {
                $(`#asset-group-${index} .row`).each(function () {
                    const detailRow = $(this);
                    const detail = {
                        property_number: detailRow.find('.asset-property-number').val(),
                        serial_number: detailRow.find('.asset-serial-number').val(),
                        condition: detailRow.find('.asset-condition').val(),
                    };

                    if (inventoryType === 'SEP') {
                        detail.brand_name = detailRow.find('.asset-brand-name').val();
                        detail.useful_life = detailRow.find('.asset-useful-life').val();
                    } else { // PPE
                        detail.model_number = detailRow.find('.asset-model-number').val();
                    }

                    if (!detail.property_number) {
                        Swal.fire('Validation Error', 'Property Number is required for all SEP/PPE items.', 'error');
                        detailRow.find('.asset-property-number').focus();
                        isValid = false;
                        return false; // break inner loop
                    }
                    item.details.push(detail);
                });
            }
            if (!isValid) return false; // break outer loop
            items.push(item);
        });

        if (!isValid) return;

        const payload = { header, items };

        // AJAX submission
        $.ajax({
            url: 'api/incoming_ics_receive.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            success: function (response) {
                Swal.fire('Success!', response.message, 'success').then(() => {
                    $('#incomingIcsForm')[0].reset();
                    $('#itemsTbody').empty();
                    updateAssetDetails();
                });
            },
            error: function (xhr) {
                const errorMsg = xhr.responseJSON ? xhr.responseJSON.message : 'An unexpected error occurred.';
                Swal.fire('Error!', errorMsg, 'error');
            }
        });
    }
});