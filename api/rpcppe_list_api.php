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
                r.rpcppe_id,
                r.rpcppe_number,
                r.as_of_date,
                r.date_created,
                u.full_name AS created_by
            FROM tbl_rpcppe r
            JOIN tbl_user u ON r.created_by_user_id = u.user_id
            ORDER BY r.as_of_date DESC, r.rpcppe_id DESC";
    
    $stmt = $pdo->query($sql);
    echo json_encode(['data' => $stmt->fetchAll()]);
} catch (PDOException $e) {
    http_response_code(500);
    error_log("RPCPPE List API Error: " . $e->getMessage());
    echo json_encode(['data' => [], 'error' => 'Database error']);
}