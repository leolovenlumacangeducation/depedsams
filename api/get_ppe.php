<?php
session_start();
header('Content-Type: application/json');
require_once '../db.php';

/**
 * API to fetch Property, Plant, and Equipment (PPE) items.
 *
 * This API fetches PPE items for both card and table views, correctly retrieving
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
            p.ppe_id,
            p.property_number,
            p.serial_number,
            p.model_number,
            p.date_acquired,
            p.current_condition,
            p.photo,
            p.assigned_to_user_id,
            p.has_been_assigned, -- Added this
            -- Use COALESCE to get the description from the first non-null source
            COALESCE(poi.description, iici.description) AS description,
            u.photo AS assigned_to_photo,
            u.full_name AS assigned_to,
            first_assignment.first_assignment_date, -- Added this
            CASE 
                WHEN p.po_item_id IS NOT NULL THEN 'Purchase Order'
                WHEN p.incoming_ics_item_id IS NOT NULL THEN 'Incoming ICS'
                ELSE 'Manual Entry'
            END AS acquisition_source,
            -- Use COALESCE to get the unit_cost from the first non-null source
            COALESCE(poi.unit_cost, iici.unit_cost) AS unit_cost
        FROM 
            tbl_ppe p
        LEFT JOIN
            tbl_po_item poi ON p.po_item_id = poi.po_item_id
        LEFT JOIN
            tbl_incoming_ics_item iici ON p.incoming_ics_item_id = iici.incoming_ics_item_id
        LEFT JOIN 
            tbl_user u ON p.assigned_to_user_id = u.user_id
        LEFT JOIN (
            SELECT
                pi.ppe_id,
                MIN(p_header.date_issued) as first_assignment_date
            FROM tbl_par_item pi
            JOIN tbl_par p_header ON pi.par_id = p_header.par_id
            WHERE p_header.status = 'Active'
            GROUP BY pi.ppe_id
        ) AS first_assignment ON p.ppe_id = first_assignment.ppe_id -- Subquery to get first assignment date
        ORDER BY 
            p.date_acquired DESC, COALESCE(poi.description) ASC;
    ";

    $stmt = $pdo->query($sql);
    $ppe_items = $stmt->fetchAll();

    echo json_encode(['success' => true, 'data' => $ppe_items]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>