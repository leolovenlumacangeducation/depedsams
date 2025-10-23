<!-- Stock Card PDF View Modal -->
<div class="modal fade" id="stockCardPdfModal" tabindex="-1" aria-labelledby="stockCardPdfModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="stockCardPdfModalLabel">Stock Card (Appendix 58)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="stock-card-pdf-modal-body">
                <!-- Content will be loaded here by JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="printStockCardPdf()"><i class="bi bi-printer"></i> Print</button>
            </div>
        </div>
    </div>
</div>

<script>
    function printStockCardPdf() {
        const content = document.getElementById('sc-pdf-content').innerHTML;
        const style = document.getElementById('sc-pdf-content').previousElementSibling.outerHTML;
        const printWindow = window.open('', '', 'height=800,width=1000');
        printWindow.document.write('<html><head><title>Print Stock Card</title>' + style + '</head><body>' + content + '</body></html>');
        printWindow.document.close();
        printWindow.focus();
        setTimeout(() => { printWindow.print(); printWindow.close(); }, 250);
    }
</script>