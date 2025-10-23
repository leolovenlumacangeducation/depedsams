        
    </div> <!-- /.row -->
</div> <!-- /.container-fluid -->



<?php if (isset($is_public_page) && $is_public_page === true): ?>
    <!-- No app-data block needed for public pages -->
<?php else: ?>
    <!-- This data block makes PHP variables securely available to your JavaScript file -->
    <div id="app-data" 
        data-categories='<?= htmlspecialchars(json_encode($categories ?? []), ENT_QUOTES, 'UTF-8') ?>'
        data-units='<?= htmlspecialchars(json_encode($units ?? []), ENT_QUOTES, 'UTF-8') ?>'
        data-inventory-types='<?= htmlspecialchars(json_encode($inventory_types ?? []), ENT_QUOTES, 'UTF-8') ?>'
        data-api-endpoints='<?= htmlspecialchars(json_encode(['get_po_items' => 'api/get_po_items.php', 'po_view' => 'api/po_view.php']), ENT_QUOTES, 'UTF-8') ?>'
    ></div>
<?php endif; ?>
<!-- Toast container for positioning -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1100">
    <?php require_once 'toast.php'; ?>
</div>
<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- PDF Generation Libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.1/dist/sweetalert2.all.min.js"></script>

<script src="assets/js/main.js"></script>
<script src="assets/js/topbar.js"></script>
<script src="assets/js/document.utils.js"></script>
<script src="assets/js/document.modals.js"></script>
<script>
    // Global function to show a toast notification
    function showToast(message, title = 'Notification', type = 'success') {
        const toastEl = document.getElementById('appToast');
        const toast = new bootstrap.Toast(toastEl);

        const titleEl = document.getElementById('toast-title');
        const bodyEl = document.getElementById('toast-body');
        const iconEl = document.getElementById('toast-icon');

        titleEl.textContent = title;
        bodyEl.textContent = message;
        
        iconEl.className = type === 'success' ? 'bi bi-check-circle-fill text-success' : 'bi bi-exclamation-triangle-fill text-danger';

        toast.show();
    }
</script>

</body>
</html>