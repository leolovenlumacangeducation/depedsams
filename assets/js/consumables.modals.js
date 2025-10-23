/**
 * consumables.modals.js
 * 
 * Contains all functions related to showing and handling submissions
 * for the various modals on the consumables page.
 */

/**
 * consumables.modals.js
 * 
 * Contains all functions related to showing and handling submissions
 * for the various modals on the consumables page.
 */

// Security: Escape HTML to prevent XSS. This function is used by showStockCardModal.
function escapeHTML(str) {
    const p = document.createElement("p");
    p.textContent = str;
    return p.innerHTML;
}

async function showRisModal(issuanceId) {
    const modalEl = document.getElementById('risModal');
    if (!modalEl) {
        console.error('RIS modal element not found');
        return;
    }

    const risModal = new bootstrap.Modal(modalEl);
    const modalBody = document.getElementById('ris-modal-body');
    if (!modalBody) {
        console.error('RIS modal body element not found');
        return;
    }

    modalBody.innerHTML = `
        <div class="text-center p-5">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <div class="mt-2">Loading RIS data...</div>
        </div>`;
    
    try {
        const response = await fetch(`api/ris_view.php?id=${issuanceId}`);
        if (!response.ok) {
            throw new Error(`Failed to fetch RIS data: ${response.status} ${response.statusText}`);
        }
        const content = await response.text();
        modalBody.innerHTML = content;
        risModal.show();
    } catch (error) {
        console.error('Error loading RIS:', error);
        modalBody.innerHTML = `
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill"></i>
                Error loading RIS: ${error.message}
            </div>`;
    }
}

async function showStockCardPdfModal(consumableId) {
    const pdfModal = new bootstrap.Modal(document.getElementById('stockCardPdfModal'));
    const modalBody = document.getElementById('stock-card-pdf-modal-body');
    modalBody.innerHTML = `<div class="text-center p-5"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>`;
    pdfModal.show();

    try {
        const response = await fetch(`api/stock_card_pdf_view.php?id=${consumableId}`);
        if (!response.ok) throw new Error('Failed to fetch Stock Card PDF data.');
        modalBody.innerHTML = await response.text();
    } catch (error) {
        modalBody.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    }
}

function showViewModal(consumableId, consumablesData, viewConsumableModal) {
    const contentArea = document.getElementById('view-consumable-content');
    const viewTemplate = document.getElementById('consumable-view-template').content;
    contentArea.innerHTML = `<div class="text-center p-5"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>`;
    viewConsumableModal.show();

    const item = consumablesData.find(c => c.consumable_id == consumableId);
    if (!item) {
        contentArea.innerHTML = `<div class="alert alert-danger">Item details not found.</div>`;
        return;
    }

    const viewContent = viewTemplate.cloneNode(true);
    const photoPath = `assets/uploads/consumables/${item.photo || 'consumable_default.png'}`;
    const fallbackPhotoPath = `assets/uploads/consumables/consumable_default.png`;

    viewContent.querySelector('img').src = photoPath;
    viewContent.querySelector('img').onerror = function() { this.onerror=null; this.src=fallbackPhotoPath; };
    viewContent.querySelector('[data-template-id="description"]').textContent = item.description;
    viewContent.querySelector('[data-template-id="stock_number"]').textContent = item.stock_number;
    viewContent.querySelector('[data-template-id="unit_cost"]').textContent = parseFloat(item.unit_cost).toLocaleString('en-US', { style: 'currency', currency: 'PHP' });
    viewContent.querySelector('[data-template-id="unit_name"]').textContent = item.unit_name;
    viewContent.querySelector('[data-template-id="date_received"]').textContent = new Date(item.date_received).toLocaleDateString();
    viewContent.querySelector('[data-template-id="quantity_received"]').textContent = item.quantity_received;
    viewContent.querySelector('[data-template-id="current_stock"]').textContent = item.current_stock;

    contentArea.innerHTML = '';
    contentArea.appendChild(viewContent);
}

function showIssueModal(consumableIds, consumablesData, existingModal = null) {
    // Get the modal element first
    const modalElement = document.getElementById('issueConsumableModal');
    // Create new modal instance if none exists, or use existing
    const issueConsumableModal = existingModal || bootstrap.Modal.getOrCreateInstance(modalElement);
    const itemsContainer = document.getElementById('issue-items-container');
    const form = document.getElementById('issueConsumableForm');
    const issueItemTemplate = document.getElementById('issue-item-template').content;
    form.reset();
    document.getElementById('date_issued').valueAsDate = new Date();
    itemsContainer.innerHTML = '';

    consumableIds.forEach(id => {
        const item = consumablesData.find(c => c.consumable_id == id);
        if (item) {
            const itemRow = issueItemTemplate.cloneNode(true);
            const rowDiv = itemRow.querySelector('.issue-item-row');
            const qtyInput = itemRow.querySelector('[name="quantity_issued"]');

            rowDiv.dataset.consumableId = item.consumable_id;
            rowDiv.querySelector('.input-group-text.flex-grow-1').textContent = item.description;
            rowDiv.querySelector('.input-group-text.flex-grow-1').title = item.stock_number;
            rowDiv.querySelector('[data-template-id="current_stock"]').textContent = item.current_stock;
            qtyInput.max = item.current_stock;

            itemRow.querySelector('.btn-outline-danger').addEventListener('click', function() {
                this.closest('.issue-item-row').remove();
            });

            itemsContainer.appendChild(itemRow);
        }
    });

    issueConsumableModal.show();
}

async function handleIssueFormSubmit(e, loadConsumables, dataTable) {
    e.preventDefault();
    const form = e.target;
    const submitButton = form.querySelector('button[type="submit"]');
    const issueConsumableModal = bootstrap.Modal.getInstance(document.getElementById('issueConsumableModal'));

    const itemsToIssue = Array.from(form.querySelectorAll('.issue-item-row')).map(row => ({
        consumable_id: row.dataset.consumableId,
        quantity_issued: row.querySelector('[name="quantity_issued"]').value
    })).filter(item => item.quantity_issued && parseInt(item.quantity_issued, 10) > 0);

    if (itemsToIssue.length === 0) {
        showToast('Please enter a quantity for at least one item.', 'Validation Error', 'danger');
        return;
    }

    const submissionData = {
        issued_to: form.querySelector('#issued_to').value,
        date_issued: form.querySelector('#date_issued').value,
        items: itemsToIssue
    };

    submitButton.disabled = true;
    submitButton.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Issuing...`;

    try {
        const response = await fetch('api/consumable_issue_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(submissionData)
        });
        const result = await response.json();
        if (!response.ok) throw new Error(result.message || 'An unknown error occurred.');

        // Show success message
        showToast(result.message, 'Success', 'success');

        // Hide the issue modal
        issueConsumableModal.hide();

        // Show RIS modal directly
        if (result.issuance_id) {
            showRisModal(result.issuance_id);
        }
        
        // Update the view
        loadConsumables();
        if (dataTable) dataTable.ajax.reload();
    } catch (error) {
        showToast(`Error: ${error.message}`, 'Issuance Failed', 'danger');
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = `<i class="bi bi-check-circle"></i> Confirm Issuance`;
    }
}

function showConvertModal(consumableId, consumablesData, convertConsumableModal) {
    const form = document.getElementById('convertConsumableForm');
    form.reset();

    const item = consumablesData.find(c => c.consumable_id == consumableId);
    if (!item) {
        showToast('Item details not found.', 'Error', 'danger');
        return;
    }

    document.getElementById('from_consumable_id').value = item.consumable_id;
    document.getElementById('convert-item-name').textContent = item.description;
    document.getElementById('convert-current-stock').textContent = item.current_stock;
    document.getElementById('convert-unit-name').textContent = item.unit_name;
    document.getElementById('quantity_to_convert').max = item.current_stock;

    convertConsumableModal.show();
}

async function handleConvertFormSubmit(e, convertConsumableModal, loadConsumables, dataTable) {
    e.preventDefault();
    const form = e.target;
    const submitButton = form.querySelector('button[type="submit"]');

    const submissionData = {
        from_consumable_id: form.querySelector('#from_consumable_id').value,
        quantity_to_convert: form.querySelector('#quantity_to_convert').value,
        conversion_factor: form.querySelector('#conversion_factor').value,
        to_unit_id: form.querySelector('#to_unit_id').value
    };

    submitButton.disabled = true;
    submitButton.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Converting...`;

    try {
        const response = await fetch('api/consumable_convert_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(submissionData)
        });
        const result = await response.json();
        if (!response.ok) throw new Error(result.message || 'An unknown error occurred.');

        showToast(result.message, 'Success', 'success');
        convertConsumableModal.hide();
        loadConsumables();
        if (dataTable) dataTable.ajax.reload();
    } catch (error) {
        showToast(`Error: ${error.message}`, 'Conversion Failed', 'danger');
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = `<i class="bi bi-arrow-repeat"></i> Confirm Conversion`;
    }
}

async function showStockCardModal(consumableId, consumablesData, stockCardModal) {
    const contentArea = document.getElementById('stock-card-content');
    const itemNameEl = document.getElementById('stock-card-item-name');
    const stockCardTableTemplate = document.getElementById('stock-card-table-template').content;
    
    itemNameEl.textContent = 'Loading...';
    contentArea.innerHTML = `<div class="text-center p-5"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>`;
    stockCardModal.show();

    try {
        const response = await fetch(`api/consumable_stock_card_api.php?id=${consumableId}`);
        const result = await response.json();
        if (!result.success) throw new Error(result.message || 'Failed to load data.');

        const itemDetails = result.item;
        // Ensure transactionHistory is always an array to prevent errors.
        const transactionHistory = result.transactions || [];
        itemNameEl.textContent = itemDetails.description;

        const tableTemplate = stockCardTableTemplate.cloneNode(true);
        const tbody = tableTemplate.querySelector('tbody');

        if (transactionHistory.length === 0) {
            contentArea.innerHTML = `<div class="alert alert-info">No transaction history found for this item.</div>`;
            return;
        }
        
        // --- Correct Balance Calculation ---
        // Start with the known current stock and work backwards in time to ensure accuracy.
        let runningBalance = parseInt(itemDetails.current_stock, 10);
        const transactions = transactionHistory.reverse(); // Reverse to calculate from newest to oldest

        transactions.forEach(tx => {
            const qtyIn = parseInt(tx.quantity_in, 10) || 0;
            const qtyOut = parseInt(tx.quantity_out, 10) || 0;

            const row = tbody.insertRow();
            let transactionTypeClass = 'bg-secondary';
            if (tx.transaction_type.includes('In') || tx.transaction_type === 'Receipt') {
                transactionTypeClass = 'bg-success';
            } else if (tx.transaction_type.includes('Out') || tx.transaction_type === 'Issuance') {
                transactionTypeClass = 'bg-warning text-dark';
            } else if (tx.transaction_type === 'Conversion Out') {
                transactionTypeClass = 'bg-warning text-dark';
            } else if (tx.transaction_type === 'Conversion In') {
                transactionTypeClass = 'bg-success';
            }

            row.innerHTML = `
                <td>${new Date(tx.transaction_date).toLocaleDateString()}</td>
                <td><span class="badge ${transactionTypeClass}">${escapeHTML(tx.transaction_type)}</span></td>
                <td>${escapeHTML(tx.reference)}</td>
                <td class="text-end text-success fw-bold">${qtyIn > 0 ? qtyIn : ''}</td>
                <td class="text-end text-danger fw-bold">${qtyOut > 0 ? qtyOut : ''}</td>
                <td class="text-end fw-bold">${runningBalance}</td>
                <td>${escapeHTML(tx.person_in_charge)}</td>
            `;

            // Adjust the balance for the *previous* transaction in the original order
            runningBalance = runningBalance - qtyIn + qtyOut;
        });
        tbody.innerHTML = Array.from(tbody.rows).reverse().map(row => row.outerHTML).join(''); // Re-reverse rows for correct display

        contentArea.innerHTML = '';
        contentArea.appendChild(tableTemplate);

    } catch (error) {
        contentArea.innerHTML = `<div class="alert alert-danger">Error loading stock card: ${error.message}</div>`;
    }
}