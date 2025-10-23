document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.getElementById('rsmiFilterForm');
    const reportArea = document.getElementById('report-content-area');
    const reportTemplate = document.getElementById('rsmi-report-template');

    filterForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        reportArea.innerHTML = `<div class="text-center p-5"><div class="spinner-border" role="status"></div></div>`;

        const formData = new FormData(filterForm);
        const params = new URLSearchParams(formData);

        try {
            const response = await fetch(`api/rsmi_report_api.php?${params.toString()}`);
            const result = await response.json();

            if (!response.ok) throw new Error(result.error || 'Failed to generate report.');

            renderReport(result);

        } catch (error) {
            reportArea.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
        }
    });

    function renderReport(data) {
        const templateContent = reportTemplate.content.cloneNode(true);
        const itemsTbody = templateContent.querySelector('[data-template-id="items_tbody"]');
        const recapStockTbody = templateContent.querySelector('[data-template-id="recap_stock_tbody"]');
        const recapUacsTbody = templateContent.querySelector('[data-template-id="recap_uacs_tbody"]');

        // Populate main items table
        itemsTbody.innerHTML = data.items.map(item => `
            <tr>
                <td>${escapeHTML(item.ris_number)}</td>
                <td></td> <!-- Responsibility Center Code -->
                <td>${escapeHTML(item.stock_number)}</td>
                <td>${escapeHTML(item.description)}</td>
                <td>${escapeHTML(item.unit_name)}</td>
                <td class="text-end">${item.quantity_issued}</td>
                <td class="text-end">${parseFloat(item.unit_cost).toFixed(2)}</td>
                <td class="text-end">${(item.quantity_issued * item.unit_cost).toFixed(2)}</td>
            </tr>
        `).join('');

        // Populate stock recapitulation
        recapStockTbody.innerHTML = Object.entries(data.recap_stock).map(([stockNo, qty]) => `
            <tr>
                <td>${escapeHTML(stockNo)}</td>
                <td class="text-end">${qty}</td>
            </tr>
        `).join('');

        // Populate UACS recapitulation
        recapUacsTbody.innerHTML = Object.entries(data.recap_uacs).map(([uacsCode, totalCost]) => `
            <tr>
                <td>${escapeHTML(uacsCode)}</td>
                <td class="text-end">${parseFloat(totalCost).toFixed(2)}</td>
            </tr>
        `).join('');

        // Populate header and footer info
        templateContent.querySelector('[data-template-id="entity_name"]').textContent = data.school_name;
        templateContent.querySelector('[data-template-id="report_date"]').textContent = `Date: ${new Date().toLocaleDateString()}`;
        templateContent.querySelector('[data-template-id="custodian_name"]').textContent = data.property_custodian;

        reportArea.innerHTML = '';
        reportArea.appendChild(templateContent);

        // Add print functionality
        document.getElementById('print-rsmi-btn').addEventListener('click', printReport);
    }

    function printReport() {
        const content = document.getElementById('printable-rsmi-report');
        const printWindow = window.open('', '', 'height=800,width=1000');
        printWindow.document.write('<html><head><title>Print RSMI Report</title>');
        printWindow.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">');
        printWindow.document.write(content.querySelector('style').outerHTML);
        printWindow.document.write('</head><body>');
        printWindow.document.write(content.innerHTML);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.focus();
        setTimeout(() => {
            printWindow.print();
            printWindow.close();
        }, 500);
    }

    function escapeHTML(str) {
        if (str === null || str === undefined) return '';
        const p = document.createElement("p");
        p.textContent = String(str);
        return p.innerHTML;
    }
});