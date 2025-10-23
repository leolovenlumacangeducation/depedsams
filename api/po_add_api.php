<?php
// Suppress warnings and notices that could break JSON output
error_reporting(E_ERROR);
ini_set('display_errors', 0);

session_start();
header('Content-Type: application/json');

require_once '../db.php';
require_once '../includes/functions.php';

// Security check: ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// --- Data Validation ---
if (empty($input['header']) || empty($input['items'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid data. Header and items are required.']);
    exit;
}

$header = $input['header'];
$items = $input['items'];

// Basic validation for required header fields
$required_fields = ['supplier_id', 'order_date', 'purchase_mode_id', 'delivery_place_id', 'delivery_term_id', 'payment_term_id'];
foreach ($required_fields as $field) {
    if (empty($header[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field in header: $field"]);
        exit;
    }
}

// --- Database Transaction ---
try {
    $pdo->beginTransaction();

    // --- 1. Handle PO Number (Manual or Auto-generated) ---
    $manual_po_number = trim($header['po_number'] ?? '');
    $po_number_to_save = '';
    $is_auto_generated = false;

    if (!empty($manual_po_number)) {
        // --- MANUAL PO NUMBER ---
        // Check for uniqueness
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_po WHERE po_number = ?");
        $stmt->execute([$manual_po_number]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Purchase Order number '{$manual_po_number}' already exists.");
        }
        $po_number_to_save = $manual_po_number;
    } else {
        // --- AUTO-GENERATED PO NUMBER ---
        // Generate new number
        $po_info = generateNextNumber($pdo, 'tbl_po_number', 'PO', 'po_number_format');
        $po_number_to_save = $po_info['next_number'];
        $is_auto_generated = true;
    }

    // 2. Insert into tbl_po (header)
    $sql_po = "INSERT INTO tbl_po (po_number, supplier_id, purchase_mode_id, delivery_place_id, delivery_term_id, payment_term_id, order_date) 
               VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt_po = $pdo->prepare($sql_po);
    $stmt_po->execute([
        $po_number_to_save,
        $header['supplier_id'],
        $header['purchase_mode_id'],
        $header['delivery_place_id'],
        $header['delivery_term_id'],
        $header['payment_term_id'],
        $header['order_date']
    ]);
    $po_id = $pdo->lastInsertId();

    // 3. Insert into tbl_po_item (items)
    $sql_item = "INSERT INTO tbl_po_item (po_id, category_id, description, quantity, unit_id, unit_cost) 
                 VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_item = $pdo->prepare($sql_item);

    foreach ($items as $item) {
        $stmt_item->execute([$po_id, $item['category_id'], $item['description'], $item['quantity'], $item['unit_id'], $item['unit_cost']]);
    }

    // 4. IMPORTANT: Only increment the sequence if we used an auto-generated number
    if ($is_auto_generated) {
        $year = date('Y', strtotime($header['order_date']));
        $sql_update_seq = "UPDATE tbl_po_number SET start_count = start_count + 1 WHERE serial = 'default' AND year = ?";
        $stmt_update_seq = $pdo->prepare($sql_update_seq);
        $stmt_update_seq->execute([$year]);
    }

    // If all queries succeeded, commit the transaction
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Purchase Order created successfully!', 'po_id' => $po_id, 'po_number' => $po_number_to_save]);

} catch (Exception $e) {
    // If any query fails, roll back the entire transaction
    $pdo->rollBack();
    error_log("PO Add API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'A database error occurred while creating the PO.', 'error' => $e->getMessage()]);
}
?>