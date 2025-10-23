$(document).ready(function () {
    let itemIndex = 0;
    const addModal = new bootstrap.Modal(document.getElementById('addIncomingIcsModal'));

    // --- Event Handlers ---

    // Add a new item row to the items table
    $('#addItemBtn').on('click', function () {
        const inventoryTypeOptions = lookupData.inventoryTypes.map(it => `<option value="${it.inventory_type_id}">${it.inventory_type_name}</option>`).join('');
        const unitOptions = lookupData.units.map(u => `<option value="${u.unit_id}">${u.unit_name}</option>`).join('');

        const itemRow = `
            <tr data-index="${itemIndex}">
                <td><textarea class="form-control item-description" rows="1" required></textarea></td>
                <td><input type="number" class="form-control item-quantity" min="1" value="1" required></td>
                <td>
                    <select class="form-select item-unit" required>
                        <option value="" selected disabled>Choose...</option>
                        ${unitOptions}
                    </select>
                </td>
                <td><input type="number" class="form-control item-unit-cost" min="0" step="0.01" required></td>
                <td>
                    <select class="form-select item-inventory-type" required>
                        <option value="" selected disabled>Choose...</option>
                        ${inventoryTypeOptions}
                    </select>
                </td>
                <td>
                    <select class="form-select item-category" required disabled>
                        <option value="" selected disabled>Choose...</option>
                    </select>
                </td>
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

    // When Inventory Type changes, update the Category dropdown
    $('#itemsTbody').on('change', '.item-inventory-type', function () {
        const row = $(this).closest('tr');
        const categorySelect = row.find('.item-category');
        const selectedTypeId = $(this).val();

        // Clear current options and disable
        categorySelect.html('<option value="" selected disabled>Choose...</option>').prop('disabled', true);

        if (selectedTypeId) {
            // Filter categories that match the selected inventory type
            const filteredCategories = lookupData.categories.filter(category => category.inventory_type_id == selectedTypeId);
            // Populate the category dropdown
            filteredCategories.forEach(category => {
                categorySelect.append(new Option(category.category_name, category.category_id));
            });
            categorySelect.prop('disabled', false);
        }
    });

    // Update asset details when inventory type or quantity changes
    $('#itemsTbody').on('change', '.item-inventory-type, .item-quantity', function() {
        updateAssetDetails();
    });

    // Handle form submission
    $('#addIncomingIcsForm').on('submit', function (e) {
        e.preventDefault();
        submitIncomingIcs();
    });

    // Reset form when modal is hidden
    $('#addIncomingIcsModal').on('hidden.bs.modal', function () {
        $('#addIncomingIcsForm')[0].reset();
        $('#itemsTbody').empty();
        updateAssetDetails();
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
            const categoryId = row.find('.item-category').val();
            const quantity = parseInt(row.find('.item-quantity').val()) || 0;
            const inventoryTypeId = row.find('.item-inventory-type').val();

            if (!inventoryTypeId) {
                return;
            }

            // Determine inventory type name from the selected dropdown value
            let inventoryTypeName = '';
            if (inventoryTypeId == 3) inventoryTypeName = 'PPE'; // Corrected ID for PPE
            else if (inventoryTypeId == 2) inventoryTypeName = 'SEP';
            else if (inventoryTypeId == 1) inventoryTypeName = 'Consumable'; // Corrected ID for Consumable
            else inventoryTypeName = '';

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
            const inventoryTypeId = row.find('.item-inventory-type').val();
            const item = {
                description: row.find('.item-description').val(),
                category_id: row.find('.item-category').val(),
                quantity: row.find('.item-quantity').val(),
                unit_id: row.find('.item-unit').val(),
                unit_cost: row.find('.item-unit-cost').val(),
                inventory_type: lookupData.inventoryTypes.find(it => it.inventory_type_id == inventoryTypeId)?.inventory_type_name,
                details: []
            };

            if (item.inventory_type === 'SEP' || item.inventory_type === 'PPE') {
                $(`#asset-group-${index} .row`).each(function () {
                    const detailRow = $(this);
                    const detail = {
                        property_number: detailRow.find('.asset-property-number').val(),
                        serial_number: detailRow.find('.asset-serial-number').val(),
                        condition: detailRow.find('.asset-condition').val(),
                    };

                    if (item.inventory_type === 'SEP') {
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
                addModal.hide();
                Swal.fire('Success!', response.message, 'success').then(() => {
                    $('#incomingIcsListTable').DataTable().ajax.reload();
                });
            },
            error: function (xhr) {
                const errorMsg = xhr.responseJSON ? xhr.responseJSON.message : 'An unexpected error occurred.';
                Swal.fire('Error!', errorMsg, 'error');
            }
        });
    }
});