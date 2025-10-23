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

$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;

if (!$start_date || !$end_date) {
    http_response_code(400);
    echo json_encode(['error' => 'Start date and end date are required.']);
    exit;
}

try {
    // Fetch main issuance details
    $sql_items = "SELECT 
                    i.ris_number,
                    c.stock_number,
                    pi.description,
                    u.unit_name,
                    ii.quantity_issued,
                    c.unit_cost,
                    cat.uacs_object_code
                FROM tbl_issuance_item ii
                JOIN tbl_issuance i ON ii.issuance_id = i.issuance_id
                JOIN tbl_consumable c ON ii.consumable_id = c.consumable_id
                JOIN tbl_po_item pi ON c.po_item_id = pi.po_item_id
                JOIN tbl_unit u ON c.unit_id = u.unit_id
                JOIN tbl_category cat ON pi.category_id = cat.category_id
                WHERE i.date_issued BETWEEN ? AND ?
                ORDER BY i.ris_number, c.stock_number";

    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute([$start_date, $end_date]);
    $items = $stmt_items->fetchAll();

    // Prepare data for recapitulation
    $recap_stock = [];
    $recap_uacs = [];

    foreach ($items as $item) {
        $amount = $item['quantity_issued'] * $item['unit_cost'];
        
        // Recapitulation by Stock Number
        if (!isset($recap_stock[$item['stock_number']])) {
            $recap_stock[$item['stock_number']] = 0;
        }
        $recap_stock[$item['stock_number']] += $item['quantity_issued'];

        // Recapitulation by UACS Code
        $uacs_code = $item['uacs_object_code'] ?? 'N/A';
        if (!isset($recap_uacs[$uacs_code])) {
            $recap_uacs[$uacs_code] = 0;
        }
        $recap_uacs[$uacs_code] += $amount;
    }

    // Fetch school and officer info
    $school = $pdo->query("SELECT school_name FROM tbl_school LIMIT 1")->fetch();
    $property_custodian = $pdo->query("SELECT u.full_name FROM tbl_officers o JOIN tbl_user u ON o.user_id = u.user_id WHERE o.officer_type = 'Accountable Officer' LIMIT 1")->fetchColumn();

    echo json_encode([
        'success' => true,
        'items' => $items,
        'recap_stock' => $recap_stock,
        'recap_uacs' => $recap_uacs,
        'school_name' => $school['school_name'] ?? 'N/A',
        'property_custodian' => $property_custodian ?? '________________'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("RSMI Report API Error: " . $e->getMessage());
    echo json_encode(['error' => 'A database error occurred.']);
}
?>