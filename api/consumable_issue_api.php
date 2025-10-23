<?php
session_start();
header('Content-Type: application/json');

require_once '../db.php';
require_once 'utils.php'; // Ensure utils is included for getNextNumber

// --- Security Check: Ensure user is logged in ---
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit;
}

// --- Input Validation ---
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input data.']);
    exit;
}

$issued_to = trim($data['issued_to'] ?? '');
$date_issued = $data['date_issued'] ?? '';
$items = $data['items'] ?? [];
$user_id = $_SESSION['user_id'];

if (empty($issued_to) || empty($date_issued) || empty($items)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields: Issued To, Date, or Items.']);
    exit;
}

// --- Database Transaction ---
$pdo->beginTransaction();

try {
    // Generate the RIS Number first
    $current_year = date('Y', strtotime($date_issued));
    $ris_info = getNextNumber($pdo, 'tbl_ris_number', $current_year);

    // 1. Create the main issuance record
    $stmt = $pdo->prepare("INSERT INTO tbl_issuance (ris_number, issued_to, date_issued, issued_by_user_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$ris_info['number'], $issued_to, $date_issued, $user_id]);
    $issuance_id = $pdo->lastInsertId();

    // Prepare statements for item processing
    $checkStockStmt = $pdo->prepare("SELECT current_stock FROM tbl_consumable WHERE consumable_id = ? FOR UPDATE");
    $updateStockStmt = $pdo->prepare("UPDATE tbl_consumable SET current_stock = current_stock - ? WHERE consumable_id = ?");
    $insertItemStmt = $pdo->prepare("INSERT INTO tbl_issuance_item (issuance_id, consumable_id, quantity_issued) VALUES (?, ?, ?)");

    // 2. Process each item
    foreach ($items as $item) {
        $consumable_id = $item['consumable_id'];
        $quantity_issued = intval($item['quantity_issued']);

        // Validate item data
        if ($quantity_issued <= 0) {
            throw new Exception("Invalid quantity for an item. Must be greater than zero.");
        }

        // Check for sufficient stock (and lock the row)
        $checkStockStmt->execute([$consumable_id]);
        $current_stock = $checkStockStmt->fetchColumn();

        if ($current_stock === false) {
            throw new Exception("Item with ID {$consumable_id} not found.");
        }

        if ($quantity_issued > $current_stock) {
            throw new Exception("Insufficient stock for an item. Requested: {$quantity_issued}, Available: {$current_stock}.");
        }

        // Update the stock level
        $updateStockStmt->execute([$quantity_issued, $consumable_id]);

        // Insert the issuance item record
        $insertItemStmt->execute([$issuance_id, $consumable_id, $quantity_issued]);
    }

    // 3. If all items processed successfully, commit the transaction
    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Items issued successfully.', 'issuance_id' => $issuance_id]);

} catch (Exception $e) {
    // If any error occurs, roll back all changes
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log the detailed error for debugging
    error_log("Consumable Issuance Error: " . $e->getMessage());

    // Send a user-friendly error message
    http_response_code(400); // Bad Request is appropriate for business logic failures like insufficient stock
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>