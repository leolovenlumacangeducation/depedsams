document.addEventListener('DOMContentLoaded', function () {
    const supplierModal = new bootstrap.Modal(document.getElementById('supplierModal'));
    const supplierForm = document.getElementById('supplierForm');
    const supplierModalLabel = document.getElementById('supplierModalLabel');
    const addSupplierBtn = document.getElementById('addSupplierBtn');

    // Initialize DataTables
    const table = $('#suppliersTable').DataTable({
        "processing": true,
        "ajax": {
            "url": "api/suppliers_api.php",
            "dataSrc": "data"
        },
        "columns": [
            { "data": "supplier_name" },
            { "data": "address" },
            { "data": "contact_person" },
            { "data": "contact_no" },
            { "data": "tin" },
            {
                "data": "supplier_id",
                "orderable": false,
                "render": function (data, type, row) {
                    return `
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-primary edit-btn" data-id="${data}" title="Edit Supplier"><i class="bi bi-pencil-square"></i></button>
                            <button type="button" class="btn btn-sm btn-danger delete-btn" data-id="${data}" title="Delete Supplier"><i class="bi bi-trash"></i></button>
                        </div>
                    `;
                }
            }
        ]
    });

    // Handle "Add New Supplier" button click
    addSupplierBtn.addEventListener('click', function () {
        supplierForm.reset();
        document.getElementById('supplier_id').value = '';
        supplierModalLabel.textContent = 'Add New Supplier';
    });

    // Handle form submission for both add and edit
    supplierForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        const formData = new FormData(supplierForm);
        const data = Object.fromEntries(formData.entries());
        const submitButton = supplierForm.querySelector('button[type="submit"]');

        submitButton.disabled = true;
        submitButton.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Saving...`;

        try {
            const response = await fetch('api/suppliers_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message);
            }

            showToast(result.message, 'Success', 'success');
            supplierModal.hide();
            table.ajax.reload();

        } catch (error) {
            showToast(`Error: ${error.message}`, 'Save Failed', 'danger');
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = 'Save Supplier';
        }
    });

    // Event delegation for Edit and Delete buttons
    $('#suppliersTable tbody').on('click', 'button', async function () {
        const button = $(this);
        const id = button.data('id');
        const rowData = table.row(button.closest('tr')).data();

        if (button.hasClass('edit-btn')) {
            // --- Handle Edit ---
            supplierForm.reset();
            supplierModalLabel.textContent = 'Edit Supplier';

            document.getElementById('supplier_id').value = rowData.supplier_id;
            document.getElementById('supplier_name').value = rowData.supplier_name;
            document.getElementById('address').value = rowData.address;
            document.getElementById('contact_person').value = rowData.contact_person;
            document.getElementById('contact_no').value = rowData.contact_no;
            document.getElementById('tin').value = rowData.tin;

            supplierModal.show();

        } else if (button.hasClass('delete-btn')) {
            // --- Handle Delete ---
            if (!confirm(`Are you sure you want to delete supplier "${rowData.supplier_name}"? This cannot be undone.`)) {
                return;
            }

            try {
                const response = await fetch('api/suppliers_api.php', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ supplier_id: id })
                });
                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.message);
                }

                showToast(result.message, 'Success', 'success');
                table.ajax.reload();

            } catch (error) {
                showToast(`Error: ${error.message}`, 'Delete Failed', 'danger');
            }
        }
    });
});