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
$doc_type = $_GET['type'] ?? null;

if (!$user_id || !in_array($doc_type, ['ics', 'par', 'ris'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user ID or document type specified.']);
    exit;
}

try {
    $data = [];
    $user_name = null;

    if ($doc_type === 'ics') {
        $sql = "SELECT 
                    i.ics_id AS doc_id, i.ics_number AS doc_number,
                    item.sep_id AS item_id, item.property_number AS item_number, item.photo,
                    po_item.description
                FROM tbl_ics i
                JOIN tbl_ics_item ii ON i.ics_id = ii.ics_id
                JOIN tbl_sep item ON ii.sep_id = item.sep_id
                JOIN tbl_po_item po_item ON item.po_item_id = po_item.po_item_id
                WHERE i.issued_to_user_id = ? AND i.status = 'Active'
                ORDER BY i.date_issued DESC, i.ics_number DESC, po_item.description ASC";
    } elseif ($doc_type === 'par') {
        $sql = "SELECT 
                    p.par_id AS doc_id, p.par_number AS doc_number,
                    item.ppe_id AS item_id, item.property_number AS item_number, item.photo,
                    po_item.description
                FROM tbl_par p
                JOIN tbl_par_item pi ON p.par_id = pi.par_id
                JOIN tbl_ppe item ON pi.ppe_id = item.ppe_id
                JOIN tbl_po_item po_item ON item.po_item_id = po_item.po_item_id
                WHERE p.issued_to_user_id = ? AND p.status = 'Active'
                ORDER BY p.date_issued DESC, p.par_number DESC, po_item.description ASC";
    } elseif ($doc_type === 'ris') {
        // For RIS, we need to get the user's name first, as it's stored as text.
        $stmt_user = $pdo->prepare("SELECT full_name FROM tbl_user WHERE user_id = ?");
        $stmt_user->execute([$user_id]);
        $user_name = $stmt_user->fetchColumn();
        if ($user_name) {
            $sql = "SELECT 
                        i.issuance_id AS doc_id, i.ris_number AS doc_number,
                        item.consumable_id AS item_id, item.stock_number AS item_number, item.photo,
                        po_item.description,
                        ii.quantity_issued
                    FROM tbl_issuance i
                    JOIN tbl_issuance_item ii ON i.issuance_id = ii.issuance_id
                    JOIN tbl_consumable item ON ii.consumable_id = item.consumable_id
                    JOIN tbl_po_item po_item ON item.po_item_id = po_item.po_item_id
                    WHERE i.issued_to = ?
                    ORDER BY i.date_issued DESC, i.ris_number DESC, po_item.description ASC";
        }
    }

    if (isset($sql)) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([($doc_type === 'ris' ? $user_name : $user_id)]);
        $results = $stmt->fetchAll();

        // Group items by document
        $grouped_data = [];
        foreach ($results as $row) {
            $doc_id = $row['doc_id'];
            if (!isset($grouped_data[$doc_id])) {
                $grouped_data[$doc_id] = [
                    'doc_id' => $doc_id,
                    'doc_number' => $row['doc_number'],
                    'items' => []
                ];
            }
            $grouped_data[$doc_id]['items'][] = $row;
        }
        $data = array_values($grouped_data); // Re-index the array
    }

    echo json_encode(['data' => $data]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("User Documents API Error: " . $e->getMessage());
    echo json_encode(['error' => 'A database error occurred.']);
}
?>