<?php
session_start();
header('Content-Type: application/json');
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'User not authenticated.']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

try {
    $sql = "SELECT p.*, 
                       COALESCE(pi.description, iici.description) as description,
                       COALESCE(pi.unit_cost, iici.unit_cost) as unit_cost,
                       u.full_name as assigned_to
            FROM tbl_ppe p
            LEFT JOIN tbl_po_item pi ON p.po_item_id = pi.po_item_id
            LEFT JOIN tbl_incoming_ics_item iici ON p.incoming_ics_item_id = iici.incoming_ics_item_id
            LEFT JOIN tbl_user u ON p.assigned_to_user_id = u.user_id
            WHERE p.assigned_to_user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['data' => $data]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>