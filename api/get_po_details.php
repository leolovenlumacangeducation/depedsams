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

$po_id = $_GET['id'] ?? null;
if (!$po_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Purchase Order ID is required.']);
    exit;
}

try {
    // --- Fetch PO Header Data ---
    $sql_header = "SELECT po_id, supplier_id, purchase_mode_id, delivery_place_id, delivery_term_id, payment_term_id, order_date 
                   FROM tbl_po 
                   WHERE po_id = ?";
    $stmt_header = $pdo->prepare($sql_header);
    $stmt_header->execute([$po_id]);
    $header = $stmt_header->fetch();

    if (!$header) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Purchase Order not found.']);
        exit;
    }

    // --- Fetch PO Items Data ---
    $sql_items = "SELECT i.po_item_id, i.description, i.quantity, i.unit_id, i.unit_cost, c.inventory_type_id, i.category_id
                  FROM tbl_po_item i
                  JOIN tbl_category c ON i.category_id = c.category_id
                  WHERE i.po_id = ?
                  ORDER BY i.po_item_id";
    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute([$po_id]);
    $items = $stmt_items->fetchAll();

    echo json_encode(['success' => true, 'header' => $header, 'items' => $items]);

} catch (PDOException $e) {
    error_log("Get PO Details API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
}
?>