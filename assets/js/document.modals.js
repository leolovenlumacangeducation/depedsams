/**
 * document.modals.js
 * Handles all document viewing modal functionality
 */

// IIRUP Modal handling
function showIirupModal(iirupId) {
    const modal = new bootstrap.Modal(document.getElementById('iirupViewModal'));
    const modalBody = document.getElementById('iirup-modal-body');
    const printBtn = document.getElementById('print-iirup-modal-btn');

    // Show loading state
    modalBody.innerHTML = `
        <div class="text-center p-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading IIRUP details...</p>
        </div>
    `;

    // Load IIRUP content
    fetch(`api/iirup_view.php?id=${iirupId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(html => {
            modalBody.innerHTML = html;
            modal.show();

            // Update print button functionality
            if (printBtn) {
                printBtn.onclick = () => {
                    const content = document.getElementById('iirup-modal-body').innerHTML;
                    const printWindow = window.open('', '_blank');
                    printWindow.document.write(`
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <title>Print IIRUP</title>
                            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                            <style>
                                @media print {
                                    @page { margin: 0.5in; }
                                    body { padding: 0; }
                                }
                            </style>
                        </head>
                        <body>
                            ${content}
                            <script>window.onload = () => window.print();</script>
                        </body>
                        </html>
                    `);
                    printWindow.document.close();
                };
            }
        })
        .catch(error => {
            modalBody.innerHTML = `
                <div class="alert alert-danger">
                    Error loading IIRUP document: ${error.message}
                </div>
            `;
        });
}

// Other document modal functions can be implemented as needed
function showParModal(parId) {
    const modal = new bootstrap.Modal(document.getElementById('parViewModal'));
    const modalBody = document.getElementById('par-view-modal-body');

    // Show loading state
    modalBody.innerHTML = `
        <div class="text-center p-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading PAR details...</p>
        </div>
    `;

    // Load PAR content
    fetch(`api/par_view.php?id=${parId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(html => {
            modalBody.innerHTML = html;
            modal.show();
        })
        .catch(error => {
            modalBody.innerHTML = `
                <div class="alert alert-danger">
                    Error loading PAR document: ${error.message}
                </div>
            `;
        });
}

// ICS Modal handling
function showIcsModal(icsId) {
    const modal = new bootstrap.Modal(document.getElementById('icsViewModal'));
    const modalBody = document.getElementById('ics-modal-body');

    // Show loading state
    modalBody.innerHTML = `
        <div class="text-center p-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading ICS details...</p>
        </div>
    `;

    // Load ICS content
    fetch(`api/ics_view.php?id=${icsId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(html => {
            modalBody.innerHTML = html;
            modal.show();
        })
        .catch(error => {
            modalBody.innerHTML = `
                <div class="alert alert-danger">
                    Error loading ICS document: ${error.message}
                </div>
            `;
        });
}

// RIS Modal handling
function showRisModal(issuanceId) {
    const modal = new bootstrap.Modal(document.getElementById('risViewModal'));
    const modalBody = document.getElementById('ris-modal-body');

    // Show loading state
    modalBody.innerHTML = `
        <div class="text-center p-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading RIS details...</p>
        </div>
    `;

    // Load RIS content
    fetch(`api/ris_view.php?id=${issuanceId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(html => {
            modalBody.innerHTML = html;
            modal.show();
        })
        .catch(error => {
            modalBody.innerHTML = `
                <div class="alert alert-danger">
                    Error loading RIS document: ${error.message}
                </div>
            `;
        });
}
// RPCI Modal handling
function showRpciModal(rpciId) {
    const modal = new bootstrap.Modal(document.getElementById('rpciViewModal'));
    const modalBody = document.getElementById('rpci-modal-body');

    // Show loading state
    modalBody.innerHTML = `
        <div class="text-center p-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading RPCI details...</p>
        </div>
    `;

    // Load RPCI content
    fetch(`api/rpci_view.php?id=${rpciId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(html => {
            modalBody.innerHTML = html;
            modal.show();
        })
        .catch(error => {
            modalBody.innerHTML = `
                <div class="alert alert-danger">
                    Error loading RPCI document: ${error.message}
                </div>
            `;
        });
}
// RPCPPE Modal handling
function showRpcppeModal(rpcppeId) {
    const modal = new bootstrap.Modal(document.getElementById('rpcppeViewModal'));
    const modalBody = document.getElementById('rpcppe-modal-body');

    // Show loading state
    modalBody.innerHTML = `
        <div class="text-center p-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading RPCPPE details...</p>
        </div>
    `;

    // Load RPCPPE content
    fetch(`api/rpcppe_view.php?id=${rpcppeId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(html => {
            modalBody.innerHTML = html;
            modal.show();
        })
        .catch(error => {
            modalBody.innerHTML = `
                <div class="alert alert-danger">
                    Error loading RPCPPE document: ${error.message}
                </div>
            `;
        });
}
// PO Modal handling
function showPoViewModal(poId) {
    const modal = new bootstrap.Modal(document.getElementById('viewPoModal'));
    const modalBody = modal._element.querySelector('.modal-body');

    // Show loading state
    modalBody.innerHTML = `
        <div class="text-center p-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading Purchase Order details...</p>
        </div>
    `;

    // Load PO content
    fetch(`api/po_view.php?id=${poId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(html => {
            modalBody.innerHTML = html;
            modal.show();
        })
        .catch(error => {
            modalBody.innerHTML = `
                <div class="alert alert-danger">
                    Error loading Purchase Order document: ${error.message}
                </div>
            `;
        });
}

// Print PO function
function printPoView() {
    const printContent = document.querySelector('#viewPoModal .modal-body').innerHTML;
    const originalContent = document.body.innerHTML;
    
    document.body.innerHTML = `
        <div class="container-fluid">
            ${printContent}
        </div>
    `;
    
    window.print();
    document.body.innerHTML = originalContent;
    
    // Reinitialize the modal after restoring content
    const modal = new bootstrap.Modal(document.getElementById('viewPoModal'));
    if (document.querySelector('#viewPoModal.show')) {
        modal.show();
    }
}

// Utility function to escape HTML
function escapeHTML(str) {
    // Ensure we're working with a string to safely call replace
    const s = String(str || '');
    return s.replace(/[&<>'"]/g, tag => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        "'": '&#39;',
        '"': '&quot;'
    }[tag]));
}