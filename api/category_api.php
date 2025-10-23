<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
require_once '../db.php';

// Security check: ensure user is an Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            $sql = "SELECT c.category_id, c.category_name, c.uacs_object_code, c.inventory_type_id, it.inventory_type_name
                    FROM tbl_category c
                    JOIN tbl_inventory_type it ON c.inventory_type_id = it.inventory_type_id
                    ORDER BY c.category_name";
            $stmt = $pdo->query($sql);
            echo json_encode(['data' => $stmt->fetchAll()]);
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['category_id'] ?? null;
            $name = trim($input['category_name'] ?? '');
            $uacs_code = trim($input['uacs_object_code'] ?? '') ?: null; // Set to null if empty
            $type_id = $input['inventory_type_id'] ?? null;

            if (empty($name) || empty($type_id)) {
                throw new Exception("Category Name and Inventory Type are required.");
            }

            if ($id) {
                // Update existing category
                $stmt = $pdo->prepare("UPDATE tbl_category SET category_name = :name, inventory_type_id = :type_id, uacs_object_code = :uacs_code WHERE category_id = :id");
                $stmt->execute(['name' => $name, 'type_id' => $type_id, 'uacs_code' => $uacs_code, 'id' => $id]);
                echo json_encode(['success' => true, 'message' => 'Category updated successfully.']);
            } else {
                // Add new category
                $stmt = $pdo->prepare("INSERT INTO tbl_category (category_name, inventory_type_id, uacs_object_code) VALUES (:name, :type_id, :uacs_code)");
                $stmt->execute(['name' => $name, 'type_id' => $type_id, 'uacs_code' => $uacs_code]);
                echo json_encode(['success' => true, 'message' => 'Category added successfully.']);
            }
            break;

        case 'DELETE':
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['category_id'] ?? null;

            if (!$id) {
                throw new Exception("Category ID is required for deletion.");
            }

            // Check if the category is in use by any PO items
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM tbl_po_item WHERE category_id = ?");
            $stmt_check->execute([$id]);
            if ($stmt_check->fetchColumn() > 0) {
                throw new Exception('Cannot delete this category because it is being used by one or more purchase order items.');
            }

            $stmt = $pdo->prepare("DELETE FROM tbl_category WHERE category_id = ?");
            $stmt->execute([$id]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Category deleted successfully.']);
            } else {
                throw new Exception('Category not found or already deleted.');
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
            break;
    }
} catch (PDOException $e) {
    // Handle potential unique constraint violation for category_name
    if ($e->errorInfo[1] == 1062) {
        http_response_code(409); // Conflict
        echo json_encode(['success' => false, 'message' => 'This category name already exists. Please choose another.']);
    } else {
        error_log("Category API Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>