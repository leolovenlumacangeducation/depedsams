<?php
session_start();
header('Content-Type: application/json');
require_once '../db.php';

/**
 * API to fetch Semi-Expendable Property (SEP) items.
 *
 * This API fetches SEP items for both card and table views, correctly retrieving
 * item descriptions and other details from either a Purchase Order or an Incoming ICS.
 */

// Security check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $sql = "
        SELECT 
            s.sep_id,
            s.property_number,
            s.serial_number,
            s.brand_name,
            s.date_acquired,
            s.current_condition,
            s.photo,
            s.assigned_to_user_id,
            s.has_been_assigned, -- Added this
            u.full_name AS assigned_to,
            u.photo AS assigned_to_photo,
            COALESCE(poi.description, iici.description) AS description,
            COALESCE(poi.unit_cost, iici.unit_cost) AS unit_cost,
            first_assignment.first_assignment_date, -- Added this
            CASE 
                WHEN s.po_item_id IS NOT NULL THEN 'Purchase Order'
                WHEN s.incoming_ics_item_id IS NOT NULL THEN 'Incoming ICS'
                ELSE 'Manual Entry'
            END AS acquisition_source
        FROM 
            tbl_sep s
        LEFT JOIN 
            tbl_po_item poi ON s.po_item_id = poi.po_item_id
        LEFT JOIN 
            tbl_incoming_ics_item iici ON s.incoming_ics_item_id = iici.incoming_ics_item_id
        LEFT JOIN
            tbl_user u ON s.assigned_to_user_id = u.user_id
        LEFT JOIN (
            SELECT
                ii.sep_id,
                MIN(i.date_issued) as first_assignment_date
            FROM tbl_ics_item ii
            JOIN tbl_ics i ON ii.ics_id = i.ics_id
            WHERE i.status = 'Active'
            GROUP BY ii.sep_id
        ) AS first_assignment ON s.sep_id = first_assignment.sep_id -- Subquery to get first assignment date
        ORDER BY 
            s.date_acquired DESC, COALESCE(poi.description, iici.description) ASC;
    ";

    $stmt = $pdo->query($sql);
    $sep_items = $stmt->fetchAll();

    echo json_encode(['success' => true, 'data' => $sep_items]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>