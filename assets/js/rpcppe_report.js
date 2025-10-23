document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.getElementById('rpcppeFilterForm');
    const reportArea = document.getElementById('report-content-area');
    const reportTemplate = document.getElementById('rpcppe-report-template');

    // Set default date to today
    document.getElementById('as_of_date_rpcppe').valueAsDate = new Date();

    filterForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        // Show loading spinner
        reportArea.innerHTML = `<div class="text-center p-5"><div class="spinner-border" role="status"></div></div>`;

        try {
            // Fetch data from the RPCPPE API
            const response = await fetch(`api/rpcppe_report_api.php`);
            const result = await response.json();

            if (!response.ok) throw new Error(result.error || 'Failed to generate report.');

            renderReport(result);

        } catch (error) {
            // Display error message
            reportArea.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
        }
    });

    function renderReport(data) {
        const templateContent = reportTemplate.content.cloneNode(true);
        const itemsTbody = templateContent.querySelector('[data-template-id="items_tbody"]');

        // Populate main items table (Property, Plant, and Equipment)
        itemsTbody.innerHTML = data.items.map(item => `
            <tr data-ppe-id="${item.ppe_id}">
                <td></td> <!-- Article -->
                <td>${escapeHTML(item.description)}</td>
                <td>${escapeHTML(item.property_number)}</td>
                <td>${escapeHTML(item.unit_name)}</td>
                <td class="text-end">${parseFloat(item.unit_cost).toFixed(2)}</td>
                <td class="text-end balance-per-card">1</td>
                <td><input type="number" class="form-control form-control-sm text-end on-hand-count" value="1" min="0" max="1"></td>
                <td class="text-end shortage-qty">0</td>
                <td class="text-end shortage-value">0.00</td>
                <td><input type="text" class="form-control form-control-sm remarks-input" value="${escapeHTML(item.current_condition)}${item.assigned_to ? ' - ' + escapeHTML(item.assigned_to) : ''}"></td>
            </tr>
        `).join('');

        // Populate header and footer info. Using your school name for context:
        const asOfDate = new Date(document.getElementById('as_of_date_rpcppe').value + 'T00:00:00');
        templateContent.querySelector('[data-template-id="as_of_date"]').textContent = `As of ${asOfDate.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })}`;
        templateContent.querySelector('[data-template-id="school_name"]').textContent = data.school_name;
        templateContent.querySelector('[data-template-id="custodian_name"]').textContent = data.property_custodian;
        templateContent.querySelector('[data-template-id="approving_officer_name"]').textContent = data.approving_officer;

        // Clear the report area and append the populated template
        reportArea.innerHTML = '';
        reportArea.appendChild(templateContent);

        // Add print functionality
        document.getElementById('print-rpcppe-btn').addEventListener('click', printReport);
        // Add save functionality
        document.getElementById('save-rpcppe-btn').addEventListener('click', saveCount);
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

    async function saveCount() {
        if (!confirm('Are you sure you want to save this physical count as a formal RPCPPE document?')) {
            return;
        }

        const countedItems = [];
        document.querySelectorAll('#printable-rpcppe-report tbody tr').forEach(row => {
            countedItems.push({
                ppe_id: row.dataset.ppeId,
                on_hand_count: row.querySelector('.on-hand-count').value,
                shortage_qty: row.querySelector('.shortage-qty').textContent,
                shortage_value: row.querySelector('.shortage-value').textContent,
                remarks: row.querySelector('.remarks-input').value
            });
        });

        const asOfDate = document.getElementById('as_of_date_rpcppe').value;

        try {
            const response = await fetch('api/rpcppe_save_count_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ counted_items: countedItems, as_of_date: asOfDate })
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'Failed to save count.');
            showToast(result.message, 'Success', 'success');
        } catch (error) {
            showToast(`Error: ${error.message}`, 'Save Failed', 'danger');
        }
    }

    function printReport() {
        const content = document.getElementById('printable-rpcppe-report');
        const printWindow = window.open('', '', 'height=800,width=1000');
        
        printWindow.document.write('<html><head><title>Print RPCPPE Report</title>');
        // Include external Bootstrap CSS and internal style for print layout
        printWindow.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">');
        printWindow.document.write(content.querySelector('style').outerHTML);
        printWindow.document.write('</head><body>');
        printWindow.document.write(content.innerHTML);
        printWindow.document.write('</body></html>');
        
        printWindow.document.close();
        printWindow.focus();
        // Wait briefly for content to load before printing
        setTimeout(() => { printWindow.print(); printWindow.close(); }, 500);
    }

    // Utility function to safely escape HTML content
    function escapeHTML(str) {
        if (str === null || str === undefined) return '';
        const p = document.createElement("p");
        p.textContent = String(str);
        return p.innerHTML;
    }
});