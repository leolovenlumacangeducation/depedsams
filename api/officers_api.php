<?php
session_start();
header('Content-Type: application/json');
require_once '../db.php';

// Security check: ensure user is an Admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$officer_assignments = $_POST['officers'] ?? [];

if (empty($officer_assignments)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No officer assignments submitted.']);
    exit;
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("UPDATE tbl_officers SET user_id = ? WHERE officer_id = ?");
    foreach ($officer_assignments as $officer_id => $user_id) {
        // Use null if the user_id is an empty string
        $stmt->execute([empty($user_id) ? null : $user_id, $officer_id]);
    }
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Officer assignments have been updated successfully.']);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'A database error occurred while updating assignments.']);
}
?>