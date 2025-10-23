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

$user_id = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);

if (!$user_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user ID specified.']);
    exit;
}

try {
    $history = [];

    // 1. Fetch PPE History (Assignments and Returns)
    $sql_ppe = "SELECT 
                    h.transaction_date AS date,
                    h.transaction_type AS type,
                    'par' AS doc_type,
                    h.reference AS doc_number,
                    par.par_id AS doc_id,
                    po_item.description AS item_description,
                    ppe.property_number AS item_number,
                    1 AS quantity
                FROM tbl_ppe_history h
                LEFT JOIN tbl_par par ON h.reference = par.par_number
                JOIN tbl_ppe ppe ON h.ppe_id = ppe.ppe_id
                JOIN tbl_po_item po_item ON ppe.po_item_id = po_item.po_item_id
                WHERE h.to_user_id = ? OR h.from_user_id = ?
                ORDER BY h.transaction_date DESC";
    $stmt_ppe = $pdo->prepare($sql_ppe);
    $stmt_ppe->execute([$user_id, $user_id]);
    $history = array_merge($history, $stmt_ppe->fetchAll());

    // 2. Fetch SEP History (from ICS documents, including voided)
    $sql_sep = "SELECT 
                    i.date_issued AS date,
                    'Assignment' AS type, 
                    'ics' AS doc_type,
                    i.ics_id AS doc_id,
                    i.ics_number AS doc_number,
                    po_item.description AS item_description,
                    sep.property_number AS item_number,
                    1 AS quantity
                FROM tbl_ics i
                JOIN tbl_ics_item ii ON i.ics_id = ii.ics_id
                JOIN tbl_sep sep ON ii.sep_id = sep.sep_id
                JOIN tbl_po_item po_item ON sep.po_item_id = po_item.po_item_id
                WHERE i.issued_to_user_id = ?
                ORDER BY i.date_issued DESC";
    $stmt_sep = $pdo->prepare($sql_sep);
    $stmt_sep->execute([$user_id]);
    $history = array_merge($history, $stmt_sep->fetchAll());

    // 3. Fetch Consumable History (from RIS documents)
    $user_name = $pdo->prepare("SELECT full_name FROM tbl_user WHERE user_id = ?");
    $user_name->execute([$user_id]);
    $full_name = $user_name->fetchColumn();

    if ($full_name) {
        $sql_ris = "SELECT i.date_issued AS date, 'Issuance' AS type, 'ris' AS doc_type, i.ris_number AS doc_number, i.issuance_id as doc_id, po_item.description AS item_description, c.stock_number AS item_number, ii.quantity_issued AS quantity FROM tbl_issuance i JOIN tbl_issuance_item ii ON i.issuance_id = ii.issuance_id JOIN tbl_consumable c ON ii.consumable_id = c.consumable_id JOIN tbl_po_item po_item ON c.po_item_id = po_item.po_item_id WHERE i.issued_to = ? ORDER BY i.date_issued DESC";
        $stmt_ris = $pdo->prepare($sql_ris);
        $stmt_ris->execute([$full_name]);
        $history = array_merge($history, $stmt_ris->fetchAll());
    }

    // Sort the combined history by date, descending
    usort($history, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });

    echo json_encode(['success' => true, 'data' => $history]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("User Full History API Error: " . $e->getMessage());
    echo json_encode(['error' => 'A database error occurred.']);
}
?>