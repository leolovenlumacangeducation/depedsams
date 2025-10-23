document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('topSearchInput');
    const resultsBox = document.getElementById('topSearchResults');
    const notifBtn = document.getElementById('notifBtn');
    const notifBadge = document.getElementById('notifBadge');

    // Debounce helper
    function debounce(fn, delay) {
        let t;
        return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), delay); };
    }

    // Theme handling - persist to localStorage and apply class to <body>
    const THEME_KEY = 'sams_theme';
    function saveTheme(theme){
        try{ localStorage.setItem(THEME_KEY, theme); } catch(e){}
    }
    function loadTheme(){
        try{ return localStorage.getItem(THEME_KEY); } catch(e){ return null; }
    }
    function applyTheme(theme){
        document.body.classList.remove('theme-dark','theme-light','theme-grey');
        if(theme) document.body.classList.add('theme-' + theme);
        // set aria-pressed on picker buttons
        document.querySelectorAll('.theme-option').forEach(btn => {
            const t = btn.getAttribute('data-theme');
            btn.setAttribute('aria-pressed', t === theme ? 'true' : 'false');
        });
    }

    // wire theme picker buttons
    function bindThemeOptions(container=document){
        container.querySelectorAll('.theme-option').forEach(btn => {
            // avoid duplicate handlers
            btn.removeEventListener('click', btn._themeHandler || (()=>{}));
            const handler = function(e){
                e.preventDefault();
                const t = this.getAttribute('data-theme');
                saveTheme(t);
                applyTheme(t);
            };
            btn.addEventListener('click', handler);
            btn._themeHandler = handler;
        });
    }
    // initial bind for static theme-option elements
    bindThemeOptions(document);

    // apply saved theme
    const saved = loadTheme();
    if(saved) applyTheme(saved);

    // Typeahead search (calls api/lookup_api.php if available)
    async function performSearch(q) {
        if (!q || q.length < 2) {
            if(resultsBox) resultsBox.classList.add('d-none');
            if(resultsBox) resultsBox.innerHTML = '';
            return;
        }
        try {
            const res = await fetch(`api/lookup_api.php?q=${encodeURIComponent(q)}`);
            if (!res.ok) throw new Error('No lookup API');
            const data = await res.json();
            const items = data.data || [];
            if (!items.length) {
                resultsBox.innerHTML = '<div class="list-group-item">No results</div>';
            } else {
                resultsBox.innerHTML = items.slice(0,6).map(it => `
                    <a href="${it.url || '#'}" class="list-group-item list-group-item-action">${it.label}</a>
                `).join('');
            }
            resultsBox.classList.remove('d-none');
        } catch (err) {
            // Fail silently
            if(resultsBox) resultsBox.classList.add('d-none');
        }
    }

    if (searchInput) {
        const onInput = debounce(e => performSearch(e.target.value.trim()), 250);
        searchInput.addEventListener('input', onInput);

        // close results on blur
        searchInput.addEventListener('blur', function() { setTimeout(() => resultsBox.classList.add('d-none'), 150); });
    }

    // Notifications: use low_stock items as alerts for now
    async function loadNotifications() {
        if (!notifBtn) return;
        try {
            const res = await fetch('api/dashboard_api.php');
            if (!res.ok) throw new Error('Dashboard API not available');
            const json = await res.json();
            if (!json.success) throw new Error('API error');

            const lowStock = json.low_stock_items || [];
            if (notifBadge) {
                if (lowStock.length > 0) {
                    notifBadge.textContent = lowStock.length > 9 ? '9+' : String(lowStock.length);
                    notifBadge.classList.remove('d-none');
                } else {
                    notifBadge.classList.add('d-none');
                }
            }

            // Prepare notifications markup for embedding inside dropdowns
            const notifHtml = lowStock.length ? (
                '<div class="list-group">' + lowStock.slice(0,5).map(i => `
                    <div class="list-group-item small">
                        <strong>${i.stock_number || ''}</strong>: ${i.description || i.item_name || ''} <br>
                        <small class="text-muted">Stock: ${i.current_stock || 0} ${i.unit_name || ''}</small>
                    </div>
                `).join('') + '</div>'
            ) : '<div class="small p-2 text-muted">No alerts</div>';

            // Populate user dropdown notifications area if present
            const userNotif = document.getElementById('userDropdownNotifications');
            if (userNotif) userNotif.innerHTML = notifHtml;

            // Also populate mobile consolidated menu if present
            const mobileMenu = document.getElementById('mobileControlsMenu');
            if (mobileMenu) {
                const notifSection = lowStock.length ? (
                    lowStock.slice(0,5).map(i => `
                        <li class="px-3 py-2 small">
                            <strong>${i.stock_number || ''}</strong>: ${i.description || i.item_name || ''}<br>
                            <small class="text-muted">Stock: ${i.current_stock || 0} ${i.unit_name || ''}</small>
                        </li>
                    `).join('')
                ) : '<li class="px-3 py-2 small text-muted">No alerts</li>';

                const themeSection = `
                    <li class="dropdown-header">Theme</li>
                    <li><button class="dropdown-item theme-option" data-theme="dark" type="button">Dark</button></li>
                    <li><button class="dropdown-item theme-option" data-theme="light" type="button">Light</button></li>
                    <li><button class="dropdown-item theme-option" data-theme="grey" type="button">Grey (Minimal)</button></li>
                    <li><hr class="dropdown-divider"></li>
                `;

                const profileLinks = `
                    <li><a class="dropdown-item" href="my_profile.php"><i class="bi bi-person me-2"></i> My Profile</a></li>
                    <li><a class="dropdown-item" href="receive.php"><i class="bi bi-box-arrow-in-down me-2"></i> Receive Items</a></li>
                    <li><a class="dropdown-item" href="scan.php"><i class="bi bi-qr-code-scan me-2"></i> Scan Items</a></li>
                    <li><a class="dropdown-item" href="my_inventory.php"><i class="bi bi-person-workspace me-2"></i> My Inventory</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Sign Out</a></li>
                `;

                mobileMenu.innerHTML = `
                    <li class="dropdown-header">Notifications</li>
                    ${notifSection}
                    <li><hr class="dropdown-divider"></li>
                    ${themeSection}
                    ${profileLinks}
                `;
                // bind theme buttons inside mobile menu
                bindThemeOptions(mobileMenu);
            }
        } catch (err) {
            // silent
            if (notifBadge) notifBadge.classList.add('d-none');
        }
    }

    loadNotifications();

    // Optional: refresh notifications periodically
    setInterval(loadNotifications, 60 * 1000); // every minute
});