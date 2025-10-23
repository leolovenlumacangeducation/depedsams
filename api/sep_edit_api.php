<?php
session_start();
header('Content-Type: application/json');

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

$sep_id = $input['sep_id'] ?? null;
$brand_name = trim($input['brand_name'] ?? '') ?: null;
$serial_number = trim($input['serial_number'] ?? '') ?: null;
$useful_life = empty($input['estimated_useful_life']) ? null : (int)$input['estimated_useful_life'];
$date_acquired = $input['date_acquired'] ?? null;
$current_condition = $input['current_condition'] ?? 'Serviceable';

if (!$sep_id || !$date_acquired) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'SEP ID and Date Acquired are required.']);
    exit;
}
try {
    $sql = "UPDATE tbl_sep SET 
                brand_name = ?,
                serial_number = ?,
                estimated_useful_life = ?,
                date_acquired = ?,
                current_condition = ?
            WHERE sep_id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$brand_name, $serial_number, $useful_life, $date_acquired, $current_condition, $sep_id]);

    echo json_encode(['success' => true, 'message' => 'SEP details updated successfully.']);

} catch (PDOException $e) {
    error_log("SEP Edit API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'A database error occurred while updating details.']);
}
?>