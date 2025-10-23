<?php
session_start();
header('Content-Type: application/json');

require_once '../db.php';

// Security check: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['data' => [], 'error' => 'Unauthorized']);
    exit;
}

try {
    // Query for disposed SEP items
    $sql_sep = "SELECT 
                    s.property_number,
                    COALESCE(pi.description, iici.description) AS description,
                    s.date_acquired,
                    'SEP' AS item_type,
                    s.serial_number
                FROM tbl_sep s
                LEFT JOIN tbl_po_item pi ON s.po_item_id = pi.po_item_id
                LEFT JOIN tbl_incoming_ics_item iici ON s.incoming_ics_item_id = iici.incoming_ics_item_id
                WHERE s.current_condition = 'Disposed'";

    // Query for disposed PPE items
    $sql_ppe = "SELECT 
                    p.property_number,
                    COALESCE(pi.description, iici.description) AS description,
                    p.date_acquired,
                    'PPE' AS item_type,
                    p.serial_number
                FROM tbl_ppe p
                LEFT JOIN tbl_po_item pi ON p.po_item_id = pi.po_item_id
                LEFT JOIN tbl_incoming_ics_item iici ON p.incoming_ics_item_id = iici.incoming_ics_item_id
                WHERE p.current_condition = 'Disposed'";

    // Combine the queries and fetch all results
    $stmt = $pdo->query("($sql_sep) UNION ALL ($sql_ppe) ORDER BY date_acquired DESC");
    $disposed_items = $stmt->fetchAll();

    echo json_encode(['data' => $disposed_items]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Disposed Items API Error: " . $e->getMessage());
    echo json_encode(['data' => [], 'error' => 'A database error occurred.']);
}
?>