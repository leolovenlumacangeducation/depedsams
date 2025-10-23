<?php 
require_once 'includes/header.php'; 
require_once 'includes/sidebar.php'; 

// Security check: ensure user is an Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    // Redirect non-admins to the dashboard or an error page
    header('Location: index.php');
    exit;
}
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h4"><strong>System Settings</strong></h1>
    </div>

    <h4>Data Management</h4>
    <div class="card border-danger">
        <div class="card-header bg-danger text-white">
            <i class="bi bi-exclamation-triangle-fill"></i> Danger Zone
        </div>
        <div class="card-body">
            <h5 class="card-title">Reset Inventory Data</h5>
            <p class="card-text">
                This action will permanently delete all inventory and transaction records from the system. 
                This includes all Purchase Orders, Deliveries, Consumables, SEP, PPE, Issuances, and Reports. 
                This action cannot be undone.
            </p>
            <p class="card-text">
                User accounts, suppliers, and other reference data will **not** be affected.
            </p>
            <button type="button" class="btn btn-danger" id="resetDataBtn">
                <i class="bi bi-trash3-fill"></i> Reset All Inventory Data
            </button>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const resetBtn = document.getElementById('resetDataBtn');

    resetBtn.addEventListener('click', function() {
        const confirmation = prompt('This is a destructive action. To proceed, please type "RESET ALL INVENTORY DATA" in the box below and click OK.');

        if (confirmation !== 'RESET ALL INVENTORY DATA') {
            showToast('Reset action cancelled. The confirmation phrase was incorrect.', 'Info', 'info');
            return;
        }

        resetBtn.disabled = true;
        resetBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Resetting...`;

        fetch('api/reset_data_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ confirmation: confirmation })
        })
        .then(response => {
            if (!response.ok) {
                // If the server response is not OK (e.g., 403, 404, 500), throw an error
                return response.json().then(err => { throw new Error(err.message || 'An unknown server error occurred.'); });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                if (data.warning) {
                    showToast(data.message, 'Reset Complete with Warnings', 'warning');
                } else {
                    showToast(data.message, 'Success', 'success');
                }
                // Redirect to login page to start a fresh session
                setTimeout(() => window.location.href = 'login.php', 3000);
            } else {
                throw new Error(data.message || 'The operation failed for an unknown reason.');
            }
        })
        .catch(error => {
            showToast(`Error: ${error.message}`, 'Reset Failed', 'danger');
            resetBtn.disabled = false;
            resetBtn.innerHTML = '<i class="bi bi-trash3-fill"></i> Reset All Inventory Data';
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>