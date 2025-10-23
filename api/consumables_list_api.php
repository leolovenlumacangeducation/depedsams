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

try {
    // The main SQL query to fetch consumable inventory data.
    $sql = "SELECT
                c.consumable_id,
                c.stock_number,
                c.quantity_received,
                c.current_stock,
                c.date_received,
                c.photo,
                c.unit_cost,
                c.parent_consumable_id,
                COALESCE(pi.description, iici.description) AS description,
                u.unit_name,
                -- Check if this item has ever been the source of a conversion
                EXISTS(SELECT 1 FROM tbl_unit_conversion uc WHERE uc.from_consumable_id = c.consumable_id) AS is_conversion_source
            FROM tbl_consumable c
            LEFT JOIN tbl_po_item pi ON c.po_item_id = pi.po_item_id
            LEFT JOIN tbl_incoming_ics_item iici ON c.incoming_ics_item_id = iici.incoming_ics_item_id
            JOIN tbl_unit u ON c.unit_id = u.unit_id
            ORDER BY c.date_received DESC, c.consumable_id DESC";

    $stmt = $pdo->query($sql);
    $consumables = $stmt->fetchAll();

    // DataTables expects the data in a 'data' key.
    echo json_encode(['data' => $consumables]);

} catch (PDOException $e) {
    // Handle potential database errors
    error_log("Consumables List API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'A database error occurred.']);
}
?>