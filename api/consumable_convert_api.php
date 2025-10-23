<?php
/**
 * API Endpoint for Converting a Consumable Item into Smaller Units
 *
 * Example: Convert 1 "Box of Pens" into 100 "Pieces of Pens".
 */
session_start();
header('Content-Type: application/json');
require_once '../db.php';
require_once 'utils.php'; // Include the new utility file

// --- Security & Method Check ---
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

// --- Input Validation ---
$from_consumable_id = filter_var($input['from_consumable_id'] ?? null, FILTER_VALIDATE_INT);
$quantity_to_convert = filter_var($input['quantity_to_convert'] ?? null, FILTER_VALIDATE_INT);
$conversion_factor = filter_var($input['conversion_factor'] ?? null, FILTER_VALIDATE_INT); // How many new items are created
$to_unit_id = filter_var($input['to_unit_id'] ?? null, FILTER_VALIDATE_INT); // The ID of the new unit

if (!$from_consumable_id || !$quantity_to_convert || !$conversion_factor || !$to_unit_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required data: from_consumable_id, quantity_to_convert, conversion_factor, and to_unit_id are required.']);
    exit;
}

if ($quantity_to_convert <= 0 || $conversion_factor <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Quantity and conversion factor must be positive numbers.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Fetch and lock the source consumable item
    $stmt_from = $pdo->prepare("SELECT * FROM tbl_consumable WHERE consumable_id = ? FOR UPDATE");
    $stmt_from->execute([$from_consumable_id]);
    $from_consumable = $stmt_from->fetch();

    if (!$from_consumable) {
        throw new Exception("Source consumable item not found.");
    }

    // 2. Check if there is enough stock to convert
    if ($from_consumable['current_stock'] < $quantity_to_convert) {
        throw new Exception("Not enough stock to convert. Available: {$from_consumable['current_stock']}, Required: {$quantity_to_convert}.");
    }

    // 3. Decrease stock of the source item
    $new_stock_from = $from_consumable['current_stock'] - $quantity_to_convert;
    $stmt_update_from = $pdo->prepare("UPDATE tbl_consumable SET current_stock = ? WHERE consumable_id = ?");
    $stmt_update_from->execute([$new_stock_from, $from_consumable_id]);

    // 4. Calculate new unit cost and generate a new stock number
    $current_year = date('Y');
    $stock_info = getNextNumber($pdo, 'tbl_item_number', $current_year);
    $total_new_quantity = $quantity_to_convert * $conversion_factor;

    // --- Cost Calculation ---
    // Cost of one original item / number of new items it creates
    $new_unit_cost = $from_consumable['unit_cost'] / $conversion_factor;

    // 5. Create the new consumable record for the smaller units
    $sql_insert_to = "INSERT INTO tbl_consumable 
                        (po_item_id, stock_number, stock_number_id, quantity_received, unit_id, unit_cost, current_stock, date_received, delivery_id, parent_consumable_id) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_insert_to = $pdo->prepare($sql_insert_to);
    $stmt_insert_to->execute([
        $from_consumable['po_item_id'],
        $stock_info['number'],
        $stock_info['id'],
        $total_new_quantity, // quantity_received is the total new items
        $to_unit_id,         // The new unit ID
        $new_unit_cost,      // The newly calculated unit cost
        $total_new_quantity, // current_stock starts at the same value
        date('Y-m-d'),       // The "date_received" is today
        $from_consumable['delivery_id'], // Inherit original delivery
        $from_consumable_id  // Link back to the parent
    ]);
    $to_consumable_id = $pdo->lastInsertId();

    // 6. Log the conversion transaction
    $sql_log = "INSERT INTO tbl_unit_conversion 
                    (from_consumable_id, to_consumable_id, quantity_converted, conversion_factor, converted_by_user_id) 
                VALUES (?, ?, ?, ?, ?)";
    $stmt_log = $pdo->prepare($sql_log);
    $stmt_log->execute([$from_consumable_id, $to_consumable_id, $quantity_to_convert, $total_new_quantity, $_SESSION['user_id']]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => "Successfully converted {$quantity_to_convert} unit(s) into {$total_new_quantity} new units."]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Consumable Convert API Error: " . $e->getMessage());
    http_response_code(400); // Use 400 for logical errors like not enough stock
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>