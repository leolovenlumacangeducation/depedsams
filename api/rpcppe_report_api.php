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
    // Fetch all non-disposed PPE items
    $sql_items = "SELECT 
                    p.ppe_id,
                    p.property_number,
                    COALESCE(pi.description, iici.description) AS description,
                    u.unit_name,
                    COALESCE(pi.unit_cost, iici.unit_cost) AS unit_cost,
                    p.current_condition,
                    usr.full_name AS assigned_to
                FROM tbl_ppe p
                LEFT JOIN tbl_po_item pi ON p.po_item_id = pi.po_item_id
                LEFT JOIN tbl_incoming_ics_item iici ON p.incoming_ics_item_id = iici.incoming_ics_item_id
                LEFT JOIN tbl_unit u ON COALESCE(pi.unit_id, iici.unit_id) = u.unit_id
                LEFT JOIN tbl_user usr ON p.assigned_to_user_id = usr.user_id
                WHERE p.current_condition != 'Disposed'
                ORDER BY pi.description ASC";

    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute();
    $items = $stmt_items->fetchAll();

    // Fetch school and officer info
    $school = $pdo->query("SELECT school_name FROM tbl_school LIMIT 1")->fetch();
    $property_custodian = $pdo->query("SELECT u.full_name FROM tbl_officers o JOIN tbl_user u ON o.user_id = u.user_id WHERE o.officer_type = 'Accountable Officer' LIMIT 1")->fetchColumn();
    $approving_officer = $pdo->query("SELECT u.full_name FROM tbl_officers o JOIN tbl_user u ON o.user_id = u.user_id WHERE o.officer_type = 'Approving Officer' LIMIT 1")->fetchColumn();

    echo json_encode([
        'success' => true,
        'items' => $items,
        'school_name' => $school['school_name'] ?? 'N/A',
        'property_custodian' => $property_custodian ?? '________________',
        'approving_officer' => $approving_officer ?? '________________'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("RPCPPE Report API Error: " . $e->getMessage());
    echo json_encode(['error' => 'A database error occurred.']);
}
?>