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
define('UPLOAD_DIR', '../assets/uploads/consumables/');
define('DEFAULT_PHOTO', 'consumable_default.png');

// --- Input Validation ---
$consumable_id = filter_input(INPUT_POST, 'consumable_id', FILTER_VALIDATE_INT);

if (!$consumable_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid or missing consumable ID.']);
    exit;
}

if (!isset($_FILES['consumable_photo']) || $_FILES['consumable_photo']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No file uploaded or an upload error occurred.']);
    exit;
}

$pdo->beginTransaction();

try {
    // 1. Get the old photo filename to delete it later
    $stmt = $pdo->prepare("SELECT photo FROM tbl_consumable WHERE consumable_id = ?");
    $stmt->execute([$consumable_id]);
    $old_photo = $stmt->fetchColumn();

    // 2. Process, resize, and save the new photo
    $filePrefix = 'consumable_' . $consumable_id . '_';
    $new_filename = processAndSaveImage($_FILES['consumable_photo'], UPLOAD_DIR, $filePrefix);

    // 4. Update the database
    $stmt = $pdo->prepare("UPDATE tbl_consumable SET photo = ? WHERE consumable_id = ?");
    $stmt->execute([$new_filename, $consumable_id]);

    // 5. Delete the old photo if it's not the default one
    if ($old_photo && $old_photo !== DEFAULT_PHOTO && file_exists(UPLOAD_DIR . $old_photo)) {
        unlink(UPLOAD_DIR . $old_photo);
    }

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Photo updated successfully.', 'new_photo' => $new_filename]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Photo Upload Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'A server error occurred while updating the photo.']);
}
?>