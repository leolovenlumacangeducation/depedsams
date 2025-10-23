/**
 * iirup_report.js
 * Handles the generation and display of IIRUP reports.
 */
document.addEventListener('DOMContentLoaded', function() {
    const reportContentArea = document.getElementById('report-content-area');
    const iirupReportTemplate = document.getElementById('iirup-report-template');
    const selectIirupDoc = document.getElementById('selectIirupDoc');
    const viewSelectedIirupBtn = document.getElementById('viewSelectedIirupBtn');
    const printIirupReportBtn = document.getElementById('printIirupReportBtn');
    const allIirupDocsTable = $('#allIirupDocsTable').DataTable();

    // --- Functions ---
    async function loadIirupDocumentsForSelection() {
        try {
            const response = await fetch('api/iirup_api.php');
            const result = await response.json();

            if (!result.success) {
                showToast(result.message, 'Error', 'danger');
                return;
            }

            selectIirupDoc.innerHTML = '<option value="">-- Select an IIRUP --</option>';
            result.data.forEach(doc => {
                const option = document.createElement('option');
                option.value = doc.iirup_id;
                option.textContent = `${doc.iirup_number} (As of ${new Date(doc.as_of_date).toLocaleDateString()})`;
                selectIirupDoc.appendChild(option);
            });

            // Populate the table of all IIRUP documents
            allIirupDocsTable.clear().draw();
            result.data.forEach(doc => {
                const actions = `
                    <button type="button" class="btn btn-sm btn-info view-iirup-btn" data-iirup-id="${doc.iirup_id}" title="View IIRUP"><i class="bi bi-eye"></i></button>
                `;
                allIirupDocsTable.row.add([
                    escapeHTML(doc.iirup_number),
                    new Date(doc.as_of_date).toLocaleDateString(),
                    escapeHTML(doc.disposal_method || 'N/A'),
                    escapeHTML(doc.status),
                    escapeHTML(doc.created_by),
                    actions
                ]).draw(false);
            });

        } catch (error) {
            showToast(`Error loading IIRUP documents: ${error.message}`, 'Error', 'danger');
        }
    }

    async function generateIirupReport(iirupId) {
        reportContentArea.innerHTML = `<div class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2">Generating report...</p></div>`;
        try {
            const response = await fetch(`api/iirup_view.php?id=${iirupId}`);
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`Failed to load IIRUP details: ${errorText}`);
            }
            const htmlContent = await response.text();
            reportContentArea.innerHTML = htmlContent;

            // Update print button data-target
            if (printIirupReportBtn) {
                printIirupReportBtn.dataset.targetId = `iirup-document-${iirupId}`;
            }
            if (document.getElementById('print-iirup-template-btn')) {
                document.getElementById('print-iirup-template-btn').dataset.targetId = `iirup-document-${iirupId}`;
            }

        } catch (error) {
            reportContentArea.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
            showToast(`Error generating report: ${error.message}`, 'Error', 'danger');
        }
    }

    // --- Event Listeners ---
    if (selectIirupDoc) {
        selectIirupDoc.addEventListener('change', function() {
            viewSelectedIirupBtn.disabled = !this.value;
        });
    }

    if (viewSelectedIirupBtn) {
        viewSelectedIirupBtn.addEventListener('click', function() {
            const selectedId = selectIirupDoc.value;
            if (selectedId) {
                generateIirupReport(selectedId);
            } else {
                showToast('Please select an IIRUP document.', 'Warning', 'warning');
            }
        });
    }

    // Handle direct URL access with iirup_id
    const urlParams = new URLSearchParams(window.location.search);
    const iirupIdFromUrl = urlParams.get('iirup_id');
    if (iirupIdFromUrl) {
        generateIirupReport(iirupIdFromUrl);
    } else {
        // Load IIRUPs for selection if not viewing a specific one
        loadIirupDocumentsForSelection();
    }

    // Event listener for printing the displayed report
    if (printIirupReportBtn) {
        printIirupReportBtn.addEventListener('click', function() {
            const targetId = this.dataset.targetId;
            if (targetId) {
                printDocument(targetId, 'IIRUP Report');
            } else {
                showToast('No report content to print.', 'Warning', 'warning');
            }
        });
    }

    // Event listener for printing from the template button (if it exists)
    if (document.getElementById('print-iirup-template-btn')) {
        document.getElementById('print-iirup-template-btn').addEventListener('click', function() {
            const targetId = this.dataset.targetId;
            if (targetId) {
                printDocument(targetId, 'IIRUP Report');
            } else {
                showToast('No report content to print.', 'Warning', 'warning');
            }
        });
    }

    // Event listener for viewing IIRUP document from the table
    $(document).on('click', '.view-iirup-btn', function() {
        const iirupId = $(this).data('iirup-id');
        // Redirect to this page with the iirup_id parameter
        window.location.href = `iirup_report.php?iirup_id=${iirupId}`;
    });
});