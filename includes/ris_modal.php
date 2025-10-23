<!-- RIS View Modal -->
<div class="modal fade" id="risModal" tabindex="-1" aria-labelledby="risModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="risModalLabel">Requisition and Issue Slip (RIS)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="ris-modal-body">
                <!-- Content will be loaded here by JavaScript -->
            </div>
            <div class="modal-footer">
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Close
                    </button>
                    <button type="button" class="btn btn-success" onclick="downloadRisPdf()">
                        <i class="bi bi-file-pdf"></i> Download PDF
                    </button>
                    <button type="button" class="btn btn-primary" onclick="printRisView()">
                        <i class="bi bi-printer"></i> Print RIS
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function printRisView() {
        const content = document.getElementById('ris-view-content').innerHTML;
        const printWindow = window.open('', '', 'height=800,width=1000');
        printWindow.document.write('<html><head><title>Print RIS</title>');
        printWindow.document.write(document.getElementById('ris-view-content').previousElementSibling.outerHTML); // Include style
        printWindow.document.write('</head><body>' + content + '</body></html>');
        printWindow.document.close();
        printWindow.focus();
        setTimeout(() => { printWindow.print(); printWindow.close(); }, 250);
    }

    function downloadRisPdf() {
        // Get the issuance ID from the content
        const risNumber = document.querySelector('#ris-view-content .ris-number')?.textContent?.trim();
        if (!risNumber) {
            showToast('Could not determine RIS number', 'Error', 'danger');
            return;
        }

        // Create a form and submit it to trigger the PDF download
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'api/ris_pdf.php';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ris_number';
        input.value = risNumber;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }
</script>