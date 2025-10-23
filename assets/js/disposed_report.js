$(document).ready(function() {
    $('#disposedItemsTable').DataTable({
        "processing": true,
        "ajax": {
            "url": "api/disposed_items_api.php",
            "dataSrc": "data"
        },
        "columns": [
            { "data": "property_number" },
            { "data": "description" },
            { "data": "serial_number", "render": data => data || 'N/A' },
            { 
                "data": "item_type",
                "className": "text-center",
                "render": function(data) {
                    const badgeClass = data === 'PPE' ? 'bg-primary' : 'bg-info';
                    return `<span class="badge ${badgeClass}">${data}</span>`;
                }
            },
            { 
                "data": "date_acquired", 
                "render": data => new Date(data).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) 
            }
        ],
        "order": [[4, 'desc']],
        "layout": {
            "topStart": {
                "buttons": [
                    {
                        extend: 'print',
                        text: '<i class="bi bi-printer"></i> Print Report',
                        className: 'btn btn-primary',
                        title: 'Disposed Items Report'
                    }
                ]
            }
        }
    });
});