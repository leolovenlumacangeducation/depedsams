<?php
session_start();
header('Content-Type: application/json');
require_once '../db.php';

/**
 * API to fetch a list of all Incoming ICS documents.
 */

// Security check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $sql = "SELECT 
                i.incoming_ics_id,
                i.ics_number,
                i.source_office,
                i.date_received,
                i.issued_by_name,
                u.full_name AS received_by_name
            FROM 
                tbl_incoming_ics i
            LEFT JOIN 
                tbl_user u ON i.received_by_user_id = u.user_id
            ORDER BY 
                i.date_received DESC, i.incoming_ics_id DESC";

    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll();

    echo json_encode(['data' => $data]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Incoming ICS List API Error: " . $e->getMessage());
    echo json_encode(['error' => 'A database error occurred.']);
}
?>