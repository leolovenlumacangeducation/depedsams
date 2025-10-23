<?php
session_start();
header('Content-Type: application/json');
require_once '../db.php';

/**
 * API to fetch a list of Purchase Orders that have been marked as 'Delivered',
 * which means they have an associated Inspection and Acceptance Report (IAR).
 */

// Security check: ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $sql = "
        SELECT 
            p.po_id,
            p.po_number,
            p.order_date,
            p.status,
            s.supplier_name,
            (SELECT SUM(pi.quantity * pi.unit_cost) FROM tbl_po_item pi WHERE pi.po_id = p.po_id) AS total_amount
        FROM 
            tbl_po p
        JOIN 
            tbl_supplier s ON p.supplier_id = s.supplier_id
        WHERE
            p.status = 'Delivered'
        ORDER BY 
            p.order_date DESC;
    ";
    $stmt = $pdo->query($sql);
    echo json_encode(['data' => $stmt->fetchAll()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>