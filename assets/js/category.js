$(document).ready(function() {
    // --- Initialize DataTable ---
    const table = $('#categoriesTable').DataTable({
        "processing": true,
        "ajax": {
            "url": "api/category_api.php",
            "dataSrc": "data"
        },
        "columns": [
            { "data": "category_name" },
            { "data": "uacs_object_code", "render": data => data || 'N/A' },
            { "data": "inventory_type_name", "className": "text-center" },
            { 
                "data": "category_id",
                "orderable": false,
                "render": function(data, type, row) {
                    return `
                        <button class="btn btn-sm btn-primary edit-btn" data-id="${data}"><i class="bi bi-pencil-square"></i> Edit</button>
                        <button class="btn btn-sm btn-danger delete-btn" data-id="${data}"><i class="bi bi-trash"></i> Delete</button>
                    `;
                }
            }
        ],
        "order": [[0, 'asc']]
    });

    const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
    const form = document.getElementById('categoryForm');
    const modalLabel = document.getElementById('categoryModalLabel');
    const idInput = document.getElementById('category_id');

    // --- Handle Add Button Click ---
    $('#addCategoryBtn').on('click', function() {
        modalLabel.textContent = 'Add New Category';
        form.reset();
        idInput.value = '';
    });

    // --- Handle Edit Button Click ---
    $('#categoriesTable tbody').on('click', '.edit-btn', function() {
        const id = $(this).data('id');
        const rowData = table.rows().data().toArray().find(row => row.category_id == id);

        if (rowData) {
            modalLabel.textContent = 'Edit Category';
            idInput.value = rowData.category_id;
            $('#category_name').val(rowData.category_name);
            $('#uacs_object_code').val(rowData.uacs_object_code);
            $('#inventory_type_id').val(rowData.inventory_type_id);
            modal.show();
        }
    });

    // --- Handle Delete Button Click ---
    $('#categoriesTable tbody').on('click', '.delete-btn', function() {
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
                    table.ajax.reload();
                } else {
                    throw new Error(body.message || 'An unknown error occurred.');
                }
            })
            .catch(error => showToast(`Error: ${error.message}`, 'Delete Failed', 'danger'));
        }
    });

    // --- Handle Form Submission (Add & Edit) ---
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const submitButton = form.querySelector('button[type="submit"]');

        const formData = {
            category_id: idInput.value,
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
                modal.hide();
                table.ajax.reload();
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
});