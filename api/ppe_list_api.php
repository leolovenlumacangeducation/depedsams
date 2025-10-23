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

$status_filter = $_GET['status'] ?? null; // 'assigned' or 'unassigned'

try {
    $sql = "SELECT 
                p.ppe_id,
                p.property_number,
                p.serial_number,
                p.model_number,
                p.date_acquired,
                p.photo,
                p.current_condition,
                pi.description,
                pi.unit_cost,
                u.full_name AS assigned_to,
                u.photo AS assigned_to_photo
            FROM tbl_ppe p
            JOIN tbl_po_item pi ON p.po_item_id = pi.po_item_id
            LEFT JOIN tbl_user u ON p.assigned_to_user_id = u.user_id";

    if ($status_filter === 'assigned') {
        $sql .= " WHERE p.assigned_to_user_id IS NOT NULL";
    } elseif ($status_filter === 'unassigned') {
        $sql .= " WHERE p.assigned_to_user_id IS NULL";
    }

    // Always add ORDER BY at the end for consistency
    $sql .= " ORDER BY p.date_acquired DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $data = $stmt->fetchAll();

    echo json_encode(['data' => $data]);
} catch (PDOException $e) {
    http_response_code(500);
    error_log("PPE List API Error: " . $e->getMessage());
    echo json_encode(['error' => 'A database error occurred.']);
}