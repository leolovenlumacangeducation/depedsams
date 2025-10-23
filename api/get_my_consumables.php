<?php
session_start();
header('Content-Type: application/json');
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$current_user_name = $_SESSION['full_name'] ?? '';

if (empty($current_user_name)) {
    echo json_encode(['success' => true, 'data' => []]);
    exit;
}

try {
    // This query finds all consumables ever issued to the current user (by their full name)
    // and groups them to get a total quantity issued for each unique item.
    $sql = "
        SELECT 
            c.consumable_id,
            c.stock_number,
            c.photo,
            un.unit_name,
            COALESCE(poi.description, iici.description) AS description,
            SUM(ii.quantity_issued) AS total_quantity_issued,
            c.unit_cost,
            c.date_received,
            c.quantity_received,
            c.current_stock
        FROM 
            tbl_issuance i
        JOIN 
            tbl_issuance_item ii ON i.issuance_id = ii.issuance_id
        JOIN 
            tbl_consumable c ON ii.consumable_id = c.consumable_id
        LEFT JOIN 
            tbl_po_item poi ON c.po_item_id = poi.po_item_id
        LEFT JOIN 
            tbl_incoming_ics_item iici ON c.incoming_ics_item_id = iici.incoming_ics_item_id
        LEFT JOIN
            tbl_unit un ON c.unit_id = un.unit_id
        WHERE 
            i.issued_to = ?
        GROUP BY
            c.consumable_id, c.stock_number, c.photo, un.unit_name, COALESCE(poi.description, iici.description), c.unit_cost, c.date_received, c.quantity_received, c.current_stock
        ORDER BY 
            description ASC;
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$current_user_name]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $items]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
