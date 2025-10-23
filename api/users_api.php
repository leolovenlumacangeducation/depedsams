<?php
session_start();
header('Content-Type: application/json');

require_once '../db.php';
require_once 'utils.php';

// Security check: ensure user is an Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden: You do not have permission.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            $stmt = $pdo->query("SELECT u.user_id, u.full_name, u.username, u.is_active, u.photo, u.role_id, r.role_name, u.position_id, p.position_name 
                                 FROM tbl_user u 
                                 JOIN tbl_role r ON u.role_id = r.role_id 
                                 LEFT JOIN tbl_position p ON u.position_id = p.position_id 
                                 ORDER BY u.full_name");
            $users = $stmt->fetchAll();
            echo json_encode(['data' => $users]);
            break;

        case 'POST':
            // --- Define Constants ---
            define('UPLOAD_DIR', '../assets/uploads/users/');
            define('DEFAULT_PHOTO', 'default_user.png');

            // --- Input Validation ---
            $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
            $full_name = trim($_POST['full_name'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $role_id = filter_input(INPUT_POST, 'role_id', FILTER_VALIDATE_INT);
            $position_id = empty($_POST['position_id']) ? null : filter_input(INPUT_POST, 'position_id', FILTER_VALIDATE_INT);

            // Check for a specific action, like toggling status
            if (isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
                $user_id_to_toggle = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
                $new_status = filter_input(INPUT_POST, 'is_active', FILTER_VALIDATE_INT);

                if ($user_id_to_toggle == $_SESSION['user_id']) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'You cannot deactivate your own account.']);
                    exit;
                }
                // --- Security Enhancement: Prevent self-deactivation ---
                if ($user_id_to_toggle == $_SESSION['user_id'] && $new_status == 0) {
                    throw new Exception('You cannot deactivate your own account.');
                }

                if ($user_id_to_toggle && isset($new_status)) {
                    $sql = "UPDATE tbl_user SET is_active = ? WHERE user_id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$new_status, $user_id_to_toggle]);
                    echo json_encode(['success' => true, 'message' => 'User status updated successfully.']);
                    exit;
                }
            }

            // Handle delete action
            if (isset($_POST['action']) && $_POST['action'] === 'delete') {
                $user_id_to_delete = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

                // Prevent self-deletion
                if ($user_id_to_delete == $_SESSION['user_id']) {
                    throw new Exception('You cannot delete your own account.');
                }

                // Check if user has any accountabilities before deletion
                $stmt = $pdo->prepare("
                    SELECT 
                        COUNT(*) as total_items,
                        SUM(CASE WHEN item_type = 'PPE' THEN 1 ELSE 0 END) as ppe_count,
                        SUM(CASE WHEN item_type = 'SEP' THEN 1 ELSE 0 END) as sep_count
                    FROM (
                        SELECT 'PPE' as item_type FROM tbl_ppe WHERE assigned_to_user_id = ?
                        UNION ALL
                        SELECT 'SEP' FROM tbl_sep WHERE assigned_to_user_id = ?
                    ) as items
                ");
                $stmt->execute([$user_id_to_delete, $user_id_to_delete]);
                $accountability = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($accountability['total_items'] > 0) {
                    throw new Exception(
                        'This user cannot be deleted because they have accountabilities: ' .
                        ($accountability['ppe_count'] > 0 ? $accountability['ppe_count'] . ' PPE items, ' : '') .
                        ($accountability['sep_count'] > 0 ? $accountability['sep_count'] . ' SEP items' : '')
                    );
                }

                // Get user's photo before deletion
                $stmt = $pdo->prepare("SELECT photo FROM tbl_user WHERE user_id = ?");
                $stmt->execute([$user_id_to_delete]);
                $photo = $stmt->fetchColumn();

                // Delete the user
                $stmt = $pdo->prepare("DELETE FROM tbl_user WHERE user_id = ?");
                $stmt->execute([$user_id_to_delete]);

                // Delete photo file if it's not the default
                if ($photo && $photo !== DEFAULT_PHOTO && file_exists(UPLOAD_DIR . $photo)) {
                    unlink(UPLOAD_DIR . $photo);
                }

                echo json_encode(['success' => true, 'message' => 'User deleted successfully.']);
                exit;
            }

            if (empty($full_name) || empty($username) || empty($role_id)) {
                throw new Exception('Full Name, Username, and Role are required.');
            }

            // --- Data Integrity Check: Ensure role_id exists ---
            $stmt_role_check = $pdo->prepare("SELECT role_id FROM tbl_role WHERE role_id = ?");
            $stmt_role_check->execute([$role_id]);
            if (!$stmt_role_check->fetch()) {
                throw new Exception("Invalid Role ID provided.");
            }


            // --- Photo Upload Logic ---
            $photo_filename = null;
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                // Use the new utility function
                $photo_filename = processAndSaveImage($_FILES['photo'], UPLOAD_DIR, 'user_');
            }

            if ($user_id) { // --- UPDATE ---
                // --- Username Uniqueness Check (for updates) ---
                $stmt_user_check = $pdo->prepare("SELECT user_id FROM tbl_user WHERE username = ? AND user_id != ?");
                $stmt_user_check->execute([$username, $user_id]);
                if ($stmt_user_check->fetch()) {
                    http_response_code(409); // Conflict
                    echo json_encode(['success' => false, 'message' => 'This username is already taken by another user.']);
                    exit;
                }

                // Start with the core parameters for the SET clause
                $params = [$full_name, $username, $role_id, $position_id];
                $sql = "UPDATE tbl_user SET full_name = ?, username = ?, role_id = ?, position_id = ?";

                if (!empty($password)) {
                    $sql .= ", hashed_password = ? ";
                    $params[] = password_hash($password, PASSWORD_DEFAULT);
                }

                if ($photo_filename) {
                    // Get old photo to delete it after a successful update
                    $stmt_old = $pdo->prepare("SELECT photo FROM tbl_user WHERE user_id = ?");
                    $stmt_old->execute([$user_id]);
                    $old_photo = $stmt_old->fetchColumn();

                    $sql .= ", photo = ? ";
                    $params[] = $photo_filename;

                    // Clean up the old photo file if it's not the default one
                    if ($old_photo && $old_photo !== DEFAULT_PHOTO && file_exists(UPLOAD_DIR . $old_photo)) {
                        unlink(UPLOAD_DIR . $old_photo);
                    }
                }

                $sql .= " WHERE user_id = ?";
                $params[] = $user_id; // Add the user_id for the WHERE clause at the very end
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                echo json_encode(['success' => true, 'message' => 'User updated successfully.']);

            } else { // --- CREATE ---
                if (empty($password)) throw new Exception('Password is required for a new user.');

                // --- Username Uniqueness Check (for creates) ---
                $stmt_user_check = $pdo->prepare("SELECT user_id FROM tbl_user WHERE username = ?");
                $stmt_user_check->execute([$username]);
                if ($stmt_user_check->fetch()) {
                    http_response_code(409); // Conflict
                    echo json_encode(['success' => false, 'message' => 'This username is already taken. Please choose another one.']);
                    exit;
                }
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO tbl_user (full_name, username, hashed_password, role_id, position_id, photo) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$full_name, $username, $hashed_password, $role_id, $position_id, $photo_filename ?? DEFAULT_PHOTO]);
                echo json_encode(['success' => true, 'message' => 'User added successfully.']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
            break;
    }
} catch (Exception $e) {
    // Use 409 for specific conflicts, otherwise 400 for general validation errors
    $statusCode = ($e->getCode() == 409) ? 409 : 400;
    if ($e instanceof PDOException) {
        $statusCode = 500; // Database-level error
    }
    http_response_code($statusCode);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>