<?php
session_start();
header('Content-Type: application/json');

require_once '../db.php';

// Security check: ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $year = $_GET['year'] ?? date('Y');

    // Read the current sequence without locking or updating
    $stmt = $pdo->prepare("SELECT * FROM tbl_po_number WHERE year = ? AND serial = 'default' LIMIT 1");
    $stmt->execute([$year]);
    $sequence = $stmt->fetch();

    if (!$sequence) {
        throw new Exception("Number sequence for year {$year} not found.");
    }

    $current_count = $sequence['start_count'];
    $format_string = $sequence['po_number_format'] ?? 'PO-{YYYY}-{NNNN}';

    $preview_number = str_replace(['{YYYY}', '{NNNN}'], [$year, str_pad($current_count, 4, '0', STR_PAD_LEFT)], $format_string);

    echo json_encode(['success' => true, 'preview_number' => $preview_number]);

} catch (Exception $e) {
    // If any query fails, send an error
    error_log("Get Next PO Number API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not retrieve next PO number.', 'error' => $e->getMessage()]);
}
?>