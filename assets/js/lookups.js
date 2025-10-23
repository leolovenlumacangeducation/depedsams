$(document).ready(function() {
    const modal = new bootstrap.Modal(document.getElementById('lookupModal'));
    const form = document.getElementById('lookupForm');
    const modalLabel = document.getElementById('lookupModalLabel');
    const idInput = document.getElementById('lookup_id');
    const typeInput = document.getElementById('lookup_type');
    const nameInput = document.getElementById('lookup_name');
    const categoryModal = new bootstrap.Modal(document.getElementById('categoryModal'));
    const categoryForm = document.getElementById('categoryForm');
    let activeTable = null;

    // Configuration for each lookup table
    const tableConfigs = {
        'units': { type: 'unit', table: '#unitsTable', columns: [{ data: 'name' }, { data: null }] },
        'positions': { type: 'position', table: '#positionsTable', columns: [{ data: 'name' }, { data: null }] },
        'roles': { type: 'role', table: '#rolesTable', columns: [{ data: 'name' }, { data: null }] },
        'categories': { type: 'category', table: '#categoriesTable', isSpecial: true },
        'inventory-types': { type: 'inventory_type', table: '#inventoryTypesTable', columns: [{ data: 'name' }, { data: null }] },
        'purchase-modes': { type: 'purchase_mode', table: '#purchaseModesTable', columns: [{ data: 'name' }, { data: null }] },
        'delivery-places': { type: 'delivery_place', table: '#deliveryPlacesTable', columns: [{ data: 'name' }, { data: null }] },
        'delivery-terms': { type: 'delivery_term', table: '#deliveryTermsTable', columns: [{ data: 'name' }, { data: null }] },
        'payment-terms': { type: 'payment_term', table: '#paymentTermsTable', columns: [{ data: 'name' }, { data: null }] },
    };

    // Function to initialize a DataTable
    function initializeTable(config) {
        if (config.isSpecial && config.type === 'category') {
            return initializeCategoryTable(config);
        }
        return $(config.table).DataTable({
            "processing": true,
            "ajax": `api/lookup_api.php?type=${config.type}`,
            "columns": [
                ...config.columns.slice(0, -1),
                {
                    "data": "id",
                    "orderable": false,
                    "render": function(data, type, row) {
                        return `
                            <button class="btn btn-sm btn-primary edit-lookup-btn" data-id="${data}" data-name="${row.name}" data-type="${config.type}"><i class="bi bi-pencil-square"></i> Edit</button>
                            <button class="btn btn-sm btn-danger delete-lookup-btn" data-id="${data}" data-name="${row.name}" data-type="${config.type}"><i class="bi bi-trash"></i> Delete</button>
                        `;
                    }
                }
            ]
        });
    }

    function initializeCategoryTable(config) {
        return $(config.table).DataTable({
            "processing": true,
            "ajax": "api/category_api.php",
            "columns": [
                { "data": "category_name" },
                { "data": "uacs_object_code", "render": data => data || 'N/A' },
                { "data": "inventory_type_name", "className": "text-center" },
                { 
                    "data": "category_id",
                    "orderable": false,
                    "render": function(data, type, row) {
                        return `
                            <button class="btn btn-sm btn-primary edit-category-btn" data-id="${data}"><i class="bi bi-pencil-square"></i> Edit</button>
                            <button class="btn btn-sm btn-danger delete-category-btn" data-id="${data}"><i class="bi bi-trash"></i> Delete</button>
                        `;
                    }
                }
            ]
        });
    }

    // Initialize the first table on page load
    activeTable = initializeTable(tableConfigs['units']);

    // Handle tab switching to initialize other tables
    $('#lookupsTab button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
        const targetPanelId = $(e.target).attr('data-bs-target').substring(1);
        const configKey = targetPanelId.replace('-panel', '');
        const config = tableConfigs[configKey];
        if (config && !$.fn.DataTable.isDataTable(config.table)) {
            activeTable = initializeTable(config);
        } else if (config) {
            activeTable = $(config.table).DataTable();
        }
    });

    // --- Handle Add Button Click ---
    $('.add-lookup-btn').on('click', function() {
        const type = $(this).data('type');
        const title = $(this).data('modal-title');
        modalLabel.textContent = title;
        form.reset();
        idInput.value = '';
        typeInput.value = type;
        modal.show();
    });

    // --- Handle Edit Button Click (event delegation) ---
    $('.tab-content').on('click', '.edit-lookup-btn', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const type = $(this).data('type');
        modalLabel.textContent = `Edit ${type.replace('_', ' ')}`;
        idInput.value = id;
        typeInput.value = type;
        nameInput.value = name;
        modal.show();
    });

    // --- Handle Delete Button Click (event delegation) ---
    $('.tab-content').on('click', '.delete-lookup-btn', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const type = $(this).data('type');

        if (confirm(`Are you sure you want to delete "${name}"?`)) {
            fetch(`api/lookup_api.php?type=${type}`, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            })
            .then(response => response.json().then(data => ({ status: response.status, body: data })))
            .then(({ status, body }) => {
                if (status === 200 && body.success) {
                    showToast(body.message, 'Success', 'success');
                    if (activeTable) activeTable.ajax.reload();
                } else {
                    throw new Error(body.message || 'An unknown error occurred.');
                }
            })
            .catch(error => showToast(`Error: ${error.message}`, 'Delete Failed', 'danger'));
        }
    });

    // --- Category Specific Handlers ---
    $('#addCategoryBtn').on('click', function() {
        document.getElementById('categoryModalLabel').textContent = 'Add New Category';
        categoryForm.reset();
        document.getElementById('category_id').value = '';
    });

    $('#categoriesTable').on('click', '.edit-category-btn', function() {
        const id = $(this).data('id');
        const rowData = $('#categoriesTable').DataTable().rows().data().toArray().find(row => row.category_id == id);
        if (rowData) {
            document.getElementById('categoryModalLabel').textContent = 'Edit Category';
            document.getElementById('category_id').value = rowData.category_id;
            $('#category_name').val(rowData.category_name);
            $('#uacs_object_code').val(rowData.uacs_object_code);
            $('#inventory_type_id').val(rowData.inventory_type_id);
            categoryModal.show();
        }
    });

    $('#categoriesTable').on('click', '.delete-category-btn', function() {
        const id = $(this).data('id');
        if (confirm('Are you sure you want to delete this category? This action cannot be undone.')) {
            fetch('api/category_api.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ category_id: id })
            })
            .then(response => response.json().then(data => ({ status: response.status, body: data })))
            .then(({ status, body }) => {
                if (status === 200 && body.success) {
                    showToast(body.message, 'Success', 'success');
                    $('#categoriesTable').DataTable().ajax.reload();
                } else {
                    throw new Error(body.message || 'An unknown error occurred.');
                }
            })
            .catch(error => showToast(`Error: ${error.message}`, 'Delete Failed', 'danger'));
        }
    });

    categoryForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const submitButton = categoryForm.querySelector('button[type="submit"]');

        const formData = {
            category_id: document.getElementById('category_id').value,
            category_name: $('#category_name').val(),
            inventory_type_id: $('#inventory_type_id').val(),
            uacs_object_code: $('#uacs_object_code').val()
        };

        submitButton.disabled = true;
        submitButton.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Saving...`;

        fetch('api/category_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        })
        .then(response => response.ok ? response.json() : response.json().then(err => Promise.reject(err)))
        .then(data => {
            if (data.success) {
                showToast(data.message, 'Success', 'success');
                categoryModal.hide();
                $('#categoriesTable').DataTable().ajax.reload();
            } else {
                throw new Error(data.message || 'An unknown error occurred.');
            }
        })
        .catch(error => {
            showToast(`Error: ${error.message}`, 'Save Failed', 'danger');
        })
        .finally(() => {
            submitButton.disabled = false;
            submitButton.innerHTML = 'Save Category';
        });
    });

    // --- Handle Form Submission ---
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const submitButton = form.querySelector('button[type="submit"]');
        const type = typeInput.value;
        const formData = { id: idInput.value, name: nameInput.value };

        submitButton.disabled = true;
        submitButton.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Saving...`;

        fetch(`api/lookup_api.php?type=${type}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        })
        .then(response => response.json().then(data => ({ status: response.status, body: data })))
        .then(({ status, body }) => {
            if (status === 200 && body.success) {
                showToast(body.message, 'Success', 'success');
                modal.hide();
                if (activeTable) activeTable.ajax.reload();
            } else {
                throw new Error(body.message || 'An unknown error occurred.');
            }
        })
        .catch(error => showToast(`Error: ${error.message}`, 'Save Failed', 'danger'))
        .finally(() => {
            submitButton.disabled = false;
            submitButton.innerHTML = 'Save';
        });
    });
});