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
                i.ics_id,
                i.ics_number,
                i.date_issued,
                custodian.full_name AS issued_to,
                issuer.full_name AS issued_by
            FROM tbl_ics i
            JOIN tbl_user custodian ON i.issued_to_user_id = custodian.user_id
            JOIN tbl_user issuer ON i.issued_by_user_id = issuer.user_id
            ORDER BY i.date_issued DESC, i.ics_id DESC";

    $stmt = $pdo->query($sql);
    echo json_encode(['data' => $stmt->fetchAll()]);
} catch (PDOException $e) {
    http_response_code(500);
    error_log("ICS List API Error: " . $e->getMessage());
    echo json_encode(['error' => 'A database error occurred.']);
}