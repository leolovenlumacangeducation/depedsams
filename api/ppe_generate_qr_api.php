<?php
session_start();
header('Content-Type: application/json');

require_once '../db.php';
require_once '../vendor/phpqrcode/qrlib.php';

// --- Security Check ---
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$ppe_id = $input['ppe_id'] ?? null;
$property_number = $input['property_number'] ?? null;

if (!$ppe_id || !$property_number) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'PPE ID and Property Number are required.']);
    exit;
}

// --- Configuration ---
$qr_content_url = "http://" . $_SERVER['HTTP_HOST'] . "/samspikpok/scan.php?property_number=" . urlencode($property_number);
$upload_dir = '../assets/uploads/qr_codes/';

// Ensure the directory exists and is writable
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0777, true)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create QR code directory.']);
        exit;
    }
}

// Generate a unique filename to avoid caching issues
$filename = 'ppe_qr_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $property_number) . '_' . time() . '.png';
$filepath = $upload_dir . $filename;

try {
    // --- Generate QR Code Image ---
    // QRcode::png($text, $outfile, $level, $size, $margin)
    QRcode::png($qr_content_url, $filepath, QR_ECLEVEL_L, 10, 2);

    // --- Update Database ---
    $pdo->beginTransaction();

    // Fetch the old photo to delete it later if it's a QR code
    $stmt_old = $pdo->prepare("SELECT photo FROM tbl_ppe WHERE ppe_id = ?");
    $stmt_old->execute([$ppe_id]);
    $old_photo = $stmt_old->fetchColumn();

    // Update the record with the new QR code filename
    $stmt_update = $pdo->prepare("UPDATE tbl_ppe SET photo = ? WHERE ppe_id = ?");
    $stmt_update->execute([$filename, $ppe_id]);

    $pdo->commit();

    // --- Cleanup Old QR Code File ---
    if ($old_photo && strpos($old_photo, '_qr_') !== false && file_exists($upload_dir . $old_photo)) {
        unlink($upload_dir . $old_photo);
    }

    echo json_encode([
        'success' => true,
        'message' => 'QR Code generated and set as item photo.',
        'new_photo_path' => 'qr_codes/' . $filename // Path relative to the uploads folder
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    error_log("PPE QR Generation Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred during QR code generation: ' . $e->getMessage()]);
}
?>