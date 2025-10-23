<?php
session_start();
header('Content-Type: application/json');
require_once '../db.php';

/**
 * API to fetch Consumable items (Supplies & Materials).
 */

try {
    // Some installations may not have a custodian_user_id column on tbl_consumable.
    // We'll attempt to include custodian_user_id and an assigned_to name where possible
    // without causing SQL errors on older schemas.
    // The query always returns `custodian_user_id` and `assigned_to` (may be NULL).
    $sql = "
        SELECT 
            c.consumable_id,
            c.stock_number,
            c.quantity_received,
            c.unit_cost,
            c.current_stock,
            c.date_received,
            c.photo,
            un.unit_name,
            COALESCE(poi.description, iici.description) AS description,
            CASE 
                WHEN c.po_item_id IS NOT NULL THEN 'Purchase Order'
                WHEN c.incoming_ics_item_id IS NOT NULL THEN 'Incoming ICS'
                ELSE 'Manual Entry'
            END AS acquisition_source,
            -- Defensive aliases: if the column exists this will return its value, otherwise NULL
            (CASE WHEN 1=1 THEN (NULL) ELSE NULL END) AS custodian_user_id,
            NULL AS assigned_to
        FROM 
            tbl_consumable c
        LEFT JOIN 
            tbl_po_item poi ON c.po_item_id = poi.po_item_id
        LEFT JOIN 
            tbl_incoming_ics_item iici ON c.incoming_ics_item_id = iici.incoming_ics_item_id
        LEFT JOIN
            tbl_unit un ON c.unit_id = un.unit_id
        ORDER BY 
            COALESCE(poi.description, iici.description) ASC;
    ";
    $stmt = $pdo->query($sql);
    $items = $stmt->fetchAll();
    // Post-process: if the table actually has a custodian_user_id column, fetch it separately and merge
    $cols = $pdo->query("SHOW COLUMNS FROM tbl_consumable LIKE 'custodian_user_id'")->fetchAll();
    if (count($cols) > 0) {
        // Re-run a richer query including the custodian and assigned user
        $sql2 = "
            SELECT 
                c.consumable_id,
                c.stock_number,
                c.quantity_received,
                c.unit_cost,
                c.current_stock,
                c.date_received,
                c.photo,
                un.unit_name,
                COALESCE(poi.description, iici.description) AS description,
                CASE 
                    WHEN c.po_item_id IS NOT NULL THEN 'Purchase Order'
                    WHEN c.incoming_ics_item_id IS NOT NULL THEN 'Incoming ICS'
                    ELSE 'Manual Entry'
                END AS acquisition_source,
                c.custodian_user_id,
                u.full_name AS assigned_to
            FROM tbl_consumable c
            LEFT JOIN tbl_po_item poi ON c.po_item_id = poi.po_item_id
            LEFT JOIN tbl_incoming_ics_item iici ON c.incoming_ics_item_id = iici.incoming_ics_item_id
            LEFT JOIN tbl_unit un ON c.unit_id = un.unit_id
            LEFT JOIN tbl_user u ON c.custodian_user_id = u.user_id
            ORDER BY COALESCE(poi.description, iici.description) ASC
        ";
        $stmt2 = $pdo->query($sql2);
        $items = $stmt2->fetchAll();
    }

    echo json_encode(['success' => true, 'data' => $items]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>