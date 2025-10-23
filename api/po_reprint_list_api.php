<?php
session_start();
header('Content-Type: application/json');
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['data' => []]);
    exit;
}

try {
    $sql = "SELECT 
                p.po_id,
                p.po_number,
                p.order_date,
                p.status,
                s.supplier_name,
                (SELECT SUM(pi.quantity * pi.unit_cost) FROM tbl_po_item pi WHERE pi.po_id = p.po_id) AS total_amount
            FROM tbl_po p
            JOIN tbl_supplier s ON p.supplier_id = s.supplier_id
            ORDER BY p.order_date DESC, p.po_id DESC";
    
    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll();

    // Format total_amount
    foreach ($results as &$row) {
        $row['total_amount'] = number_format((float)$row['total_amount'], 2);
    }

    echo json_encode(['data' => $results]);
} catch (PDOException $e) {
    http_response_code(500);
    error_log("PO Reprint List API Error: " . $e->getMessage());
    echo json_encode(['data' => [], 'error' => 'Database error']);
}