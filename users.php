<?php 
require_once 'db.php';
require_once 'includes/header.php'; 
require_once 'includes/sidebar.php'; 

// Fetch lookup data for the modal dropdowns
$roles = $pdo->query("SELECT role_id, role_name FROM tbl_role ORDER BY role_name")->fetchAll();
$positions = $pdo->query("SELECT position_id, position_name FROM tbl_position ORDER BY position_name")->fetchAll();
?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.min.css">

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">User Management</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#userModal" id="addUserBtn">
                <i class="bi bi-plus-circle"></i> Add New User
            </button>
        </div>
    </div>

    <!-- Users List Table -->
    <div class="table-responsive">
        <table id="usersTable" class="table table-striped table-bordered table-hover" style="width:100%">
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Position</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- Data will be loaded here by DataTables -->
            </tbody>
        </table>
    </div>

</main>

<!-- Add/Edit User Modal -->
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="userModalLabel">Add New User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="userForm">
          <div class="modal-body">
            <input type="hidden" id="user_id" name="user_id">
            <div class="mb-3 text-center">
                <img id="photo-preview" src="assets/uploads/users/default_user.png" class="img-thumbnail rounded-circle" alt="User Photo" style="width: 120px; height: 120px; object-fit: cover;">
                <input class="form-control form-control-sm mt-2" type="file" id="photo" name="photo" accept="image/png, image/jpeg, image/webp">
            </div>
            <div class="mb-3">
                <label for="full_name" class="form-label">Full Name</label>
                <input type="text" class="form-control" id="full_name" name="full_name" required>
            </div>
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" autocomplete="new-password">
                <div class="form-text">Leave blank to keep the current password when editing.</div>
            </div>
            <div class="mb-3">
                <label for="role_id" class="form-label">Role</label>
                <select class="form-select" id="role_id" name="role_id" required>
                    <option value="" disabled selected>Select a role...</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= $role['role_id'] ?>"><?= htmlspecialchars($role['role_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="position_id" class="form-label">Position</label>
                <select class="form-select" id="position_id" name="position_id">
                    <option value="">None</option>
                    <?php foreach ($positions as $position): ?>
                        <option value="<?= $position['position_id'] ?>"><?= htmlspecialchars($position['position_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Save User</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- jQuery (required by DataTables) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- DataTables JS (Latest) -->
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.min.js"></script>
<!-- Custom JS for this page -->
<script src="assets/js/users.js"></script>

<?php require_once 'includes/footer.php'; ?>