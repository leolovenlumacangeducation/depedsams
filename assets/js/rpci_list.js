$(document).ready(function() {
    const table = $('#rpciListTable').DataTable({
        "processing": true,
        "ajax": {
            "url": "api/rpci_list_api.php",
            "dataSrc": "data"
        },
        "columns": [
            { "data": "rpci_number" },
            { "data": "as_of_date", "render": data => new Date(data + 'T00:00:00').toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) },
            { "data": "created_by" },
            { "data": "date_created", "render": data => new Date(data).toLocaleString('en-US') },
            { 
                "data": "rpci_id",
                "orderable": false,
                "render": function(data) {
                    return `<button class="btn btn-sm btn-info view-rpci-btn" data-id="${data}"><i class="bi bi-eye"></i> View</button>`;
                }
            }
        ],
        "order": [[1, 'desc']]
    });

    $('#rpciListTable tbody').on('click', '.view-rpci-btn', function() {
        const rpciId = $(this).data('id');
        if (typeof showRpciModal === 'function') {
            showRpciModal(rpciId);
        } else {
            console.error('showRpciModal function not found.');
        }
    });
});