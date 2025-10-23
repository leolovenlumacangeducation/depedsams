/**
 * ppe.cards.js
 * Contains the rendering logic for creating PPE asset cards.
 */

/**
 * Creates the HTML for a single PPE asset card.
 * @param {object} item - The PPE item data from the API.
 * @param {boolean} isSelected - Whether the card should be rendered as selected.
 * @returns {string} The HTML string for the card.
 */
function createPpeCard(item, isSelected = false) {
    // Determine photo path, checking for QR codes
    let photoPath;
    const photoName = item.photo ? String(item.photo) : '';
    if (photoName.startsWith('ppe_qr_') || photoName.startsWith('sep_qr_')) {
        photoPath = `assets/uploads/qr_codes/${photoName}`;
    } else {
        photoPath = `assets/uploads/ppe/${photoName || 'ppe_default.png'}`;
    }
    const fallbackPhotoPath = `assets/uploads/ppe/ppe_default.png`;
    const isAssigned = !!item.assigned_to;
    const hasBeenAssigned = !!item.has_been_assigned; // Equivalent for PPE
    const hasPar = !!item.par_id; // PPE uses PAR, not ICS

    const cardClass = isAssigned ? 'card-assigned' : `selectable-card ${isSelected ? 'selected' : ''}`
    // --- Status Tags & Buttons ---
    let conditionTag = '';
    if (item.current_condition === 'Serviceable') {
        conditionTag = `<span class="badge bg-success-subtle text-success-emphasis"><i class="bi bi-check-circle me-1"></i>Serviceable</span>`;
    } else if (item.current_condition === 'For Repair') {
        conditionTag = `<span class="badge bg-warning-subtle text-warning-emphasis"><i class="bi bi-tools me-1"></i>For Repair</span>`;
    } else if (item.current_condition === 'Unserviceable') {
        conditionTag = `<span class="badge bg-danger-subtle text-danger-emphasis"><i class="bi bi-x-octagon me-1"></i>Unserviceable</span>`;
    }

    let assignmentTag = '';
    if (isAssigned) {
        const userPhotoPath = `assets/uploads/users/${item.assigned_to_photo || 'default_user.png'}`;
        assignmentTag = `
            <div class="d-flex align-items-center" title="Assigned to ${escapeHTML(item.assigned_to)}">
                <img src="${userPhotoPath}" 
                     class="rounded-circle me-2" 
                     alt="${escapeHTML(item.assigned_to)}" 
                     style="width: 40px; height: 40px; object-fit: cover;"
                     onerror="this.onerror=null;this.src='assets/uploads/users/default_user.png';">
            </div>`;
    } else {
        assignmentTag = `<span class="badge bg-secondary-subtle text-secondary-emphasis"><i class="bi bi-person-fill-x me-1"></i>Unassigned</span>`;
    }

    const disposeButton = (!isAssigned && item.current_condition === 'Unserviceable')
        ? `<button class="btn btn-sm btn-light text-danger dispose-btn position-absolute top-0 start-0 m-2" style="z-index: 10;" title="Dispose Item"><i class="bi bi-trash3-fill"></i></button>`
        : '';

    const assignmentActionButton = isAssigned
        ? `<button class="btn btn-sm btn-light text-danger unassign-card-btn" title="Unassign Item"><i class="bi bi-person-dash"></i></button>`
        : `<button class="btn btn-sm btn-light assign-card-btn" title="Assign Item"><i class="bi bi-person-plus-fill"></i></button>`;

    return `
        <div class="col">
            <div class="card h-100 ${cardClass}" data-ppe-id="${escapeHTML(item.ppe_id)}">
                <div class="position-relative">
                    <div class="card-icon-actions d-flex flex-column">
                        <button class="btn btn-sm btn-light change-photo-btn" title="Change Photo"><i class="bi bi-image"></i></button>
                        <button class="btn btn-sm btn-light view-details-btn" title="View Details"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-sm btn-light view-property-card-pdf-btn" title="View Property Card"><i class="bi bi-file-earmark-text"></i></button>
                        ${assignmentActionButton}
                    </div>
                    <img src="${escapeHTML(photoPath)}" class="card-img-top bg-light" alt="${escapeHTML(item.description)}" style="height: 216px; object-fit: contain;" onerror="this.onerror=null;this.src='${escapeHTML(fallbackPhotoPath)}';">
                    <div class="position-absolute bottom-0 start-0 m-2 d-flex flex-wrap gap-1">
                        ${conditionTag}
                    </div>
                    <div class="position-absolute top-0 end-0 m-2">
                        <button class="btn btn-sm btn-light qr-code-btn" title="Generate QR Code" style="border-radius: 50%; padding: 0.25rem 0.5rem;"><i class="bi bi-qr-code"></i></button>
                    </div>
                    ${disposeButton}
                </div>
                <div class="card-body d-flex flex-column">
                    <h6 class="card-title flex-grow-1">${escapeHTML(item.description)}</h6>
                    <p class="card-text text-muted small mb-1">SN: ${escapeHTML(item.serial_number || 'N/A')}</p>
                    <div class="mt-auto d-flex justify-content-between align-items-center">
                        <span class="badge bg-dark text-white">${escapeHTML(item.property_number)}</span>
                        ${assignmentTag}
                    </div>
                </div>
            </div>
        </div>
    `;
}