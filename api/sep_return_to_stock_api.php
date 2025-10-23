<?php
session_start();
header('Content-Type: application/json');

require_once '../db.php';

// Security check: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Ensure the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$sep_id = $input['sep_id'] ?? null;

if (!$sep_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'SEP ID is required.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // To "return to stock", we must remove its assignment history from tbl_ics_item.
    // This will make the EXISTS() check in the list API return false.
    $stmt = $pdo->prepare(
        "DELETE FROM tbl_ics_item WHERE sep_id = ?"
    );
    $stmt->execute([$sep_id]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Item has been successfully returned to stock and marked as new.']);

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    error_log("Return to Stock API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
}