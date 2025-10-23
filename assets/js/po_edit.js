document.addEventListener('DOMContentLoaded', function () {
    const editPoModal = document.getElementById('editPoModal');
    if (!editPoModal) return;

    const formContainer = document.getElementById('edit-po-form-container');
    const formTemplate = document.getElementById('edit-po-form-template');
    const newItemRowTemplate = document.getElementById('edit-item-row-template');
    const appData = document.getElementById('app-data');
    const allCategories = JSON.parse(appData.dataset.categories || '[]');
    const allUnits = JSON.parse(appData.dataset.units || '[]');
    const allInventoryTypes = JSON.parse(appData.dataset.inventoryTypes || '[]');
    
    // --- Business Logic Constants ---
    const PPE_TYPE_ID = 1;
    const SEP_TYPE_ID = 2;
    
    let deletedItemIds = [];

    /**
     * Updates the category dropdown for a given row based on the selected inventory type.
     * @param {HTMLSelectElement} inventoryTypeSelect The inventory type select element that changed.
     */
    function updateCategoryDropdown(inventoryTypeSelect) {
        const row = inventoryTypeSelect.closest('tr');
        const categorySelect = row.querySelector('.category-select');
        const selectedTypeId = inventoryTypeSelect.value;

        categorySelect.innerHTML = '<option value="" selected disabled>Select...</option>';
        categorySelect.disabled = true;

        if (selectedTypeId) {
            const filteredCategories = allCategories.filter(cat => cat.inventory_type_id == selectedTypeId);
            filteredCategories.forEach(cat => categorySelect.add(new Option(cat.category_name, cat.category_id)));
            categorySelect.disabled = false;
        }
    }

    /**
     * Calculates the total for a single row and updates the grand total for the entire form.
     */
    function calculateTotals() {
        let grandTotal = 0;
        formContainer.querySelectorAll('.po-item-row').forEach(row => {
            const quantityInput = row.querySelector('[name^="update_quantity"], [name^="new_quantity"]');
            const costInput = row.querySelector('[name^="update_unit_cost"], [name^="new_unit_cost"]');
            const totalCell = row.querySelector('.item-total');

            const quantity = parseFloat(quantityInput.value) || 0;
            // Parse the value, removing any formatting like commas
            const unitCost = parseFloat(String(costInput.value).replace(/,/g, '')) || 0;
            const itemTotal = quantity * unitCost;
            
            if (totalCell) {
                totalCell.textContent = `${itemTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            }
            grandTotal += itemTotal;
        });

        const grandTotalEl = document.getElementById('edit-po-grand-total');
        if (grandTotalEl) {
            grandTotalEl.textContent = `${grandTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        }
    }

    /**
     * Automatically sets the inventory type based on the unit cost.
     * @param {HTMLInputElement} costInput The unit cost input element that changed.
     */
    function applyCostBasedRules(costInput) {
        const row = costInput.closest('.po-item-row');
        const inventoryTypeSelect = row.querySelector('.inventory-type-select');
        const cost = parseFloat(costInput.value) || 0;

        inventoryTypeSelect.value = cost >= 50000 ? PPE_TYPE_ID : (cost >= 5000 ? SEP_TYPE_ID : '');
        inventoryTypeSelect.dispatchEvent(new Event('change', { bubbles: true }));
    }

    // Populate the form when the modal is shown
    editPoModal.addEventListener('show.bs.modal', async function (event) {
        const button = event.relatedTarget;
        const poId = button.getAttribute('data-po-id');
        deletedItemIds = []; // Reset deleted items

        // Show loading spinner
        formContainer.innerHTML = `<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>`;

        try {
            const response = await fetch(`api/get_po_details.php?id=${poId}`);
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message);
            }

            // Clone and append the form template
            formContainer.innerHTML = '';
            formContainer.appendChild(formTemplate.content.cloneNode(true));

            // Populate header fields
            const header = data.header;
            document.getElementById('edit_po_id').value = header.po_id;
            document.getElementById('edit_supplier_id').value = header.supplier_id;
            document.getElementById('edit_order_date').value = header.order_date;
            document.getElementById('edit_purchase_mode_id').value = header.purchase_mode_id;
            document.getElementById('edit_delivery_place_id').value = header.delivery_place_id;
            document.getElementById('edit_delivery_term_id').value = header.delivery_term_id;
            document.getElementById('edit_payment_term_id').value = header.payment_term_id;

            // Populate existing item rows
            const existingItemsContainer = document.getElementById('edit-po-item-rows');
            existingItemsContainer.innerHTML = '';
            data.items.forEach(item => {
                const row = document.createElement('tr');
                row.className = 'po-item-row existing-item';
                row.dataset.itemId = item.po_item_id;

                const filteredCategories = allCategories.filter(cat => cat.inventory_type_id == item.inventory_type_id);

                row.innerHTML = `
                    <td><textarea name="update_description[${item.po_item_id}]" class="form-control form-control-sm" rows="1" required>${item.description}</textarea></td>
                    <td><input type="number" name="update_quantity[${item.po_item_id}]" class="form-control form-control-sm text-end" value="${item.quantity}" min="1" required></td>
                    <td>
                        <select name="update_unit_id[${item.po_item_id}]" class="form-select form-select-sm" required>
                            ${allUnits.map(u => `<option value="${u.unit_id}" ${u.unit_id == item.unit_id ? 'selected' : ''}>${u.unit_name}</option>`).join('')}
                        </select>
                    </td>
                    <td><input type="number" name="update_unit_cost[${item.po_item_id}]" class="form-control form-control-sm text-end" value="${item.unit_cost}" step="0.01" min="0" required></td>
                    <td>
                        <select name="update_inventory_type_id[${item.po_item_id}]" class="form-select form-select-sm inventory-type-select" required>
                            ${allInventoryTypes.map(it => `<option value="${it.inventory_type_id}" ${it.inventory_type_id == item.inventory_type_id ? 'selected' : ''}>${it.inventory_type_name}</option>`).join('')}
                        </select>
                    </td>
                    <td>
                        <select name="update_category_id[${item.po_item_id}]" class="form-select form-select-sm category-select" required>
                            ${filteredCategories.map(c => `<option value="${c.category_id}" ${c.category_id == item.category_id ? 'selected' : ''}>${c.category_name}</option>`).join('')}
                        </select>
                    </td>
                    <td class="item-total text-end"></td>
                    <td class="text-center"><button type="button" class="btn btn-sm btn-danger remove-item-row"><i class="bi bi-trash"></i></button></td>
                `;
                existingItemsContainer.appendChild(row);
            });

            calculateTotals(); // Initial calculation

        } catch (error) {
            formContainer.innerHTML = `<div class="alert alert-danger">Failed to load purchase order details: ${error.message}</div>`;
        }
    });

    // Event delegation for the whole modal body to handle dynamic elements
    editPoModal.querySelector('.modal-body').addEventListener('click', function(e) {
        // Add new item row
        if (e.target.id === 'edit-add-item-row') {
            const itemsContainer = document.getElementById('edit-po-item-rows');
            itemsContainer.appendChild(newItemRowTemplate.content.cloneNode(true));
        }
        // Remove item row (both existing and new)
        if (e.target.closest('.remove-item-row')) {
            const row = e.target.closest('tr');
            if (row.classList.contains('existing-item')) {
                deletedItemIds.push(row.dataset.itemId);
            }
            row.remove();
            calculateTotals();
        }
    });

    editPoModal.querySelector('.modal-body').addEventListener('change', function(e) {
        if (e.target.matches('.inventory-type-select')) {
            updateCategoryDropdown(e.target);
        }
    });

    editPoModal.querySelector('.modal-body').addEventListener('input', function(e) {
        if (e.target.matches('[name^="update_quantity"], [name^="new_quantity"], [name^="update_unit_cost"], [name^="new_unit_cost"]')) {
            calculateTotals();
            if (e.target.matches('[name^="update_unit_cost"], [name^="new_unit_cost"]')) applyCostBasedRules(e.target);
        }
    });

    // --- Currency Formatting Event Listeners for Edit Modal ---
    editPoModal.querySelector('.modal-body').addEventListener('focus', function(e) {
        if (e.target.matches('[name^="update_unit_cost"], [name^="new_unit_cost"]')) {
            // When focusing, remove formatting
            const value = e.target.value;
            e.target.value = parseFloat(String(value).replace(/,/g, '')) || '';
            e.target.type = 'number';
        }
    }, true);

    editPoModal.querySelector('.modal-body').addEventListener('blur', function(e) {
        if (e.target.matches('[name^="update_unit_cost"], [name^="new_unit_cost"]')) {
            // When blurring, format as currency
            const value = parseFloat(e.target.value) || 0;
            e.target.type = 'text'; // Allow commas
            e.target.value = value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    }, true);

    // Handle form submission
    editPoModal.addEventListener('submit', async function(e) {
        if (e.target.id !== 'editPoForm') return;
        e.preventDefault();

        const form = e.target;
        const poId = form.querySelector('#edit_po_id').value;
        const submitButton = editPoModal.querySelector('.modal-footer button[type="submit"]');

        // Collect data
        const headerData = {
            supplier_id: form.querySelector('#edit_supplier_id').value,
            order_date: form.querySelector('#edit_order_date').value,
            purchase_mode_id: form.querySelector('#edit_purchase_mode_id').value,
            delivery_place_id: form.querySelector('#edit_delivery_place_id').value,
            delivery_term_id: form.querySelector('#edit_delivery_term_id').value,
            payment_term_id: form.querySelector('#edit_payment_term_id').value,
        };

        const itemsToUpdate = Array.from(form.querySelectorAll('.existing-item')).map(row => ({
            po_item_id: row.dataset.itemId,
            description: row.querySelector('[name^="update_description"]').value,
            quantity: row.querySelector('[name^="update_quantity"]').value,
            unit_id: row.querySelector('[name^="update_unit_id"]').value,
            inventory_type_id: row.querySelector('[name^="update_inventory_type_id"]').value,
            unit_cost: parseFloat(String(row.querySelector('[name^="update_unit_cost"]').value).replace(/,/g, '')) || 0,
            category_id: row.querySelector('[name^="update_category_id"]').value,
        }));

        const itemsToAdd = Array.from(form.querySelectorAll('.new-item')).map(row => ({
            description: row.querySelector('[name="new_description"]').value,
            quantity: row.querySelector('[name="new_quantity"]').value,
            unit_id: row.querySelector('[name="new_unit_id"]').value,
            unit_cost: parseFloat(String(row.querySelector('[name="new_unit_cost"]').value).replace(/,/g, '')) || 0,
            inventory_type_id: row.querySelector('[name="new_inventory_type_id"]').value,
            category_id: row.querySelector('[name="new_category_id"]').value,
        }));

        const submissionData = {
            po_id: poId,
            header: headerData,
            items_to_update: itemsToUpdate,
            items_to_add: itemsToAdd,
            items_to_delete: deletedItemIds
        };

        // UI feedback
        submitButton.disabled = true;
        submitButton.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Saving...`;

        try {
            const response = await fetch('api/po_edit.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(submissionData)
            });
            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message);
            }

            showToast('Purchase Order updated successfully!', 'Success', 'success');
            bootstrap.Modal.getInstance(editPoModal).hide();
            // Reload both tables as an edit could affect either list.
            if ($.fn.DataTable.isDataTable('#pendingPoListTable')) {
                $('#pendingPoListTable').DataTable().ajax.reload();
            }
            if ($.fn.DataTable.isDataTable('#deliveredPoListTable')) {
                $('#deliveredPoListTable').DataTable().ajax.reload();
            }

        } catch (error) {
            showToast(`Error updating PO: ${error.message}`, 'Update Failed', 'danger');
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = 'Save Changes';
        }
    });
});