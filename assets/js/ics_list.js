$(document).ready(function() {
    // --- Initialize DataTable ---
    const table = $('#icsListTable').DataTable({
        "processing": true,
        "ajax": {
            "url": "api/ics_list_api.php",
            "dataSrc": "data"
        },
        "columns": [
            { "data": "ics_number" },
            { "data": "date_issued", "render": data => new Date(data).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) },
            { "data": "issued_to" },
            { "data": "issued_by" },
            { 
                "data": "status",
                "className": "text-center",
                "render": function(data) {
                    if (data === 'Void') return `<span class="badge bg-danger">Void</span>`;
                    return `<span class="badge bg-success">Active</span>`;
                }
            },
            { 
                "data": "ics_id",
                "orderable": false,
                "render": function(data, type, row) {
                    return `<button class="btn btn-sm btn-info view-ics-btn" data-id="${data}"><i class="bi bi-eye"></i> View</button>`;
                }
            }
        ],
        "order": [[1, 'desc']]
    });

    // --- Handle View Button Click ---
    $('#icsListTable tbody').on('click', '.view-ics-btn', function() {
        const icsId = $(this).data('id');
        showIcsModal(icsId);
    });
});