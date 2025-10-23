<?php
session_start();
header('Content-Type: application/json');
require_once '../db.php';

// Security check: ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Ensure the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// --- Input Validation ---
$ppe_id = $input['ppe_id'] ?? null;

if (!$ppe_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required data: PPE ID is required.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Get the current user ID for history logging before clearing it
    $stmt_get_user = $pdo->prepare("SELECT assigned_to_user_id FROM tbl_ppe WHERE ppe_id = ?");
    $stmt_get_user->execute([$ppe_id]);
    $from_user_id = $stmt_get_user->fetchColumn();

    // Find the PAR number from the item's most recent assignment to use as a reference for the return.
    $stmt_get_par = $pdo->prepare(
        "SELECT reference FROM tbl_ppe_history 
         WHERE ppe_id = ? AND transaction_type = 'Assignment' 
         ORDER BY history_id DESC LIMIT 1"
    );
    $stmt_get_par->execute([$ppe_id]);
    $par_number_reference = $stmt_get_par->fetchColumn();

    // Log the return in history
    $sql_history = "INSERT INTO tbl_ppe_history (ppe_id, transaction_date, transaction_type, reference, from_user_id, notes) VALUES (?, CURDATE(), 'Return', ?, ?, 'Returned to Property Office')";
    $stmt_history = $pdo->prepare($sql_history);
    $stmt_history->execute([$ppe_id, $par_number_reference ?: 'Returned to Property Office', $from_user_id]);

    // Unassign the item by setting its custodian and location to NULL
    $sql_unassign = "UPDATE tbl_ppe SET assigned_to_user_id = NULL, current_location = NULL WHERE ppe_id = ?";
    $stmt_unassign = $pdo->prepare($sql_unassign);
    $stmt_unassign->execute([$ppe_id]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'PPE item unassigned successfully.']);
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("PPE Unassign API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'A database error occurred while unassigning the item.']);
}
?>