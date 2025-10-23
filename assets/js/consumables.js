/**
 * consumables.js
 * 
 * Main script for the consumables page. Handles initialization,
 * data loading, view switching, and event listeners.
 */
document.addEventListener('DOMContentLoaded', function() {
    // View containers and buttons
    const cardContainer = document.getElementById('consumables-card-container');
    const outOfStockCardContainer = document.getElementById('out-of-stock-card-container');
    const stockStatusTabs = document.getElementById('stock-status-tabs');
    const tableContainer = document.getElementById('consumables-table-container');
    const cardViewBtn = document.getElementById('card-view-btn');
    const tableViewBtn = document.getElementById('table-view-btn');
    const issueSelectedBtn = document.getElementById('issue-selected-btn');
    const searchInput = document.getElementById('consumable-card-search');
    const directPhotoUploadInput = document.getElementById('direct-photo-upload');

    // Modal instances
    const viewConsumableModal = new bootstrap.Modal(document.getElementById('viewConsumableModal'));
    const stockCardModal = new bootstrap.Modal(document.getElementById('stockCardModal'));
    const convertConsumableModal = new bootstrap.Modal(document.getElementById('convertConsumableModal'));
    // Don't pre-initialize issueConsumableModal, it will be created when needed

    if (!cardContainer || !tableContainer) return;

    let consumablesData = [];
    let selectedConsumableIds = new Set(); // State for selected card IDs
    let dataTable = null;

    // --- Search/Filter Function ---
    function filterAndRenderCards() {
        const searchTerm = searchInput.value.toLowerCase();

        // Separate items into in-stock and out-of-stock
        const inStockItems = consumablesData.filter(item => item.current_stock > 0);
        const outOfStockItems = consumablesData.filter(item => item.current_stock <= 0);

        // Update the count on the tab
        document.getElementById('out-of-stock-count').textContent = outOfStockItems.length;

        // Filter the in-stock items based on the search term
        const filteredInStock = inStockItems.filter(item => {
            const description = item.description ? item.description.toLowerCase() : '';
            const stockNumber = item.stock_number ? item.stock_number.toLowerCase() : '';
            return description.includes(searchTerm) || stockNumber.includes(searchTerm);
        });

        // Render In-Stock Cards
        if (filteredInStock.length > 0) {
            const cardsHtml = filteredInStock.map(item => createCard(item, selectedConsumableIds.has(item.consumable_id))).join('');
            cardContainer.innerHTML = cardsHtml;
        } else {
            cardContainer.innerHTML = `<div class="col-12"><div class="alert alert-secondary text-center">No items found matching your search.</div></div>`;
        }

        // Render Out-of-Stock Cards (they are not affected by the search bar)
        const outOfStockCardsHtml = outOfStockItems.map(createCard).join('');
        outOfStockCardContainer.innerHTML = outOfStockItems.length > 0 ? outOfStockCardsHtml : `<div class="col-12"><div class="alert alert-secondary text-center">No out-of-stock items.</div></div>`;

        // Re-initialize tooltips for the newly rendered cards
        new bootstrap.Tooltip(document.body, { selector: '[data-bs-toggle="tooltip"]' });
    }


    // --- Helper Functions ---
    // --- Data Loading Functions ---

    // Fetch data and render the card view
    async function loadConsumables() {
        cardContainer.innerHTML = `<div class="col-12 text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>`;
        try {
            const response = await fetch('api/get_consumables.php');
            const result = await response.json();
            if (!result.data) throw new Error('Invalid data format from API.');

            consumablesData = result.data;
            filterAndRenderCards(); // Apply initial filter (if any) and render
        } catch (error) {
            cardContainer.innerHTML = `<div class="col-12"><div class="alert alert-danger">Error loading inventory: ${error.message}</div></div>`;
        }
    }

    // Initialize the DataTable for the table view
    function loadConsumablesTable() {
        if (dataTable) {
            dataTable.ajax.reload();
            return;
        }

            dataTable = $('#consumablesListTable').DataTable({
                "processing": true,
                "ajax": { 
                    "url": "api/get_consumables.php", 
                    "dataSrc": function(json) {
                        // Ensure data is valid and handle photo paths
                        if (!json.data) return [];
                        return json.data.map(item => {
                            if (item.photo && item.photo.startsWith('consumable_qr_')) {
                                item.photoPath = `assets/uploads/qr_codes/${item.photo}`;
                            } else {
                                item.photoPath = `assets/uploads/consumables/${item.photo || 'consumable_default.png'}`;
                            }
                            return item;
                        });
                    }
                },
            "columns": [
                { "data": "stock_number" },
                { "data": "description" },
                { "data": "unit_name" },
                { "data": "current_stock", "className": "text-end fw-bold" },
                { "data": "date_received", "render": data => new Date(data).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) },
                { "data": "consumable_id", "orderable": false, "render": (data, type, row) => {
                    // Disable convert button if the item is part of a conversion
                    const convertButtonDisabled = row.parent_consumable_id || row.is_conversion_source ? 'disabled' : '';
                    return `
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-outline-info view-btn" title="View Details"><i class="bi bi-eye"></i></button>
                            <button type="button" class="btn btn-sm btn-outline-primary stock-card-btn" title="View Stock Card"><i class="bi bi-journal-text"></i></button>
                            <button type="button" class="btn btn-sm btn-outline-secondary convert-btn" title="Convert Unit" ${convertButtonDisabled}><i class="bi bi-arrow-repeat"></i></button>
                            <button type="button" class="btn btn-sm btn-outline-dark photo-btn" title="Change Photo"><i class="bi bi-image"></i></button>
                            <button type="button" class="btn btn-sm btn-outline-success issue-btn" title="Issue Stock"><i class="bi bi-box-arrow-up-right"></i></button>
                            <button type="button" class="btn btn-sm btn-outline-danger pdf-btn" title="PDF Stock Card"><i class="bi bi-file-earmark-pdf"></i></button>
                        </div>
                    `;
                    }
                }
            ],
            "order": [[4, 'desc']]
        });
    }

    /**
     * Handles the QR code generation request and UI update.
     * @param {object} item The consumable item object from consumablesData.
     * @param {HTMLElement} cardElement The card element that was clicked.
     */
    async function generateAndSetQrCode(item, cardElement) {
        const button = cardElement.querySelector('.qr-code-btn');
        const cardImg = cardElement.querySelector('.card-img-top');

        if (!item || !item.stock_number) {
            showToast('Item stock number is missing.', 'Error', 'danger');
            return;
        }

        button.disabled = true;
        button.innerHTML = `<span class="spinner-border spinner-border-sm"></span>`;
        cardImg.style.opacity = '0.5';

        try {
            const response = await fetch('api/consumable_generate_qr_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ consumable_id: item.consumable_id, stock_number: item.stock_number })
            });
            const result = await response.json();
            if (!result.success) throw new Error(result.message);

            showToast(result.message, 'Success', 'success');
            // Update the image source and the underlying data
            // Handle the new photo path if available
            if (result.new_photo_path) {
                const newPhotoSrc = `assets/uploads/${result.new_photo_path}?t=${new Date().getTime()}`;
                cardImg.src = newPhotoSrc;
                // Only update cache if we have a new path
                item.photo = result.new_photo_path.replace('qr_codes/', '');
            }
        } catch (error) {
            showToast(`Error: ${error.message}`, 'QR Code Generation Failed', 'danger');
        } finally {
            button.disabled = false;
            button.innerHTML = `<i class="bi bi-qr-code"></i>`;
            cardImg.style.opacity = '1';
        }
    }

    // --- Event Handler for Card Interactions ---
    function handleCardClick(e) {
        // Broaden the selector to catch clicks on ANY card, including out-of-stock ones.
        // The logic inside will differentiate between selectable and non-selectable actions.
        const card = e.target.closest('.card[data-consumable-id]');

        if (!card) return;

        // Handle button clicks inside the card
        const button = e.target.closest('button');
        if (button) {
            e.stopPropagation(); // Prevent card selection when a button is clicked
            const consumableId = card.dataset.consumableId;
            if (button.title === 'View Details') showViewModal(consumableId, consumablesData, viewConsumableModal);
            else if (button.title === 'Change Photo') triggerPhotoUpload(consumableId);
            else if (button.title === 'View Stock Card') showStockCardModal(consumableId, consumablesData, stockCardModal);
            else if (button.title === 'Convert Unit') showConvertModal(consumableId, consumablesData, convertConsumableModal);
            else if (button.title === 'PDF Stock Card') showStockCardPdfModal(consumableId);
            else if (button.classList.contains('qr-code-btn')) {
                const item = consumablesData.find(c => c.consumable_id == consumableId);
                if (item) {
                    generateAndSetQrCode(item, card);
                }
            }
        } else if (card.classList.contains('selectable-card')) {
            // Handle card selection for issuing only if a button wasn't clicked
            const consumableId = card.dataset.consumableId;
            card.classList.toggle('selected');
            if (card.classList.contains('selected')) {
                selectedConsumableIds.add(consumableId);
            } else {
                selectedConsumableIds.delete(consumableId);
            }
            // Update the button based on the size of the Set
            issueSelectedBtn.disabled = selectedConsumableIds.size === 0;
        }
    }

    // --- Event Listeners ---

    // Attach the single handler to both card containers
    cardContainer.addEventListener('click', handleCardClick);
    outOfStockCardContainer.addEventListener('click', (e) => {
        const button = e.target.closest('button');
        if (button) {
            e.stopPropagation(); // Prevent card selection when a button is clicked
        }
        handleCardClick(e);
    });

    // Event listener for buttons in the table view
    $('#consumablesListTable tbody').on('click', 'button', function (e) {
        e.stopPropagation();
        const data = dataTable.row($(this).closest('tr')).data();
        const consumableId = data.consumable_id;
        if ($(this).hasClass('view-btn')) showViewModal(consumableId, consumablesData, viewConsumableModal);
        else if ($(this).hasClass('issue-btn')) showIssueModal([consumableId], consumablesData);
        else if ($(this).hasClass('stock-card-btn')) showStockCardModal(consumableId, consumablesData, stockCardModal);
        else if ($(this).hasClass('convert-btn')) showConvertModal(consumableId, consumablesData, convertConsumableModal);
        else if ($(this).hasClass('photo-btn')) triggerPhotoUpload(consumableId);
        else if ($(this).hasClass('pdf-btn')) showStockCardPdfModal(consumableId);
    });

    // Event listener for the main "Issue Selected" button
    issueSelectedBtn.addEventListener('click', () => {
        // Use the Set for IDs
        const idsToIssue = Array.from(selectedConsumableIds);
        if (idsToIssue.length > 0) showIssueModal(idsToIssue, consumablesData);
    });

    // Event listeners for forms and direct actions
    document.getElementById('issueConsumableForm').addEventListener('submit', async (e) => {
        // The handleIssueFormSubmit function is async, so we await it.
        await handleIssueFormSubmit(e, loadConsumables, dataTable);
        // After a successful submission, clear the selection state.
        selectedConsumableIds.clear();
        issueSelectedBtn.disabled = true;
        // The loadConsumables() call inside handleIssueFormSubmit will redraw the cards without the selected class.
    });
    document.getElementById('convertConsumableForm').addEventListener('submit', (e) => handleConvertFormSubmit(e, convertConsumableModal, loadConsumables, dataTable));
    directPhotoUploadInput.addEventListener('change', (e) => handleDirectPhotoUpload(e, consumablesData, cardContainer));
    
    // Event listener for the search bar
    searchInput.addEventListener('input', filterAndRenderCards);

    // Event listener for view switching
    cardViewBtn.addEventListener('click', () => {
        cardViewBtn.classList.add('active');
        tableViewBtn.classList.remove('active');
        cardContainer.classList.remove('d-none');
        stockStatusTabs.classList.remove('d-none');
        tableContainer.classList.add('d-none');
        document.getElementById('stock-status-tabs-content').classList.remove('d-none');
    });

    tableViewBtn.addEventListener('click', () => {
        tableViewBtn.classList.add('active');
        cardViewBtn.classList.remove('active');
        stockStatusTabs.classList.add('d-none');
        tableContainer.classList.remove('d-none');
        document.getElementById('stock-status-tabs-content').classList.add('d-none');
        if (!dataTable) loadConsumablesTable();
    });

    document.getElementById('print-stock-card-btn').addEventListener('click', () => {
        window.print();
    });

    // --- Initial Load ---
    loadConsumables();
});