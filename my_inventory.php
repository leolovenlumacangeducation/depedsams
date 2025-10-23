<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

// Include the view modals
require_once 'includes/ppe_view_modal.php';
require_once 'includes/sep_view_modal.php';
require_once 'includes/consumable_view_modal.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$current_user_id = (int) $_SESSION['user_id'];
$current_user_name = htmlspecialchars($_SESSION['full_name'] ?? '');

?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h4">My Inventory</h1>
    </div>

    <p class="text-muted">Quick view of items assigned to you across Consumables, SEP and PPE.</p>

    <ul class="nav nav-tabs mb-3" id="myInventoryTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="ppe-tab" data-bs-toggle="tab" data-bs-target="#ppe-panel" type="button" role="tab">Property, Plant and Equipment</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="sep-tab" data-bs-toggle="tab" data-bs-target="#sep-panel" type="button" role="tab">Semi-Expendable Property</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="consumables-tab" data-bs-toggle="tab" data-bs-target="#consumables-panel" type="button" role="tab">Consumables</button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="ppe-panel" role="tabpanel">
            <div id="my-ppe-cards" class="d-flex flex-wrap gap-2"></div>
        </div>
        <div class="tab-pane fade" id="sep-panel" role="tabpanel">
            <div id="my-sep-cards" class="d-flex flex-wrap gap-2"></div>
        </div>
        <div class="tab-pane fade" id="consumables-panel" role="tabpanel">
            <div id="my-consumables-cards" class="d-flex flex-wrap gap-2"></div>
        </div>
    </div>

</main>

<style>
    /* compact card style used only on this page */
    .my-inv-card {
        width: 220px;
        border: 1px solid #e9ecef;
        border-radius: 6px;
        padding: 8px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    }
    .my-inv-card img { width: 100%; height: 120px; object-fit: cover; border-radius: 4px; }
    .my-inv-card .title { font-size: 0.95rem; font-weight: 600; margin-top: 6px; }
    .my-inv-card .meta { font-size: 0.82rem; color: #6c757d; }
    .my-inv-empty { padding: 32px; color: #6c757d; }
</style>

<?php require_once 'includes/footer.php'; ?>


<script>
(function() {
    const CURRENT_USER_ID = <?= $current_user_id ?>;
    const CURRENT_USER_NAME = <?= json_encode($current_user_name) ?>;

    let ppeViewModal, sepViewModal, consumableViewModal;
    let ppeItems = [], sepItems = [], myConsumables = [];

    function createCompactCard(item, type) {
        let photoPath = '';
        if (type === 'ppe') {
            const photoName = item.photo ? String(item.photo) : '';
            photoPath = photoName.startsWith('ppe_qr_') ? `assets/uploads/qr_codes/${photoName}` : `assets/uploads/ppe/${photoName || 'ppe_default.png'}`;
        } else if (type === 'sep') {
            const photoName = item.photo ? String(item.photo) : '';
            photoPath = photoName.startsWith('sep_qr_') ? `assets/uploads/qr_codes/${photoName}` : `assets/uploads/sep/${photoName || 'sep_default.png'}`;
        } else if (type === 'consumable') {
            const photoName = item.photo ? String(item.photo) : '';
            photoPath = photoName.startsWith('consumable_qr_') ? `assets/uploads/qr_codes/${photoName}` : `assets/uploads/consumables/${photoName || 'consumable_default.png'}`;
        }

        const title = item.description || item.property_number || item.stock_number || 'Item';
        let sub = '';
        if (type === 'consumable') {
            sub = `Total Issued: ${item.total_quantity_issued} ${item.unit_name || ''}`.trim();
        } else {
            sub = item.property_number || item.stock_number || '';
        }

        const idVal = item.ppe_id || item.sep_id || item.consumable_id || '';
        return `
            <div class="my-inv-card" data-id="${idVal}" data-type="${type}" style="cursor: pointer;" title="Click to see details">
                <img src="${photoPath}" alt="${escapeHTML(title)}" onerror="this.onerror=null;this.src='assets/uploads/consumables/consumable_default.png'">
                <div class="title">${escapeHTML(title)}</div>
                <div class="meta">${escapeHTML(sub)}</div>
            </div>
        `;
    }

    function escapeHTML(str) { return String(str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

    async function loadMyInventory() {
        try {
            const ppeResp = await fetch('api/get_my_ppe.php');
            const ppeJson = await ppeResp.json();
            ppeItems = ppeJson.data || [];
            const container = document.getElementById('my-ppe-cards');
            if (ppeItems.length === 0) container.innerHTML = '<div class="my-inv-empty">No PPE assigned to you.</div>';
            else container.innerHTML = ppeItems.map(i => createCompactCard(i,'ppe')).join('');
        } catch (e) { console.error('Error loading PPE:', e); }

        try {
            const sepResp = await fetch('api/get_my_sep.php');
            const sepJson = await sepResp.json();
            sepItems = sepJson.data || [];
            const container = document.getElementById('my-sep-cards');
            if (sepItems.length === 0) container.innerHTML = '<div class="my-inv-empty">No SEP assigned to you.</div>';
            else container.innerHTML = sepItems.map(i => createCompactCard(i,'sep')).join('');
        } catch (e) { console.error('Error loading SEP:', e); }

        try {
            const consResp = await fetch('api/get_my_consumables.php');
            const consJson = await consResp.json();
            myConsumables = consJson.data || [];
            const container = document.getElementById('my-consumables-cards');
            if (myConsumables.length === 0) {
                container.innerHTML = '<div class="my-inv-empty">No consumables have been issued to you.</div>';
            } else {
                container.innerHTML = myConsumables.map(i => createCompactCard(i, 'consumable')).join('');
            }
        } catch (e) { console.error('Error loading consumables:', e); }
    }

    function showPpeDetailsModal(ppeId) {
        const item = ppeItems.find(i => i.ppe_id == ppeId);
        if (!item) return;
        const contentArea = document.getElementById('view-ppe-content');
        const viewTemplate = document.getElementById('ppe-view-template').content;
        const viewContent = viewTemplate.cloneNode(true);
        const photoName = item.photo || 'ppe_default.png';
        viewContent.querySelector('img').src = photoName.startsWith('ppe_qr_') ? `assets/uploads/qr_codes/${photoName}` : `assets/uploads/ppe/${photoName}`;
        viewContent.querySelector('[data-template-id="description"]').textContent = item.description;
        viewContent.querySelector('[data-template-id="property_number"]').textContent = item.property_number;
        viewContent.querySelector('[data-template-id="serial_number"]').textContent = item.serial_number || 'N/A';
        viewContent.querySelector('[data-template-id="model_number"]').textContent = item.model_number || 'N/A';
        viewContent.querySelector('[data-template-id="unit_cost"]').textContent = item.unit_cost ? new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(parseFloat(item.unit_cost)) : '₱0.00';
        viewContent.querySelector('[data-template-id="date_acquired"]').textContent = new Date(item.date_acquired).toLocaleDateString();
        viewContent.querySelector('[data-template-id="assigned_to"]').textContent = item.assigned_to || 'Unassigned';
        contentArea.innerHTML = '';
        contentArea.appendChild(viewContent);
        ppeViewModal.show();
    }

    function showSepDetailsModal(sepId) {
        const item = sepItems.find(i => i.sep_id == sepId);
        if (!item) return;
        const contentArea = document.getElementById('view-sep-content');
        const viewTemplate = document.getElementById('sep-view-template').content;
        const viewContent = viewTemplate.cloneNode(true);
        const photoName = item.photo || 'sep_default.png';
        viewContent.querySelector('img').src = photoName.startsWith('sep_qr_') ? `assets/uploads/qr_codes/${photoName}` : `assets/uploads/sep/${photoName}`;
        viewContent.querySelector('[data-template-id="description"]').textContent = item.description;
        viewContent.querySelector('[data-template-id="property_number"]').textContent = item.property_number;
        viewContent.querySelector('[data-template-id="serial_number"]').textContent = item.serial_number || 'N/A';
        viewContent.querySelector('[data-template-id="brand_name"]').textContent = item.brand_name || 'N/A';
        viewContent.querySelector('[data-template-id="unit_cost"]').textContent = item.unit_cost ? new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(parseFloat(item.unit_cost)) : '₱0.00';
        viewContent.querySelector('[data-template-id="date_acquired"]').textContent = new Date(item.date_acquired).toLocaleDateString();
        viewContent.querySelector('[data-template-id="estimated_useful_life"]').textContent = item.estimated_useful_life ? `${item.estimated_useful_life} years` : 'N/A';
        viewContent.querySelector('[data-template-id="current_condition"]').textContent = item.current_condition || 'N/A';
        viewContent.querySelector('[data-template-id="assigned_to"]').textContent = item.assigned_to || 'Unassigned';
        contentArea.innerHTML = '';
        contentArea.appendChild(viewContent);
        sepViewModal.show();
    }

    function showConsumableDetailsModal(consumableId) {
        const item = myConsumables.find(i => i.consumable_id == consumableId);
        if (!item) return;

        const contentArea = document.getElementById('view-consumable-content');
        const viewTemplate = document.getElementById('consumable-view-template').content;
        const viewContent = viewTemplate.cloneNode(true);

    const consumablePhotoName = item.photo ? String(item.photo) : '';
    const consumablePhotoPath = consumablePhotoName.startsWith('consumable_qr_') ? `assets/uploads/qr_codes/${consumablePhotoName}` : `assets/uploads/consumables/${consumablePhotoName || 'consumable_default.png'}`;
    const consumableImgEl = viewContent.querySelector('img');
    consumableImgEl.src = consumablePhotoPath;
    consumableImgEl.onerror = function() { this.onerror = null; this.src = 'assets/uploads/consumables/consumable_default.png'; };
        viewContent.querySelector('[data-template-id="description"]').textContent = item.description;
        viewContent.querySelector('[data-template-id="stock_number"]').textContent = item.stock_number;
        viewContent.querySelector('[data-template-id="unit_cost"]').textContent = parseFloat(item.unit_cost).toLocaleString('en-US', { style: 'currency', currency: 'PHP' });
        viewContent.querySelector('[data-template-id="unit_name"]').textContent = item.unit_name;
        viewContent.querySelector('[data-template-id="date_received"]').textContent = new Date(item.date_received).toLocaleDateString();
        viewContent.querySelector('[data-template-id="quantity_received"]').textContent = item.quantity_received;
        viewContent.querySelector('[data-template-id="current_stock"]').textContent = item.current_stock;

        contentArea.innerHTML = '';
        contentArea.appendChild(viewContent);
        consumableViewModal.show();
    }

    document.addEventListener('DOMContentLoaded', function() {
        try {
            ppeViewModal = new bootstrap.Modal(document.getElementById('viewPpeModal'));
            sepViewModal = new bootstrap.Modal(document.getElementById('viewSepModal'));
            // actual modal ID defined in includes/consumable_view_modal.php is 'viewConsumableModal'
            consumableViewModal = new bootstrap.Modal(document.getElementById('viewConsumableModal'));
        } catch (e) {
            console.error('Failed to initialize one or more modals:', e);
        }
        
        loadMyInventory();
    });

    document.body.addEventListener('click', function(e) {
        const card = e.target.closest('.my-inv-card');
        if (!card) return;

        const id = card.dataset.id;
        const type = card.dataset.type;
        if (!id) return; // guard against cards missing an id (prevents showing the wrong item)

        if (type === 'ppe') {
            showPpeDetailsModal(id);
        } else if (type === 'sep') {
            showSepDetailsModal(id);
        } else if (type === 'consumable') {
            showConsumableDetailsModal(id);
        }
    });
})();
</script>
