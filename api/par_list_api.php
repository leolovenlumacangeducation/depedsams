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
                p.par_id,
                p.par_number,
                p.date_issued,
                custodian.full_name AS issued_to,
                issuer.full_name AS issued_by
            FROM tbl_par p
            JOIN tbl_user custodian ON p.issued_to_user_id = custodian.user_id
            JOIN tbl_user issuer ON p.issued_by_user_id = issuer.user_id
            ORDER BY p.date_issued DESC, p.par_id DESC";

    $stmt = $pdo->query($sql);
    echo json_encode(['data' => $stmt->fetchAll()]);
} catch (PDOException $e) {
    http_response_code(500);
    error_log("PAR List API Error: " . $e->getMessage());
    echo json_encode(['error' => 'A database error occurred.']);
}