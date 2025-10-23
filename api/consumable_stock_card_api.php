<?php
session_start();
header('Content-Type: application/json');

require_once '../db.php';

// Security check: ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden: You do not have permission.']);
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Bad Request: Invalid or missing consumable ID.']);
    exit;
}

$consumable_id = intval($_GET['id']);

try {
    // 1. Fetch item details
    $item_stmt = $pdo->prepare("
        SELECT c.current_stock, poi.description
        FROM tbl_consumable c
        LEFT JOIN tbl_po_item poi ON c.po_item_id = poi.po_item_id
        WHERE c.consumable_id = ?
    ");
    $item_stmt->execute([$consumable_id]);
    $item = $item_stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Fetch transaction history
    $transactions_stmt = $pdo->prepare("SELECT * FROM vw_consumable_stock_card WHERE consumable_id = ? ORDER BY transaction_date DESC, transaction_datetime DESC");
    $transactions_stmt->execute([$consumable_id]);
    $transactions = $transactions_stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'item' => $item, 'transactions' => $transactions]);
} catch (PDOException $e) {
    http_response_code(500);
    error_log("Stock Card API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred while fetching stock card data.']);
}
?>