/**
 * ppe.js
 * Main script for the ppe.php page.
 */
document.addEventListener('DOMContentLoaded', function() {
    // --- Elements ---
    const cardContainer = document.getElementById('ppe-card-container');
    const assignedCardContainer = document.getElementById('assigned-card-container');
    const tableContainer = document.getElementById('ppe-table-container');
    const cardViewBtn = document.getElementById('card-view-btn');
    const tableViewBtn = document.getElementById('table-view-btn');
    const assignSelectedBtn = document.getElementById('assign-selected-btn');
    const searchInput = document.getElementById('ppe-card-search');
    const directPhotoUploadInput = document.getElementById('direct-ppe-photo-upload');

    // --- Modals ---
    const assignPpeModalEl = document.getElementById('assignPpeModal');
    const assignModal = assignPpeModalEl ? new bootstrap.Modal(assignPpeModalEl) : null;
    const viewPpeModalEl = document.getElementById('viewPpeModal');
    const viewPpeModal = viewPpeModalEl ? new bootstrap.Modal(viewPpeModalEl) : null;
    const editPpeModalEl = document.getElementById('editPpeModal');
    const editPpeModal = editPpeModalEl ? new bootstrap.Modal(editPpeModalEl) : null;
    const propertyCardModalEl = document.getElementById('propertyCardModal');
    const propertyCardModal = propertyCardModalEl ? new bootstrap.Modal(propertyCardModalEl) : null;
    // const parViewModal = new bootstrap.Modal(document.getElementById('viewPoModal')); // This is now handled by the global showParModal function

    // --- State ---
    let allPpeData = [];
    let unassignedData = [];
    let assignedData = [];
    let dataTable = null;
    let selectedPpeIds = new Set(); // State for selected card IDs

    // --- Data Loading ---
    async function loadPpeData() {
        cardContainer.innerHTML = `<div class="col-12 text-center p-5"><div class="spinner-border text-primary" role="status"></div></div>`;
        assignedCardContainer.innerHTML = `<div class="col-12 text-center p-5"><div class="spinner-border text-primary" role="status"></div></div>`;
        try {
            const response = await fetch('api/get_ppe.php');
            const result = await response.json();

            if (!result.success) throw new Error(result.message || 'Failed to load data.');

            allPpeData = result.data || [];
            // Filter the unified data into the respective arrays
            unassignedData = allPpeData.filter(item => !item.assigned_to);
            assignedData = allPpeData.filter(item => !!item.assigned_to);

            filterAndRenderCards();
        } catch (error) {
            cardContainer.innerHTML = `<div class="col-12"><div class="alert alert-danger">Error loading assets: ${error.message}</div></div>`;
            assignedCardContainer.innerHTML = `<div class="col-12"><div class="alert alert-danger">Error loading assets: ${error.message}</div></div>`;
        }
    }

    // --- Rendering ---
    function filterAndRenderCards() {
        const searchTerm = searchInput.value.toLowerCase();

        const filteredUnassigned = unassignedData.filter(item => 
            (item.description?.toLowerCase() || '').includes(searchTerm) ||
            (item.property_number?.toLowerCase() || '').includes(searchTerm) ||
            (item.serial_number?.toLowerCase() || '').includes(searchTerm)
        );
        cardContainer.innerHTML = filteredUnassigned.length > 0 
            ? filteredUnassigned.map(item => createPpeCard(item, selectedPpeIds.has(item.ppe_id))).join('') 
            : `<div class="col-12"><div class="alert alert-secondary text-center">No unassigned items found.</div></div>`;

        const filteredAssigned = assignedData.filter(item => 
            (item.description?.toLowerCase() || '').includes(searchTerm) ||
            (item.property_number?.toLowerCase() || '').includes(searchTerm) ||
            (item.serial_number?.toLowerCase() || '').includes(searchTerm) ||
            (item.assigned_to?.toLowerCase() || '').includes(searchTerm)
        );
        assignedCardContainer.innerHTML = filteredAssigned.length > 0 
            ? filteredAssigned.map(item => createPpeCard(item, false)).join('') // Explicitly pass `false` for isSelected
            : `<div class="col-12"><div class="alert alert-secondary text-center">No assigned items found.</div></div>`;
    }

    function loadPpeTable() {
        if (dataTable) {
            dataTable.ajax.reload();
            return;
        }

        dataTable = $('#ppeListTable').DataTable({
            "processing": true,
            "ajax": { "url": "api/get_ppe.php", "dataSrc": "data" },
            "columns": [
                { "searchable": false, "orderable": false, "className": "text-center align-middle", "render": (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
                { "data": "property_number" },
                { "data": "description" },
                { "data": "serial_number", "render": data => escapeHTML(data || 'N/A') },
                { "data": "date_acquired", "render": data => new Date(data).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) },
                { "data": "assigned_to", "className": "text-center", "render": data => data ? `<span class="badge bg-success">${escapeHTML(data)}</span>` : `<span class="badge bg-warning text-dark">Unassigned</span>` },
                { "data": "current_condition", "className": "text-center", "render": data => {
                    let badgeClass = 'bg-secondary';
                    if (data === 'Serviceable') badgeClass = 'bg-success';
                    else if (data === 'For Repair') badgeClass = 'bg-warning text-dark';
                    else if (data === 'Unserviceable') badgeClass = 'bg-danger';
                    return `<span class="badge ${badgeClass}">${data}</span>`;
                }},
                { "data": "ppe_id", "orderable": false, "render": (data, type, row) => {
                    const viewBtn = `<button type="button" class="btn btn-sm btn-info view-details-btn" data-ppe-id="${data}" title="View Details"><i class="bi bi-eye"></i></button>`;
                    const editBtn = `<button type="button" class="btn btn-sm btn-primary edit-btn" data-ppe-id="${data}" title="Edit Details"><i class="bi bi-pencil-square"></i></button>`;
                    let primaryActionBtn;
                    if (row.assigned_to) {
                        primaryActionBtn = `<button type="button" class="btn btn-sm btn-warning unassign-btn" data-ppe-id="${data}" title="Unassign Item"><i class="bi bi-person-dash-fill"></i></button>`;
                    } else {
                        primaryActionBtn = `<button type="button" class="btn btn-sm btn-success assign-btn" data-ppe-id="${data}" title="Assign Item"><i class="bi bi-person-check-fill"></i></button>`;
                    }
                    return `<div class="btn-group">${primaryActionBtn}${viewBtn}${editBtn}</div>`;
                }},
            ],
            "order": [[4, 'desc']]
        });
    }

    async function unassignPpeItem(ppeId) {
        try {
            const response = await fetch('api/ppe_unassign_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ppe_id: ppeId })
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.error || 'An unknown error occurred.');

            showToast('Item unassigned successfully.', 'Success', 'success');

            // Clear the unassigned item from the selection to prevent inconsistencies
            selectedPpeIds.delete(ppeId);

            // --- FIX: Reload all data from the server to ensure consistency ---
            loadPpeData();
            if (dataTable) dataTable.ajax.reload(); // Refresh table view

        } catch (error) {
            // If unassignment fails, we should re-add the item to the assigned list visually
            // This is an advanced step, for now, a full reload is safest.
            // loadPpeData(); 
            showToast(`Error: ${error.message}`, 'Unassignment Failed', 'danger');
        }
    }

    /**
     * Handles the QR code generation request and UI update for a PPE item.
     * @param {object} item The PPE item object from ppeData.
     * @param {HTMLElement} cardElement The card element that was clicked.
     */
    async function generateAndSetQrCode(item, cardElement) {
        const button = cardElement.querySelector('.qr-code-btn');
        const cardImg = cardElement.querySelector('.card-img-top');

        if (!item || !item.property_number) {
            showToast('Item property number is missing.', 'Error', 'danger');
            return;
        }

        // Provide immediate visual feedback
        button.disabled = true;
        button.innerHTML = `<span class="spinner-border spinner-border-sm"></span>`;
        cardImg.style.opacity = '0.5';

        try {
            const response = await fetch('api/ppe_generate_qr_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ ppe_id: item.ppe_id, property_number: item.property_number })
            });

            const result = await response.json();
            if (!result.success) throw new Error(result.message);

            showToast(result.message, 'Success', 'success');
            
            // Safely handle new_photo_path (may be undefined/null)
            if (result.new_photo_path) {
                const newPhotoSrc = `assets/uploads/${result.new_photo_path}?t=${new Date().getTime()}`;
                cardImg.src = newPhotoSrc;
                item.photo = String(result.new_photo_path).replace('qr_codes/', ''); // Update local cache
            }
        } catch (error) {
            showToast(`Error: ${error.message}`, 'QR Code Generation Failed', 'danger');
        } finally {
            button.disabled = false;
            button.innerHTML = `<i class="bi bi-qr-code"></i>`;
            cardImg.style.opacity = '1';
        }
    }

    async function disposePpeItem(ppeId) {
        try {
            const response = await fetch('api/ppe_dispose_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ppe_id: ppeId })
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'An unknown error occurred.');

            showToast(result.message, 'Success', 'success');
            loadPpeData(); // Refresh data to remove the card from the view
            if (dataTable) dataTable.ajax.reload();

        } catch (error) {
            showToast(`Error: ${error.message}`, 'Disposal Failed', 'danger');
        }
    }

    function triggerPhotoUpload(ppeId) {
        directPhotoUploadInput.dataset.ppeId = ppeId;
        directPhotoUploadInput.click();
    }

    async function handleDirectPhotoUpload(e) {
        const file = e.target.files[0];
        if (!file) return;

        const ppeId = e.target.dataset.ppeId;
        const cardImg = document.querySelector(`.card[data-ppe-id="${ppeId}"] img`);

        const formData = new FormData();
        formData.append('ppe_id', ppeId);
        formData.append('ppe_photo', file);

        if (cardImg) cardImg.style.opacity = '0.5';

        try {
            const response = await fetch('api/ppe_change_photo_api.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'An unknown error occurred.');

            showToast(result.message, 'Success', 'success');

            const allData = [...unassignedData, ...assignedData];
            const itemInCache = allData.find(c => c.ppe_id == ppeId);
            if (itemInCache) itemInCache.photo = result.new_photo;
            if (cardImg) cardImg.src = `assets/uploads/ppe/${result.new_photo}?t=${new Date().getTime()}`;

        } catch (error) {
            showToast(`Error: ${error.message}`, 'Upload Failed', 'danger');
        } finally {
            if (cardImg) cardImg.style.opacity = '1';
            e.target.value = '';
        }
    }

    function showViewModal(ppeId) {
        const contentArea = document.getElementById('view-ppe-content');
        const viewTemplate = document.getElementById('ppe-view-template').content;
        contentArea.innerHTML = `<div class="text-center p-5"><div class="spinner-border" role="status"></div></div>`;

    const editButton = document.getElementById('edit-details-from-view-btn');
    const propertyCardButton = document.getElementById('view-property-card-btn');
    if (editButton) editButton.dataset.ppeId = ppeId;
    if (propertyCardButton) propertyCardButton.dataset.ppeId = ppeId;

    if (viewPpeModal) viewPpeModal.show();

        const allData = [...unassignedData, ...assignedData];
        const item = allData.find(c => c.ppe_id == ppeId);
        if (!item) {
            contentArea.innerHTML = `<div class="alert alert-danger">Item details not found.</div>`;
            return;
        }

        const viewContent = viewTemplate.cloneNode(true);

        const photoName = item.photo || 'ppe_default.png';
        let photoPath;
        if (photoName.startsWith('ppe_qr_')) {
            photoPath = `assets/uploads/qr_codes/${photoName}`;
        } else {
            photoPath = `assets/uploads/ppe/${photoName}`;
        }
        viewContent.querySelector('img').src = photoPath;

        viewContent.querySelector('[data-template-id="description"]').textContent = item.description;
        viewContent.querySelector('[data-template-id="property_number"]').textContent = item.property_number;
        viewContent.querySelector('[data-template-id="serial_number"]').textContent = item.serial_number || 'N/A';
        viewContent.querySelector('[data-template-id="model_number"]').textContent = item.model_number || 'N/A';
        viewContent.querySelector('[data-template-id="unit_cost"]').textContent = parseFloat(item.unit_cost).toLocaleString('en-US', { style: 'currency', currency: 'PHP' });
        viewContent.querySelector('[data-template-id="date_acquired"]').textContent = new Date(item.date_acquired).toLocaleDateString();
        viewContent.querySelector('[data-template-id="assigned_to"]').textContent = item.assigned_to || 'Unassigned';

        contentArea.innerHTML = '';
        contentArea.appendChild(viewContent);
    }

    function showEditModal(ppeId) {
        const allData = [...unassignedData, ...assignedData];
        const item = allData.find(c => c.ppe_id == ppeId);
        if (!item) {
            showToast('Could not find item details to edit.', 'Error', 'danger');
            return;
        }

    if (viewPpeModal) viewPpeModal.hide();

        document.getElementById('edit_ppe_id').value = item.ppe_id;
        document.getElementById('edit_ppe_description').value = item.description;
        document.getElementById('edit_ppe_property_number').value = item.property_number;
        document.getElementById('edit_ppe_model_number').value = item.model_number || '';
        document.getElementById('edit_ppe_serial_number').value = item.serial_number || '';
        document.getElementById('edit_ppe_date_acquired').value = item.date_acquired;
        document.getElementById('edit_ppe_condition').value = item.current_condition;
    if (editPpeModal) editPpeModal.show();
    }

    /**
     * Populates and shows the assignment modal for one or more PPE IDs.
     * @param {string[]} ppeIds - An array of PPE IDs to be assigned.
     */
    function showAssignModal(ppeIds) {
        if (ppeIds.length === 0) return;

        const itemsContainer = document.getElementById('assign-items-container');
        itemsContainer.innerHTML = ''; // Clear previous items
        ppeIds.forEach(id => {
            const item = unassignedData.find(d => d.ppe_id == id);
            if (item) {
                const itemEl = document.createElement('li');
                itemEl.className = 'list-group-item';
                itemEl.textContent = `${item.description} (PN: ${item.property_number})`;
                itemEl.dataset.ppeId = id;
                itemsContainer.appendChild(itemEl);
            }
        });
        assignModal.show();
    }

    function handleCardClick(e) {
        // Find the closest parent card element, regardless of whether it's assigned or not.
        const card = e.target.closest('.card');
        if (!card) return;

        const changePhotoBtn = e.target.closest('.change-photo-btn');
        const viewDetailsBtn = e.target.closest('.view-details-btn');
        const unassignCardBtn = e.target.closest('.unassign-card-btn');
        const assignCardBtn = e.target.closest('.assign-card-btn');
        const disposeBtn = e.target.closest('.dispose-btn');
        const viewPdfBtn = e.target.closest('.view-property-card-pdf-btn');
        const qrCodeBtn = e.target.closest('.qr-code-btn');
        const ppeId = card.dataset.ppeId;

        if (changePhotoBtn) {
            e.stopPropagation();
            triggerPhotoUpload(ppeId);
        } else if (unassignCardBtn) {
            e.stopPropagation();
            if (confirm('Are you sure you want to unassign this item?')) {
                unassignPpeItem(ppeId);
            }
        } else if (viewDetailsBtn) {
            e.stopPropagation();
            showViewModal(ppeId);
        } else if (assignCardBtn) {
            e.stopPropagation();
            showAssignModal([ppeId]);
        } else if (disposeBtn) {
            e.stopPropagation();
            if (confirm('Are you sure you want to dispose of this item? This action cannot be undone.')) {
                disposePpeItem(ppeId);
            }
        } else if (viewPdfBtn) {
            e.stopPropagation();
            showPropertyCardModal(ppeId);
        } else if (qrCodeBtn) {
            e.stopPropagation();
            const allData = [...unassignedData, ...assignedData];
            const item = allData.find(p => p.ppe_id == ppeId);
            if (item) {
                generateAndSetQrCode(item, card);
            }
        } else if (!card.classList.contains('card-assigned')) {
            // Update the selection state in our Set
            card.classList.toggle('selected');
            if (card.classList.contains('selected')) {
                selectedPpeIds.add(ppeId);
            } else {
                selectedPpeIds.delete(ppeId);
            }
            // Update the button based on the size of the Set
            assignSelectedBtn.disabled = selectedPpeIds.size === 0;
        }
    }

    if (cardViewBtn) {
        cardViewBtn.addEventListener('click', () => {
            cardViewBtn.classList.add('active');
            if (tableViewBtn) tableViewBtn.classList.remove('active');
            const tabsContentEl = document.getElementById('assignment-status-tabs-content');
            const tabsEl = document.getElementById('assignment-status-tabs');
            if (tabsContentEl) tabsContentEl.classList.remove('d-none');
            if (tabsEl) tabsEl.classList.remove('d-none');
            if (tableContainer) tableContainer.classList.add('d-none');
        });
    }

    if (tableViewBtn) {
        tableViewBtn.addEventListener('click', () => {
            tableViewBtn.classList.add('active');
            if (cardViewBtn) cardViewBtn.classList.remove('active');
            const tabsContentEl = document.getElementById('assignment-status-tabs-content');
            const tabsEl = document.getElementById('assignment-status-tabs');
            if (tabsContentEl) tabsContentEl.classList.add('d-none');
            if (tabsEl) tabsEl.classList.add('d-none');
            if (tableContainer) tableContainer.classList.remove('d-none');
            if (!dataTable) loadPpeTable();
        });
    }

    if (assignSelectedBtn) {
        assignSelectedBtn.addEventListener('click', () => {
            // Use the Set for IDs
            const idsToAssign = Array.from(selectedPpeIds);
            showAssignModal(idsToAssign);
        });
    }

    const assignPpeFormEl = document.getElementById('assignPpeForm');
    if (assignPpeFormEl) {
        assignPpeFormEl.addEventListener('submit', async function(e) {
        e.preventDefault();
        const form = e.target;
        const submitButton = form.querySelector('button[type="submit"]');
        const itemsToAssign = Array.from(document.getElementById('assign-items-container').children).map(li => li.dataset.ppeId);

        submitButton.disabled = true;
        submitButton.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Assigning...`;

        try {
            const response = await fetch('api/ppe_assign_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                        ppe_ids: itemsToAssign,
                        user_id: parseInt(form.querySelector('#assign_to_user_id').value) || null,
                        // Backwards-compatible: if a name input is used elsewhere it can still be provided
                        user_name: form.querySelector('#assign_to_user_id').dataset.fullName || null,
                        location: form.querySelector('#location').value,
                        generate_par: form.querySelector('#generate_par').checked
                    })
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.error || 'An unknown error occurred.');

            showToast(result.message, 'Success', 'success');
            assignModal.hide();

            // If a PAR was generated, ask the user if they want to see the modal view
            if (result.par_id) {
                if (confirm("Assignment successful. A Property Acknowledgment Receipt (PAR) was generated. Do you want to view and print it now?")) {
                    if (typeof showParModal === 'function') {
                        showParModal(result.par_id);
                    } else {
                        // Fallback for safety, though showParModal should exist
                        const pdfUrl = `api/par_pdf.php?id=${result.par_id}`;
                        window.open(pdfUrl, '_blank');
                        alert('Could not find the modal view function, opening PDF in a new tab instead.');
                    }
                }
            }

            // --- FIX: Reload all data from the server to ensure consistency ---
            loadPpeData();
            if (dataTable) dataTable.ajax.reload();

            selectedPpeIds.clear(); // Clear the selection Set
            assignSelectedBtn.disabled = true; // Disable the button

        } catch (error) {
            showToast(`Error: ${error.message}`, 'Assignment Failed', 'danger');
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = `<i class="bi bi-check-circle"></i> Confirm Assignment`;
        }
        });
    }

    const editDetailsFromViewBtn = document.getElementById('edit-details-from-view-btn');
    if (editDetailsFromViewBtn) {
        editDetailsFromViewBtn.addEventListener('click', function() {
            const ppeId = this.dataset.ppeId;
            if (ppeId) showEditModal(ppeId);
        });
    }

    const editPpeFormEl = document.getElementById('editPpeForm');
    if (editPpeFormEl) {
        editPpeFormEl.addEventListener('submit', async function(e) {
        e.preventDefault();
        const form = e.target;
        const submitButton = form.querySelector('button[type="submit"]');
        const formData = new FormData(form);
        const submissionData = Object.fromEntries(formData.entries());

        submitButton.disabled = true;
        submitButton.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Saving...`;

        try {
            const response = await fetch('api/ppe_edit_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(submissionData)
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'An unknown error occurred.');

            showToast(result.message, 'Success', 'success');
            editPpeModal.hide();
            loadPpeData();
            if (dataTable) dataTable.ajax.reload();
        } catch (error) {
            showToast(`Error: ${error.message}`, 'Update Failed', 'danger');
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = `<i class="bi bi-save"></i> Save Changes`;
        }
        });
    }

    $('#ppeListTable').on('click', '.assign-btn', function() {
        const ppeId = $(this).data('ppe-id');
        showAssignModal([ppeId]);
    });

    $('#ppeListTable').on('click', '.view-details-btn', function() {
        const ppeId = $(this).data('ppe-id');
        showViewModal(ppeId);
    });

    $('#ppeListTable').on('click', '.edit-btn', function() {
        const ppeId = $(this).data('ppe-id');
        showEditModal(ppeId);
    });

    $('#ppeListTable').on('click', '.unassign-btn', async function() {
        const ppeId = $(this).data('ppe-id');
        if (confirm('Are you sure you want to unassign this item?')) {
            await unassignPpeItem(ppeId);
        }
    });

    if (cardContainer) cardContainer.addEventListener('click', handleCardClick);
    assignedCardContainer.addEventListener('click', handleCardClick);
    searchInput.addEventListener('input', filterAndRenderCards);
    directPhotoUploadInput.addEventListener('change', handleDirectPhotoUpload);
    loadPpeData();
});

/**
 * Shows the Property Card modal and loads its content.
 * @param {string} ppeId The ID of the PPE item.
 */
async function showPropertyCardModal(ppeId) {
    const modalBody = document.getElementById('property-card-modal-body');
    modalBody.innerHTML = `<div class="text-center p-5"><div class="spinner-border" role="status"></div></div>`;
    
    const propertyCardModal = new bootstrap.Modal(document.getElementById('propertyCardModal'));
    propertyCardModal.show();

    try {
        const response = await fetch(`api/property_card_view.php?id=${ppeId}`);
        if (!response.ok) throw new Error('Failed to fetch Property Card data.');
        modalBody.innerHTML = await response.text();
    } catch (error) {
        modalBody.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    }
}

/**
 * Prints the content of the Property Card modal.
 */
function printPropertyCard() {
    const content = document.getElementById('property-card-content');
    if (content) {
        const printWindow = window.open('', '', 'height=800,width=1000');
        printWindow.document.write('<html><head><title>Print Property Card</title>');
        printWindow.document.write(content.querySelector('style')?.outerHTML || '');
        printWindow.document.write('</head><body>' + content.innerHTML + '</body></html>');
        printWindow.document.close();
        printWindow.focus();
        setTimeout(() => { printWindow.print(); printWindow.close(); }, 250);
    }
}