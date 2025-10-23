<?php 
require_once 'db.php'; 
require_once 'includes/header.php'; 
require_once 'includes/sidebar.php'; 

// Fetch the current user's complete data
    try {
    $stmt = $pdo->prepare(
        "SELECT u.full_name, u.username, u.photo, p.position_name, u.date_created, (SELECT NULL) AS last_login_date, r.role_name 
         FROM tbl_user u 
         LEFT JOIN tbl_position p ON u.position_id = p.position_id 
         LEFT JOIN tbl_role r ON u.role_id = r.role_id
         WHERE u.user_id = ?"
    );
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        // This should not happen if the user is logged in, but it's a good safeguard
        throw new Exception("User not found.");
    }

    $current_photo_path = 'assets/uploads/users/' . ($user['photo'] ?: 'default_user.png');

} catch (Exception $e) {
    // Handle error, maybe redirect or show a message
    die("Error fetching user data: " . $e->getMessage());
}
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">My Profile</h1>
        <a href="logout.php" class="btn btn-outline-danger">
            <i class="bi bi-box-arrow-right"></i> Sign Out
        </a>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <img src="<?= htmlspecialchars($current_photo_path) ?>" id="profile-photo-preview" class="img-thumbnail rounded-circle mb-3" alt="User Photo" style="width: 150px; height: 150px; object-fit: cover;">
                    <h5 class="card-title"><?= htmlspecialchars($user['full_name']) ?></h5>
                    <p class="mb-1">
                        <span class="badge bg-primary"><?= htmlspecialchars($user['role_name'] ?? 'User') ?></span>
                    </p>
                    <p class="card-text text-muted"><?= htmlspecialchars($user['position_name'] ?? 'No position set') ?></p>
                    <div class="text-muted small mt-2">
                        <div>Member since: <?= isset($user['date_created']) ? (new DateTime($user['date_created']))->format('M d, Y') : 'N/A' ?></div>
                        <div>Last login: <?= isset($user['last_login_date']) && $user['last_login_date'] ? (new DateTime($user['last_login_date']))->format('M d, Y h:i A') : 'N/A' ?></div>
                    </div>
                </div>
            </div>
            <!-- Quick Stats Card -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">My Assets Overview</h6>
                </div>
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        PPE Items
                        <span class="badge bg-primary rounded-pill" id="ppe-count">0</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        SEP Items
                        <span class="badge bg-info rounded-pill" id="sep-count">0</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        Consumables
                        <span class="badge bg-success rounded-pill" id="consumable-count">0</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    Update Your Information
                </div>
                <div class="card-body">
                    <form id="myProfileForm" enctype="multipart/form-data">
                        <!-- Full Name -->
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                        </div>

                        <!-- Username -->
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                        </div>

                        <!-- Change Photo -->
                        <div class="mb-3">
                            <label for="photo" class="form-label">Change Profile Photo</label>
                            <input class="form-control" type="file" id="photo" name="photo" accept="image/png, image/jpeg, image/webp">
                            <div class="form-text">Select a new photo to update your current one. Max 2MB.</div>
                        </div>

                        <hr>

                        <!-- New Password -->
                        <h6 class="text-muted">Change Password</h6>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Leave blank to keep current password">
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm your new password">
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="assets/js/my_profile.js"></script>
<?php require_once 'includes/footer.php'; ?>