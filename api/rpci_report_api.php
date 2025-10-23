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
    // Fetch all consumable items that have stock or have been stocked before.
    $sql_items = "SELECT 
                    c.consumable_id,
                    c.stock_number,
                    pi.description,
                    u.unit_name,
                    c.unit_cost,
                    c.current_stock
                FROM tbl_consumable c
                JOIN tbl_po_item pi ON c.po_item_id = pi.po_item_id
                JOIN tbl_unit u ON c.unit_id = u.unit_id
                WHERE c.current_stock >= 0 -- Includes items that are currently out of stock
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
    error_log("RPCI Report API Error: " . $e->getMessage());
    echo json_encode(['error' => 'A database error occurred.']);
}
?>