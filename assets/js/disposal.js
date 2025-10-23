/**
 * disposal.js
 * Handles the Disposal Management (IIRUP) page functionality.
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTables without DOM searching for now
    const unserviceableItemsTable = $('#unserviceableItemsTable').DataTable({ dom: 'rt<"bottom"ip>' });
    const iirupDocsTable = $('#iirupDocsTable').DataTable({ dom: 'rt<"bottom"ip>' });
    const unserviceableSearchInput = document.getElementById('unserviceable-search');
    const iirupDocsSearchInput = document.getElementById('iirup-docs-search');
    const createIirupBtn = document.getElementById('create-iirup-btn');
    const selectAllCheckbox = document.getElementById('select-all-unserviceable');
    const createIirupModal = new bootstrap.Modal(document.getElementById('createIirupModal'));
    const createIirupForm = document.getElementById('createIirupForm');
    const selectedDisposalItemsList = document.getElementById('selected-disposal-items');
    const iirupNumberPreview = document.getElementById('iirup_number_preview');
    const printIirupModalBtn = document.getElementById('print-iirup-modal-btn');

    let selectedAssetIds = new Set(); // Stores {asset_id, asset_type} for selected items

    // --- Functions to load data ---
    async function loadUnserviceableItems() {
        try {
            const response = await fetch('api/get_unserviceable_assets.php');
            const result = await response.json();

            if (!result.success) {
                showToast(result.message, 'Error', 'danger');
                return;
            }

            unserviceableItemsTable.clear().draw();
            result.data.forEach(item => {
                const checkbox = `<input type="checkbox" class="unserviceable-item-checkbox" data-asset-id="${item.asset_id}" data-asset-type="${item.asset_type}" data-description="${escapeHTML(item.description)}">`;
                unserviceableItemsTable.row.add([
                    checkbox,
                    escapeHTML(item.property_number),
                    escapeHTML(item.description),
                    escapeHTML(item.serial_number || 'N/A'),
                    escapeHTML(item.asset_type),
                    escapeHTML(item.current_condition),
                    new Date(item.date_acquired).toLocaleDateString()
                ]).draw(false);
            });
            updateCreateIirupButtonState();
        } catch (error) {
            showToast(`Error loading unserviceable items: ${error.message}`, 'Error', 'danger');
        }
    }

    async function loadIirupDocuments() {
        try {
            const response = await fetch('api/iirup_api.php');
            const result = await response.json();

            if (!result.success) {
                showToast(result.message, 'Error', 'danger');
                return;
            }

            iirupDocsTable.clear().draw();
            result.data.forEach(doc => {
                let actions = `
                    <button type="button" class="btn btn-sm btn-info view-iirup-btn" data-iirup-id="${doc.iirup_id}" title="View IIRUP"><i class="bi bi-eye"></i></button>
                `;
                if (doc.status === 'Draft') {
                    actions += ` <button type="button" class="btn btn-sm btn-warning finalize-iirup-btn" data-iirup-id="${doc.iirup_id}" data-iirup-number="${doc.iirup_number}" title="Finalize Disposal"><i class="bi bi-check-circle"></i></button>`;
                }

                iirupDocsTable.row.add([
                    escapeHTML(doc.iirup_number),
                    new Date(doc.as_of_date).toLocaleDateString(),
                    escapeHTML(doc.disposal_method || 'N/A'),
                    escapeHTML(doc.status),
                    escapeHTML(doc.created_by),
                    new Date(doc.date_created).toLocaleDateString(),
                    actions
                ]).draw(false);
            });
        } catch (error) {
            showToast(`Error loading IIRUP documents: ${error.message}`, 'Error', 'danger');
        }
    }

    async function fetchNextIirupNumber() {
        try {
            const response = await fetch('api/get_next_iirup_number.php');
            const result = await response.json();
            if (result.success) {
                iirupNumberPreview.value = result.preview_number;
            } else {
                iirupNumberPreview.value = 'Error fetching number';
                showToast(result.message, 'Error', 'danger');
            }
        } catch (error) {
            iirupNumberPreview.value = 'Error fetching number';
            showToast(`Error: ${error.message}`, 'Error', 'danger');
        }
    }

    // --- UI Update Functions ---
    function updateCreateIirupButtonState() {
        createIirupBtn.disabled = selectedAssetIds.size === 0;
    }

    function populateSelectedDisposalItems() {
        selectedDisposalItemsList.innerHTML = '';
        if (selectedAssetIds.size === 0) {
            selectedDisposalItemsList.innerHTML = '<li class="list-group-item text-muted">No items selected.</li>';
            return;
        }

        unserviceableItemsTable.rows().every(function() {
            const rowData = this.data();
            const checkbox = $(rowData[0]).find('input[type="checkbox"]');
            const assetId = checkbox.data('asset-id');
            const assetType = checkbox.data('asset-type');
            const description = checkbox.data('description');

            if (selectedAssetIds.has(`${assetType}-${assetId}`)) {
                const listItem = document.createElement('li');
                listItem.className = 'list-group-item';
                listItem.textContent = `${description} (PN: ${rowData[1]}, Type: ${assetType})`;
                selectedDisposalItemsList.appendChild(listItem);
            }
        });
    }

    // --- Event Listeners ---

    // Custom search for Unserviceable Items Table
    if (unserviceableSearchInput) {
        unserviceableSearchInput.addEventListener('keyup', function() {
            unserviceableItemsTable.search(this.value).draw();
        });
    }

    // Custom search for IIRUP Docs Table
    if (iirupDocsSearchInput) {
        iirupDocsSearchInput.addEventListener('keyup', function() {
            iirupDocsTable.search(this.value).draw();
        });
    }


    selectAllCheckbox.addEventListener('change', function() {
        const isChecked = this.checked;
        $('.unserviceable-item-checkbox').prop('checked', isChecked).trigger('change');
    });

    $(document).on('change', '.unserviceable-item-checkbox', function() {
        const assetId = $(this).data('asset-id');
        const assetType = $(this).data('asset-type');
        const key = `${assetType}-${assetId}`;

        if (this.checked) {
            selectedAssetIds.add(key);
        } else {
            selectedAssetIds.delete(key);
        }
        updateCreateIirupButtonState();

        // Update selectAllCheckbox state
        const allCheckboxes = $('.unserviceable-item-checkbox');
        selectAllCheckbox.checked = allCheckboxes.length > 0 && allCheckboxes.get().every(cb => cb.checked);
    });

    createIirupBtn.addEventListener('click', function() {
        if (selectedAssetIds.size === 0) {
            showToast('Please select at least one item to create an IIRUP.', 'Warning', 'warning');
            return;
        }
        fetchNextIirupNumber();
        populateSelectedDisposalItems();
        createIirupModal.show();
    });

    createIirupForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Generating...';

        const asOfDate = document.getElementById('as_of_date').value;
        const disposalMethod = document.getElementById('disposal_method').value;

        const assetsToDispose = [];
        $('.unserviceable-item-checkbox:checked').each(function() {
            const assetId = $(this).data('asset-id');
            const assetType = $(this).data('asset-type');
            const description = $(this).data('description');

            assetsToDispose.push({
                asset_id: assetId,
                asset_type: assetType,
                description: description,
                remarks: ''
            });
        });

        try {
            const response = await fetch('api/iirup_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    as_of_date: asOfDate,
                    disposal_method: disposalMethod,
                    selected_assets: assetsToDispose
                })
            });
            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'Failed to create IIRUP.');
            }

            showToast(result.message, 'Success', 'success');
            createIirupModal.hide();
            selectedAssetIds.clear(); // Clear selection
            loadUnserviceableItems(); // Refresh unserviceable list
            loadIirupDocuments(); // Refresh IIRUP documents list
            selectAllCheckbox.checked = false; // Uncheck select all
        } catch (error) {
            showToast(`Error: ${error.message}`, 'Error', 'danger');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Generate IIRUP';
        }
    });

    // Event listener for viewing IIRUP document
    $(document).on('click', '.view-iirup-btn', function() {
        const iirupId = $(this).data('iirup-id');
        showIirupModal(iirupId); // Assuming showIirupModal is defined in document.modals.js
    });

    // Event listener for finalizing IIRUP document
    $(document).on('click', '.finalize-iirup-btn', function() {
        const iirupId = $(this).data('iirup-id');
        const iirupNumber = $(this).data('iirup-number');

        Swal.fire({
            title: 'Are you sure?',
            html: `You are about to finalize IIRUP <strong>${iirupNumber}</strong>. This will permanently mark all associated assets as 'Disposed'. This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, finalize it!'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const response = await fetch('api/iirup_api.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'finalize',
                            iirup_id: iirupId
                        })
                    });
                    const resData = await response.json();
                    if (!response.ok) {
                        throw new Error(resData.message || 'Failed to finalize IIRUP.');
                    }
                    showToast(resData.message, 'Success', 'success');
                    loadUnserviceableItems(); // Refresh both tables
                    loadIirupDocuments();
                } catch (error) {
                    showToast(`Error: ${error.message}`, 'Error', 'danger');
                }
            }
        });
    });

    // Event listener for printing IIRUP from modal
    printIirupModalBtn.addEventListener('click', function() {
        printDocument('iirup-modal-body', 'IIRUP Document'); // Assuming printDocument is in document.utils.js
    });

    // --- Tab Change Listener ---
    document.getElementById('disposal-tabs').addEventListener('shown.bs.tab', function(event) {
        if (event.target.id === 'unserviceable-tab') {
            unserviceableItemsTable.columns.adjust().draw();
            loadUnserviceableItems();
        } else if (event.target.id === 'iirup-docs-tab') {
            iirupDocsTable.columns.adjust().draw();
            loadIirupDocuments();
        }
    });

    // --- Initial Load ---
    loadUnserviceableItems();
    loadIirupDocuments();
});