$(document).ready(function() {
    // --- Initialize DataTable ---
    const table = $('#icsSequenceTable').DataTable({
        "processing": true,
        "ajax": {
            "url": "api/number_sequence_api.php?type=ics",
            "dataSrc": "data"
        },
        "columns": [
            { "data": "year" },
            { "data": "ics_number_format" },
            { 
                "data": "start_count",
                "render": function(data, type, row) {
                    // Show the next number that will be generated
                    const format = row.ics_number_format || 'ICS-{YYYY}-{NNNN}';
                    const nextNumber = String(data).padStart(4, '0');
                    return format.replace('{YYYY}', row.year).replace('{NNNN}', nextNumber);
                }
            },
            { 
                "data": "ics_number_id",
                "orderable": false,
                "render": function(data, type, row) {
                    return `<button class="btn btn-sm btn-primary edit-btn" data-id="${data}"><i class="bi bi-pencil-square"></i> Edit</button>`;
                }
            }
        ],
        "order": [[0, 'desc']] // Default sort by year descending
    });

    const modal = new bootstrap.Modal(document.getElementById('icsSequenceModal'));
    const form = document.getElementById('icsSequenceForm');
    const modalLabel = document.getElementById('icsSequenceModalLabel');
    const idInput = document.getElementById('ics_number_id');

    // --- Handle Add Button Click ---
    $('#addIcsSequenceBtn').on('click', function() {
        modalLabel.textContent = 'Add New Sequence';
        form.reset();
        idInput.value = '';
        $('#year').val(new Date().getFullYear() + 1); // Suggest next year
        $('#ics_number_format').val('ICS-{YYYY}-{NNNN}');
        $('#start_count').val(1);
    });

    // --- Handle Edit Button Click ---
    $('#icsSequenceTable tbody').on('click', '.edit-btn', function() {
        const id = $(this).data('id');
        const rowData = table.rows().data().toArray().find(row => row.ics_number_id == id);

        if (rowData) {
            modalLabel.textContent = 'Edit Sequence';
            idInput.value = rowData.ics_number_id;
            $('#year').val(rowData.year);
            $('#ics_number_format').val(rowData.ics_number_format);
            $('#start_count').val(rowData.start_count);
            modal.show();
        }
    });

    // --- Handle Form Submission (Add & Edit) ---
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const submitButton = form.querySelector('button[type="submit"]');

        const formData = {
            ics_number_id: idInput.value,
            year: $('#year').val(),
            ics_number_format: $('#ics_number_format').val(),
            start_count: $('#start_count').val()
        };

        submitButton.disabled = true;
        submitButton.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Saving...`;

        fetch('api/number_sequence_api.php?type=ics', {
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