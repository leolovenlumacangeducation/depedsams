<?php
session_start();
header('Content-Type: application/json');
require_once '../db.php';
require_once 'utils.php'; // For getNextNumber

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
$counted_items = $input['counted_items'] ?? [];
$as_of_date = $input['as_of_date'] ?? date('Y-m-d');

if (empty($counted_items)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No item data provided.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Create the RPCPPE document header
    $current_year = date('Y', strtotime($as_of_date));
    $rpcppe_info = getNextNumber($pdo, 'tbl_rpcppe_number', $current_year);

    $sql_rpcppe = "INSERT INTO tbl_rpcppe (rpcppe_number, as_of_date, created_by_user_id) VALUES (?, ?, ?)";
    $stmt_rpcppe = $pdo->prepare($sql_rpcppe);
    $stmt_rpcppe->execute([$rpcppe_info['number'], $as_of_date, $_SESSION['user_id']]);
    $rpcppe_id = $pdo->lastInsertId();

    // 2. Insert the counted items
    $sql_item = "INSERT INTO tbl_rpcppe_item (rpcppe_id, ppe_id, on_hand_per_count, shortage_qty, shortage_value, remarks) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_item = $pdo->prepare($sql_item);

    foreach ($counted_items as $item) {
        $stmt_item->execute([
            $rpcppe_id,
            $item['ppe_id'],
            $item['on_hand_count'],
            $item['shortage_qty'],
            $item['shortage_value'],
            $item['remarks']
        ]);
    }

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Physical count has been successfully saved as an RPCPPE document.']);

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    error_log("RPCPPE Save Count API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred while saving the count.']);
}
?>