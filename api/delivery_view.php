<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');
require_once '../db.php';

// Verify database connection and tables
try {
    $test_sql = "SELECT COUNT(*) as count FROM tbl_delivery";
    $test_stmt = $pdo->query($test_sql);
    $test_result = $test_stmt->fetch(PDO::FETCH_ASSOC);
    error_log("Found " . $test_result['count'] . " deliveries in database");
} catch (PDOException $e) {
    error_log("Database test query failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Database connection test failed', 
        'error' => $e->getMessage()
    ]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$delivery_id = $_GET['id'] ?? null;
if (!$delivery_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Delivery ID is required']);
    exit;
}

try {
    // Fetch delivery header information
    $sql_header = "SELECT 
                    d.*,
                    p.po_number,
                    p.po_id,
                    p.order_date,
                    s.supplier_name,
                    s.tin as supplier_tin,
                    s.address as supplier_address,
                    u.full_name as received_by_name
                   FROM tbl_delivery d
                   LEFT JOIN tbl_po p ON d.po_id = p.po_id
                   LEFT JOIN tbl_supplier s ON p.supplier_id = s.supplier_id
                   LEFT JOIN tbl_user u ON d.received_by_user_id = u.user_id
                   WHERE d.delivery_id = ?";
    
    $stmt_header = $pdo->prepare($sql_header);
    $stmt_header->execute([$delivery_id]);
    $header = $stmt_header->fetch(PDO::FETCH_ASSOC);

    if (!$header) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Delivery not found']);
        exit;
    }

    // Fetch delivery items with category information
    $sql_items = "SELECT 
                    di.delivery_item_id,
                    di.delivery_id,
                    di.po_item_id,
                    di.quantity_delivered,
                    pi.description,
                    pi.unit_cost,
                    u.unit_name,
                    c.category_name,
                    c.inventory_type_id,
                    it.inventory_type_name,
                    COALESCE(con.stock_number, sep.property_number, ppe.property_number) as item_number
                  FROM tbl_delivery_item di
                  LEFT JOIN tbl_po_item pi ON di.po_item_id = pi.po_item_id
                  LEFT JOIN tbl_unit u ON pi.unit_id = u.unit_id
                  LEFT JOIN tbl_category c ON pi.category_id = c.category_id
                  LEFT JOIN tbl_inventory_type it ON c.inventory_type_id = it.inventory_type_id
                  LEFT JOIN tbl_consumable con ON (con.po_item_id = pi.po_item_id AND con.delivery_id = di.delivery_id)
                  LEFT JOIN tbl_sep sep ON (sep.po_item_id = pi.po_item_id AND sep.delivery_id = di.delivery_id)
                  LEFT JOIN tbl_ppe ppe ON (ppe.po_item_id = pi.po_item_id AND ppe.delivery_id = di.delivery_id)
                  WHERE di.delivery_id = ?";
    
    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute([$delivery_id]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    // Format response data
    $formattedItems = [];
    foreach($items as $item) {
        $formattedItems[] = [
            'inventory_type' => $item['inventory_type_name'],
            'description' => $item['description'],
            'stock_number' => $item['item_number'],
            'quantity' => $item['quantity_delivered'],
            'unit' => $item['unit_name'],
            'unit_cost' => $item['unit_cost'],
            'total' => $item['quantity_delivered'] * $item['unit_cost'],
            'property_number' => $item['item_number']  // Using the same number since it's already the right one based on type
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'header' => $header,
            'items' => $formattedItems
        ]
    ]);

} catch (PDOException $e) {
    $error_details = $e->getMessage();
    $trace = $e->getTraceAsString();
    error_log("Database error in delivery_view: " . $error_details);
    error_log("Stack trace: " . $trace);
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $error_details,
        'debug' => [
            'error' => $error_details,
            'trace' => $trace,
            'delivery_id' => $delivery_id,
            'query_header' => $sql_header,
            'query_items' => $sql_items
        ]
    ]);
}