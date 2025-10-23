document.addEventListener('DOMContentLoaded', function () {
    const receivePoModal = document.getElementById('receivePoModal');
    if (!receivePoModal) return;

    const receiveForm = document.getElementById('receivePoForm');
    const itemsContainer = document.getElementById('receive-items-container');
    const poIdInput = document.getElementById('receive_po_id');
    const poNumberTitle = receivePoModal.querySelector('.modal-title');

    // Data from the global app-data div
    const appData = document.getElementById('app-data');
    const allInventoryTypes = JSON.parse(appData.dataset.inventoryTypes || '[]');
    const allCategories = JSON.parse(appData.dataset.categories || '[]');

    // Helper to find inventory type name from category ID
    function getInventoryTypeName(categoryId) {
        const category = allCategories.find(c => c.category_id == categoryId);
        if (!category) return null;
        const inventoryType = allInventoryTypes.find(it => it.inventory_type_id == category.inventory_type_id);
        return inventoryType ? inventoryType.inventory_type_name : null;
    }

    // Populate the modal when it's shown
    receivePoModal.addEventListener('show.bs.modal', async function (event) {
        const button = event.relatedTarget;
        const poId = button.getAttribute('data-po-id');
        const poNumber = button.getAttribute('data-po-number');

        // Set modal title and hidden PO ID
        poNumberTitle.textContent = `Receive Items for PO #${poNumber}`;
        poIdInput.value = poId;
        receiveForm.reset(); // Clear previous data
        document.getElementById('date_received').valueAsDate = new Date();

        // Show loading spinner
        itemsContainer.innerHTML = `<div class="text-center p-5"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>`;

        try {
            const response = await fetch(`api/get_po_details.php?id=${poId}`);
            const data = await response.json();

            if (!data.success) throw new Error(data.message);

            itemsContainer.innerHTML = ''; // Clear spinner
            let itemHtml = '';

            data.items.forEach(item => {
                const inventoryTypeName = getInventoryTypeName(item.category_id);
                itemHtml += `<div class="card mb-3" data-po-item-id="${item.po_item_id}" data-category-id="${item.category_id}" data-quantity="${item.quantity}" data-inventory-type="${inventoryTypeName}">`;
                itemHtml += `<div class="card-header"><strong>Item:</strong> ${item.description} | <strong>Qty:</strong> ${item.quantity}</div>`;
                itemHtml += `<div class="card-body">`;

                if (inventoryTypeName === 'Consumable') {
                    itemHtml += `<div class="row align-items-center">
                                    <div class="col-md-8"><p class="text-muted mb-0">This is a consumable item. Enter the quantity being received in this delivery.</p></div>
                                    <div class="col-md-4">
                                        <input type="number" class="form-control" name="consumable_quantity" value="${item.quantity}" min="1" max="${item.quantity}" required>
                                    </div>
                                 </div>`;
                } else if (inventoryTypeName === 'SEP' || inventoryTypeName === 'PPE') {
                    itemHtml += `<div class="table-responsive"><table class="table table-sm">`;
                    for (let i = 0; i < item.quantity; i++) {
                        itemHtml += `<tr><td class="align-middle" style="width: 10%;"><span class="badge bg-secondary">#${i + 1}</span></td><td>`;
                        if (inventoryTypeName === 'SEP') {
                            itemHtml += `<div class="row g-2">
                                <div class="col-md-6"><input type="text" class="form-control form-control-sm" name="sep_brand_name" placeholder="Brand Name"></div>
                                <div class="col-md-6"><input type="text" class="form-control form-control-sm" name="sep_serial_number" placeholder="Serial Number (Optional)"></div>
                            </div>`;
                        } else { // PPE
                            itemHtml += `<div class="row g-2">
                                <div class="col-md-6"><input type="text" class="form-control form-control-sm" name="ppe_model_number" placeholder="Model Number"></div>
                                <div class="col-md-6"><input type="text" class="form-control form-control-sm" name="ppe_serial_number" placeholder="Serial Number"></div>
                            </div>`;
                        }
                        itemHtml += `</td></tr>`;
                    }
                    itemHtml += `</table></div>`;
                } else {
                    itemHtml += `<p class="text-danger">Unknown inventory type for this item.</p>`;
                }
                itemHtml += `</div></div>`;
            });
            itemsContainer.innerHTML = itemHtml;

        } catch (error) {
            itemsContainer.innerHTML = `<div class="alert alert-danger">Failed to load item details: ${error.message}</div>`;
        }
    });

    // Handle form submission
    receiveForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const submitButton = receivePoModal.querySelector('.modal-footer button[type="submit"]');

        const items = [];
        itemsContainer.querySelectorAll('.card').forEach(card => {
            const itemData = {
                po_item_id: card.dataset.poItemId,
                inventory_type: card.dataset.inventoryType,
                details: []
            };

            // Handle quantity based on inventory type
            if (card.dataset.inventoryType === 'Consumable') {
                itemData.quantity = card.querySelector('[name="consumable_quantity"]').value;
            } else {
                // For SEP/PPE, quantity is the number of detail rows
                itemData.quantity = card.querySelectorAll('tr').length;
            }
            
            if (card.dataset.inventoryType === 'SEP') {
                card.querySelectorAll('tr').forEach(row => {
                    itemData.details.push({
                        brand_name: row.querySelector('[name="sep_brand_name"]').value,
                        serial_number: row.querySelector('[name="sep_serial_number"]').value
                    });
                });
            } else if (card.dataset.inventoryType === 'PPE') {
                card.querySelectorAll('tr').forEach(row => {
                    itemData.details.push({
                        model_number: row.querySelector('[name="ppe_model_number"]').value,
                        serial_number: row.querySelector('[name="ppe_serial_number"]').value
                    });
                });
            }
            items.push(itemData);
        });

        const submissionData = {
            po_id: poIdInput.value,
            delivery_receipt_no: document.getElementById('delivery_receipt_no').value,
            date_received: document.getElementById('date_received').value,
            items: items
        };

        // UI Feedback
        submitButton.disabled = true;
        submitButton.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Receiving...`;

        try {
            const response = await fetch('api/po_receive.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(submissionData)
            });
            const result = await response.json();

            if (!result.success) throw new Error(result.message);

            showToast(result.message, 'Success', 'success');
            bootstrap.Modal.getInstance(receivePoModal).hide();
            // Reload the pending table to update the button states (e.g., show "Mark as Delivered").
            $('#pendingPoListTable').DataTable().ajax.reload();
            // Also reload the delivered table, just in case it's visible and needs updates.
            if ($.fn.DataTable.isDataTable('#deliveredPoListTable')) {
                $('#deliveredPoListTable').DataTable().ajax.reload();
            }

        } catch (error) {
            showToast(`Error: ${error.message}`, 'Receive Failed', 'danger');
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = 'Receive Items';
        }
    });
});