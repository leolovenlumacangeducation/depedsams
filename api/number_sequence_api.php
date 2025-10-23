<?php
/**
 * Generic API for Managing Number Sequences
 *
 * Handles GET and POST requests for various number sequences (PO, RIS, SN, PN, ICS).
 * The type of sequence is determined by the 'type' URL parameter.
 * Example: /api/number_sequence_api.php?type=po
 */
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

// Whitelist of allowed sequence types and their database configurations
$allowed_types = [
    'po' => ['table' => 'tbl_po_number', 'id_col' => 'po_number_id', 'format_col' => 'po_number_format', 'name' => 'Purchase Order'],
    'ris' => ['table' => 'tbl_ris_number', 'id_col' => 'ris_number_id', 'format_col' => 'ris_number_format', 'name' => 'RIS'],
    'sn' => ['table' => 'tbl_item_number', 'id_col' => 'item_number_id', 'format_col' => 'item_number_format', 'name' => 'Stock Number'],
    'pn' => ['table' => 'tbl_pn_number', 'id_col' => 'pn_number_id', 'format_col' => 'pn_number_format', 'name' => 'Property Number'],
    'ics' => ['table' => 'tbl_ics_number', 'id_col' => 'ics_number_id', 'format_col' => 'ics_number_format', 'name' => 'ICS'],
    'par' => ['table' => 'tbl_par_number', 'id_col' => 'par_number_id', 'format_col' => 'par_number_format', 'name' => 'PAR'],
];

if (!$type || !isset($allowed_types[$type])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid or missing sequence type specified.']);
    exit;
}

$config = $allowed_types[$type];
$table = $config['table'];
$id_col = $config['id_col'];
$format_col = $config['format_col'];
$name = $config['name'];

try {
    switch ($method) {
        case 'GET':
            $stmt = $pdo->query("SELECT * FROM {$table} ORDER BY year DESC");
            $sequences = $stmt->fetchAll();
            echo json_encode(['data' => $sequences]);
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);

            $id = $input[$id_col] ?? null;
            $year = $input['year'] ?? null;
            $format = $input[$format_col] ?? null;
            $start_count = $input['start_count'] ?? null;

            if (!$year || !$format || !$start_count) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Year, format, and start count are required.']);
                exit;
            }

            if ($id) {
                // Update existing sequence
                $sql = "UPDATE {$table} SET year = ?, {$format_col} = ?, start_count = ? WHERE {$id_col} = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$year, $format, $start_count, $id]);
                echo json_encode(['success' => true, 'message' => "{$name} number sequence updated successfully."]);
            } else {
                // Add new sequence - Check for duplicates first
                $stmt_check = $pdo->prepare("SELECT {$id_col} FROM {$table} WHERE year = ? AND serial = 'default'");
                $stmt_check->execute([$year]);
                if ($stmt_check->fetch()) {
                    http_response_code(409); // Conflict
                    echo json_encode(['success' => false, 'message' => "A sequence for the year {$year} already exists."]);
                    exit;
                }

                $sql = "INSERT INTO {$table} (serial, year, {$format_col}, start_count) VALUES ('default', ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$year, $format, $start_count]);
                echo json_encode(['success' => true, 'message' => "{$name} number sequence added successfully."]);
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
                echo json_encode(['success' => true, 'message' => 'Sequence deleted successfully.']);
            } else {
                throw new Exception('Sequence not found or already deleted.');
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
        echo json_encode(['success' => false, 'message' => "A sequence for the year {$year} already exists."]);
    } else if ($e->errorInfo[1] == 1451) { // Foreign key constraint violation
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Cannot delete this sequence because it is currently in use by a document or asset.']);
    } else {
        error_log("Number Sequence API Error ({$type}): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>