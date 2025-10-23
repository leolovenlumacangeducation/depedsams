document.addEventListener('DOMContentLoaded', function() {
    let iarListTable = null;

    // Initialize the DataTable
    iarListTable = $('#iarListTable').DataTable({
        processing: true,
        ajax: { 
            url: "api/iar_list_api.php", 
            dataSrc: "data" 
        },
        columns: [
            { data: "po_number" },
            { data: "supplier_name" },
            { data: "order_date", render: data => new Date(data).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) },
            { data: "total_amount", className: "text-end", render: data => `₱ ${(parseFloat(data) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}` },
            { data: "status", className: "text-center align-middle", render: data => `<span class="badge bg-success">${data}</span>` },
            { 
                data: "po_id", 
                orderable: false, 
                className: "text-center align-middle", 
                render: (data, type, row) => {
                    return `<button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#iarModal" data-po-id="${data}"><i class="bi bi-eye"></i> View IAR</button>`;
                }
            }
        ],
        order: [[2, 'desc']] // Default sort by Order Date descending
    });

    // --- Logic for "View IAR" modal ---
    const iarModal = document.getElementById('iarModal');
    if (iarModal) {
        iarModal.addEventListener('show.bs.modal', async function (event) {
            const button = event.relatedTarget;
            const poId = button.getAttribute('data-po-id');
            const modalBody = iarModal.querySelector('.modal-body');
            
            // Set PO ID for the print function
            document.getElementById('iar_po_id').value = poId;

            modalBody.innerHTML = `<div class="text-center p-5"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>`;
    
            try {
                const response = await fetch(`api/iar_view.php?id=${poId}`);
                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(errorText || 'Server error');
                }
                modalBody.innerHTML = await response.text();
            } catch (error) {
                modalBody.innerHTML = `<div class="alert alert-danger"><strong>Error:</strong> Failed to load IAR details. <br><pre>${error.message}</pre></div>`;
                console.error('Error fetching IAR view:', error);
            }
        });
    }
});