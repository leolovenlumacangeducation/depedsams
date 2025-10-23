<?php
session_start();
header('Content-Type: application/json');
require_once '../db.php';

/**
 * API to fetch the details of a single Incoming ICS document.
 */

// Security check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$ics_id = $_GET['id'] ?? null;
if (!$ics_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Incoming ICS ID is required.']);
    exit;
}

try {
    // Fetch header information
    $stmt_header = $pdo->prepare("SELECT * FROM tbl_incoming_ics WHERE incoming_ics_id = ?");
    $stmt_header->execute([$ics_id]);
    $header = $stmt_header->fetch();

    if (!$header) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Document not found.']);
        exit;
    }

    // Fetch item details
    $stmt_items = $pdo->prepare("
        SELECT i.*, c.category_name, u.unit_name 
        FROM tbl_incoming_ics_item i
        JOIN tbl_category c ON i.category_id = c.category_id
        JOIN tbl_unit u ON i.unit_id = u.unit_id
        WHERE i.incoming_ics_id = ?
        ORDER BY i.description ASC
    ");
    $stmt_items->execute([$ics_id]);
    $items = $stmt_items->fetchAll();

    echo json_encode(['success' => true, 'data' => ['header' => $header, 'items' => $items]]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Incoming ICS View API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
}
?>