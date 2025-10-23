<?php
session_start();
header('Content-Type: application/json');

require_once '../db.php';
require_once 'utils.php'; // Include for processAndSaveImage function

// --- Security Check: Ensure user is logged in ---
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit;
}

// --- Define Constants ---
define('UPLOAD_DIR', '../assets/uploads/ppe/');
define('DEFAULT_PHOTO', 'ppe_default.png');

// --- Input Validation ---
$ppe_id = filter_input(INPUT_POST, 'ppe_id', FILTER_VALIDATE_INT);

if (!$ppe_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid or missing PPE ID.']);
    exit;
}

if (!isset($_FILES['ppe_photo']) || $_FILES['ppe_photo']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No file uploaded or an upload error occurred.']);
    exit;
}

$file = $_FILES['ppe_photo'];

$pdo->beginTransaction();

try {
    // 1. Get the old photo filename to delete it after a successful update
    $stmt = $pdo->prepare("SELECT photo FROM tbl_ppe WHERE ppe_id = ?");
    $stmt->execute([$ppe_id]);
    $old_photo = $stmt->fetchColumn();

    // 2. Process, resize, and save the new photo using the utility function
    $filePrefix = 'ppe_' . $ppe_id . '_';
    $new_filename = processAndSaveImage($file, UPLOAD_DIR, $filePrefix);

    // 3. Update the database with the new filename
    $stmt = $pdo->prepare("UPDATE tbl_ppe SET photo = ? WHERE ppe_id = ?");
    $stmt->execute([$new_filename, $ppe_id]);

    // 4. Clean up the old photo file if it's not the default one
    if ($old_photo && $old_photo !== DEFAULT_PHOTO && file_exists(UPLOAD_DIR . $old_photo)) {
        unlink(UPLOAD_DIR . $old_photo);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Photo updated successfully.', 'new_photo' => $new_filename]);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("PPE Photo Upload Error: " . $e->getMessage());
    // Use a 400 Bad Request for validation errors from processAndSaveImage
    if (strpos($e->getMessage(), 'File is too large') !== false || strpos($e->getMessage(), 'Invalid file type') !== false) {
        http_response_code(400);
    } else {
        http_response_code(500);
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>