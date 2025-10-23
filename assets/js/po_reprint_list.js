$(document).ready(function() {
    const table = $('#poReprintListTable').DataTable({
        "processing": true,
        "ajax": {
            "url": "api/po_reprint_list_api.php",
            "dataSrc": "data"
        },
        "columns": [
            { "data": "po_number" },
            { "data": "supplier_name" },
            { 
                "data": "order_date", 
                "render": data => new Date(data + 'T00:00:00').toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) 
            },
            { "data": "total_amount", "className": "text-end" },
            { 
                "data": "status",
                "className": "text-center",
                "render": function(data) {
                    const badgeClass = data === 'Delivered' ? 'bg-success' : 'bg-warning text-dark';
                    return `<span class="badge ${badgeClass}">${data}</span>`;
                }
            },
            { 
                "data": "po_id",
                "orderable": false,
                "render": function(data) {
                    return `<button class="btn btn-sm btn-info view-po-btn" data-id="${data}"><i class="bi bi-eye"></i> View</button>`;
                }
            }
        ],
        "order": [[2, 'desc']]
    });

    $('#poReprintListTable tbody').on('click', '.view-po-btn', function() {
        const poId = $(this).data('id');
        // This function is now globally available from document.modals.js
        if (typeof showPoViewModal === 'function') {
            showPoViewModal(poId);
        } else {
            console.error('showPoViewModal function not found.');
        }
    });
});