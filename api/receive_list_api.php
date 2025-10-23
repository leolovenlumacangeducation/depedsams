<?php
session_start();
header('Content-Type: application/json');

require_once '../db.php';

// Security check: ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    // The main SQL query to fetch delivery data with DataTables server-side processing
    $sql = "SELECT 
                d.delivery_id,
                d.delivery_receipt_no as dr_number,
                d.date_received,
                p.po_number,
                s.supplier_name
            FROM tbl_delivery d
            JOIN tbl_po p ON d.po_id = p.po_id
            JOIN tbl_supplier s ON p.supplier_id = s.supplier_id";
    
    // Add WHERE clause for search
    $search = $_POST['search']['value'] ?? '';
    if ($search) {
        $sql .= " WHERE d.delivery_receipt_no LIKE :search 
                 OR p.po_number LIKE :search 
                 OR s.supplier_name LIKE :search";
    }
    
    // Add ORDER BY clause
    $orderColumn = isset($_POST['order'][0]['column']) ? $_POST['order'][0]['column'] : 4;
    $orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'desc';
    $columns = ['dr_number', 'po_number', 'supplier_name', 'dr_number', 'date_received'];
    if (isset($columns[$orderColumn])) {
        $sql .= " ORDER BY " . $columns[$orderColumn] . " " . $orderDir;
    } else {
        $sql .= " ORDER BY d.date_received DESC";
    }
    
    // Add LIMIT clause
    if (isset($_POST['length']) && $_POST['length'] != -1) {
        $sql .= " LIMIT :start, :length";
    }

    $stmt = $pdo->prepare($sql);
    
    // Bind search parameter if needed
    if ($search) {
        $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
    }
    
    // Bind limit parameters if needed
    if (isset($_POST['length']) && $_POST['length'] != -1) {
        $stmt->bindValue(':start', intval($_POST['start']), PDO::PARAM_INT);
        $stmt->bindValue(':length', intval($_POST['length']), PDO::PARAM_INT);
    }
    
    $stmt->execute();
    $deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total count for pagination
    $countSql = "SELECT COUNT(*) FROM tbl_delivery";
    $total = $pdo->query($countSql)->fetchColumn();
    
    // Get filtered count
    $filteredSql = "SELECT COUNT(*) 
                    FROM tbl_delivery d
                    JOIN tbl_po p ON d.po_id = p.po_id
                    JOIN tbl_supplier s ON p.supplier_id = s.supplier_id";
    if ($search) {
        $filteredSql .= " WHERE d.delivery_receipt_no LIKE :search 
                         OR p.po_number LIKE :search 
                         OR s.supplier_name LIKE :search";
        $stmt = $pdo->prepare($filteredSql);
        $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        $stmt->execute();
        $filtered = $stmt->fetchColumn();
    } else {
        $filtered = $total;
    }

    echo json_encode([
        'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 0,
        'recordsTotal' => $total,
        'recordsFiltered' => $filtered,
        'data' => $deliveries
    ]);

} catch (PDOException $e) {
    error_log("Receive List API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'A database error occurred.']);
}
?>