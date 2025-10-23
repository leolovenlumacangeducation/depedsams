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
    // Query for unserviceable SEP items
    $sql_sep = "SELECT 
                    s.property_number,
                    COALESCE(pi.description, iici.description) AS description,
                    s.date_acquired,
                    COALESCE(pi.unit_cost, iici.unit_cost) AS unit_cost,
                    'SEP' AS item_type
                FROM tbl_sep s
                LEFT JOIN tbl_po_item pi ON s.po_item_id = pi.po_item_id
                LEFT JOIN tbl_incoming_ics_item iici ON s.incoming_ics_item_id = iici.incoming_ics_item_id
                WHERE s.current_condition = 'Unserviceable'";

    // Query for unserviceable PPE items
    $sql_ppe = "SELECT 
                    p.property_number,
                    COALESCE(pi.description, iici.description) AS description,
                    p.date_acquired,
                    COALESCE(pi.unit_cost, iici.unit_cost) AS unit_cost,
                    'PPE' AS item_type
                FROM tbl_ppe p
                LEFT JOIN tbl_po_item pi ON p.po_item_id = pi.po_item_id
                LEFT JOIN tbl_incoming_ics_item iici ON p.incoming_ics_item_id = iici.incoming_ics_item_id
                WHERE p.current_condition = 'Unserviceable'";

    // Combine the queries
    $stmt = $pdo->query("($sql_sep) UNION ALL ($sql_ppe) ORDER BY date_acquired DESC");
    $items = $stmt->fetchAll();

    // Fetch school and officer info
    $school = $pdo->query("SELECT school_name FROM tbl_school LIMIT 1")->fetch();
    $property_custodian = $pdo->query("SELECT u.full_name FROM tbl_officers o JOIN tbl_user u ON o.user_id = u.user_id WHERE o.officer_type = 'Accountable Officer' LIMIT 1")->fetchColumn();

    echo json_encode([
        'success' => true,
        'items' => $items,
        'school_name' => $school['school_name'] ?? 'N/A',
        'property_custodian' => $property_custodian ?? '________________'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("IIRUP Report API Error: " . $e->getMessage());
    echo json_encode(['error' => 'A database error occurred.']);
}
?>