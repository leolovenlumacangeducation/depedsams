/**
 * sep.js
 * Main script for the sep.php page.
 */
document.addEventListener('DOMContentLoaded', function() {
    // --- Elements ---
    const unassignedCardContainer = document.getElementById('unassigned-card-container');
    const assignedCardContainer = document.getElementById('assigned-card-container');
    const unserviceableCardContainer = document.getElementById('unserviceable-card-container');
    const forRepairCardContainer = document.getElementById('for-repair-card-container');
    const disposedCardContainer = document.getElementById('disposed-card-container');
    const tableContainer = document.getElementById('sep-table-container');
    const cardViewBtn = document.getElementById('card-view-btn');
    const tableViewBtn = document.getElementById('table-view-btn');
    const assignSelectedBtn = document.getElementById('assign-selected-btn');
    const searchInput = document.getElementById('sep-card-search');
    const directPhotoUploadInput = document.getElementById('direct-sep-photo-upload');
    const generateIcsCheckbox = document.getElementById('generate_ics');

    // --- Modals ---
    const assignModalEl = document.getElementById('assignSepModal');
const assignModal = new bootstrap.Modal(assignModalEl);

// Proper focus management for the assign modal
assignModalEl.addEventListener('shown.bs.modal', function () {
    // Focus on the first focusable element
    const firstFocusable = assignModalEl.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
    if (firstFocusable) {
        firstFocusable.focus();
    }
});
    const viewSepModal = new bootstrap.Modal(document.getElementById('viewSepModal'));
    const editSepModal = new bootstrap.Modal(document.getElementById('editSepModal'));

    // --- State ---
    let allSepData = [];
    let dataTable = null;
    let selectedSepIds = new Set(); // State for selected card IDs

    // --- Data Loading ---
    async function loadSepData() {
        const containers = [unassignedCardContainer, assignedCardContainer, unserviceableCardContainer, forRepairCardContainer, disposedCardContainer];
        containers.forEach(c => c.innerHTML = `<div class="col-12 text-center p-5"><div class="spinner-border text-primary" role="status"></div></div>`);
        
        try {
            const response = await fetch('api/get_sep.php'); // Fetch all data
            const result = await response.json();

            if (result.error) {
                throw new Error(result.error);
            }

            allSepData = result.data || [];

            filterAndRenderCards();
        } catch (error) {
            containers.forEach(c => c.innerHTML = `<div class="col-12"><div class="alert alert-danger">Error loading assets: ${error.message}</div></div>`);
        }
    }

    // --- Rendering ---
    function filterAndRenderCards() {
        const searchTerm = searchInput.value.toLowerCase();
        
        const filteredData = allSepData.filter(item => 
            (item.description?.toLowerCase() || '').includes(searchTerm) ||
            (item.property_number?.toLowerCase() || '').includes(searchTerm) ||
            (item.serial_number?.toLowerCase() || '').includes(searchTerm) ||
            (item.assigned_to?.toLowerCase() || '').includes(searchTerm) ||
            (item.current_condition?.toLowerCase() || '').includes(searchTerm)
        );

        const renderToContainer = (container, data, message) => {
            container.innerHTML = data.length > 0
                ? data.map(item => createSepCard(item, selectedSepIds.has(item.sep_id))).join('')
                : `<div class="col-12"><div class="alert alert-secondary text-center">${message}</div></div>`;
        };

        // Unassigned
        const unassigned = filteredData.filter(item => !item.assigned_to && item.current_condition === 'Serviceable');
        renderToContainer(unassignedCardContainer, unassigned, 'No unassigned items found.');

        // Assigned
        const assigned = filteredData.filter(item => !!item.assigned_to && item.current_condition !== 'Disposed');
        renderToContainer(assignedCardContainer, assigned, 'No assigned items found.');

        // Unserviceable
        const unserviceable = filteredData.filter(item => item.current_condition === 'Unserviceable');
        renderToContainer(unserviceableCardContainer, unserviceable, 'No unserviceable items found.');

        // For Repair
        const forRepair = filteredData.filter(item => item.current_condition === 'For Repair');
        renderToContainer(forRepairCardContainer, forRepair, 'No items for repair found.');

        // Disposed
        const disposed = filteredData.filter(item => item.current_condition === 'Disposed');
        renderToContainer(disposedCardContainer, disposed, 'No disposed items found.');
    }

    function loadSepTable() {
        if (dataTable) {
            dataTable.ajax.reload();
            return;
        }

        // Filter out 'Unserviceable' and 'Disposed' items from the main table view
        const tableData = allSepData.filter(item => 
            item.current_condition !== 'Unserviceable' && 
            item.current_condition !== 'Disposed'
        );

        dataTable = $('#sepListTable').DataTable({
            "processing": false, // Changed to false since we're using client-side data
            "data": tableData, // Use the pre-filtered data
            "columns": [
                {
                    "searchable": false,
                    "orderable": false,
                    "className": "text-center align-middle",
                    "render": (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1
                },
                { "data": "property_number" },
                { "data": "description" },
                { "data": "serial_number", "render": data => escapeHTML(data || 'N/A') },
                { "data": "date_acquired", "render": data => new Date(data).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) },
                { 
                    "data": "assigned_to",
                    "render": data => data 
                        ? `<span class="badge bg-success">${escapeHTML(data)}</span>` 
                        : `<span class="badge bg-warning text-dark">Unassigned</span>`
                },
                { 
                    "data": "sep_id", 
                    "orderable": false, 
                    "render": (data, type, row) => {
                        if (row.assigned_to) {
                            return `<button type="button" class="btn btn-sm btn-outline-danger unassign-btn" data-sep-id="${data}" title="Unassign Item"><i class="bi bi-person-dash-fill"></i> Unassign</button>`;
                        } else {
                            return `<button type="button" class="btn btn-sm btn-outline-primary assign-btn" data-sep-id="${data}" title="Assign Item"><i class="bi bi-person-check-fill"></i> Assign</button>`;
                        }
                    }
                }
            ],
            "order": [[4, 'desc']] // Default sort by date acquired
        });
    }

    async function unassignSepItem(sepId, voidIcs = false) {
        try {
            const response = await fetch('api/sep_unassign_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ sep_id: sepId, void_ics: voidIcs })
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.error || result.message || 'An unknown error occurred.');

            // Show a specific message if the ICS was not voided because it has other items.
            if (voidIcs && result.message && result.message.includes("still contains other assigned items")) {
                showToast(result.message, 'Info', 'info');
            } else {
                showToast('Item unassigned successfully.', 'Success', 'success');
            }

            loadSepData(); // Refresh card view
            if (dataTable) dataTable.ajax.reload(); // Refresh table view

        } catch (error) {
            showToast(`Error: ${error.message}`, 'Unassignment Failed', 'danger');
        }
    }

    // --- Photo Upload Functions ---
    function triggerPhotoUpload(sepId) {
        directPhotoUploadInput.dataset.sepId = sepId;
        directPhotoUploadInput.click();
    }

    async function handleDirectPhotoUpload(e) {
        const file = e.target.files[0];
        if (!file) return;

        // Validate file type and size
        const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!validTypes.includes(file.type)) {
            showToast('Please upload a valid image file (JPEG, PNG, GIF, or WEBP)', 'Error', 'danger');
            e.target.value = '';
            return;
        }

        const maxSize = 5 * 1024 * 1024; // 5MB
        if (file.size > maxSize) {
            showToast('Image file size must be less than 5MB', 'Error', 'danger');
            e.target.value = '';
            return;
        }

        const sepId = e.target.dataset.sepId;
        // Find ALL cards that represent this item across all tabs
        const cardImages = document.querySelectorAll(`.card[data-sep-id="${sepId}"] img`);

        const formData = new FormData();
        formData.append('sep_id', sepId);
        formData.append('sep_photo', file);

        // Apply opacity to all found images
        cardImages.forEach(img => img.style.opacity = '0.5');

        try {
            // Step 1: Send the request
            const response = await fetch('api/sep_change_photo_api.php', { 
                method: 'POST', 
                body: formData 
            });

            // Step 2: Parse the response
            let result;
            try {
                result = await response.json();
            } catch (parseError) {
                console.error('JSON Parse Error:', parseError);
                throw new Error('Server returned invalid response format');
            }

            // Step 3: Check for success
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to process photo');
            }

            // Step 4: Update UI on success
            if (result.new_photo) {
                // Update data cache
                const foundItem = allSepData.find(c => c.sep_id == sepId);
                if (foundItem) {
                    foundItem.photo = result.new_photo;
                }

                // Update all image instances
                const photoPath = `assets/uploads/sep/${result.new_photo}?t=${new Date().getTime()}`;
                cardImages.forEach(img => img.src = photoPath);

                showToast('Photo updated successfully', 'Success', 'success');
            }
        } catch (error) {
            console.error('Photo upload error:', error);
            showToast(error.message || 'Failed to upload photo', 'Error', 'danger');
        } finally {
            // Always reset the file input and restore image opacity
            e.target.value = '';
            cardImages.forEach(img => img.style.opacity = '1');
        }
            

    }

    // --- View Modal Function ---
    // Toggle edit mode in view modal
    function toggleEditMode(enterEdit) {
        const viewElements = document.querySelectorAll('.view-mode');
        const editElements = document.querySelectorAll('.edit-mode');
        const editBtn = document.getElementById('editSepDetailsBtn');
        const saveBtn = document.getElementById('saveSepDetailsBtn');
        const cancelBtn = document.getElementById('cancelSepEditBtn');

        if (enterEdit) {
            viewElements.forEach(el => el.classList.add('d-none'));
            editElements.forEach(el => {
                el.classList.remove('d-none');
                // Set input values from current display values
                const field = el.dataset.field;
                const viewEl = document.querySelector(`[data-template-id="${field}"]`);
                if (viewEl) {
                    if (field === 'unit_cost') {
                        el.value = parseFloat(viewEl.textContent.replace(/[^0-9.-]+/g, ""));
                    } else {
                        el.value = viewEl.textContent;
                    }
                }
            });
            editBtn.classList.add('d-none');
            saveBtn.classList.remove('d-none');
            cancelBtn.classList.remove('d-none');
        } else {
            viewElements.forEach(el => el.classList.remove('d-none'));
            editElements.forEach(el => el.classList.add('d-none'));
            editBtn.classList.remove('d-none');
            saveBtn.classList.add('d-none');
            cancelBtn.classList.add('d-none');
        }
    }

    // Save changes from edit mode
    async function saveChanges(sepId) {
        const data = {
            sep_id: sepId
        };

        // Collect values from all edit inputs
        document.querySelectorAll('.edit-mode').forEach(input => {
            if (input.dataset.field) {
                data[input.dataset.field] = input.value;
            }
        });

        try {
            const response = await fetch('api/sep_edit_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'Failed to save changes');

            showToast('Changes saved successfully', 'Success', 'success');
            
            // Update local data
            const idx = allSepData.findIndex(item => item.sep_id == sepId);
            if (idx !== -1) {
                allSepData[idx] = { ...allSepData[idx], ...data };
            }

            // Refresh the view
            showViewModal(sepId);
            
            // Refresh card/table views
            loadSepData();
            if (dataTable) dataTable.ajax.reload();

        } catch (error) {
            showToast(error.message, 'Error', 'danger');
        }
    }

    function showViewModal(sepId) {
        const contentArea = document.getElementById('view-sep-content');
        const viewTemplate = document.getElementById('sep-view-template').content;
        contentArea.innerHTML = `<div class="text-center p-5"><div class="spinner-border" role="status"></div></div>`;

        const modalEditBtn = document.getElementById('editSepDetailsBtn');
        const modalSaveBtn = document.getElementById('saveSepDetailsBtn');
        const modalCancelBtn = document.getElementById('cancelSepEditBtn');

        // Setup edit mode handlers if buttons exist
        if (modalEditBtn && modalSaveBtn && modalCancelBtn) {
            modalEditBtn.onclick = () => toggleEditMode(true);
            modalSaveBtn.onclick = () => saveChanges(sepId);
            modalCancelBtn.onclick = () => toggleEditMode(false);
        }

        // Store the sepId on the edit button within the view modal for later use
        const editButton = viewSepModal._element.querySelector('#edit-details-from-view-btn');
        if (editButton) {
            editButton.dataset.sepId = sepId;
        }

        // Find the item first
        const item = allSepData.find(c => c.sep_id == sepId);
        
        if (!item) {
            contentArea.innerHTML = `<div class="alert alert-danger">Item details not found.</div>`;
            return;
        }

        // Initialize document management
        if (typeof DocumentManager !== 'undefined') {
            DocumentManager.setCurrentItem(item);
        }
        
        viewSepModal.show();
        if (!item) {
            contentArea.innerHTML = `<div class="alert alert-danger">Item details not found.</div>`;
            return;
        }

        const viewContent = viewTemplate.cloneNode(true);

        const photoName = item.photo || 'sep_default.png';
        let photoPath;
        if (photoName.startsWith('sep_qr_')) {
            photoPath = `assets/uploads/qr_codes/${photoName}`;
        } else {
            photoPath = `assets/uploads/sep/${photoName}`;
        }
        viewContent.querySelector('img').src = photoPath;

        viewContent.querySelector('[data-template-id="description"]').textContent = item.description;
        viewContent.querySelector('[data-template-id="property_number"]').textContent = item.property_number;
        viewContent.querySelector('[data-template-id="serial_number"]').textContent = item.serial_number || 'N/A';
        viewContent.querySelector('[data-template-id="brand_name"]').textContent = item.brand_name || 'N/A';
        viewContent.querySelector('[data-template-id="unit_cost"]').textContent = parseFloat(item.unit_cost).toLocaleString('en-US', { style: 'currency', currency: 'PHP' });
        viewContent.querySelector('[data-template-id="date_acquired"]').textContent = new Date(item.date_acquired).toLocaleDateString();
        viewContent.querySelector('[data-template-id="estimated_useful_life"]').textContent = item.estimated_useful_life ? `${item.estimated_useful_life} years` : 'N/A';
        viewContent.querySelector('[data-template-id="current_condition"]').textContent = item.current_condition || 'N/A';
        viewContent.querySelector('[data-template-id="assigned_to"]').textContent = item.assigned_to || 'Unassigned';
    // --- Add assigned user photo if available ---
        const userPhotoContainer = viewContent.querySelector('[data-template-id="assigned_user_photo_container"]');
        if (item.assigned_to && userPhotoContainer) {
            const userPhotoPath = `assets/uploads/users/${item.assigned_to_photo || 'default.png'}`;
            const fallbackUserPhotoPath = `.../assets/uploads/users/default.png`;
            userPhotoContainer.innerHTML = `
                <div class="d-flex align-items-center mt-3">
                    <img src="${userPhotoPath}" 
                         class="rounded me-2" 
                         alt="User Photo" 
                         style="width: 40px; height: 40px; object-fit: cover;"
                         onerror="this.onerror=null;this.src='${fallbackUserPhotoPath}';">
                    <small class="text-muted">Assigned to ${escapeHTML(item.assigned_to)}</small>
                </div>`;
        }

        // Add inline Edit feature inside the modal
        const toolbar = document.createElement('div');
        toolbar.className = 'd-flex justify-content-end mb-2';
        const editBtn = document.createElement('button');
        editBtn.className = 'btn btn-sm btn-outline-primary me-2';
        editBtn.type = 'button';
        editBtn.textContent = 'Edit';
        const saveBtn = document.createElement('button');
        saveBtn.className = 'btn btn-sm btn-primary me-2 d-none';
        saveBtn.type = 'button';
        saveBtn.textContent = 'Save';
        const cancelBtn = document.createElement('button');
        cancelBtn.className = 'btn btn-sm btn-secondary d-none';
        cancelBtn.type = 'button';
        cancelBtn.textContent = 'Cancel';
        toolbar.appendChild(editBtn);
        toolbar.appendChild(saveBtn);
        toolbar.appendChild(cancelBtn);
        // Insert toolbar at top of view content
        viewContent.insertBefore(toolbar, viewContent.firstChild);

        // Fields that can be edited inline
        const editableFields = [
            'description','property_number','serial_number','brand_name','unit_cost','date_acquired','estimated_useful_life','current_condition'
        ];

    let originalValues = {};
    const originalInner = {};
    const originalLabels = {};
        function enterEditMode() {
            console.log('sep.js: enterEditMode called for sep_id=', item.sep_id);
            // store originals
            originalValues = {};
            editableFields.forEach(fid => {
                const node = viewContent.querySelector(`[data-template-id="${fid}"]`);
                if (!node) return;
                originalValues[fid] = node.textContent;
                const li = node.closest('li') || node.parentElement;
                const labelText = (li && li.childNodes && li.childNodes[0] && li.childNodes[0].textContent) ? li.childNodes[0].textContent.trim() : fid;
                originalInner[fid] = li ? li.innerHTML : '';
                originalLabels[fid] = labelText;
                // create input
                let input;
                if (fid === 'description') {
                    input = document.createElement('textarea');
                    input.className = 'form-control form-control-sm';
                    input.rows = 3;
                    input.value = (item.description || '');
                } else if (fid === 'date_acquired') {
                    input = document.createElement('input');
                    input.type = 'date';
                    input.className = 'form-control form-control-sm';
                    input.value = item.date_acquired || '';
                } else if (fid === 'current_condition') {
                    input = document.createElement('select');
                    input.className = 'form-select form-select-sm';
                    ['Serviceable','For Repair','Unserviceable','Disposed'].forEach(opt => {
                        const o = document.createElement('option'); o.value = opt; o.textContent = opt;
                        if ((item.current_condition || '') === opt) o.selected = true;
                        input.appendChild(o);
                    });
                } else if (fid === 'estimated_useful_life') {
                    input = document.createElement('input'); input.type = 'number'; input.className = 'form-control form-control-sm'; input.value = item.estimated_useful_life || '';
                } else if (fid === 'unit_cost') {
                    input = document.createElement('input'); input.type = 'number'; input.step = '0.01'; input.className = 'form-control form-control-sm'; input.value = item.unit_cost || '';
                } else {
                    input = document.createElement('input'); input.type = 'text'; input.className = 'form-control form-control-sm';
                    input.value = item[fid] || '';
                }
                // replace the whole li content with a labeled input for clarity
                if (li) {
                    const inputHtml = (function() {
                        if (fid === 'description') {
                            return `<textarea data-edit-field="${fid}" class="form-control form-control-sm" rows="3">${(item.description||'')}</textarea>`;
                        } else if (fid === 'date_acquired') {
                            return `<input data-edit-field="${fid}" type="date" class="form-control form-control-sm" value="${item.date_acquired||''}">`;
                        } else if (fid === 'current_condition') {
                            const opts = ['Serviceable','For Repair','Unserviceable','Disposed'];
                            return `<select data-edit-field="${fid}" class="form-select form-select-sm">${opts.map(o=>`<option value="${o}" ${((item.current_condition||'')===o)?'selected':''}>${o}</option>`).join('')}</select>`;
                        } else if (fid === 'estimated_useful_life') {
                            return `<input data-edit-field="${fid}" type="number" class="form-control form-control-sm" value="${item.estimated_useful_life||''}">`;
                        } else if (fid === 'unit_cost') {
                            return `<input data-edit-field="${fid}" type="number" step="0.01" class="form-control form-control-sm" value="${item.unit_cost||''}">`;
                        } else {
                            return `<input data-edit-field="${fid}" type="text" class="form-control form-control-sm" value="${item[fid]||''}">`;
                        }
                    })();
                    li.innerHTML = `<div class="d-flex justify-content-between align-items-center"><div class="text-muted small">${labelText}</div><div style="width:55%">${inputHtml}</div></div>`;
                } else {
                    node.innerHTML = '';
                    node.appendChild(input);
                }
            });
            editBtn.classList.add('d-none');
            saveBtn.classList.remove('d-none');
            cancelBtn.classList.remove('d-none');
            // focus the first input to show it's editable
            const firstInput = viewContent.querySelector('input, textarea, select');
            if (firstInput) {
                firstInput.focus();
            }
        }

        function exitEditMode(revert=false) {
            editableFields.forEach(fid => {
                const node = viewContent.querySelector(`[data-template-id="${fid}"]`);
                if (!node) return;
                if (revert) {
                    node.textContent = originalValues[fid] ?? '';
                } else {
                    // keep current input value
                    const input = node.querySelector('input, textarea, select');
                    if (input) {
                        if (fid === 'unit_cost') node.textContent = parseFloat(input.value || 0).toLocaleString('en-US', { style: 'currency', currency: 'PHP' });
                        else if (fid === 'date_acquired') node.textContent = input.value ? new Date(input.value).toLocaleDateString() : '';
                        else node.textContent = input.value;
                    }
                }
            });
            editBtn.classList.remove('d-none');
            saveBtn.classList.add('d-none');
            cancelBtn.classList.add('d-none');
        }

    async function saveEdits() {
            // prepare payload
            const payload = { sep_id: item.sep_id };
            editableFields.forEach(fid => {
                // prefer the edit-field input
                const input = viewContent.querySelector(`[data-edit-field="${fid}"]`);
                if (input) { payload[fid] = input.value; return; }
                const node = viewContent.querySelector(`[data-template-id="${fid}"]`);
                if (!node) return;
                payload[fid] = node.textContent;
            });

            // send to API
            saveBtn.disabled = true; saveBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Saving`;
            try {
                const resp = await fetch('api/sep_edit_api.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                const res = await resp.json();
                if (!resp.ok) throw new Error(res.message || 'Failed to save');
                showToast(res.message || 'Updated', 'Success', 'success');
                // update local cache
                const idx = allSepData.findIndex(s => s.sep_id == item.sep_id);
                if (idx !== -1) allSepData[idx] = Object.assign({}, allSepData[idx], payload);
                // update item reference
                Object.assign(item, payload);
                // Reload the modal content so the template is re-rendered from the updated data
                // This avoids DOM mismatches between replaced <li> innerHTML and the original template spans
                showViewModal(item.sep_id);
            } catch (err) {
                showToast(err.message, 'Save Failed', 'danger');
            } finally {
                saveBtn.disabled = false; saveBtn.innerHTML = 'Save';
            }
        }

        // Cancel should simply reload the original view content for this item
        cancelBtn.addEventListener('click', () => {
            showViewModal(item.sep_id);
        });
        saveBtn.addEventListener('click', saveEdits);
        editBtn.addEventListener('click', enterEditMode);

        // Header edit button intentionally removed to avoid duplicate Edit controls

        contentArea.innerHTML = '';
        contentArea.appendChild(viewContent);
    }

    // --- Edit Modal Functions ---
    function showEditModal(sepId) {
        const item = allSepData.find(c => c.sep_id == sepId);
        if (!item) {
            showToast('Could not find item details to edit.', 'Error', 'danger');
            return;
        }

        // Close the view modal before showing the edit modal
        viewSepModal.hide();

        // Populate the edit form
        document.getElementById('edit_sep_id').value = item.sep_id;
        document.getElementById('edit_sep_description').value = item.description;
        document.getElementById('edit_sep_property_number').value = item.property_number;
        document.getElementById('edit_sep_brand_name').value = item.brand_name || '';
        document.getElementById('edit_sep_serial_number').value = item.serial_number || '';
        document.getElementById('edit_sep_useful_life').value = item.estimated_useful_life || '';
        document.getElementById('edit_sep_date_acquired').value = item.date_acquired;
        document.getElementById('edit_sep_condition').value = item.current_condition || 'Serviceable';
        editSepModal.show();
    }

    /**
     * Handles the QR code generation request and UI update for SEP.
     * @param {object} item The SEP item object from sepData.
     * @param {HTMLElement} cardElement The card element that was clicked.
     */
    async function generateAndSetQrCode(item, cardElement) {
        const button = cardElement.querySelector('.qr-code-btn');
        const cardImg = cardElement.querySelector('.card-img-top');

        if (!item || !item.property_number) {
            showToast('Item property number is missing.', 'Error', 'danger');
            return;
        }

        button.disabled = true;
        button.innerHTML = `<span class="spinner-border spinner-border-sm"></span>`;
        cardImg.style.opacity = '0.5';

        try {
            const response = await fetch('api/sep_generate_qr_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ sep_id: item.sep_id, property_number: item.property_number })
            });
            const result = await response.json();
            if (!result.success) throw new Error(result.message);

            showToast(result.message, 'Success', 'success');
            if (result.new_photo_path) {
                const newPhotoSrc = `assets/uploads/${result.new_photo_path}?t=${new Date().getTime()}`;
                cardImg.src = newPhotoSrc;
                item.photo = String(result.new_photo_path).replace('qr_codes/', ''); // Update cache
            }
        } catch (error) {
            showToast(`Error: ${error.message}`, 'QR Code Generation Failed', 'danger');
        } finally {
            button.disabled = false;
            button.innerHTML = `<i class="bi bi-qr-code"></i>`;
            cardImg.style.opacity = '1';
        }
    }

    async function changeItemStatus(sepId, newStatus) {
        try {
            // Get the current item to include required fields
            const item = allSepData.find(i => i.sep_id == sepId);
            if (!item) throw new Error('Item not found');

            const response = await fetch('api/sep_edit_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    sep_id: sepId,
                    current_condition: newStatus,
                    date_acquired: item.date_acquired, // Required field
                    brand_name: item.brand_name || '',
                    serial_number: item.serial_number || '',
                    estimated_useful_life: item.estimated_useful_life || ''
                })
            });

            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'Failed to change status');

            showToast(`Item status changed to ${newStatus}`, 'Success', 'success');
            loadSepData(); // Refresh the view
            if (dataTable) dataTable.ajax.reload();

        } catch (error) {
            showToast(error.message, 'Error', 'danger');
        }
    }

    // --- Event Handlers ---
    function handleCardClick(e) {
        // Broaden the selector to catch clicks on ANY card, including assigned ones.
        // The logic inside will differentiate between selectable and non-selectable actions.
        const card = e.target.closest('.card[data-sep-id]');
        if (!card) return;

        const changePhotoBtn = e.target.closest('.change-photo-btn');
        const viewDetailsBtn = e.target.closest('.view-details-btn');
        const viewIcsBtn = e.target.closest('.view-ics-pdf-btn');
        const unassignCardBtn = e.target.closest('.unassign-card-btn');
        const assignCardBtn = e.target.closest('.assign-card-btn');
        const disposeBtn = e.target.closest('.dispose-btn');
        const qrCodeBtn = e.target.closest('.qr-code-btn');
        const changeStatusBtn = e.target.closest('.change-status-btn');
        const sepId = card.dataset.sepId;

        if (changePhotoBtn) {
            e.stopPropagation();
            triggerPhotoUpload(sepId);
        } else if (unassignCardBtn) {
            e.stopPropagation();
            const item = allSepData.find(c => c.sep_id == sepId);
            const hasIcs = !!item?.ics_id;

            if (confirm('Are you sure you want to unassign this item?')) {
                let voidIcs = false;
                if (hasIcs && confirm('This item is part of an ICS. Do you want to VOID the related ICS document as well?')) {
                    voidIcs = true;
                }
                // Use the existing unassign function
                unassignSepItem(sepId, voidIcs);
            }
        } else if (viewDetailsBtn) {
            e.stopPropagation();
            showViewModal(sepId);
        } else if (viewIcsBtn) {
            const icsId = card.dataset.icsId;
            e.stopPropagation();
            if (icsId) showIcsModal(icsId);
        } else if (assignCardBtn) {
            e.stopPropagation();
            showAssignModal([sepId]);
        } else if (disposeBtn) {
            e.stopPropagation();
            if (confirm('Are you sure you want to dispose of this item? This action cannot be undone.')) {
                disposeItem(sepId);
            }
        } else if (qrCodeBtn) {
            e.stopPropagation();
            const item = allSepData.find(s => s.sep_id == sepId);
            if (item) {
                generateAndSetQrCode(item, card);
            }
        } else if (changeStatusBtn) {
            e.stopPropagation();
            const item = allSepData.find(s => s.sep_id == sepId);
            if (item) {
                // Remove any existing status menus
                const existingMenu = document.getElementById('statusDropdownMenu');
                if (existingMenu) {
                    existingMenu.remove();
                }

                // Create a dropdown menu for status selection
                const statusMenu = document.createElement('div');
                statusMenu.id = 'statusDropdownMenu';
                statusMenu.className = 'dropdown-menu show';
                statusMenu.style.position = 'absolute';
                statusMenu.style.transform = 'translate3d(0px, 40px, 0px)';
                statusMenu.style.zIndex = '1050';
                
                const statuses = ['Serviceable', 'For Repair', 'Unserviceable'];
                statuses.forEach(status => {
                    const menuItem = document.createElement('a');
                    menuItem.className = `dropdown-item ${item.current_condition === status ? 'active' : ''}`;
                    menuItem.href = '#';
                    menuItem.textContent = status;
                    menuItem.onclick = (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        if (status !== item.current_condition && 
                            confirm(`Are you sure you want to change the status to ${status}?`)) {
                            changeItemStatus(sepId, status);
                        }
                        statusMenu.remove();
                    };
                    statusMenu.appendChild(menuItem);
                });

                // Position the menu near the button
                const buttonRect = changeStatusBtn.getBoundingClientRect();
                statusMenu.style.top = (buttonRect.bottom + window.scrollY) + 'px';
                statusMenu.style.left = buttonRect.left + 'px';
                
                // Add click outside listener to close menu
                const closeMenu = (e) => {
                    const menu = document.getElementById('statusDropdownMenu');
                    if (menu && !menu.contains(e.target) && !changeStatusBtn.contains(e.target)) {
                        menu.remove();
                        document.removeEventListener('click', closeMenu);
                    }
                };
                
                // Delay adding the click listener to prevent immediate closure
                setTimeout(() => {
                    document.addEventListener('click', closeMenu);
                }, 0);
                
                document.body.appendChild(statusMenu);
            }
        } else if (!card.classList.contains('card-assigned')) {
            // Handle card selection only if not clicking a button and card is assignable
            const sepId = card.dataset.sepId;
            card.classList.toggle('selected');
            if (card.classList.contains('selected')) {
                selectedSepIds.add(sepId);
            } else {
                selectedSepIds.delete(sepId);
            }
            // Update the button based on the size of the Set
            assignSelectedBtn.disabled = selectedSepIds.size === 0;
        }
    }



    async function disposeItem(sepId) {
        try {
            const response = await fetch('api/sep_dispose_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ sep_id: sepId })
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'An unknown error occurred.');

            showToast(result.message, 'Success', 'success');
            loadSepData(); // Refresh data to remove the card from the view

        } catch (error) {
            showToast(`Error: ${error.message}`, 'Disposal Failed', 'danger');
        }
    }

    // --- View Switching ---
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
            if (!dataTable) loadSepTable();
        });
    }

    // --- Modal & Form Logic ---
    if (assignSelectedBtn) {
        assignSelectedBtn.addEventListener('click', () => {
            // Use the Set for IDs
            const idsToAssign = Array.from(selectedSepIds);
            showAssignModal(idsToAssign);
        });
    }

    function showAssignModal(sepIds) {
        if (sepIds.length === 0) return;

        const itemsContainer = document.getElementById('assign-items-container');
        itemsContainer.innerHTML = '';
        sepIds.forEach(id => {
            const item = allSepData.find(d => d.sep_id == id && !d.assigned_to);
            if(item) {
                const itemEl = document.createElement('li');
                itemEl.className = 'list-group-item';
                itemEl.textContent = `${item.description} (PN: ${item.property_number})`;
                itemEl.dataset.sepId = id;
                itemsContainer.appendChild(itemEl);
            }
        });
        assignModal.show();
    }

    // --- Modal & Form Logic (Updated for ICS Generation) ---
    const assignSepFormEl = document.getElementById('assignSepForm');
    if (assignSepFormEl) {
        assignSepFormEl.addEventListener('submit', async function(e) {
        e.preventDefault();
        const form = e.target;
        const submitButton = form.querySelector('button[type="submit"]');
        const itemsToAssign = Array.from(document.getElementById('assign-items-container').children).map(li => li.dataset.sepId);
        const userName = form.querySelector('#assign_to_user_id').value;

        submitButton.disabled = true;
        submitButton.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Assigning...`;

        try {
            // The API expects a name. The backend will handle resolving it to an ID or creating a new user.
            const response = await fetch('api/sep_assign_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    sep_ids: itemsToAssign,
                    user_id: userName, // Sending the name from the input field
                    location: form.querySelector('#location').value,
                    generate_ics: form.querySelector('#generate_ics').checked
                })
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.error || 'An unknown error occurred.');

            showToast(result.message, 'Success', 'success');

            // If an ICS was created, offer to print it
            if (result.ics_id) {
                if (confirm('Assignment successful. An Inventory Custodian Slip (ICS) was generated. Do you want to view and print it now?')) {
                    // The showIcsModal function is now globally available from ics_list.js
                    showIcsModal(result.ics_id); 
                }
            }

            assignModal.hide();
            loadSepData();
            if (dataTable) dataTable.ajax.reload(); // Refresh table view

            // Clear selection
            selectedSepIds.clear();
            assignSelectedBtn.disabled = true;

        } catch (error) {
            showToast(`Error: ${error.message}`, 'Assignment Failed', 'danger');
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = `<i class="bi bi-check-circle"></i> Confirm Assignment`;
        }
        });
    }

    // Show/hide ICS preview based on checkbox
    if (generateIcsCheckbox) {
        generateIcsCheckbox.addEventListener('change', function() {
            document.getElementById('ics-options-container').style.display = this.checked ? 'block' : 'none';
        });
    }

    // Fetch and display the next ICS number when the modal is shown
    const assignSepModalEl = document.getElementById('assignSepModal');
    if (assignSepModalEl) {
        assignSepModalEl.addEventListener('show.bs.modal', async function() {
        try {
            const response = await fetch('api/get_next_ics_number.php');
            const result = await response.json();
            if (result.success) {
                document.getElementById('ics_number_preview').value = result.preview_number;
            } else {
                document.getElementById('ics_number_preview').value = 'Error loading number';
            }
        } catch (error) {
            document.getElementById('ics_number_preview').value = 'Error loading number';
        }
        });
    }

    // Handle click on "Edit Details" button from the view modal
    const editDetailsFromViewBtn = document.getElementById('edit-details-from-view-btn');
    if (editDetailsFromViewBtn) {
        editDetailsFromViewBtn.addEventListener('click', function() {
            const sepId = this.dataset.sepId; // Read the ID we stored earlier
            if (sepId) { 
                showEditModal(sepId);
            }
        });
    }

    // Handle condition change in edit form
    const editSepConditionSelect = document.getElementById('edit_sep_condition');
    if (editSepConditionSelect) {
        editSepConditionSelect.addEventListener('change', function(e) {
            const newCondition = e.target.value;
            const messages = {
                'For Repair': 'This will move the item to the For Repair tab and make it unavailable for assignment.',
                'Unserviceable': 'This will mark the item as Unserviceable and move it to the Unserviceable tab.',
                'Disposed': 'This will permanently mark the item as Disposed. This action cannot be undone easily.',
            };
            
            if (messages[newCondition] && !confirm(`Are you sure you want to change the status to ${newCondition}?\n\n${messages[newCondition]}`)) {
                // If user cancels, revert to previous value
                e.target.value = e.target.dataset.lastValue || 'Serviceable';
                return;
            }
            // Store the new value as last value
            e.target.dataset.lastValue = newCondition;
        });
    }

    // Handle the submission of the edit form
    const editSepFormEl = document.getElementById('editSepForm');
    if (editSepFormEl) {
        editSepFormEl.addEventListener('submit', async function(e) {
        e.preventDefault();
        const form = e.target;
        const submitButton = form.querySelector('button[type="submit"]');
        const formData = new FormData(form);
        const submissionData = Object.fromEntries(formData.entries());

        submitButton.disabled = true;
        submitButton.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Saving...`;

        try {
            const response = await fetch('api/sep_edit_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(submissionData)
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'An unknown error occurred.');

            // Show appropriate success message based on condition change
            const newCondition = submissionData.current_condition;
            let successMessage = result.message;
            if (newCondition === 'For Repair') {
                successMessage = 'Item has been marked for repair and moved to the For Repair tab.';
            } else if (newCondition === 'Unserviceable') {
                successMessage = 'Item has been marked as unserviceable and moved to the Unserviceable tab.';
            } else if (newCondition === 'Disposed') {
                successMessage = 'Item has been marked as disposed and moved to the Disposed tab.';
            }
            
            showToast(successMessage, 'Success', 'success');
            editSepModal.hide();
            loadSepData(); // Reload all data to reflect changes
            if (dataTable) dataTable.ajax.reload();
        } catch (error) {
            showToast(`Error: ${error.message}`, 'Update Failed', 'danger');
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = `<i class="bi bi-save"></i> Save Changes`;
        }
        });
    }

    // --- Table Event Handlers ---
    $('#sepListTable').on('click', '.assign-btn', function() {
        const sepId = $(this).data('sep-id');
        const item = allSepData.find(d => d.sep_id == sepId && !d.assigned_to);
        if (item) {
            const itemsContainer = document.getElementById('assign-items-container');
            itemsContainer.innerHTML = '';
            const itemEl = document.createElement('li');
            itemEl.className = 'list-group-item';
            itemEl.textContent = `${item.description} (PN: ${item.property_number})`;
            itemEl.dataset.sepId = sepId;
            itemsContainer.appendChild(itemEl);
            assignModal.show();
        }
    });

    $('#sepListTable').on('click', '.unassign-btn', async function() {
        const sepId = $(this).data('sep-id');
        const rowData = dataTable.row($(this).closest('tr')).data();
        const hasIcs = !!rowData.ics_id;

        if (confirm('Are you sure you want to unassign this item?')) {
            let voidIcs = false;
            if (hasIcs && confirm('This item is part of an ICS. Do you want to VOID the related ICS document as well?')) {
                voidIcs = true;
            }
            await unassignSepItem(sepId, voidIcs);
        }
    });

    // --- Initializations ---
    const assignmentTabsContentEl = document.getElementById('assignment-status-tabs-content');
    if (assignmentTabsContentEl) assignmentTabsContentEl.addEventListener('click', handleCardClick);
    if (searchInput) searchInput.addEventListener('input', filterAndRenderCards);
    if (directPhotoUploadInput) directPhotoUploadInput.addEventListener('change', handleDirectPhotoUpload);
    loadSepData();
});