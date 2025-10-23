<?php
session_start();
header('Content-Type: application/json');
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid User ID']);
    exit;
}

try {
    // Fetch user's full name for consumable lookup
    $user_stmt = $pdo->prepare("SELECT full_name FROM tbl_user WHERE user_id = ?");
    $user_stmt->execute([$user_id]);
    $user_full_name = $user_stmt->fetchColumn();

    // Fetch Consumables issued via RIS
    $consumables_sql = "
        SELECT i.ris_number, c.stock_number, poi.description, ii.quantity_issued, i.date_issued
        FROM tbl_issuance_item ii
        JOIN tbl_issuance i ON ii.issuance_id = i.issuance_id
        JOIN tbl_consumable c ON ii.consumable_id = c.consumable_id
        LEFT JOIN tbl_po_item poi ON c.po_item_id = poi.po_item_id
        WHERE i.issued_to = ?
        ORDER BY i.date_issued DESC
    ";
    $consumables_stmt = $pdo->prepare($consumables_sql);
    $consumables_stmt->execute([$user_full_name]);
    $consumables = $consumables_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch SEP items
    $sep_sql = "
        SELECT 
            s.property_number, 
            COALESCE(poi.description, iici.description) AS description, 
            s.date_acquired, 
            COALESCE(poi.unit_cost, iici.unit_cost) AS unit_cost,
            ics.ics_number
        FROM tbl_sep s
        LEFT JOIN tbl_po_item poi ON s.po_item_id = poi.po_item_id
        LEFT JOIN tbl_incoming_ics_item iici ON s.incoming_ics_item_id = iici.incoming_ics_item_id
        LEFT JOIN tbl_ics_item icsi ON s.sep_id = icsi.sep_id
        LEFT JOIN tbl_ics ics ON icsi.ics_id = ics.ics_id
        WHERE s.assigned_to_user_id = ? AND s.current_condition != 'Disposed'
        ORDER BY s.date_acquired DESC
    ";
    $sep_stmt = $pdo->prepare($sep_sql);
    $sep_stmt->execute([$user_id]);
    $sep_items = $sep_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch PPE items
    $ppe_sql = "
        SELECT 
            p.property_number, 
            COALESCE(poi.description, iici.description) AS description, 
            p.date_acquired, 
            COALESCE(poi.unit_cost, iici.unit_cost) AS unit_cost,
            par.par_number
        FROM tbl_ppe p
        LEFT JOIN tbl_po_item poi ON p.po_item_id = poi.po_item_id
        LEFT JOIN tbl_incoming_ics_item iici ON p.incoming_ics_item_id = iici.incoming_ics_item_id
        LEFT JOIN tbl_par_item pari ON p.ppe_id = pari.ppe_id
        LEFT JOIN tbl_par par ON pari.par_id = par.par_id
        WHERE p.assigned_to_user_id = ? AND p.current_condition != 'Disposed'
        ORDER BY p.date_acquired DESC
    ";
    $ppe_stmt = $pdo->prepare($ppe_sql);
    $ppe_stmt->execute([$user_id]);
    $ppe_items = $ppe_stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => ['consumables' => $consumables, 'sep' => $sep_items, 'ppe' => $ppe_items]]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("User Accountability API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
}
?>