/**
 * consumables.cards.js
 * 
 * Contains logic related to the Card View for consumables, including
 * card creation and photo upload functionality.
 */

// Function to create a single item card
function createCard(item, isSelected = false) {
    const stockLeft = parseInt(item.current_stock, 10);

    // Determine the correct photo path. QR codes are stored in a different folder.
    let photoPath;
    if (item.photo && item.photo.startsWith('consumable_qr_')) {
        photoPath = `assets/uploads/qr_codes/${item.photo}`;
    } else {
        photoPath = `assets/uploads/consumables/${item.photo || 'consumable_default.png'}`;
    }

    const fallbackPhotoPath = `assets/uploads/consumables/consumable_default.png`;
    const unitCost = parseFloat(item.unit_cost).toLocaleString('en-US', { style: 'currency', currency: 'PHP' });
    const isOutOfStock = stockLeft <= 0;
    const cardClass = isOutOfStock ? 'card-small card-out-of-stock' : `selectable-card ${isSelected ? 'selected' : ''}`;

    // Disable the convert button if the item is a result of a conversion OR has been used as a source for a conversion.
    const convertButtonDisabled = item.parent_consumable_id || item.is_conversion_source ? 'disabled' : '';
    
    let conversionTag = '';
    if (item.parent_consumable_id) {
        // This item is the result of a conversion
        conversionTag = `<span class="badge bg-info-subtle text-info-emphasis position-absolute bottom-0 end-0 m-2" title="This item was created from a unit conversion."><i class="bi bi-arrow-repeat"></i> Converted</span>`;
    } else if (item.is_conversion_source) {
        // This item was the source of a conversion
        conversionTag = `<span class="badge bg-secondary-subtle text-secondary-emphasis position-absolute bottom-0 end-0 m-2" title="This item has been used for unit conversion."><i class="bi bi-arrow-repeat"></i> Converted</span>`;
    }

    return `
        <div class="col">
            <div class="card h-100 shadow-sm ${cardClass}" data-consumable-id="${escapeHTML(item.consumable_id)}">
                <div class="position-relative">
                    <div class="card-icon-actions d-flex flex-column">
                        <button class="btn btn-sm btn-light" title="Change Photo"><i class="bi bi-image"></i></button>
                        <button class="btn btn-sm btn-light" title="View Stock Card"><i class="bi bi-journal-text"></i></button>                        
                        <button class="btn btn-sm btn-light" title="Convert Unit" ${convertButtonDisabled}><i class="bi bi-arrow-repeat"></i></button>
                        <button class="btn btn-sm btn-light" title="View Details"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-sm btn-light" title="PDF Stock Card"><i class="bi bi-file-earmark-pdf"></i></button>
                    </div>
                    <img src="${escapeHTML(photoPath)}" class="card-img-top bg-light" alt="${escapeHTML(item.description)}" style="height: 180px; object-fit: contain;" onerror="this.onerror=null;this.src='${escapeHTML(fallbackPhotoPath)}';">
                    ${conversionTag}
                    <div class="position-absolute top-0 end-0 m-2 d-flex flex-column align-items-center gap-2">
                        <span class="stock-badge" data-bs-toggle="tooltip" data-bs-placement="top" title="${escapeHTML(stockLeft)} ${escapeHTML(item.unit_name)}${stockLeft !== 1 ? 's' : ''} left in stock">${escapeHTML(stockLeft)}</span>
                        <button class="btn btn-sm btn-light qr-code-btn" title="Show QR Code" style="border-radius: 50%; padding: 0.25rem 0.5rem;"><i class="bi bi-qr-code"></i></button>
                    </div>
                </div>
                <div class="card-body d-flex flex-column">
                    <h6 class="card-title flex-grow-1">${escapeHTML(item.description)}</h6>
                    <p class="card-text text-muted small mb-2">${escapeHTML(unitCost)} per ${escapeHTML(item.unit_name)}</p>
                    <div class="mt-auto">
                        <span class="badge bg-dark text-white">${escapeHTML(item.stock_number || 'N/A')}</span>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// --- Direct Upload Functions ---
function triggerPhotoUpload(consumableId) {
    const directPhotoUploadInput = document.getElementById('direct-photo-upload');
    directPhotoUploadInput.dataset.consumableId = consumableId;
    directPhotoUploadInput.click();
}

async function handleDirectPhotoUpload(e, consumablesData, cardContainer) {
    const file = e.target.files[0];
    if (!file) return;

    const consumableId = e.target.dataset.consumableId;
    const cardImg = cardContainer.querySelector(`.card[data-consumable-id="${consumableId}"] img`);

    const formData = new FormData();
    formData.append('consumable_id', consumableId);
    formData.append('consumable_photo', file);

    if (cardImg) cardImg.style.opacity = '0.5';

    try {
        const response = await fetch('api/consumable_change_photo_api.php', { method: 'POST', body: formData });
        const result = await response.json();
        if (!response.ok) throw new Error(result.message || 'An unknown error occurred.');

        showToast(result.message, 'Success', 'success');

        const itemInCache = consumablesData.find(c => c.consumable_id == consumableId);
        if (itemInCache) itemInCache.photo = result.new_photo;
        if (cardImg) cardImg.src = `assets/uploads/consumables/${result.new_photo}?t=${new Date().getTime()}`;

    } catch (error) {
        showToast(`Error: ${error.message}`, 'Upload Failed', 'danger');
    } finally {
        if (cardImg) cardImg.style.opacity = '1';
        e.target.value = '';
    }
}