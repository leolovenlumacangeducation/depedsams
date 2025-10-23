<?php
session_start();
header('Content-Type: application/json');
require_once 'utils.php'; // For getNextNumber

require_once '../db.php';

// Security check
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
$adjustments = $input['adjustments'] ?? [];
$as_of_date = $input['as_of_date'] ?? date('Y-m-d');

if (empty($adjustments)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No adjustments data provided.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Create the RPCI document header
    $current_year = date('Y', strtotime($as_of_date));
    $rpci_info = getNextNumber($pdo, 'tbl_rpci_number', $current_year);

    $sql_rpci = "INSERT INTO tbl_rpci (rpci_number, as_of_date, created_by_user_id) VALUES (?, ?, ?)";
    $stmt_rpci = $pdo->prepare($sql_rpci);
    $stmt_rpci->execute([$rpci_info['number'], $as_of_date, $_SESSION['user_id']]);
    $rpci_id = $pdo->lastInsertId();

    // Prepare statements for items and stock update
    $sql_item = "INSERT INTO tbl_rpci_item (rpci_id, consumable_id, balance_per_card, on_hand_per_count, shortage_qty, shortage_value, remarks) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt_item = $pdo->prepare($sql_item);

    $sql_update = "UPDATE tbl_consumable SET current_stock = ? WHERE consumable_id = ?";
    $stmt_update = $pdo->prepare($sql_update);

    foreach ($adjustments as $adj) {
        $physical_count = $adj['physical_count'];
        $consumable_id = $adj['consumable_id'];

        if (is_numeric($physical_count) && is_numeric($consumable_id)) {
            // 2. Insert the item details into the RPCI document
            $stmt_item->execute([
                $rpci_id, $consumable_id,
                $adj['balance_per_card'], $physical_count,
                $adj['shortage_qty'], $adj['shortage_value'],
                $adj['remarks']
            ]);
            // 3. Update the actual stock level
            $stmt_update->execute([$physical_count, $consumable_id]);
        }
    }

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Inventory stock levels have been successfully adjusted and RPCI document has been saved.']);

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    error_log("RPCI Stock Adjust API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred during stock adjustment.']);
}
?>