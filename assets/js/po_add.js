document.addEventListener('DOMContentLoaded', function () {
    const addPoModal = new bootstrap.Modal(document.getElementById('addPoModal'));
    if (!addPoModal) return;

    const addItemRowBtn = document.getElementById('add-item-row');
    const itemRowsContainer = document.getElementById('po-item-rows');
    const addPoForm = document.getElementById('addPoForm');
    const grandTotalEl = document.getElementById('po-grand-total');
    const submitButton = addPoForm.closest('.modal-content').querySelector('button[type="submit"]');
    const generatePoBtn = document.getElementById('generate-po-btn');
    const poNumberInput = document.getElementById('po_number');

    // --- Business Logic Constants ---
    // Determine type IDs from server-provided data to avoid hardcoding
    const appDataEl = document.getElementById('app-data');
    let PPE_TYPE_ID = null;
    let SEP_TYPE_ID = null;
    let CONSUMABLE_TYPE_ID = null;
    try {
        const inventoryTypes = JSON.parse(appDataEl.dataset.inventoryTypes || '[]');
        inventoryTypes.forEach(t => {
            if (/ppe/i.test(t.inventory_type_name)) PPE_TYPE_ID = String(t.inventory_type_id);
            if (/sep/i.test(t.inventory_type_name) || /semi/i.test(t.inventory_type_name)) SEP_TYPE_ID = String(t.inventory_type_id);
            if (/consum/i.test(t.inventory_type_name) || /supply/i.test(t.inventory_type_name)) CONSUMABLE_TYPE_ID = String(t.inventory_type_id);
        });
    } catch (e) {
        // Leave IDs as null if parsing fails; existing logic will fall back to empty selection
    }

    const appData = document.getElementById('app-data');
    const rowTemplate = document.getElementById('po-item-row-template');
    const allCategories = JSON.parse(appData.dataset.categories || '[]');


    // Function to add a new item row
    function addNewRow() {
        const newRow = rowTemplate.content.cloneNode(true);
        itemRowsContainer.appendChild(newRow);
    }

    // Add a new row when the modal is shown for the first time
    addPoModal._element.addEventListener('show.bs.modal', function () {
        // Clear existing rows and add one fresh one
        addPoForm.reset(); // Reset form fields
        itemRowsContainer.innerHTML = ''; // Clear previous items
        addNewRow(); // Add a starting row

        const orderDateInput = document.getElementById('order_date');
        orderDateInput.valueAsDate = new Date(); // Set today's date

        calculateTotals();
    });

    // Handle PO Number Generation
    if (generatePoBtn) {
        generatePoBtn.addEventListener('click', async () => {
            generatePoBtn.disabled = true;
            generatePoBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span>`;
            try {
                const response = await fetch('api/po_generate_number_api.php');
                const data = await response.json();
                if (data.success) {
                    poNumberInput.value = data.po_number;
                } else {
                    showToast(data.message, 'Error', 'danger');
                }
            } catch (error) {
                showToast('Failed to generate PO number.', 'Error', 'danger');
            } finally {
                generatePoBtn.disabled = false;
                generatePoBtn.innerHTML = `<i class="bi bi-arrow-clockwise"></i>`;
            }
        });
    }

    // Add a new row on button click
    if (addItemRowBtn) {
        addItemRowBtn.addEventListener('click', addNewRow);
    }

    // Function to update a category dropdown based on the selected inventory type
    function updateCategoryDropdown(inventoryTypeSelect) {
        const row = inventoryTypeSelect.closest('.po-item-row');
        const categorySelect = row.querySelector('.category-select');
        const selectedTypeId = inventoryTypeSelect.value;

        // Clear current options and disable
        categorySelect.innerHTML = '<option value="" selected disabled>Select...</option>';
        categorySelect.disabled = true;

        if (selectedTypeId) {
            // Filter categories that match the selected inventory type
            const filteredCategories = allCategories.filter(category => category.inventory_type_id == selectedTypeId);

            // Populate the category dropdown
            filteredCategories.forEach(category => {
                categorySelect.add(new Option(category.category_name, category.category_id));
            });
            categorySelect.disabled = false;
        }
    }

    // Function to automatically set inventory type based on unit cost
    function applyCostBasedRules(costInput) {
        const row = costInput.closest('.po-item-row');
        const inventoryTypeSelect = row.querySelector('.inventory-type-select');
        // Normalize numeric value (handles formatted text inputs)
        let raw = costInput.value;
        if (typeof raw === 'string') raw = raw.replace(/,/g, '');
        const cost = parseFloat(raw) || 0;

        // Apply thresholds:
        // 0 - 4,999 => Consumable
        // 5,000 - 49,999 => SEP
        // 50,000 and above => PPE
        if (cost >= 50000 && PPE_TYPE_ID !== null) {
            inventoryTypeSelect.value = PPE_TYPE_ID;
        } else if (cost >= 5000 && cost < 50000 && SEP_TYPE_ID !== null) {
            inventoryTypeSelect.value = SEP_TYPE_ID;
        } else if (cost >= 0 && CONSUMABLE_TYPE_ID !== null) {
            inventoryTypeSelect.value = CONSUMABLE_TYPE_ID;
        } else {
            inventoryTypeSelect.value = '';
        }
        // Trigger change so categories update
        inventoryTypeSelect.dispatchEvent(new Event('change', { bubbles: true }));
        inventoryTypeSelect.dispatchEvent(new Event('change', { bubbles: true }));
    }

    // Event delegation for removing rows and calculating totals
    itemRowsContainer.addEventListener('click', function (e) {
        if (e.target.closest('.remove-item-row')) {
            e.target.closest('.po-item-row').remove();
            calculateTotals();
        }
    });

    itemRowsContainer.addEventListener('input', function (e) {
        if (e.target.matches('[name="quantity"], [name="unit_cost"]')) {
            calculateTotals();
            if (e.target.matches('[name="unit_cost"]')) {
                applyCostBasedRules(e.target);
            }
        }
    });
    itemRowsContainer.addEventListener('change', function (e) {
        if (e.target.matches('.inventory-type-select')) updateCategoryDropdown(e.target);
    });


    // Function to calculate totals for all rows
    function calculateTotals() {
        let grandTotal = 0;
        itemRowsContainer.querySelectorAll('.po-item-row').forEach(row => {
            const quantity = parseFloat(row.querySelector('[name="quantity"]').value) || 0;
            const unitCost = parseFloat(String(row.querySelector('[name="unit_cost"]').value).replace(/,/g, '')) || 0;
            const totalCell = row.querySelector('.item-total');
            const itemTotal = quantity * unitCost;

            if (totalCell) {
                totalCell.textContent = '₱ ' + itemTotal.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }
            grandTotal += itemTotal;
        });

        if (grandTotalEl) {
            grandTotalEl.textContent = '₱ ' + grandTotal.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
    }

    // Handle form submission
    if (addPoForm) {
        addPoForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const header = {
                po_number: document.getElementById('po_number').value,
                supplier_id: document.getElementById('supplier_id').value,
                order_date: document.getElementById('order_date').value,
                purchase_mode_id: document.getElementById('purchase_mode_id').value,
                delivery_place_id: document.getElementById('delivery_place_id').value,
                delivery_term_id: document.getElementById('delivery_term_id').value,
                payment_term_id: document.getElementById('payment_term_id').value,
            };

            const items = Array.from(itemRowsContainer.querySelectorAll('.po-item-row')).map(row => {
                return {
                    description: row.querySelector('[name="description"]').value,
                    quantity: row.querySelector('[name="quantity"]').value,
                    unit_id: row.querySelector('[name="unit_id"]').value,
                    unit_cost: String(row.querySelector('[name="unit_cost"]').value).replace(/,/g, ''),
                    inventory_type_id: row.querySelector('[name="inventory_type_id"]').value,
                    category_id: row.querySelector('[name="category_id"]').value,
                };
            });

            if (items.length === 0) {
                showToast('Please add at least one item to the purchase order.', 'Validation Error', 'warning');
                return;
            }

            const submissionData = {
                header: header,
                items: items
            };

            // UI feedback
            submitButton.disabled = true;
            submitButton.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Saving...`;

            try {
                const response = await fetch('api/po_add_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(submissionData)
                });

                const result = await response.json();

                if (result.success) {
                    showToast(result.message, 'Success', 'success');
                    addPoModal.hide();
                    if ($.fn.DataTable.isDataTable('#pendingPoListTable')) {
                        $('#pendingPoListTable').DataTable().ajax.reload();
                    }
                } else {
                    throw new Error(result.message || 'An unknown error occurred.');
                }
            } catch (error) {
                showToast(`Error: ${error.message}`, 'Save Failed', 'danger');
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = 'Save Purchase Order';
            }
        });
    }

    // --- Currency Formatting Event Listeners ---
    itemRowsContainer.addEventListener('focus', function(e) {
        if (e.target.matches('[name="unit_cost"]')) {
            const value = e.target.value;
            e.target.value = parseFloat(String(value).replace(/,/g, '')) || '';
            e.target.type = 'number';
        }
    }, true);

    itemRowsContainer.addEventListener('blur', function(e) {
        if (e.target.matches('[name="unit_cost"]')) {
            const value = parseFloat(e.target.value) || 0;
            e.target.type = 'text'; // Allow commas
            e.target.value = value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    }, true);
});
