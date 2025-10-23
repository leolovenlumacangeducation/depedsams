document.addEventListener('DOMContentLoaded', function() {
    let pendingPoTable = null;
    let deliveredPoTable = null;

    const commonTableOptions = {
        processing: true,
        columns: [
            {
                "searchable": false,
                "orderable": false,
                "className": "text-center align-middle",
                "render": (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1
            },
            { data: "po_number" },
            { data: "supplier_name" },
            { data: "order_date", render: data => new Date(data).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) },
            { data: "total_amount", className: "text-end", render: data => `₱ ${(parseFloat(data) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}` },
            { data: "status", className: "text-center align-middle", render: data => `<span class="badge ${data === 'Delivered' ? 'bg-success' : 'bg-warning'}">${data}</span>` },
            { data: "po_id", orderable: false, className: "text-center align-middle", render: (data, type, row) => {
                if (row.status === 'Delivered') {
                    return `<button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewPoModal" data-po-id="${data}"><i class="bi bi-eye"></i> View</button>`;
                }
                const primaryAction = row.is_fully_received
                    ? `<button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#deliveredPoModal" data-po-id="${data}" data-po-number="${row.po_number}" title="Finalize this PO and generate the IAR"><i class="bi bi-check-circle me-1"></i> Mark as Delivered</button>`
                    : `<button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#receivePoModal" data-po-id="${data}" data-po-number="${row.po_number}" title="Receive items from this PO"><i class="bi bi-box-arrow-in-down me-1"></i> Receive Items</button>`;
                
                return `
                    <div class="btn-group">
                        ${primaryAction}
                        <button type="button" class="btn btn-sm ${row.is_fully_received ? 'btn-primary' : 'btn-success'} dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="visually-hidden">Toggle Dropdown</span>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#viewPoModal" data-po-id="${data}"><i class="bi bi-eye me-2"></i>View Details</a></li>
                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#editPoModal" data-po-id="${data}"><i class="bi bi-pencil-square me-2"></i>Edit PO</a></li>
                        </ul>
                    </div>`;
            }}
        ],
        order: [[3, 'desc']]
    };

    // Initialize Pending PO Table
    pendingPoTable = $('#pendingPoListTable').DataTable({
        ...commonTableOptions,
        ajax: { url: "api/po_list_api.php?status=Pending", dataSrc: "data" }
    });

    // --- Logic for Tab Switching ---
    const deliveredTab = document.getElementById('delivered-po-tab');
    if (deliveredTab) {
        deliveredTab.addEventListener('shown.bs.tab', function () {
            if (!deliveredPoTable) {
                deliveredPoTable = $('#deliveredPoListTable').DataTable({
                    ...commonTableOptions,
                    ajax: { url: "api/po_list_api.php?status=Delivered", dataSrc: "data" }
                });
            } else {
                deliveredPoTable.ajax.reload();
            }
        });
    }

    // --- Logic for "View PO" modal ---
    const viewPoModal = document.getElementById('viewPoModal');
    if (!viewPoModal) return;
 
    viewPoModal.addEventListener('show.bs.modal', async function (event) {
        const button = event.relatedTarget;
        const poId = button.getAttribute('data-po-id');
        const modalBody = viewPoModal.querySelector('.modal-body');
        
        modalBody.innerHTML = `<div class="text-center p-5"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>`;
 
        try {
            const response = await fetch(`api/po_view.php?id=${poId}`);
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(errorText || 'Server error');
            }
            modalBody.innerHTML = await response.text();
        } catch (error) {
            modalBody.innerHTML = `<div class="alert alert-danger"><strong>Error:</strong> Failed to load Purchase Order details. <br><pre>${error.message}</pre></div>`;
            console.error('Error fetching PO view:', error);
        }
    });
});
 
function printPoView() { // Keep as a global function for the inline `onclick`
    const content = document.getElementById('po-view-content');
    if (content) {
        const printWindow = window.open('', '', 'height=800,width=1000');
        printWindow.document.write('<html><head><title>Print Purchase Order</title>');
        printWindow.document.write(content.querySelector('style')?.outerHTML || '');
        printWindow.document.write('</head><body>');
        printWindow.document.write(content.innerHTML);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.focus();
        setTimeout(() => { printWindow.print(); printWindow.close(); }, 250);
    }
}