<?php
session_start();
header('Content-Type: application/json');

require_once '../db.php';

// Security check: ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// --- Input Validation ---
$ppe_id = $input['ppe_id'] ?? null;
$description = trim($input['description'] ?? '');
$property_number = trim($input['property_number'] ?? '');
$model_number = trim($input['model_number'] ?? '') ?: null;
$serial_number = trim($input['serial_number'] ?? '') ?: null;
$date_acquired = $input['date_acquired'] ?? null;
$current_condition = $input['current_condition'] ?? 'Serviceable';

if (!$ppe_id || empty($description) || empty($property_number) || empty($date_acquired)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'PPE ID, Description, Property Number, and Date Acquired are required.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Find out if the item came from a PO or an Incoming ICS
    $stmt_get_source = $pdo->prepare("SELECT po_item_id, incoming_ics_item_id FROM tbl_ppe WHERE ppe_id = ?");
    $stmt_get_source->execute([$ppe_id]);
    $source = $stmt_get_source->fetch();

    if (!$source) {
        throw new Exception("Could not find the specified PPE item.");
    }

    // 2. Update the description in the correct source table
    if ($source['po_item_id']) {
        $stmt_update_desc = $pdo->prepare("UPDATE tbl_po_item SET description = ? WHERE po_item_id = ?");
        $stmt_update_desc->execute([$description, $source['po_item_id']]);
    } elseif ($source['incoming_ics_item_id']) {
        $stmt_update_desc = $pdo->prepare("UPDATE tbl_incoming_ics_item SET description = ? WHERE incoming_ics_item_id = ?");
        $stmt_update_desc->execute([$description, $source['incoming_ics_item_id']]);
    }

    // 3. Update the details in tbl_ppe
    $sql_update_ppe = "UPDATE tbl_ppe SET 
                        property_number = ?,
                        model_number = ?,
                        serial_number = ?,
                        date_acquired = ?,
                        current_condition = ?
                    WHERE ppe_id = ?";

    $stmt_update_ppe = $pdo->prepare($sql_update_ppe);
    $stmt_update_ppe->execute([$property_number, $model_number, $serial_number, $date_acquired, $current_condition, $ppe_id]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'PPE details updated successfully.']);

} catch (PDOException $e) {
    $pdo->rollBack();
    // Check for unique constraint violation (error code 1062)
    if ($e->errorInfo[1] == 1062) {
        http_response_code(409); // Conflict
        $errorMessage = 'A record with the same Property Number or Serial Number already exists.';
        if (str_contains($e->getMessage(), 'property_number')) $errorMessage = 'This Property Number is already in use.';
        if (str_contains($e->getMessage(), 'serial_number')) $errorMessage = 'This Serial Number is already in use.';
        echo json_encode(['success' => false, 'message' => $errorMessage]);
    } else {
        error_log("PPE Edit API Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'A database error occurred while updating details.']);
    }
}
?>