<?php
session_start();
header('Content-Type: application/json');
require_once '../db.php';
require_once '../includes/functions.php';

// Security Check: Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);

if (!$type) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Number type is required.']);
    exit;
}

// Whitelist of allowed types and their table/column configurations
$type_map = [
    'PO' => ['table' => 'tbl_po_number', 'format_col' => 'po_number_format'],
    'RIS' => ['table' => 'tbl_ris_number', 'format_col' => 'ris_number_format'],
    'ICS' => ['table' => 'tbl_ics_number', 'format_col' => 'ics_number_format'],
    'PAR' => ['table' => 'tbl_par_number', 'format_col' => 'par_number_format'],
    'IIRUP' => ['table' => 'tbl_iirup_number', 'format_col' => 'iirup_number_format'],
    'PN' => ['table' => 'tbl_pn_number', 'format_col' => 'pn_number_format'],
    'SN' => ['table' => 'tbl_item_number', 'format_col' => 'item_number_format'],
];

if (!array_key_exists($type, $type_map)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid number type specified.']);
    exit;
}

$config = $type_map[$type];

try {
    // Pass the specific table and format column to the generic function
    $number_data = generateNextNumber($pdo, $config['table'], $type, $config['format_col']);
    echo json_encode(['success' => true, 'next_number' => $number_data['next_number']]);
} catch (Exception $e) {
    http_response_code(500);
    error_log("Get Next Number API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to generate next number: ' . $e->getMessage()]);
}
?>