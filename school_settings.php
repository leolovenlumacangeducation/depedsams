<?php 
require_once 'db.php';
require_once 'includes/header.php'; 
require_once 'includes/sidebar.php'; 

// Fetch current school settings to pre-populate the form
$school = $pdo->query("SELECT * FROM tbl_school LIMIT 1")->fetch();

// If no settings exist, create a default empty record to avoid errors
if (!$school) {
    $pdo->exec("INSERT INTO tbl_school (school_name) VALUES ('My School')");
    $school = $pdo->query("SELECT * FROM tbl_school LIMIT 1")->fetch();
}

// Fetch users with 'Admin' role for the officer assignment dropdowns.
$users_stmt = $pdo->prepare("SELECT u.user_id, u.full_name FROM tbl_user u JOIN tbl_role r ON u.role_id = r.role_id WHERE u.is_active = 1 AND r.role_name = 'Admin' ORDER BY u.full_name");
$users_stmt->execute();
$users = $users_stmt->fetchAll();

// Fetch current officer assignments
$officers = $pdo->query("SELECT o.officer_id, o.officer_type, o.user_id, u.full_name 
                         FROM tbl_officers o LEFT JOIN tbl_user u ON o.user_id = u.user_id")->fetchAll();

$logo_path = 'assets/uploads/school/' . ($school['logo'] ?? 'default_logo.png');
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">School Settings</h1>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <strong>School Information</strong>
                </div>
                <div class="card-body">
                    <form id="schoolSettingsForm" enctype="multipart/form-data">
                        <input type="hidden" name="school_id" value="<?= $school['school_id'] ?>">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="school_name" class="form-label">School Name</label>
                                    <input type="text" class="form-control" id="school_name" name="school_name" value="<?= htmlspecialchars($school['school_name']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <input type="text" class="form-control" id="address" name="address" value="<?= htmlspecialchars($school['address'] ?? '') ?>">
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="division_name" class="form-label">Division</label>
                                        <input type="text" class="form-control" id="division_name" name="division_name" value="<?= htmlspecialchars($school['division_name'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="region_name" class="form-label">Region</label>
                                        <input type="text" class="form-control" id="region_name" name="region_name" value="<?= htmlspecialchars($school['region_name'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="school_code" class="form-label">School Code</label>
                                        <input type="text" class="form-control" id="school_code" name="school_code" value="<?= htmlspecialchars($school['school_code'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="contact_number" class="form-label">Contact Number</label>
                                        <input type="text" class="form-control" id="contact_number" name="contact_number" value="<?= htmlspecialchars($school['contact_number'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <label for="logo" class="form-label">School Logo</label>
                                <img id="logo-preview" src="<?= $logo_path ?>?t=<?= time() ?>" class="img-thumbnail mb-3" alt="Logo Preview" style="max-height: 150px;">
                                <input class="form-control" type="file" id="logo" name="logo" accept="image/png, image/jpeg, image/webp">
                            </div>
                        </div>
                        <hr>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Settings</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <strong>Officer Assignments</strong>
                </div>
                <div class="card-body">
                    <form id="officersForm">
                        <?php foreach ($officers as $officer): ?>
                            <div class="mb-3">
                                <label for="officer_<?= $officer['officer_id'] ?>" class="form-label"><?= htmlspecialchars($officer['officer_type']) ?></label>
                                <select class="form-select" id="officer_<?= $officer['officer_id'] ?>" name="officers[<?= $officer['officer_id'] ?>]">
                                    <option value="">-- Unassigned --</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?= $user['user_id'] ?>" <?= ($user['user_id'] == $officer['user_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($user['full_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endforeach; ?>
                        <hr>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-person-check"></i> Save Assignments</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Custom JS for this page -->
<script src="assets/js/school_settings.js"></script>
<script src="assets/js/officers.js"></script>

<?php require_once 'includes/footer.php'; ?>