<?php
session_start();
header('Content-Type: application/json');
require_once '../db.php';
require_once '../includes/functions.php'; // For generateNextNumber

// Security Check: Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo->beginTransaction();

    switch ($method) {
        case 'GET':
            // Fetch all IIRUP documents
            $stmt = $pdo->query("
                SELECT
                    i.iirup_id,
                    i.iirup_number,
                    i.as_of_date,
                    i.disposal_method,
                    i.status,
                    COALESCE(u.full_name, 'System/Deleted User') AS created_by,
                    i.date_created
                FROM tbl_iirup i
                LEFT JOIN tbl_user u ON i.created_by_user_id = u.user_id
                ORDER BY i.date_created DESC
            ");
            $iirups = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $iirups]);
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['as_of_date']) || !isset($data['selected_assets'])) {
                // Check for finalize action
                if (isset($data['action']) && $data['action'] === 'finalize' && isset($data['iirup_id'])) {
                    $result = finalizeIirup($pdo, $data['iirup_id'], $user_id);
                    if ($result['success']) {
                        $pdo->commit();
                        echo json_encode($result);
                    } else {
                        throw new Exception($result['message']);
                    }
                    exit; // Exit after handling finalize action
                }
                throw new Exception('Missing required IIRUP data.');
            }

            $as_of_date = $data['as_of_date'];
            $disposal_method = $data['disposal_method'] ?? null;
            $selected_assets = $data['selected_assets']; // Array of {asset_id, asset_type, description}

            if (empty($selected_assets)) {
                throw new Exception('No assets selected for IIRUP.');
            }

            // Generate next IIRUP number
            $iirup_number_data = generateNextNumber($pdo, 'tbl_iirup_number', 'IIRUP', 'iirup_number_format');
            $iirup_number = $iirup_number_data['next_number'];

            // Insert into tbl_iirup
            $stmt_iirup = $pdo->prepare("
                INSERT INTO tbl_iirup (iirup_number, as_of_date, disposal_method, created_by_user_id)
                VALUES (?, ?, ?, ?)
            ");
            $stmt_iirup->execute([$iirup_number, $as_of_date, $disposal_method, $user_id]);
            $iirup_id = $pdo->lastInsertId();

            // Update the number sequence
            $stmt_update_seq = $pdo->prepare("
                UPDATE tbl_iirup_number SET start_count = start_count + 1 WHERE serial = 'default' AND year = ?
            ");
            $stmt_update_seq->execute([date('Y')]);

            // Insert into tbl_iirup_item and update asset status
            foreach ($selected_assets as $asset) {
                $asset_id = $asset['asset_id'];
                $asset_type = $asset['asset_type'];
                $remarks = $asset['remarks'] ?? null;

                $stmt_item = $pdo->prepare("
                    INSERT INTO tbl_iirup_item (iirup_id, asset_id, asset_type, remarks)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt_item->execute([$iirup_id, $asset_id, $asset_type, $remarks]);
            }

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'IIRUP created successfully.', 'iirup_id' => $iirup_id]);
            break;

        // Add other methods like PUT (for updating status) or DELETE if needed

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
            break;
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    error_log("IIRUP API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}

/**
 * Finalizes an IIRUP document by updating its status and the status of all associated assets.
 * @param PDO $pdo The PDO database connection.
 * @param int $iirup_id The ID of the IIRUP to finalize.
 * @param int $user_id The ID of the user performing the action.
 * @return array An associative array with 'success' and 'message' keys.
 */
function finalizeIirup(PDO $pdo, int $iirup_id, int $user_id): array {
    // Fetch IIRUP details
    $stmt_iirup = $pdo->prepare("SELECT * FROM tbl_iirup WHERE iirup_id = ?");
    $stmt_iirup->execute([$iirup_id]);
    $iirup = $stmt_iirup->fetch(PDO::FETCH_ASSOC);

    if (!$iirup || $iirup['status'] !== 'Draft') {
        return ['success' => false, 'message' => 'IIRUP not found or is not in a draft state.'];
    }

    // Fetch all items associated with this IIRUP
    $stmt_items = $pdo->prepare("SELECT asset_id, asset_type FROM tbl_iirup_item WHERE iirup_id = ?");
    $stmt_items->execute([$iirup_id]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    if (empty($items)) {
        return ['success' => false, 'message' => 'This IIRUP has no items to finalize.'];
    }

    // Update asset statuses
    foreach ($items as $item) {
        $asset_id = $item['asset_id'];
        $asset_type = $item['asset_type'];

        if ($asset_type === 'PPE') {
            $stmt_update_asset = $pdo->prepare("UPDATE tbl_ppe SET current_condition = 'Disposed', date_disposed = ? WHERE ppe_id = ?");
            $stmt_update_asset->execute([$iirup['as_of_date'], $asset_id]);

            // Log to PPE history
            $stmt_history = $pdo->prepare("
                INSERT INTO tbl_ppe_history (ppe_id, transaction_date, transaction_type, reference, notes)
                VALUES (?, ?, 'Disposal', ?, ?)
            ");
            $stmt_history->execute([$asset_id, $iirup['as_of_date'], "IIRUP #{$iirup['iirup_number']}", "Disposed via {$iirup['disposal_method']}"]);
        } elseif ($asset_type === 'SEP') {
            $stmt_update_asset = $pdo->prepare("UPDATE tbl_sep SET current_condition = 'Disposed' WHERE sep_id = ?");
            $stmt_update_asset->execute([$asset_id]);
        }
    }

    // Update the IIRUP status to 'Disposed'
    $stmt_update_iirup = $pdo->prepare("UPDATE tbl_iirup SET status = 'Disposed' WHERE iirup_id = ?");
    $stmt_update_iirup->execute([$iirup_id]);

    return [
        'success' => true,
        'message' => 'IIRUP has been finalized and all associated assets are now marked as disposed.'
    ];
}

?>