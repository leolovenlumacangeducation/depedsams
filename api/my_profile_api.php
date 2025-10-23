<?php
session_start();
header('Content-Type: application/json');

require_once '../db.php';
require_once 'utils.php'; // Include the utils file for the image function

// --- Security Check: Ensure user is logged in ---
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// --- Define Constants ---
define('UPLOAD_DIR', '../assets/uploads/users/');
define('DEFAULT_PHOTO', 'default_user.png');

$user_id = $_SESSION['user_id']; // Use session ID for security

$pdo->beginTransaction();

try {
    // --- Input Validation ---
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($full_name) || empty($username)) {
        throw new Exception('Full Name and Username are required.');
    }

    // --- Password Validation ---
    if (!empty($new_password) && $new_password !== $confirm_password) {
        throw new Exception('New passwords do not match.');
    }

    // --- Username Uniqueness Check ---
    $stmt_user_check = $pdo->prepare("SELECT user_id FROM tbl_user WHERE username = ? AND user_id != ?");
    $stmt_user_check->execute([$username, $user_id]);
    if ($stmt_user_check->fetch()) {
        http_response_code(409); // Conflict
        throw new Exception('This username is already taken by another user.');
    }

    // --- Photo Upload Logic ---
    $photo_filename = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        // Get old photo to delete it after a successful update
        $stmt_old = $pdo->prepare("SELECT photo FROM tbl_user WHERE user_id = ?");
        $stmt_old->execute([$user_id]);
        $old_photo = $stmt_old->fetchColumn();

        // Process, resize, and save the new photo
        $filePrefix = 'user_' . $user_id . '_';
        $photo_filename = processAndSaveImage($_FILES['photo'], UPLOAD_DIR, $filePrefix);

        // Clean up the old photo file if it's not the default one
        if ($old_photo && $old_photo !== DEFAULT_PHOTO && file_exists(UPLOAD_DIR . $old_photo)) {
            unlink(UPLOAD_DIR . $old_photo);
        }
    }

    // --- Build Dynamic SQL Query ---
    $params = [$full_name, $username];
    $sql = "UPDATE tbl_user SET full_name = ?, username = ?";

    if (!empty($new_password)) {
        $sql .= ", hashed_password = ? ";
        $params[] = password_hash($new_password, PASSWORD_DEFAULT);
    }

    if ($photo_filename) {
        $sql .= ", photo = ? ";
        $params[] = $photo_filename;
    }

    $sql .= " WHERE user_id = ?";
    $params[] = $user_id;

    // --- Execute Query ---
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // --- Update Session ---
    $_SESSION['full_name'] = $full_name;

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Profile updated successfully.']);

} catch (Exception $e) {
    $pdo->rollBack();
    
    // Use the existing status code if it was set (e.g., 409 for conflict)
    $statusCode = http_response_code();
    if ($statusCode < 400) {
        $statusCode = 400; // Default to Bad Request for general validation errors
    }
    if ($e instanceof PDOException) {
        $statusCode = 500; // Database-level error
        error_log("My Profile API Error: " . $e->getMessage());
    }

    http_response_code($statusCode);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>