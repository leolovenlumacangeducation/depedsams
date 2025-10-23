/**
 * dashboard.js
 * Handles fetching and displaying data for the main dashboard.
 */
document.addEventListener('DOMContentLoaded', function() {
    const loader = document.getElementById('dashboard-loader');
    const content = document.getElementById('dashboard-content');
    const refreshBtn = document.getElementById('refreshDashboardBtn');
    const lowStockBadge = document.getElementById('low-stock-badge');

    let assetsChart = null; // To hold the Chart.js instance

    /**
     * Fetches data from the dashboard API and updates the UI.
     */
    async function loadDashboardData() {
        // Show loader and hide content
        loader.classList.remove('d-none');
        content.classList.add('d-none');
        refreshBtn.disabled = true;

        try {
            const response = await fetch('api/dashboard_api.php');
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`Network response was not ok: ${errorText}`);
            }
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || 'API returned an error.');
            }

            // Update UI elements with the fetched data
            updateSummaryCards(data.asset_counts);
            updateValueCards(data.asset_values);
            updateLowStockTable(data.low_stock_items);
            updateActivityFeed(data.recent_activity);
            updateAssetsByConditionChart(data.assets_by_condition);

            // Show content and hide loader
            content.classList.remove('d-none');
            loader.classList.add('d-none');

        } catch (error) {
            loader.innerHTML = `<div class="alert alert-danger">Failed to load dashboard data: ${error.message}</div>`;
        } finally {
            refreshBtn.disabled = false;
        }
    }

    /**
     * Updates the summary cards with asset values (currency formatted).
     * @param {object} values - The asset_values object from the API.
     */
    function updateValueCards(values) {
        const fmt = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });
        document.getElementById('total-value').textContent = fmt.format(values.total || 0);
        document.getElementById('ppe-value').textContent = fmt.format(values.ppe || 0);
        document.getElementById('sep-value').textContent = fmt.format(values.sep || 0);
        document.getElementById('consumables-value').textContent = fmt.format(values.consumable || 0);
    }

    /**
     * Updates the summary cards with asset counts.
     * @param {object} counts - The asset_counts object from the API.
     */
    function updateSummaryCards(counts) {
        document.getElementById('consumable-count').textContent = counts.consumable || 0;
        document.getElementById('sep-count').textContent = counts.sep || 0;
        document.getElementById('ppe-count').textContent = counts.ppe || 0;
    }

    /**
     * Populates the low-stock items table.
     * @param {Array} items - The low_stock_items array from the API.
     */
    function updateLowStockTable(items) {
        const tbody = document.getElementById('low-stock-table-body');
        tbody.innerHTML = ''; // Clear existing rows

        if (items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted p-3">No low-stock items. Good job!</td></tr>';
            return;
        }

        // Update sidebar badge
        if (lowStockBadge) {
            lowStockBadge.textContent = items.length;
            lowStockBadge.classList.remove('d-none');
        }



        items.forEach(item => {
            const row = `
                <tr>
                    <td>${item.stock_number || 'N/A'}</td>
                    <td>${item.description}</td>
                    <td class="text-end"><span class="badge bg-danger">${item.current_stock} ${item.unit_name}</span></td>
                </tr>
            `;
            tbody.insertAdjacentHTML('beforeend', row);
        });
    }

    /**
     * Populates the recent activity feed.
     * @param {Array} activities - The recent_activity array from the API.
     */
    function updateActivityFeed(activities) {
        const feedContainer = document.getElementById('recent-activity-feed'); // This is a div with list-group class now
        feedContainer.innerHTML = ''; // Clear existing items

        if (!activities || activities.length === 0) {
            feedContainer.innerHTML = '<div class="text-center text-muted p-3">No recent activity to display.</div>';
            return;
        }

        activities.forEach(activity => {
            const userPhoto = activity.user_photo ? `assets/uploads/users/${activity.user_photo}` : 'assets/uploads/users/default_user.png';
            const timeAgo = formatTimeAgo(new Date(activity.activity_date));

            // Make the entire div clickable by wrapping it in an anchor tag
            const activityItem = `
                <a href="#" class="list-group-item list-group-item-action activity-item" 
                   data-doc-type="${activity.activity_type}" 
                   data-doc-id="${activity.document_id}">
                    <div class="d-flex w-100 justify-content-between">
                        <div class="d-flex align-items-start">
                            <img src="${userPhoto}" class="rounded-circle me-2" alt="User" width="32" height="32" style="object-fit: cover;">
                            <p class="mb-1 small">${activity.description}</p>
                        </div>
                    </div>
                    <small class="text-muted">${timeAgo}</small>
                </a>
            `;
            feedContainer.insertAdjacentHTML('beforeend', activityItem);
        });
    }

    /**
     * Creates or updates the "Assets by Condition" doughnut chart.
     * @param {object} conditions - The assets_by_condition object from the API.
     */
    function updateAssetsByConditionChart(conditions) {
        const ctx = document.getElementById('assetsByConditionChart').getContext('2d');
        
        if (assetsChart) {
            assetsChart.destroy(); // Destroy previous chart instance before creating a new one
        }

        assetsChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(conditions),
                datasets: [{
                    label: 'Asset Count',
                    data: Object.values(conditions),
                    backgroundColor: ['#198754', '#ffc107', '#dc3545', '#6c757d'],
                    hoverOffset: 4
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, layout: { padding: 8 } }
        });
    }

    /**
     * Formats a date into a "time ago" string.
     * @param {Date} date - The date to format.
     * @returns {string} A human-readable time difference.
     */
    function formatTimeAgo(date) {
        const seconds = Math.floor((new Date() - date) / 1000);
        let interval = seconds / 31536000;
        if (interval > 1) return Math.floor(interval) + " years ago";
        interval = seconds / 2592000;
        if (interval > 1) return Math.floor(interval) + " months ago";
        interval = seconds / 86400;
        if (interval > 1) return Math.floor(interval) + " days ago";
        interval = seconds / 3600;
        if (interval > 1) return Math.floor(interval) + " hours ago";
        interval = seconds / 60;
        if (interval > 1) return Math.floor(interval) + " minutes ago";
        return Math.floor(seconds) + " seconds ago";
    }

    // --- Event Listeners ---
    refreshBtn.addEventListener('click', loadDashboardData);

    document.getElementById('recent-activity-feed').addEventListener('click', function(e) {
        const item = e.target.closest('.activity-item');
        if (!item) return;

        e.preventDefault();
        const docType = item.dataset.docType;
        const docId = item.dataset.docId;

        // Call the appropriate modal function based on the document type
        switch(docType) {
            case 'IAR':
                // The IAR view is based on PO ID.
                showPoViewModal(docId); // Assuming IAR is part of the PO view
                break;
            case 'RIS':
                showRisModal(docId);
                break;
            case 'ICS':
                showIcsModal(docId);
                break;
            case 'PAR':
                showParModal(docId);
                break;
            case 'IIRUP':
                showIirupModal(docId);
                break;
        }
    });

    // --- Initial Load ---
    loadDashboardData();
});