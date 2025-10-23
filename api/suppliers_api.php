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

try {
    switch ($method) {
        case 'GET':
            // Fetch all suppliers for the DataTable
            $stmt = $pdo->query("SELECT * FROM tbl_supplier ORDER BY supplier_name");
            $suppliers = $stmt->fetchAll();
            echo json_encode(['data' => $suppliers]);
            break;

        case 'POST':
            // Handle both creating and updating suppliers
            $input = json_decode(file_get_contents('php://input'), true);

            // --- Data Validation ---
            $id = $input['supplier_id'] ?? null;
            $name = trim($input['supplier_name'] ?? '');
            $address = trim($input['address'] ?? '');
            $contact_person = trim($input['contact_person'] ?? '');
            $contact_no = trim($input['contact_no'] ?? '');
            $tin = trim($input['tin'] ?? '');

            if (empty($name)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Supplier Name is required.']);
                exit;
            }

            if ($id) {
                // --- Update Existing Supplier ---
                $sql = "UPDATE tbl_supplier SET supplier_name = ?, address = ?, contact_person = ?, contact_no = ?, tin = ? WHERE supplier_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$name, $address, $contact_person, $contact_no, $tin, $id]);
                echo json_encode(['success' => true, 'message' => 'Supplier updated successfully.']);
            } else {
                // --- Add New Supplier ---
                $sql = "INSERT INTO tbl_supplier (supplier_name, address, contact_person, contact_no, tin) VALUES (?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$name, $address, $contact_person, $contact_no, $tin]);
                echo json_encode(['success' => true, 'message' => 'New supplier added successfully.']);
            }
            break;

        case 'DELETE':
            // Handle deleting a supplier
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['supplier_id'] ?? null;

            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Supplier ID is required for deletion.']);
                exit;
            }

            // Check if the supplier is linked to any purchase orders
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM tbl_po WHERE supplier_id = ?");
            $stmt_check->execute([$id]);
            if ($stmt_check->fetchColumn() > 0) {
                http_response_code(409); // Conflict
                echo json_encode(['success' => false, 'message' => 'Cannot delete supplier. It is associated with one or more purchase orders.']);
                exit;
            }

            $sql = "DELETE FROM tbl_supplier WHERE supplier_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Supplier deleted successfully.']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Supplier not found.']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
            break;
    }
} catch (PDOException $e) {
    error_log("Suppliers API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
}
?>