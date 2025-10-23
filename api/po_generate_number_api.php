<?php
session_start();
header('Content-Type: application/json');

require_once '../db.php';
require_once '../includes/functions.php';

// Security check: ensure user is logged in and has permission
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden: You do not have permission.']);
    exit;
}

try {
    // Generate the next PO number without incrementing the counter yet.
    // The generateNextNumber function is perfect for this.
    $po_info = generateNextNumber($pdo, 'tbl_po_number', 'PO');

    echo json_encode(['success' => true, 'po_number' => $po_info['next_number']]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>