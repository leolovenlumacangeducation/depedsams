<?php
session_start();
header('Content-Type: application/json');

require_once '../db.php';

// Security check: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Ensure the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$ppe_id = $input['ppe_id'] ?? null;

if (!$ppe_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'PPE ID is required.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Update the item's condition to 'Disposed'
    // It must be unserviceable and unassigned to be disposed.
    $stmt = $pdo->prepare(
        "UPDATE tbl_ppe SET current_condition = 'Disposed' WHERE ppe_id = ? AND current_condition = 'Unserviceable' AND assigned_to_user_id IS NULL"
    );
    $stmt->execute([$ppe_id]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Item has been marked as disposed.']);

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    error_log("PPE Dispose API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
}
?>