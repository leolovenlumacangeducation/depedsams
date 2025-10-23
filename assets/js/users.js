$(document).ready(function() {
    // Get the logged-in user's ID from the data attribute on the body, set in header.php
    const loggedInUserId = $('body').data('user-id');

    // --- Initialize DataTable ---
    const table = $('#usersTable').DataTable({
        "processing": true,
        "ajax": {
            "url": "api/users_api.php",
            "dataSrc": "data",
            "data": { "logged_in_user_id": loggedInUserId } // Send logged-in user ID to API if needed
        },
        "columns": [
            { 
                "data": "photo",
                "orderable": false,
                "searchable": false,
                "render": function(data, type, row) {
                    const photoPath = `assets/uploads/users/${data || 'default_user.png'}`;
                    return `<img src="${photoPath}" alt="${row.full_name}" class="img-thumbnail rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">`;
                }
            },
            { "data": "full_name" },
            { "data": "username" },
            { "data": "role_name" },
            { "data": "position_name", "render": data => data || 'N/A' },
            { 
                "data": "is_active",
                "render": data => `<span class="badge ${data == 1 ? 'bg-success' : 'bg-danger'}">${data == 1 ? 'Active' : 'Inactive'}</span>`
            },
            { 
                "data": "user_id",
                "orderable": false,
                "render": function(data, type, row) {
                    const statusBtnClass = row.is_active == 1 ? 'btn-warning' : 'btn-success';
                    const statusBtnIcon = row.is_active == 1 ? 'bi-slash-circle' : 'bi-check-circle';
                    const statusBtnText = row.is_active == 1 ? ' Deactivate' : ' Activate';
                    const statusBtnTitle = row.is_active == 1 ? 'Deactivate User' : 'Activate User';

                    // --- UX Improvement: Don't show the status toggle button for the current user ---
                    if (row.user_id == loggedInUserId) {
                        return `<button class="btn btn-sm btn-primary edit-btn" data-id="${data}" title="Edit User"><i class="bi bi-pencil-square"></i></button> <span class="badge bg-secondary ms-1">You</span>`;
                    }

                    return `
                        <div class="btn-group" role="group">
                            <button class="btn btn-sm btn-primary edit-btn" data-id="${data}" title="Edit User">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <button class="btn btn-sm ${statusBtnClass} status-btn" data-id="${data}" data-status="${row.is_active}" title="${statusBtnTitle}">
                                <i class="bi ${statusBtnIcon}"></i>
                            </button>
                            <button class="btn btn-sm btn-danger delete-btn" data-id="${data}" title="Delete User">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </div>`;
                }
            }
        ],
        "order": [[1, 'asc']] // Default sort by Full Name
    });

    const modal = new bootstrap.Modal(document.getElementById('userModal'));
    const form = document.getElementById('userForm');
    const modalLabel = document.getElementById('userModalLabel');
    const idInput = document.getElementById('user_id');
    const photoPreview = document.getElementById('photo-preview');
    const photoInput = document.getElementById('photo');

    // --- Handle Add Button Click ---
    $('#addUserBtn').on('click', function() {
        modalLabel.textContent = 'Add New User';
        form.reset();
        idInput.value = '';
        $('#password').attr('placeholder', 'Required for new user').prop('required', true);
        photoPreview.src = 'assets/uploads/users/default_user.png';
    });

    // --- Handle Edit Button Click ---
    $('#usersTable tbody').on('click', '.edit-btn', function() {
        const id = $(this).data('id');
        const rowData = table.rows().data().toArray().find(row => row.user_id == id);

        if (rowData) {
            modalLabel.textContent = 'Edit User';
            idInput.value = rowData.user_id;
            $('#full_name').val(rowData.full_name);
            $('#username').val(rowData.username);
            $('#role_id').val(rowData.role_id);
            $('#position_id').val(rowData.position_id);
            $('#password').attr('placeholder', 'Leave blank to keep current').prop('required', false);
            photoPreview.src = `assets/uploads/users/${rowData.photo || 'default_user.png'}`;
            modal.show();
        }
    });

    // --- Handle Delete Button Click ---
    $('#usersTable tbody').on('click', '.delete-btn', function() {
        const id = $(this).data('id');
        const rowData = table.row($(this).closest('tr')).data();
        
        if (confirm(`Are you sure you want to delete ${rowData.full_name}? This action cannot be undone.`)) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('user_id', id);

            fetch('api/users_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'Success', 'success');
                    table.ajax.reload();
                } else {
                    throw new Error(data.message || 'An unknown error occurred.');
                }
            })
            .catch(error => {
                showToast(`Error: ${error.message}`, 'Delete Failed', 'danger');
            });
        }
    });

    // --- Handle Status Toggle Button Click ---
    $('#usersTable tbody').on('click', '.status-btn', function() {
        const id = $(this).data('id');
        const currentStatus = $(this).data('status');
        const newStatus = currentStatus == 1 ? 0 : 1;
        const actionText = newStatus == 1 ? 'activate' : 'deactivate';

        if (confirm(`Are you sure you want to ${actionText} this user?`)) {
            const formData = new FormData();
            formData.append('action', 'toggle_status');
            formData.append('user_id', id);
            formData.append('is_active', newStatus);

            fetch('api/users_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'Success', 'success');
                    table.ajax.reload();
                } else {
                    throw new Error(data.message || 'An unknown error occurred.');
                }
            })
            .catch(error => {
                showToast(`Error: ${error.message}`, 'Update Failed', 'danger');
            });
        }
    });

    // --- Handle Photo Preview ---
    photoInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                photoPreview.src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    });

    // --- Handle Form Submission (Add & Edit) ---
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const submitButton = form.querySelector('button[type="submit"]');
        const formData = new FormData(form);

        // UI Feedback
        submitButton.disabled = true;
        submitButton.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Saving...`;

        fetch('api/users_api.php', {
            method: 'POST',
            body: formData // Use FormData to send multipart data (including file)
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => { throw new Error(err.message) });
            }
            return response.json();
        })
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
            submitButton.innerHTML = 'Save User';
        });
    });
});