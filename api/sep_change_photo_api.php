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

// --- Define Constants ---
define('UPLOAD_DIR', '../assets/uploads/sep/');
define('DEFAULT_PHOTO', 'sep_default.png');

// --- Input Validation ---
$sep_id = filter_input(INPUT_POST, 'sep_id', FILTER_VALIDATE_INT);

if (!$sep_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid or missing SEP ID.']);
    exit;
}

if (!isset($_FILES['sep_photo']) || $_FILES['sep_photo']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No file uploaded or an upload error occurred.']);
    exit;
}

try {
    // Ensure upload directory exists and is writable
    if (!file_exists(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0777, true);
    }
    if (!is_writable(UPLOAD_DIR)) {
        throw new Exception('Upload directory is not writable');
    }

    // Validate file size (5MB limit)
    if ($_FILES['sep_photo']['size'] > 5 * 1024 * 1024) {
        throw new Exception('File size must be less than 5MB');
    }

    $pdo->beginTransaction();

    // 1. Get the old photo filename to delete it later
    $stmt = $pdo->prepare("SELECT photo FROM tbl_sep WHERE sep_id = ?");
    $stmt->execute([$sep_id]);
    $old_photo = $stmt->fetchColumn();

    if (!$stmt->rowCount()) {
        throw new Exception('SEP item not found');
    }

    // 2. Process, resize, and save the new photo using the utility function
    $filePrefix = 'sep_' . $sep_id . '_';
    try {
        $new_filename = processAndSaveImage($_FILES['sep_photo'], UPLOAD_DIR, $filePrefix);
    } catch (Exception $e) {
        error_log("Image processing error: " . $e->getMessage());
        throw new Exception('Failed to process image: ' . $e->getMessage());
    }

    // 3. Update the database with the new filename
    $stmt = $pdo->prepare("UPDATE tbl_sep SET photo = ? WHERE sep_id = ?");
    $stmt->execute([$new_filename, $sep_id]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('Failed to update database record');
    }

    // 4. Delete the old photo if it's not the default one
    if ($old_photo && $old_photo !== DEFAULT_PHOTO && file_exists(UPLOAD_DIR . $old_photo)) {
        if (!unlink(UPLOAD_DIR . $old_photo)) {
            error_log("Warning: Could not delete old photo: " . $old_photo);
        }
    }

    $pdo->commit();
    echo json_encode([
        'success' => true,
        'message' => 'Photo updated successfully.',
        'new_photo' => $new_filename
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("SEP Photo Upload Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Upload failed: ' . $e->getMessage()
    ]);
}
?>