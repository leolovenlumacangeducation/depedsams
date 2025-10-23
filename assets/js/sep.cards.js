/**
 * sep.cards.js
 * Contains logic for creating SEP cards.
 */

function createSepCard(item) {
    // Determine photo path, checking for QR codes
    let photoPath;
    const photoName = item.photo ? String(item.photo) : '';
    if (photoName.startsWith('sep_qr_') || photoName.startsWith('ppe_qr_')) { // Check both for safety
        photoPath = `assets/uploads/qr_codes/${photoName}`;
    } else {
        photoPath = `assets/uploads/sep/${photoName || 'sep_default.png'}`;
    }
    const fallbackPhotoPath = `assets/uploads/sep/sep_default.png`;
    const isAssigned = !!item.assigned_to;
    const hasBeenAssigned = item.has_been_assigned == '1';
    const hasIcs = !!item.ics_id;

    // --- Status Tags ---

    // Age Tag
    let ageTag = '';
    if (!hasBeenAssigned) {
        ageTag = `<span class="badge bg-info-subtle text-info-emphasis"><i class="bi bi-stars me-1"></i>Brand New</span>`;
    } else if (item.first_assignment_date) {
        const dateAssigned = new Date(item.first_assignment_date);
        const today = new Date();
        dateAssigned.setHours(0, 0, 0, 0);
        today.setHours(0, 0, 0, 0);
        const diffTime = Math.abs(today - dateAssigned);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        const dayText = diffDays === 1 ? 'day' : 'days';
        ageTag = `<span class="badge bg-secondary-subtle text-secondary-emphasis" title="First assigned on ${dateAssigned.toLocaleDateString()}"><i class="bi bi-clock-history me-1"></i>${diffDays} ${dayText} old</span>`;
    } else {
        // Fallback for items that have been assigned but have no ICS record (e.g. legacy data)
        ageTag = `<span class="badge bg-secondary-subtle text-secondary-emphasis"><i class="bi bi-patch-check-fill me-1"></i>Used</span>`;
    }

    // Condition Tag
    let conditionTag = '';
    if (item.current_condition === 'Serviceable') {
        conditionTag = `<span class="badge bg-success-subtle text-success-emphasis"><i class="bi bi-check-circle me-1"></i>Serviceable</span>`;
    } else if (item.current_condition === 'For Repair') {
        conditionTag = `<span class="badge bg-warning-subtle text-warning-emphasis"><i class="bi bi-tools me-1"></i>For Repair</span>`;
    } else if (item.current_condition === 'Unserviceable') {
        conditionTag = `<span class="badge bg-danger-subtle text-danger-emphasis"></i>Unserviceable</span>`;
    }

    // Assignment Tag
    let assignmentTag = '';
    if (isAssigned) {
        const userPhotoPath = `assets/uploads/users/${item.assigned_to_photo || 'default_user.png'}`;
        const fallbackUserPhotoPath = `assets/uploads/users/default_user.png`;
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

    const icsButton = (isAssigned && hasIcs)
        ? `<button class="btn btn-sm btn-light view-ics-pdf-btn" title="View ICS Document"><i class="bi bi-file-earmark-pdf"></i></button>`
        : '';
    
    const disposeButton = (!isAssigned && item.current_condition === 'Unserviceable')
        ? `<button class="btn btn-sm btn-light text-danger dispose-btn position-absolute top-0 end-0 m-2" style="z-index: 10;" title="Dispose Item"><i class="bi bi-trash3-fill"></i></button>`
        : '';

    const assignmentActionButton = isAssigned
        ? `<button class="btn btn-sm btn-light text-danger unassign-card-btn" title="Unassign Item"><i class="bi bi-person-dash"></i></button>`
        : `<button class="btn btn-sm btn-light assign-card-btn" title="Assign Item"><i class="bi bi-person-plus-fill"></i></button>`;

    return `
        <div class="col">
            <div class="card h-100 shadow-sm selectable-card ${isAssigned ? 'card-assigned' : ''}" data-sep-id="${escapeHTML(item.sep_id)}" data-ics-id="${escapeHTML(item.ics_id || '')}">
                <div class="position-relative">
                    <div class="card-icon-actions d-flex flex-column">
                        <button class="btn btn-sm btn-light change-photo-btn" title="Change Photo"><i class="bi bi-image"></i></button>
                        <button class="btn btn-sm btn-light view-details-btn" title="View Details"><i class="bi bi-eye"></i></button>
                        ${icsButton}
                        ${assignmentActionButton}
                    </div>
                    <img src="${escapeHTML(photoPath)}" class="card-img-top bg-light" alt="${escapeHTML(item.description)}" style="height: 216px; object-fit: contain;" onerror="this.onerror=null;this.src='${escapeHTML(fallbackPhotoPath)}';">
                    <div class="position-absolute bottom-0 start-0 m-2 d-flex flex-wrap gap-1">
                        ${ageTag} ${conditionTag}
                    </div>
                    <div class="position-absolute top-0 end-0 m-2 d-flex flex-column gap-2">
                        <button class="btn btn-sm btn-light qr-code-btn" title="Show QR Code" style="border-radius: 50%; padding: 0.25rem 0.5rem;"><i class="bi bi-qr-code"></i></button>
                        <button class="btn btn-sm btn-light change-status-btn" title="Change Status" style="border-radius: 50%; padding: 0.25rem 0.5rem;"><i class="bi bi-arrow-repeat"></i></button>
                    </div>
                    ${disposeButton}
                </div>
                <div class="card-body d-flex flex-column">
                    <h6 class="card-title flex-grow-1">${escapeHTML(item.description)}</h6>
                    <p class="card-text text-muted small mb-1">SN: ${escapeHTML(item.serial_number || 'N/A')}</p>
                    <!-- Bottom row for Property Number and Assignment Status -->
                    <div class="mt-auto d-flex justify-content-between align-items-center">
                        <span class="badge bg-dark text-white">${escapeHTML(item.property_number)}</span>
                        ${assignmentTag}
                    </div>
                </div>
            </div>
        </div>
    `;
}