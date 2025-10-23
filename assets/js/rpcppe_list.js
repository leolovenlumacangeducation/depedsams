$(document).ready(function() {
    const table = $('#rpcppeListTable').DataTable({
        "processing": true,
        "ajax": {
            "url": "api/rpcppe_list_api.php",
            "dataSrc": "data"
        },
        "columns": [
            { "data": "rpcppe_number" },
            { "data": "as_of_date", "render": data => new Date(data + 'T00:00:00').toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) },
            { "data": "created_by" },
            { "data": "date_created", "render": data => new Date(data).toLocaleString('en-US') },
            { 
                "data": "rpcppe_id",
                "orderable": false,
                "render": function(data) {
                    return `<button class="btn btn-sm btn-info view-rpcppe-btn" data-id="${data}"><i class="bi bi-eye"></i> View</button>`;
                }
            }
        ],
        "order": [[1, 'desc']]
    });

    $('#rpcppeListTable tbody').on('click', '.view-rpcppe-btn', function() {
        const rpcppeId = $(this).data('id');
        if (typeof showRpcppeModal === 'function') {
            showRpcppeModal(rpcppeId);
        } else {
            console.error('showRpcppeModal function not found.');
        }
    });
});