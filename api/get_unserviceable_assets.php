<?php
session_start();
header('Content-Type: application/json');
require_once '../db.php';

// Security Check: Ensure the user is logged in and has appropriate role
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $unserviceable_assets = [];

    // Fetch unserviceable SEP items
    $stmt_sep = $pdo->query("
        SELECT
            sep_id AS asset_id,
            'SEP' AS asset_type,
            s.property_number,
            COALESCE(pi.description, iici.description, 'N/A') as description,
            s.serial_number,
            s.current_condition,
            s.date_acquired
        FROM tbl_sep s
        LEFT JOIN tbl_po_item pi ON s.po_item_id = pi.po_item_id
        LEFT JOIN tbl_incoming_ics_item iici ON s.incoming_ics_item_id = iici.incoming_ics_item_id
        WHERE TRIM(UPPER(current_condition)) = 'UNSERVICEABLE'
    ");
    $unserviceable_sep = $stmt_sep->fetchAll(PDO::FETCH_ASSOC);

    // Fetch unserviceable PPE items
    $stmt_ppe = $pdo->query("
        SELECT
            ppe_id AS asset_id,
            'PPE' AS asset_type,
            p.property_number,
            COALESCE(pi.description, iici.description, 'N/A') as description,
            p.serial_number,
            p.current_condition,
            p.date_acquired
        FROM tbl_ppe p
        LEFT JOIN tbl_po_item pi ON p.po_item_id = pi.po_item_id
        LEFT JOIN tbl_incoming_ics_item iici ON p.incoming_ics_item_id = iici.incoming_ics_item_id
        WHERE TRIM(UPPER(current_condition)) = 'UNSERVICEABLE'
    ");
    $unserviceable_ppe = $stmt_ppe->fetchAll(PDO::FETCH_ASSOC);

    $unserviceable_assets = array_merge($unserviceable_sep, $unserviceable_ppe);

    echo json_encode(['success' => true, 'data' => $unserviceable_assets]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>