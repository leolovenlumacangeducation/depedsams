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

try {
    $year = $_GET['year'] ?? date('Y');

    $stmt = $pdo->prepare("SELECT * FROM tbl_ics_number WHERE year = ? AND serial = 'default' LIMIT 1");
    $stmt->execute([$year]);
    $sequence = $stmt->fetch();

    if (!$sequence) {
        throw new Exception("Number sequence for year {$year} not found.");
    }

    $preview_number = str_replace(['{YYYY}', '{NNNN}'], [$year, str_pad($sequence['start_count'], 4, '0', STR_PAD_LEFT)], $sequence['ics_number_format']);

    echo json_encode(['success' => true, 'preview_number' => $preview_number]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not retrieve next ICS number.', 'error' => $e->getMessage()]);
}