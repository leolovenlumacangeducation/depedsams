<?php
session_start();
header('Content-Type: application/json');

require_once '../db.php';

// Security check: ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    http_response_code(401);
    exit;
}

$status_filter = $_GET['status'] ?? null;

try {
    // The main SQL query to fetch purchase order data.
    // This improved query uses JOINs and GROUP BY instead of correlated subqueries for better performance.
    $base_sql = "SELECT 
                    p.po_id,
                    p.po_number,
                    s.supplier_name,
                    p.order_date,
                    p.status,
                    COALESCE(po_agg.total_amount, 0) AS total_amount,
                    (COALESCE(po_agg.total_ordered, 0) <= COALESCE(received_agg.total_received, 0)) AS is_fully_received
                FROM tbl_po p
                JOIN tbl_supplier s ON p.supplier_id = s.supplier_id
                LEFT JOIN (
                    SELECT po_id, SUM(quantity * unit_cost) AS total_amount, SUM(quantity) AS total_ordered
                    FROM tbl_po_item
                    GROUP BY po_id
                ) AS po_agg ON p.po_id = po_agg.po_id
                LEFT JOIN (
                    SELECT 
                        pi.po_id,
                        (COALESCE(SUM(c.quantity_received), 0) + COUNT(sep.sep_id) + COUNT(ppe.ppe_id)) AS total_received
                    FROM tbl_po_item pi
                    LEFT JOIN tbl_consumable c ON pi.po_item_id = c.po_item_id
                    LEFT JOIN tbl_sep sep ON pi.po_item_id = sep.po_item_id
                    LEFT JOIN tbl_ppe ppe ON pi.po_item_id = ppe.po_item_id
                    GROUP BY pi.po_id
                ) AS received_agg ON p.po_id = received_agg.po_id";

    $params = [];
    if ($status_filter) {
        $base_sql .= " WHERE p.status = ?";
        $params[] = $status_filter;
    }

    $base_sql .= " ORDER BY p.order_date DESC";

    $stmt = $pdo->prepare($base_sql);
    $stmt->execute($params);
    $pos = $stmt->fetchAll();

    // DataTables expects the data in a 'data' key.
    echo json_encode(['data' => $pos]);

} catch (PDOException $e) {
    // Handle potential database errors
    error_log("PO List API Error: " . $e->getMessage());
    echo json_encode(['error' => 'A database error occurred.']);
    http_response_code(500);
}
?>