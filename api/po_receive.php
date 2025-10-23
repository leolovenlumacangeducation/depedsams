<?php
session_start();
header('Content-Type: application/json');
require_once '../db.php';
require_once 'utils.php'; // Ensure utils is included for getNextNumber

// Security & Method Check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// --- Data Validation ---
$po_id = $input['po_id'] ?? null;
$date_received = $input['date_received'] ?? null;
$delivery_receipt_no = $input['delivery_receipt_no'] ?? null;
$items = $input['items'] ?? [];

if (!$po_id || !$date_received || !$delivery_receipt_no || empty($items)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required data: PO ID, DR No., date, and items are required.']);
    exit;
}

// --- Database Transaction ---
try {
    $pdo->beginTransaction();

    // --- Additional Validation: Check for duplicate Delivery Receipt for the same PO ---
    $stmt_check_dr = $pdo->prepare("SELECT delivery_id FROM tbl_delivery WHERE po_id = ? AND delivery_receipt_no = ?");
    $stmt_check_dr->execute([$po_id, $delivery_receipt_no]);
    if ($stmt_check_dr->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => "This Delivery Receipt number has already been recorded for this Purchase Order."]);
        exit;
    }

    // 1. Insert a record into tbl_delivery
    // Validate the session user exists in tbl_user to avoid FK constraint violations
    $received_by_user_id = null;
    if (!empty($_SESSION['user_id'])) {
        $stmt_check_user = $pdo->prepare("SELECT user_id FROM tbl_user WHERE user_id = ? LIMIT 1");
        $stmt_check_user->execute([$_SESSION['user_id']]);
        if ($stmt_check_user->fetchColumn()) {
            $received_by_user_id = $_SESSION['user_id'];
        } else {
            error_log("po_receive: session user_id {$_SESSION['user_id']} not found in tbl_user; inserting NULL for received_by_user_id");
        }
    }

    $sql_delivery = "INSERT INTO tbl_delivery (po_id, delivery_receipt_no, date_received, received_by_user_id) VALUES (?, ?, ?, ?)";
    $stmt_delivery = $pdo->prepare($sql_delivery);
    $stmt_delivery->execute([$po_id, $delivery_receipt_no, $date_received, $received_by_user_id]);
    $delivery_id = $pdo->lastInsertId();

    // --- Get a list of valid po_item_ids for this PO to validate against ---
    $stmt_valid_items = $pdo->prepare("SELECT po_item_id FROM tbl_po_item WHERE po_id = ?");
    $stmt_valid_items->execute([$po_id]);
    $valid_po_item_ids = $stmt_valid_items->fetchAll(PDO::FETCH_COLUMN);

    // Prepare statements for each inventory type
    $sql_consumable = "INSERT INTO tbl_consumable (po_item_id, stock_number, stock_number_id, quantity_received, unit_id, unit_cost, current_stock, date_received, delivery_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_consumable = $pdo->prepare($sql_consumable);

    $sql_sep = "INSERT INTO tbl_sep (po_item_id, property_number, pn_number_id, serial_number, brand_name, estimated_useful_life, date_acquired, delivery_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_sep = $pdo->prepare($sql_sep);

    $sql_ppe = "INSERT INTO tbl_ppe (po_item_id, property_number, pn_number_id, model_number, serial_number, date_acquired, delivery_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt_ppe = $pdo->prepare($sql_ppe);

    $sql_ppe_history = "INSERT INTO tbl_ppe_history (ppe_id, transaction_date, transaction_type, reference, notes) VALUES (?, CURDATE(), 'Receipt', ?, ?)";
    $stmt_ppe_history = $pdo->prepare($sql_ppe_history);

    $current_year = date('Y', strtotime($date_received));
    
    // Prepare a statement to check for over-receiving
    $sql_check_qty = "SELECT 
                        pi.unit_id,
                        pi.unit_cost,
                        pi.quantity AS quantity_ordered,
                        (
                            COALESCE((SELECT SUM(c.quantity_received) FROM tbl_consumable c WHERE c.po_item_id = pi.po_item_id), 0) + 
                            COALESCE((SELECT COUNT(*) FROM tbl_sep s WHERE s.po_item_id = pi.po_item_id), 0) +
                            COALESCE((SELECT COUNT(*) FROM tbl_ppe p WHERE p.po_item_id = pi.po_item_id), 0)
                        ) AS quantity_already_received,
                        pi.description
                      FROM tbl_po_item pi
                      WHERE pi.po_item_id = ?";
    $stmt_check_qty = $pdo->prepare($sql_check_qty);

    // --- Performance & Security: Fetch PO number once outside the loop ---
    $stmt_get_po_number = $pdo->prepare("SELECT po_number FROM tbl_po WHERE po_id = ?");
    $stmt_get_po_number->execute([$po_id]);
    $po_number = $stmt_get_po_number->fetchColumn();

    foreach ($items as $item) {
        // --- Security Validation: Ensure the item belongs to this PO ---
        if (!in_array($item['po_item_id'], $valid_po_item_ids)) {
            throw new Exception("Invalid item submitted. Item ID {$item['po_item_id']} does not belong to Purchase Order ID {$po_id}.");
        }
        
        // --- Over-receiving Validation ---
        $stmt_check_qty->execute([$item['po_item_id']]);
        $qty_status = $stmt_check_qty->fetch();

        if ($qty_status) {
            $quantity_to_receive = intval($item['quantity']);
            $remaining_qty = intval($qty_status['quantity_ordered']) - intval($qty_status['quantity_already_received']);
            if ($quantity_to_receive > $remaining_qty) {
                throw new Exception("Over-receiving error for item '{$qty_status['description']}'. Ordered: {$qty_status['quantity_ordered']}, Already Received: {$qty_status['quantity_already_received']}, Attempting to Receive: {$quantity_to_receive}. You can only receive {$remaining_qty} more.");
            }
        }

        $inventory_type = $item['inventory_type'] ?? null;
        if (!$inventory_type) throw new Exception("Could not determine inventory type for an item.");

        if ($inventory_type === 'Consumable') {
            $stock_info = getNextNumber($pdo, 'tbl_item_number', $current_year);
            $quantity_to_receive = intval($item['quantity']);
            $stmt_consumable->execute([
                $item['po_item_id'], 
                $stock_info['number'], 
                $stock_info['id'], 
                $quantity_to_receive,
                $qty_status['unit_id'],      // New: Original unit_id
                $qty_status['unit_cost'],    // New: Original unit_cost
                $quantity_to_receive, 
                $date_received, 
                $delivery_id
            ]);
        } else {
            for ($i = 0; $i < $item['quantity']; $i++) {
                $property_info = getNextNumber($pdo, 'tbl_pn_number', $current_year);
                $details = $item['details'][$i] ?? [];
                if ($inventory_type === 'SEP') {
                    $stmt_sep->execute([
                        $item['po_item_id'], 
                        $property_info['number'], 
                        $property_info['id'], 
                        $details['serial_number'] ?? null, 
                        $details['brand_name'] ?? null, 
                        empty($details['useful_life']) ? null : $details['useful_life'],
                        $date_received, 
                        $delivery_id
                    ]);
                } elseif ($inventory_type === 'PPE') {
                    $stmt_ppe->execute([$item['po_item_id'], $property_info['number'], $property_info['id'], $details['model_number'] ?? null, $details['serial_number'] ?? null, $date_received, $delivery_id]);
                    $new_ppe_id = $pdo->lastInsertId();
                    
                    // Log the receipt in the PPE history table
                    $reference = "PO# " . ($po_number ?: 'N/A');
                    $stmt_ppe_history->execute([$new_ppe_id, $reference, "Initial receipt from supplier"]);
                }
            }
        }

        // Add to tbl_delivery_item
        $sql_delivery_item = "INSERT INTO tbl_delivery_item (delivery_id, po_item_id, quantity_delivered) VALUES (?, ?, ?)";
        $stmt_delivery_item = $pdo->prepare($sql_delivery_item);
        $stmt_delivery_item->execute([$delivery_id, $item['po_item_id'], $item['quantity']]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Items have been successfully received into inventory!']);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("PO Receive API Error: " . $e->getMessage());
    // Use 400 for client-side/logic errors (e.g., over-receiving), 500 for actual server errors.
    // Since many exceptions here are logic-based, 400 is often more appropriate.
    $statusCode = ($e instanceof PDOException) ? 500 : 400;
    http_response_code($statusCode);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>