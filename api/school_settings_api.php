<?php
session_start();
header('Content-Type: application/json');

require_once '../db.php';
require_once 'utils.php'; // Include the utils file for the image function

// Security check: ensure user is an Admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden: You do not have permission to change settings.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// --- Define Constants ---
define('UPLOAD_DIR', '../assets/uploads/school/');
define('DEFAULT_LOGO', 'default_logo.png');

// --- Input Validation ---
$school_id = filter_input(INPUT_POST, 'school_id', FILTER_VALIDATE_INT);
$school_name = trim($_POST['school_name'] ?? '');

if (!$school_id || empty($school_name)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'School ID and School Name are required.']);
    exit;
}

$pdo->beginTransaction();

try {
    // 1. Handle Logo Upload if a new file is provided
    $logo_filename = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        // Get old logo to delete it after successful update
        $stmt_old_logo = $pdo->prepare("SELECT logo FROM tbl_school WHERE school_id = ?");
        $stmt_old_logo->execute([$school_id]);
        $old_logo = $stmt_old_logo->fetchColumn();

        // Process, resize, and save the new logo
        $logo_filename = processAndSaveImage($_FILES['logo'], UPLOAD_DIR, 'logo_');

        // --- Improvement: Check directory existence and permissions ---
        if (!is_dir(UPLOAD_DIR)) {
            if (!mkdir(UPLOAD_DIR, 0775, true)) {
                throw new Exception('Failed to create upload directory.');
            }
        }
        if (!is_writable(UPLOAD_DIR)) {
            throw new Exception('Upload directory is not writable. Please check server permissions for the folder: ' . UPLOAD_DIR);
        }

        // Delete old logo if it's not the default
        if ($old_logo && $old_logo !== DEFAULT_LOGO && file_exists(UPLOAD_DIR . $old_logo)) {
            unlink(UPLOAD_DIR . $old_logo);
        }
    }

    // 2. Update the database record
    $sql = "UPDATE tbl_school SET 
                school_name = ?, school_code = ?, address = ?, 
                division_name = ?, region_name = ?, contact_number = ?" .
                ($logo_filename ? ", logo = ?" : "") . // Only update logo if a new one was uploaded
           " WHERE school_id = ?";

    $params = [
        $school_name, $_POST['school_code'], $_POST['address'],
        $_POST['division_name'], $_POST['region_name'], $_POST['contact_number']
    ];
    if ($logo_filename) $params[] = $logo_filename;
    $params[] = $school_id;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'School settings updated successfully.']);

} catch (Exception $e) {
    $pdo->rollBack();
    // Check if the exception is a PDOException and if it's a unique constraint violation
    if ($e instanceof PDOException && $e->errorInfo[1] == 1062) {
        http_response_code(409); // Conflict
        echo json_encode(['success' => false, 'message' => 'The School Code you entered is already in use. Please choose a unique one.']);
    } else {
        // Log the detailed error for the developer
        error_log("School Settings API Error: " . $e->getMessage());
        // Send a more generic error to the client
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'An error occurred while saving settings.']);
    }
}
?>