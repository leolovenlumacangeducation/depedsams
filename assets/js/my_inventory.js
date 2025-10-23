// My Inventory Enhancement Script
document.addEventListener('DOMContentLoaded', function() {
    // Initialize variables
    let currentView = 'card'; // or 'list'
    let currentItems = {
        ppe: [],
        sep: [],
        consumables: []
    };
    let filters = {
        search: '',
        category: '',
        status: ''
    };

    // Initialize Bootstrap components
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Search functionality
    const searchInput = document.getElementById('inventory-search');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function(e) {
            filters.search = e.target.value.toLowerCase();
            applyFilters();
        }, 300));
    }

    // Category filter
    const categoryFilter = document.getElementById('category-filter');
    if (categoryFilter) {
        categoryFilter.addEventListener('change', function(e) {
            filters.category = e.target.value;
            applyFilters();
        });
    }

    // Status filter
    const statusFilter = document.getElementById('status-filter');
    if (statusFilter) {
        statusFilter.addEventListener('change', function(e) {
            filters.status = e.target.value;
            applyFilters();
        });
    }

    // View toggle
    const cardViewBtn = document.getElementById('card-view-btn');
    const listViewBtn = document.getElementById('list-view-btn');
    
    if (cardViewBtn && listViewBtn) {
        cardViewBtn.addEventListener('click', () => setView('card'));
        listViewBtn.addEventListener('click', () => setView('list'));
    }

    // Function to set view mode
    function setView(mode) {
        currentView = mode;
        if (mode === 'card') {
            cardViewBtn?.classList.add('active');
            listViewBtn?.classList.remove('active');
        } else {
            cardViewBtn?.classList.remove('active');
            listViewBtn?.classList.add('active');
        }
        refreshDisplay();
    }

    // Function to apply filters
    function applyFilters() {
        const activeTab = document.querySelector('.tab-pane.active');
        if (!activeTab) return;

        const itemsContainer = activeTab.querySelector('.items-container');
        if (!itemsContainer) return;

        const items = currentItems[activeTab.id.replace('-panel', '')];
        const filteredItems = items.filter(item => {
            const matchesSearch = !filters.search || 
                item.description?.toLowerCase().includes(filters.search) ||
                item.property_number?.toLowerCase().includes(filters.search);
            
            const matchesCategory = !filters.category || 
                item.category_id === filters.category;
            
            const matchesStatus = !filters.status || 
                item.status === filters.status;
            
            return matchesSearch && matchesCategory && matchesStatus;
        });

        displayItems(filteredItems, itemsContainer);
        updateSummary(filteredItems);
    }

    // Function to update summary
    function updateSummary(items) {
        const countElement = document.getElementById('items-count');
        const valueElement = document.getElementById('total-value');
        
        if (countElement) {
            countElement.textContent = items.length;
        }
        
        if (valueElement) {
            const total = items.reduce((sum, item) => sum + (parseFloat(item.unit_cost) || 0), 0);
            valueElement.textContent = total.toLocaleString('en-PH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
    }

    // Utility function for debouncing
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Load categories for filter
    function loadCategories() {
        fetch('api/category_api.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && categoryFilter) {
                    data.categories.forEach(category => {
                        const option = document.createElement('option');
                        option.value = category.category_id;
                        option.textContent = category.name;
                        categoryFilter.appendChild(option);
                    });
                }
            })
            .catch(console.error);
    }

    // Initialize
    loadCategories();
    setView('card'); // Start with card view
});