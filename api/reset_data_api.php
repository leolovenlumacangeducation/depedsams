<?php
session_start();
header('Content-Type: application/json');
require_once '../db.php';

// --- Constants for upload directories ---
define('UPLOADS_BASE_PATH', __DIR__ . '/../assets/uploads/');
define('PPE_PATH', UPLOADS_BASE_PATH . 'ppe/');
define('SEP_PATH', UPLOADS_BASE_PATH . 'sep/');
define('CONSUMABLES_PATH', UPLOADS_BASE_PATH . 'consumables/');

/**
 * API Endpoint for resetting all inventory and transaction data.
 * This is a destructive action and should be protected.
 */

// --- Security & Method Check ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden: You do not have permission to perform this action.']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// --- Confirmation Check ---
if (!isset($input['confirmation']) || $input['confirmation'] !== 'RESET ALL INVENTORY DATA') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Incorrect confirmation phrase. Reset action aborted.']);
    exit;
}

// --- List of tables to truncate ---
$tables_to_truncate = [
    // Reporting
    'tbl_rpci_item',
    'tbl_rpci',
    'tbl_rpcppe_item',
    'tbl_rpcppe',
    // IIRUP and Disposal
    'tbl_iirup_item',
    'tbl_iirup',
    // Maintenance
     
    // Transactions
    'tbl_issuance_item',
    'tbl_issuance',
    'tbl_ics_item',
    'tbl_ics',
    'tbl_par_item',
    'tbl_par',
    'tbl_ppe_history',
    'tbl_unit_conversion',
    // Inventory
    'tbl_consumable',
    'tbl_sep',
    'tbl_ppe',
    // Acquisition
    'tbl_delivery_item',
    'tbl_delivery',
    'tbl_po_item',
    'tbl_po',
    'tbl_incoming_ics_item',
    'tbl_incoming_ics'
];

// --- List of sequence tables to reset ---
$sequence_tables = [
    'tbl_po_number',
    'tbl_pn_number',
    'tbl_item_number',
    'tbl_iirup_number',
    'tbl_ris_number',    
    'tbl_ics_number',
    'tbl_par_number',
    'tbl_rpci_number',
    'tbl_rpcppe_number'
];

$failed_deletions = [];

try {
    // First, gather paths to files that need to be deleted
    $photos_to_delete = [];
    
    $sql = "
        SELECT CONCAT('".PPE_PATH."', photo) as photo_path FROM tbl_ppe WHERE photo IS NOT NULL AND photo != 'ppe_default.png'
        UNION ALL
        SELECT CONCAT('".SEP_PATH."', photo) as photo_path FROM tbl_sep WHERE photo IS NOT NULL AND photo != 'sep_default.png'
        UNION ALL
        SELECT CONCAT('".CONSUMABLES_PATH."', photo) as photo_path FROM tbl_consumable WHERE photo IS NOT NULL AND photo != 'consumable_default.png'
    ";
    $stmt = $pdo->query($sql);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($row['photo_path'])) {
            $photos_to_delete[] = $row['photo_path'];
        }
    }

    $pdo->beginTransaction();

    // Log the reset action
    $log_stmt = $pdo->prepare("INSERT INTO tbl_system_log (action, performed_by, details) VALUES (?, ?, ?)");
    $log_stmt->execute([
        'RESET_DATA',
        $_SESSION['user_id'],
        'Reset all inventory and transaction data'
    ]);

    // Temporarily disable foreign key checks to allow truncation
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0;');

    // Truncate all specified tables
    foreach ($tables_to_truncate as $table) {
        $pdo->exec("TRUNCATE TABLE `$table`;");
    }

    // Reset all number sequence counters to 1
    foreach ($sequence_tables as $table) {
        $pdo->exec("UPDATE `$table` SET `start_count` = 1;");
    }

    // Re-enable foreign key checks
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1;');

    $pdo->commit();

    // After successful database reset, delete the photo files
    foreach ($photos_to_delete as $photo_path) {
        if (file_exists($photo_path) && !is_dir($photo_path)) {
            // Use error suppression with unlink and check the return value
            if (@unlink($photo_path) === false) {
                $failed_deletions[] = $photo_path;
            }
        }
    }

    if (empty($failed_deletions)) {
        echo json_encode(['success' => true, 'message' => 'All inventory and transaction data has been successfully reset.']);
    } else {
        $errorMessage = 'Database reset successful, but failed to delete some files. Please check file permissions. Failed files: ' . implode(', ', array_map('basename', $failed_deletions));
        // Log this for the admin to review
        error_log("Data Reset Cleanup Warning: " . $errorMessage);
        // Inform the user
        echo json_encode(['success' => true, 'message' => $errorMessage, 'warning' => true]);
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Data Reset API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'A database error occurred during the reset process. Please check the server logs.']);
}
?>