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
    $sql = "SELECT 
                s.sep_id,
                s.property_number,
                s.serial_number,
                s.brand_name,
                s.date_acquired,
                s.estimated_useful_life,
                s.current_condition,
                s.photo,
                pi.description,
                pi.unit_cost,
                u.full_name AS assigned_to,
                u.photo AS assigned_to_photo,
                -- Subquery to get the most recent ICS ID associated with this SEP item
                (SELECT ii.ics_id FROM tbl_ics_item ii WHERE ii.sep_id = s.sep_id ORDER BY ii.ics_id DESC LIMIT 1) AS ics_id,
                -- Subquery to check if it has ever been assigned via an ICS
                EXISTS(SELECT 1 FROM tbl_ics_item ii WHERE ii.sep_id = s.sep_id) AS has_been_assigned
            FROM tbl_sep s
            JOIN tbl_po_item pi ON s.po_item_id = pi.po_item_id
            LEFT JOIN tbl_user u ON s.assigned_to_user_id = u.user_id";

    $stmt = $pdo->query($sql);
    echo json_encode(['data' => $stmt->fetchAll()]);
} catch (PDOException $e) {
    http_response_code(500);
    error_log("SEP List API Error: " . $e->getMessage());
    echo json_encode(['error' => 'A database error occurred.']);
}