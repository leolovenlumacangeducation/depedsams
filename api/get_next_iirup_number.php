<?php
session_start();
header('Content-Type: application/json');

require_once '../db.php';
require_once '../includes/functions.php';

// Security check: ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if user has admin rights
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access forbidden. Admin rights required.']);
    exit;
}

try {
    $year = $_GET['year'] ?? date('Y');

    // First, ensure the sequence exists for the current year
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_iirup_number WHERE year = ? AND serial = 'default'");
    $stmt->execute([$year]);
    if ($stmt->fetchColumn() == 0) {
        // Create a new sequence for the current year if it doesn't exist
        $stmt = $pdo->prepare("
            INSERT INTO tbl_iirup_number (year, serial, start_count, iirup_number_format)
            VALUES (?, 'default', 1, 'IIRUP-{YYYY}-{NNNN}')
        ");
        $stmt->execute([$year]);
    }

    // Now get the sequence
    $stmt = $pdo->prepare("SELECT * FROM tbl_iirup_number WHERE year = ? AND serial = 'default' LIMIT 1");
    $stmt->execute([$year]);
    $sequence = $stmt->fetch();

    if (!$sequence) {
        throw new Exception("Failed to retrieve or create IIRUP number sequence for year {$year}.");
    }

    $preview_number = str_replace(
        ['{YYYY}', '{NNNN}'], 
        [$year, str_pad($sequence['start_count'], 4, '0', STR_PAD_LEFT)], 
        $sequence['iirup_number_format']
    );

    echo json_encode([
        'success' => true, 
        'preview_number' => $preview_number,
        'format' => $sequence['iirup_number_format'],
        'current_count' => $sequence['start_count']
    ]);

} catch (Exception $e) {
    error_log("IIRUP Number Generation Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Could not retrieve next IIRUP number.', 
        'error' => $e->getMessage()
    ]);
}
?>