$(document).ready(function() {
    // --- Initialize DataTable ---
    $('#parListTable').DataTable({
        "processing": true,
        "ajax": {
            "url": "api/par_list_api.php",
            "dataSrc": "data"
        },
        "columns": [
            { "data": "par_number" },
            { 
                "data": "date_issued",
                "render": data => new Date(data).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
            },
            { "data": "issued_to" },
            { "data": "issued_by" },
            { 
                "data": "par_id",
                "orderable": false,
                "render": data => `<button class="btn btn-sm btn-info view-par-btn" data-id="${data}"><i class="bi bi-eye"></i> View</button>`
            }
        ],
        "order": [[1, 'desc']]
    });

    const parViewModal = new bootstrap.Modal(document.getElementById('parViewModal'));
    const modalBody = document.getElementById('par-view-modal-body');

    // --- Handle View Button Click ---
    $('#parListTable tbody').on('click', '.view-par-btn', async function() {
        const parId = $(this).data('id');
        modalBody.innerHTML = `<div class="text-center p-5"><div class="spinner-border" role="status"></div></div>`;
        parViewModal.show();

        try {
            const response = await fetch(`api/par_view.php?id=${parId}`);
            if (!response.ok) throw new Error('Failed to fetch PAR details.');
            modalBody.innerHTML = await response.text();
        } catch (error) {
            modalBody.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
        }
    });
});