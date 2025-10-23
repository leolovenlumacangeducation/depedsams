<?php
session_start();
header('Content-Type: application/json');

require_once '../db.php';

// Security check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$sep_id = $input['sep_id'] ?? null;
$void_ics = $input['void_ics'] ?? false;

if (!$sep_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required data: SEP ID is required.']);
    exit;
}
try {
    $pdo->beginTransaction();
    
    $info_message = null;
    // If the user wants to void the ICS, perform checks first.
    if ($void_ics) {
        $stmt_find_ics = $pdo->prepare("SELECT ics_id FROM tbl_ics_item WHERE sep_id = ? ORDER BY ics_id DESC LIMIT 1");
        $stmt_find_ics->execute([$sep_id]);
        $ics_id = $stmt_find_ics->fetchColumn();

        if ($ics_id) {
            // Check how many items are on this ICS
            $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM tbl_ics_item WHERE ics_id = ?");
            $stmt_count->execute([$ics_id]);
            $item_count = $stmt_count->fetchColumn();

            if ($item_count > 1) {
                // Set an info message instead of throwing an error.
                $info_message = "Cannot void ICS because it still contains other assigned items. Only the selected item was unassigned.";
            }
            // If count is 1 (this is the last item), proceed to void.
            $stmt_void_ics = $pdo->prepare("UPDATE tbl_ics SET status = 'Voided' WHERE ics_id = ?");
            $stmt_void_ics->execute([$ics_id]);
        }
    }

    // Unassign the item by setting its custodian to NULL
    $sql_unassign = "UPDATE tbl_sep SET assigned_to_user_id = NULL, current_location = NULL WHERE sep_id = ?";
    $stmt_unassign = $pdo->prepare($sql_unassign);
    $stmt_unassign->execute([$sep_id]);

    $pdo->commit();
    // The success message is now conditional based on the exception thrown for the void action.
    // If an info message was set, use it. Otherwise, use the default success message.
    echo json_encode(['success' => true, 'message' => $info_message ?? 'Item has been successfully unassigned.']);
} catch (Exception $e) {
    // This will now catch both PDOExceptions and the generic Exception for the business rule.
    $pdo->rollBack();
    error_log("SEP Unassign API Error: " . $e->getMessage()); // Log the error for debugging
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred during unassignment.']);
}
?>