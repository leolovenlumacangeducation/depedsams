// User Dashboard Main JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Toggle sidebar on mobile
    const sidebarToggle = document.querySelector('.navbar-toggler');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            document.querySelector('#sidebarMenu').classList.toggle('show');
        });
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', (e) => {
        const sidebar = document.querySelector('#sidebarMenu');
        const toggle = document.querySelector('.navbar-toggler');
        
        if (sidebar && toggle) {
            if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
                if (window.innerWidth < 768 && sidebar.classList.contains('show')) {
                    sidebar.classList.remove('show');
                }
            }
        }
    });

    // Update inventory badge count
    function updateInventoryCount() {
        fetch('api/dashboard_api.php?action=inventory_count')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const badge = document.querySelector('#my-inventory-count');
                    if (badge) {
                        badge.textContent = data.data.count;
                        badge.style.display = data.data.count > 0 ? 'inline' : 'none';
                    }
                }
            })
            .catch(error => console.error('Error:', error));
    }

    // Initialize tooltips
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(tooltip => {
        new bootstrap.Tooltip(tooltip);
    });

    // Initial inventory count update
    updateInventoryCount();

    // Refresh inventory count every 5 minutes
    setInterval(updateInventoryCount, 300000);
});