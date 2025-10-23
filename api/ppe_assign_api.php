<?php
session_start();
header('Content-Type: application/json');
require_once '../db.php';
require_once 'utils.php'; // For getNextNumber

// Security check: ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Ensure the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// --- Input Validation ---
$ppe_ids = $input['ppe_ids'] ?? [];
$user_id = isset($input['user_id']) && is_numeric($input['user_id']) ? (int)$input['user_id'] : null;
$user_name = trim($input['user_name'] ?? ''); // fallback name
$location = $input['location'] ?? null;
$generate_par = $input['generate_par'] ?? false;

if (empty($ppe_ids) || (!$user_id && empty($user_name)) || !$location) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required data: PPE IDs, User (ID or name), and Location are required.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Resolve the user_id.
    if (!$user_id) {
        // Try find by full name
        $stmt_find_user = $pdo->prepare("SELECT user_id FROM tbl_user WHERE full_name = ?");
        $stmt_find_user->execute([$user_name]);
        $user_id = $stmt_find_user->fetchColumn();

        // If still not found, create a new, inactive user record.
        if (!$user_id) {
            $default_role_id = 2; // Assuming 'User' role has ID 2.
            $placeholder_username = 'user_' . time() . rand(100, 999);
            $placeholder_password = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);

            $stmt_create_user = $pdo->prepare(
                "INSERT INTO tbl_user (full_name, username, hashed_password, role_id, is_active) VALUES (?, ?, ?, ?, 0)"
            );
            $stmt_create_user->execute([$user_name, $placeholder_username, $placeholder_password, $default_role_id]);
            $user_id = $pdo->lastInsertId();
        }
    }


    $par_id = null;
    $par_number = null;

    // 1. Determine the PAR number to use.
    if ($generate_par) {
        // Generate a brand new PAR number.
        $current_year = date('Y');
        $par_info = getNextNumber($pdo, 'tbl_par_number', $current_year);
        $par_number = $par_info['number'];

        // Create the new PAR header record.
        $sql_par = "INSERT INTO tbl_par (par_number, issued_to_user_id, location, date_issued, issued_by_user_id) VALUES (?, ?, ?, ?, ?)";
        $stmt_par = $pdo->prepare($sql_par);
        $stmt_par->execute([$par_number, $user_id, $location, date('Y-m-d'), $_SESSION['user_id']]);
        $par_id = $pdo->lastInsertId();
    } else {
        // If not generating a new PAR, find the most recent PAR number for the first item being assigned.
        // This is useful for re-assignments where you want to keep the same PAR reference.
        $stmt_get_par = $pdo->prepare(
            "SELECT reference FROM tbl_ppe_history WHERE ppe_id = ? AND transaction_type = 'Assignment' AND reference IS NOT NULL ORDER BY history_id DESC LIMIT 1"
        );
        $stmt_get_par->execute([$ppe_ids[0]]); // Use the first item as the reference
        $par_number = $stmt_get_par->fetchColumn();
    }

    // Prepare the statement to update each PPE item
    $sql = "UPDATE tbl_ppe SET assigned_to_user_id = ?, current_location = ?, has_been_assigned = 1 WHERE ppe_id = ?";
    $stmt = $pdo->prepare($sql);

    // Prepare statement for history logging
    $sql_history = "INSERT INTO tbl_ppe_history (ppe_id, transaction_date, transaction_type, reference, to_user_id, notes) VALUES (?, CURDATE(), 'Assignment', ?, ?, ?)";
    $stmt_history = $pdo->prepare($sql_history);

    // Prepare statement for PAR items
    $sql_par_item = "INSERT INTO tbl_par_item (par_id, ppe_id) VALUES (?, ?)";
    $stmt_par_item = $pdo->prepare($sql_par_item);

    // 2. Loop through each ID to update records and log history
    foreach ($ppe_ids as $ppe_id) {
        $stmt->execute([$user_id, $location, $ppe_id]); // Update PPE record
        $note = "Assigned to custodian at " . $location;
        $stmt_history->execute([$ppe_id, $par_number, $user_id, $note]); // Log history with PAR number as reference
        if ($par_id) {
            $stmt_par_item->execute([$par_id, $ppe_id]);
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true, 
        'message' => 'PPE items assigned successfully.',
        'par_id' => $par_id // Return the new PAR ID
    ]);
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("PPE Assign API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'A database error occurred while assigning items. ' . $e->getMessage()]);
}
?>