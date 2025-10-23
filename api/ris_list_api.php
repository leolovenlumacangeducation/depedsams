<?php
session_start();
header('Content-Type: application/json');

require_once '../db.php';

// Security check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $sql = "SELECT 
                i.issuance_id,
                i.ris_number,
                i.date_issued,
                i.issued_to,
                u.full_name AS issued_by
            FROM tbl_issuance i
            JOIN tbl_user u ON i.issued_by_user_id = u.user_id
            ORDER BY i.date_issued DESC, i.issuance_id DESC";

    $stmt = $pdo->query($sql);
    echo json_encode(['data' => $stmt->fetchAll()]);
} catch (PDOException $e) {
    http_response_code(500);
    error_log("RIS List API Error: " . $e->getMessage());
    echo json_encode(['error' => 'A database error occurred.']);
}