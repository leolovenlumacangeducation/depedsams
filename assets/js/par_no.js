$(document).ready(function() {
    // --- Initialize DataTable ---
    const table = $('#parSequenceTable').DataTable({
        "processing": true,
        "ajax": {
            "url": "api/number_sequence_api.php?type=par",
            "dataSrc": "data"
        },
        "columns": [
            { "data": "year" },
            { "data": "par_number_format" },
            { 
                "data": "start_count",
                "render": function(data, type, row) {
                    const format = row.par_number_format || 'PAR-{YYYY}-{NNNN}';
                    const nextNumber = String(data).padStart(4, '0');
                    return format.replace('{YYYY}', row.year).replace('{NNNN}', nextNumber);
                }
            },
            { 
                "data": "par_number_id",
                "orderable": false,
                "render": function(data, type, row) {
                    return `<button class="btn btn-sm btn-primary edit-btn" data-id="${data}"><i class="bi bi-pencil-square"></i> Edit</button>`;
                }
            }
        ],
        "order": [[0, 'desc']]
    });

    const modal = new bootstrap.Modal(document.getElementById('parSequenceModal'));
    const form = document.getElementById('parSequenceForm');
    const modalLabel = document.getElementById('parSequenceModalLabel');
    const idInput = document.getElementById('par_number_id');

    // --- Handle Add Button Click ---
    $('#addParSequenceBtn').on('click', function() {
        modalLabel.textContent = 'Add New PAR Sequence';
        form.reset();
        idInput.value = '';
        $('#year').val(new Date().getFullYear() + 1);
        $('#par_number_format').val('PAR-{YYYY}-{NNNN}');
        $('#start_count').val(1);
    });

    // --- Handle Edit Button Click ---
    $('#parSequenceTable tbody').on('click', '.edit-btn', function() {
        const id = $(this).data('id');
        const rowData = table.rows().data().toArray().find(row => row.par_number_id == id);

        if (rowData) {
            modalLabel.textContent = 'Edit PAR Sequence';
            idInput.value = rowData.par_number_id;
            $('#year').val(rowData.year);
            $('#par_number_format').val(rowData.par_number_format);
            $('#start_count').val(rowData.start_count);
            modal.show();
        }
    });

    // --- Handle Form Submission (Add & Edit) ---
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const submitButton = form.querySelector('button[type="submit"]');

        const formData = {
            par_number_id: idInput.value,
            year: $('#year').val(),
            par_number_format: $('#par_number_format').val(),
            start_count: $('#start_count').val()
        };

        submitButton.disabled = true;
        submitButton.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Saving...`;

        fetch('api/number_sequence_api.php?type=par', {
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
            submitButton.innerHTML = 'Save Sequence';
        });
    });
});