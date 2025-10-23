<?php
session_start();
header('Content-Type: application/json');
require_once '../db.php';

// Security check: ensure user is an Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden: You do not have permission.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$type = $_GET['type'] ?? null;

// Whitelist of allowed lookup types and their table/column names
$allowed_types = [
    'unit' => ['table' => 'tbl_unit', 'id' => 'unit_id', 'name' => 'unit_name'],
    'position' => ['table' => 'tbl_position', 'id' => 'position_id', 'name' => 'position_name'],
    'role' => ['table' => 'tbl_role', 'id' => 'role_id', 'name' => 'role_name'],
    'inventory_type' => ['table' => 'tbl_inventory_type', 'id' => 'inventory_type_id', 'name' => 'inventory_type_name'],
    'purchase_mode' => ['table' => 'tbl_purchase_mode', 'id' => 'purchase_mode_id', 'name' => 'mode_name'],
    'delivery_place' => ['table' => 'tbl_delivery_place', 'id' => 'delivery_place_id', 'name' => 'place_name'],
    'delivery_term' => ['table' => 'tbl_delivery_term', 'id' => 'delivery_term_id', 'name' => 'term_description'],
    'payment_term' => ['table' => 'tbl_payment_term', 'id' => 'payment_term_id', 'name' => 'term_description'],
];

if (!$type || !isset($allowed_types[$type])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid lookup type specified.']);
    exit;
}

$config = $allowed_types[$type];
$table = $config['table'];
$id_col = $config['id'];
$name_col = $config['name'];

try {
    switch ($method) {
        case 'GET':
            $stmt = $pdo->query("SELECT {$id_col} as id, {$name_col} as name FROM {$table} ORDER BY {$name_col}");
            echo json_encode(['data' => $stmt->fetchAll()]);
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? null;
            $name = trim($input['name'] ?? '');

            if (empty($name)) {
                throw new Exception("Name/Description cannot be empty.");
            }

            if ($id) {
                $stmt = $pdo->prepare("UPDATE {$table} SET {$name_col} = ? WHERE {$id_col} = ?");
                $stmt->execute([$name, $id]);
                echo json_encode(['success' => true, 'message' => 'Item updated successfully.']);
            } else {
                $stmt = $pdo->prepare("INSERT INTO {$table} ({$name_col}) VALUES (?)");
                $stmt->execute([$name]);
                echo json_encode(['success' => true, 'message' => 'Item added successfully.']);
            }
            break;

        case 'DELETE':
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? null;

            if (!$id) {
                throw new Exception("ID is required for deletion.");
            }

            $stmt = $pdo->prepare("DELETE FROM {$table} WHERE {$id_col} = ?");
            $stmt->execute([$id]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Item deleted successfully.']);
            } else {
                throw new Exception('Item not found or already deleted.');
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
            break;
    }
} catch (PDOException $e) {
    if ($e->errorInfo[1] == 1062) { // Unique constraint violation
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'This name/description already exists.']);
    } else if ($e->errorInfo[1] == 1451) { // Foreign key constraint violation
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Cannot delete this item because it is currently in use.']);
    } else {
        error_log("Lookup API Error ({$type}): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>