<?php
session_start();
header('Content-Type: application/json');
require_once '../db.php';
require_once 'utils.php'; // For getNextNumber


// Security check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$sep_ids = $input['sep_ids'] ?? [];
$user_name = trim($input['user_id'] ?? ''); // This is now a name, not an ID
$location = $input['location'] ?? null;
$generate_ics = $input['generate_ics'] ?? false;

if (empty($sep_ids) || empty($user_name) || !$location) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required data: SEP IDs, User ID, and Location are required.']);
    exit;
}
try {
    $pdo->beginTransaction();

    // Find user_id from the provided name.
    $stmt_find_user = $pdo->prepare("SELECT user_id FROM tbl_user WHERE full_name = ?");
    $stmt_find_user->execute([$user_name]);
    $user_id = $stmt_find_user->fetchColumn();

    // If user does not exist, create a new, inactive user record.
    // This maintains data integrity and allows for later management.
    if (!$user_id) {
        // A default role is needed. Assuming 'User' role has ID 2.
        $default_role_id = 2; 
        $placeholder_username = 'user_' . time() . rand(100, 999);
        $placeholder_password = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);

        $stmt_create_user = $pdo->prepare(
            "INSERT INTO tbl_user (full_name, username, hashed_password, role_id, is_active) VALUES (?, ?, ?, ?, 0)"
        );
        $stmt_create_user->execute([$user_name, $placeholder_username, $placeholder_password, $default_role_id]);
        $user_id = $pdo->lastInsertId();
    }

    // 1. Assign the items and mark them as having been assigned
    $sql = "UPDATE tbl_sep SET assigned_to_user_id = ?, current_location = ?, has_been_assigned = 1 WHERE sep_id = ?";
    $stmt = $pdo->prepare($sql);
    foreach ($sep_ids as $sep_id) {
        $stmt->execute([$user_id, $location, $sep_id]);
    }

    $ics_id = null;
    // 2. If requested, generate the ICS document
    if ($generate_ics) {
        // Get the next ICS number
        $current_year = date('Y');
        $ics_info = getNextNumber($pdo, 'tbl_ics_number', $current_year);

        // Create the ICS header record
        $sql_ics = "INSERT INTO tbl_ics (ics_number, issued_to_user_id, location, date_issued, issued_by_user_id) VALUES (?, ?, ?, ?, ?)";
        $stmt_ics = $pdo->prepare($sql_ics);
        $stmt_ics->execute([$ics_info['number'], $user_id, $location, date('Y-m-d'), $_SESSION['user_id']]);
        $ics_id = $pdo->lastInsertId();

        // Link the assigned SEP items to the new ICS record
        $sql_ics_item = "INSERT INTO tbl_ics_item (ics_id, sep_id) VALUES (?, ?)";
        $stmt_ics_item = $pdo->prepare($sql_ics_item);
        foreach ($sep_ids as $sep_id) {
            $stmt_ics_item->execute([$ics_id, $sep_id]);
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true, 
        'message' => 'SEP items assigned successfully.',
        'ics_id' => $ics_id // Return the new ICS ID so the frontend can offer to print it
    ]);
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("SEP Assign API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'A database error occurred.']);
}
?>