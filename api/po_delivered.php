<?php
/**
 * API Endpoint to Mark a Purchase Order as Delivered
 *
 * This script updates the status of a given Purchase Order to 'Delivered'.
 * It expects a POST request with a JSON body containing the 'po_id'.
 */

session_start();
header('Content-Type: application/json');

require_once '../db.php';

// Security & Method Check
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
$po_id = $input['po_id'] ?? null;

if (!$po_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Purchase Order ID is required.']);
    exit;
}

try {
    // --- Validation Step: Check if all items have been received ---
    $sql_check = "SELECT 
                    poi.po_item_id, 
                    poi.quantity AS quantity_ordered,
                    (COALESCE(SUM(c.quantity_received), 0) + COALESCE(sep_counts.sep_count, 0) + COALESCE(ppe_counts.ppe_count, 0)) AS quantity_received
                FROM tbl_po_item poi
                LEFT JOIN tbl_consumable c ON poi.po_item_id = c.po_item_id
                LEFT JOIN (SELECT po_item_id, COUNT(*) AS sep_count FROM tbl_sep GROUP BY po_item_id) AS sep_counts ON poi.po_item_id = sep_counts.po_item_id
                LEFT JOIN (SELECT po_item_id, COUNT(*) AS ppe_count FROM tbl_ppe GROUP BY po_item_id) AS ppe_counts ON poi.po_item_id = ppe_counts.po_item_id
                WHERE poi.po_id = ?
                GROUP BY poi.po_item_id, poi.quantity, sep_counts.sep_count, ppe_counts.ppe_count";
    
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([$po_id]);
    $items_status = $stmt_check->fetchAll();

    if (empty($items_status)) {
        throw new Exception("This PO has no items or has not been processed for receiving yet.");
    }

    foreach ($items_status as $item) {
        if ($item['quantity_received'] < $item['quantity_ordered']) {
            http_response_code(400); // Bad Request
            echo json_encode(['success' => false, 'message' => 'Cannot mark as delivered. Not all items have been fully received into inventory. Please use the "Receive Items" function first.']);
            exit;
        }
    }

    // --- If validation passes, update the status ---
    $pdo->beginTransaction();
    $sql_update = "UPDATE tbl_po SET status = 'Delivered' WHERE po_id = ?";
    $stmt_update = $pdo->prepare($sql_update);
    $stmt_update->execute([$po_id]);
    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'All items verified. Purchase Order status has been updated to Delivered.']);
    
} catch (PDOException $e) {
    error_log("PO Delivered API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
} catch (Exception $e) {
    // Catches other logical errors, like the one for no items found.
    error_log("PO Delivered Logic Error: " . $e->getMessage());
    http_response_code(400); // Bad Request is more appropriate here
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>