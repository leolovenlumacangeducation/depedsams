$(document).ready(function() {
    // --- Initialize DataTable ---
    const table = $('#risListTable').DataTable({
        "processing": true,
        "ajax": {
            "url": "api/ris_list_api.php",
            "dataSrc": "data"
        },
        "columns": [
            { "data": "ris_number" },
            { "data": "date_issued", "render": data => new Date(data).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) },
            { "data": "issued_to" },
            { "data": "issued_by" },
            { 
                "data": "issuance_id",
                "orderable": false,
                "render": function(data, type, row) {
                    return `<button class="btn btn-sm btn-info view-ris-btn" data-id="${data}"><i class="bi bi-eye"></i> View</button>`;
                }
            }
        ],
        "order": [[1, 'desc']]
    });

    // --- Handle View Button Click ---
    $('#risListTable tbody').on('click', '.view-ris-btn', function() {
        const issuanceId = $(this).data('id');
        showRisModal(issuanceId);
    });
});

// --- Global function for printing RIS from modal ---
function printRisView() {
    const content = document.getElementById('ris-view-content');
    if (content) {
        const printWindow = window.open('', '', 'height=800,width=1000');
        printWindow.document.write('<html><head><title>Print RIS</title>');
        
        // Clone and include the stylesheet from the main document for consistent styling
        const styles = document.querySelectorAll('link[rel="stylesheet"], style');
        styles.forEach(style => {
            printWindow.document.head.appendChild(style.cloneNode(true));
        });

        // Add the specific style from the template if it exists
        printWindow.document.write(content.querySelector('style')?.outerHTML || '');
        
        printWindow.document.write('</head><body>' + content.innerHTML + '</body></html>');
        printWindow.document.close();
        printWindow.focus();
        setTimeout(() => { printWindow.print(); printWindow.close(); }, 500);
    }
}