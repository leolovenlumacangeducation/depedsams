// --- Logic for "Mark as Delivered" modal ---
const deliveredPoModal = document.getElementById('deliveredPoModal');
const iarModal = document.getElementById('iarModal');

if (deliveredPoModal && iarModal) {
    // 1. Populate the initial confirmation modal
    deliveredPoModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const poId = button.getAttribute('data-po-id');
        const poNumber = button.getAttribute('data-po-number');
        document.getElementById('delivered_po_id').value = poId;
        document.getElementById('delivered-po-number').textContent = poNumber;
    });

    // 2. Handle the first confirmation click ("Yes, Mark as Delivered")
    const confirmBtn = document.getElementById('confirm-delivered-btn');
    confirmBtn.addEventListener('click', async function() {
        const poId = document.getElementById('delivered_po_id').value;
        const iarModalBody = document.getElementById('iar-modal-body');
        const iarPoIdInput = document.getElementById('iar_po_id');
        
        // Hide the first modal
        bootstrap.Modal.getInstance(deliveredPoModal).hide();

        // Show the IAR modal and set its PO ID
        const iarModalInstance = new bootstrap.Modal(iarModal);
        iarModalInstance.show();
        iarPoIdInput.value = poId;

        // Show spinner and fetch IAR content
        iarModalBody.innerHTML = `<div class="text-center p-5"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>`;
        
        try {
            const response = await fetch(`api/iar_view.php?id=${poId}`);
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(errorText || 'Server error');
            }
            const html = await response.text();
            iarModalBody.innerHTML = html;
        } catch (error) {
            iarModalBody.innerHTML = `<div class="alert alert-danger"><strong>Error:</strong> Failed to load IAR details. <br><pre>${error.message}</pre></div>`;
        }
    });

    // 3. Handle the FINAL confirmation click in the IAR modal
    const confirmAcceptanceBtn = document.getElementById('confirm-acceptance-btn');
    confirmAcceptanceBtn.addEventListener('click', function() {
        const poId = document.getElementById('iar_po_id').value;

        // UI feedback for the final button
        confirmAcceptanceBtn.disabled = true;
        confirmAcceptanceBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Confirming...`;

        // Now, call the original API to mark the PO as delivered
        fetch('api/po_delivered.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ po_id: poId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'Success', 'success');
                bootstrap.Modal.getInstance(iarModal).hide();
                // Reload the pending table to remove the item.
                $('#pendingPoListTable').DataTable().ajax.reload();
                // Only reload the delivered table if it has already been initialized.
                if ($.fn.DataTable.isDataTable('#deliveredPoListTable')) {
                    $('#deliveredPoListTable').DataTable().ajax.reload();
                }
            } else {
                throw new Error(data.message || 'An unknown error occurred.');
            }
        })
        .catch(error => {
            showToast(`Error during final confirmation: ${error.message}`, 'Confirmation Failed', 'danger');
        })
        .finally(() => {
            // Restore button state
            confirmAcceptanceBtn.disabled = false;
            confirmAcceptanceBtn.innerHTML = 'Confirm Acceptance & Mark Delivered';
        });
    });
}

// --- Global function for printing IAR from modal ---
function printIarView() {
    const content = document.getElementById('iar-view-content');
    if (content) {
        const printWindow = window.open('', '', 'height=800,width=1000');
        printWindow.document.write('<html><head><title>Print IAR</title>');
        const styles = content.querySelector('style');
        if (styles) {
            printWindow.document.write(styles.outerHTML);
        }
        printWindow.document.write('</head><body>');
        printWindow.document.write(content.innerHTML);
        printWindow.document.write('</body></html>');
        
        printWindow.document.close();
        printWindow.focus();
        
        setTimeout(() => {
            printWindow.print();
            printWindow.close();
        }, 250);
    }
}