<?php
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

// --- Data Validation ---
$po_id = $input['po_id'] ?? null;
$header = $input['header'] ?? [];
$items_to_update = $input['items_to_update'] ?? [];
$items_to_add = $input['items_to_add'] ?? [];
$items_to_delete = $input['items_to_delete'] ?? [];

if (!$po_id || empty($header)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid data. PO ID and header are required.']);
    exit;
}

// --- Database Transaction ---
try {
    $pdo->beginTransaction();

    // 1. Update PO Header
    $sql_header = "UPDATE tbl_po SET 
                        supplier_id = :supplier_id, 
                        purchase_mode_id = :purchase_mode_id, 
                        delivery_place_id = :delivery_place_id, 
                        delivery_term_id = :delivery_term_id, 
                        payment_term_id = :payment_term_id, 
                        order_date = :order_date
                   WHERE po_id = :po_id";
    $stmt_header = $pdo->prepare($sql_header);
    $stmt_header->execute(array_merge($header, ['po_id' => $po_id]));

    // 2. Update Existing Items
    if (!empty($items_to_update)) {
        $sql_update_item = "UPDATE tbl_po_item SET 
                                description = ?, 
                                quantity = ?, 
                                unit_id = ?, 
                                unit_cost = ?, 
                                category_id = ?
                            WHERE po_item_id = ?";
        $stmt_update_item = $pdo->prepare($sql_update_item);
        foreach ($items_to_update as $item) {
            $stmt_update_item->execute([$item['description'], $item['quantity'], $item['unit_id'], $item['unit_cost'], $item['category_id'], $item['po_item_id']]);
        }
    }

    // 3. Add New Items
    if (!empty($items_to_add)) {
        $sql_add_item = "INSERT INTO tbl_po_item (po_id, category_id, description, quantity, unit_id, unit_cost) 
                         VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_add_item = $pdo->prepare($sql_add_item);
        foreach ($items_to_add as $item) {
            $stmt_add_item->execute([$po_id, $item['category_id'], $item['description'], $item['quantity'], $item['unit_id'], $item['unit_cost']]);
        }
    }

    // 4. Delete Items
    if (!empty($items_to_delete)) {
        // Using IN() clause for efficiency
        $placeholders = implode(',', array_fill(0, count($items_to_delete), '?'));
        $sql_delete_item = "DELETE FROM tbl_po_item WHERE po_item_id IN ($placeholders)";
        $stmt_delete_item = $pdo->prepare($sql_delete_item);
        $stmt_delete_item->execute($items_to_delete);
    }

    // If all queries succeeded, commit the transaction
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Purchase Order updated successfully!']);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("PO Edit API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'A database error occurred while updating the PO.', 'error' => $e->getMessage()]);
}
?>