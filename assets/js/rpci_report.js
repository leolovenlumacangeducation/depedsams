document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.getElementById('rpciFilterForm');
    const reportArea = document.getElementById('report-content-area');
    const reportTemplate = document.getElementById('rpci-report-template');

    // Set default date to today
    document.getElementById('as_of_date').valueAsDate = new Date();

    filterForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        reportArea.innerHTML = `<div class="text-center p-5"><div class="spinner-border" role="status"></div></div>`;

        try {
            const response = await fetch(`api/rpci_report_api.php`);
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

        // Populate main items table (Physical Inventory)
        itemsTbody.innerHTML = data.items.map(item => {
            const balanceQty = parseInt(item.current_stock, 10);
            return `
            <tr data-consumable-id="${item.consumable_id}">
                <td></td> <!-- Article -->
                <td>${escapeHTML(item.description)}</td>
                <td>${escapeHTML(item.stock_number)}</td>
                <td>${escapeHTML(item.unit_name)}</td>
                <td class="text-end">${parseFloat(item.unit_cost).toFixed(2)}</td>
                <td class="text-end balance-per-card">${balanceQty}</td>
                <td><input type="number" class="form-control form-control-sm text-end on-hand-count" value="${balanceQty}" min="0"></td>
                <td class="text-end shortage-qty">0</td>
                <td class="text-end shortage-value">0.00</td>
                <td><input type="text" class="form-control form-control-sm remarks-input"></td>
            </tr>
        `}).join('');

        // Populate header and footer info
        const asOfDate = new Date(document.getElementById('as_of_date').value + 'T00:00:00');
        templateContent.querySelector('[data-template-id="as_of_date"]').textContent = `As of ${asOfDate.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })}`;
        templateContent.querySelector('[data-template-id="school_name"]').textContent = data.school_name;
        templateContent.querySelector('[data-template-id="custodian_name"]').textContent = data.property_custodian;
        templateContent.querySelector('[data-template-id="approving_officer_name"]').textContent = data.approving_officer;

        reportArea.innerHTML = '';
        reportArea.appendChild(templateContent);

        // Add print functionality
        document.getElementById('print-rpci-btn').addEventListener('click', printReport);
        // Add save functionality
        document.getElementById('save-adjustments-btn').addEventListener('click', saveAdjustments);
        // Add event listeners for dynamic calculations
        reportArea.addEventListener('input', handleRowInput);
    }

    function handleRowInput(e) {
        if (e.target.classList.contains('on-hand-count')) {
            const row = e.target.closest('tr');
            const balancePerCard = parseInt(row.querySelector('.balance-per-card').textContent, 10);
            const onHandCount = parseInt(e.target.value, 10) || 0;
            const unitCost = parseFloat(row.cells[4].textContent);

            const shortageQty = onHandCount - balancePerCard;
            const shortageValue = shortageQty * unitCost;

            row.querySelector('.shortage-qty').textContent = shortageQty;
            row.querySelector('.shortage-value').textContent = shortageValue.toFixed(2);
        }
    }

    async function saveAdjustments() {
        if (!confirm('Are you sure you want to save these counts and adjust the current stock levels? This action cannot be undone.')) {
            return;
        }

        const adjustments = [];
        document.querySelectorAll('#printable-rpci-report tbody tr').forEach(row => {
            const onHandInput = row.querySelector('.on-hand-count');
            const remarksInput = row.querySelector('.remarks-input');
            if (onHandInput) {
                adjustments.push({
                    consumable_id: row.dataset.consumableId,
                    physical_count: onHandInput.value,
                    balance_per_card: row.querySelector('.balance-per-card').textContent,
                    shortage_qty: row.querySelector('.shortage-qty').textContent,
                    shortage_value: row.querySelector('.shortage-value').textContent,
                    remarks: remarksInput ? remarksInput.value : ''
                });
            }
        });

        const asOfDate = document.getElementById('as_of_date').value;

        try {
            const response = await fetch('api/rpci_adjust_stock_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ adjustments: adjustments, as_of_date: asOfDate })
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'Failed to save adjustments.');

            showToast(result.message, 'Success', 'success');
            // Optionally, refresh the report
            filterForm.requestSubmit();

        } catch (error) {
            showToast(`Error: ${error.message}`, 'Save Failed', 'danger');
        }
    }

    function printReport() {
        const content = document.getElementById('printable-rpci-report');
        const printWindow = window.open('', '', 'height=800,width=1000');
        printWindow.document.write('<html><head><title>Print RPCI Report</title>');
        printWindow.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">');
        printWindow.document.write(content.querySelector('style').outerHTML);
        printWindow.document.write('</head><body>');
        printWindow.document.write(content.innerHTML);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.focus();
        setTimeout(() => { printWindow.print(); printWindow.close(); }, 500);
    }

    function escapeHTML(str) {
        if (str === null || str === undefined) return '';
        const p = document.createElement("p");
        p.textContent = String(str);
        return p.innerHTML;
    }
});