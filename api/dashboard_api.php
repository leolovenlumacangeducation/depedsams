<?php
session_start();
header('Content-Type: application/json');
require_once '../db.php';

/**
 * Defines the threshold for what is considered "low stock".
 * Any consumable item with a quantity at or below this value will be returned.
 */
define('LOW_STOCK_THRESHOLD', 10);

// Security Check: Ensure the user is logged in.
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Method Check: Only allow GET requests.
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// Get the requested action
$action = $_GET['action'] ?? '';

// Enable error logging
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

try {
    $pdo->beginTransaction();

    // Handle user-specific actions
    if ($action === 'user_info' && $_SESSION['role_id'] === 2) {
        // Get user information
        $stmt = $pdo->prepare("
            SELECT 
                u.user_id, 
                u.username, 
                u.full_name,
                p.position_name,
                u.photo
            FROM tbl_user u
            LEFT JOIN tbl_position p ON u.position_id = p.position_id
            WHERE u.user_id = ? AND u.is_active = 1
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $pdo->commit();
        echo json_encode([
            'success' => true,
            'data' => [
                'full_name' => $user['full_name'] ?? 'N/A',
                'email' => $user['email'] ?? 'N/A',
                'department' => $user['department_name'] ?? 'N/A'
            ]
        ]);
        exit;
    }

    if ($action === 'assets_summary' && $_SESSION['role_id'] === 2) {
        // Get user's assets summary
        // Get PPE assets from PAR
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT p.ppe_id) as ppe_count
            FROM tbl_ppe p
            JOIN tbl_par_item pi ON p.ppe_id = pi.ppe_id
            JOIN tbl_par par ON pi.par_id = par.par_id
            WHERE par.issued_to_user_id = ?
            AND par.status = 'Active'
            AND p.current_condition != 'Disposed'
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $ppe_count = $stmt->fetchColumn();

        // Get SEP assets from ICS
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT s.sep_id) as sep_count
            FROM tbl_sep s
            JOIN tbl_ics_item ii ON s.sep_id = ii.sep_id
            JOIN tbl_ics ics ON ii.ics_id = ics.ics_id
            WHERE ics.issued_to_user_id = ?
            AND ics.status = 'Active'
            AND s.current_condition != 'Disposed'
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $sep_count = $stmt->fetchColumn();

        $assets = [
            'ppe_count' => $ppe_count,
            'sep_count' => $sep_count,
        ];

        $total_assets = ($assets['ppe_count'] ?? 0) + ($assets['sep_count'] ?? 0) + ($assets['ics_count'] ?? 0);

        // Get recent activities
        // Get recent activities from PAR and ICS
        // Get recent activities (PAR and ICS assignments in the last 30 days)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as recent_count
            FROM (
                SELECT par.date_created
                FROM tbl_par par
                WHERE par.issued_to_user_id = ?
                AND par.date_created >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                AND par.status = 'Active'
                UNION ALL
                SELECT ics.date_created
                FROM tbl_ics ics
                WHERE ics.issued_to_user_id = ?
                AND ics.date_created >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                AND ics.status = 'Active'
            ) as combined_activities
        ");
        $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
        $activities = $stmt->fetch(PDO::FETCH_ASSOC);

        $pdo->commit();
        echo json_encode([
            'success' => true,
            'data' => [
                'total_assets' => $total_assets,
                'recent_activities' => $activities['recent_count'] ?? 0
            ]
        ]);
        exit;
    }

    // For admin dashboard data
    // 1. Get total asset counts and values
    // Fixed consumable value calculation
    $stmt_consumable = $pdo->query("
        SELECT 
            COUNT(*) as count,
            COALESCE(SUM(
                CASE 
                    WHEN current_stock IS NOT NULL AND unit_cost IS NOT NULL 
                    THEN current_stock * unit_cost 
                    ELSE 0 
                END
            ), 0) as total_value
        FROM tbl_consumable
        WHERE current_stock > 0
    ");
    $consumable_data = $stmt_consumable->fetch(PDO::FETCH_ASSOC);
    $consumable_count = $consumable_data['count'];
    $consumable_value = $consumable_data['total_value'];

    // Fixed SEP value calculation
    $stmt_sep = $pdo->query("
        SELECT 
            COUNT(*) as count,
            COALESCE(SUM(
                CASE 
                    WHEN pi.unit_cost IS NOT NULL AND pi.unit_cost > 0 THEN pi.unit_cost
                    WHEN iici.unit_cost IS NOT NULL AND iici.unit_cost > 0 THEN iici.unit_cost
                    ELSE 0 
                END
            ), 0) as total_value
        FROM tbl_sep s
        LEFT JOIN tbl_po_item pi ON s.po_item_id = pi.po_item_id
        LEFT JOIN tbl_incoming_ics_item iici ON s.incoming_ics_item_id = iici.incoming_ics_item_id
        WHERE s.current_condition != 'Disposed'
    ");
    $sep_data = $stmt_sep->fetch(PDO::FETCH_ASSOC);
    $sep_count = $sep_data['count'];
    $sep_value = $sep_data['total_value'];
    // Line removed to fix duplicate count assignment

    // Fixed PPE value calculation
    $stmt_ppe = $pdo->query("
        SELECT 
            COUNT(*) as count,
            COALESCE(SUM(
                CASE 
                    WHEN pi.unit_cost IS NOT NULL AND pi.unit_cost > 0 THEN pi.unit_cost
                    WHEN iici.unit_cost IS NOT NULL AND iici.unit_cost > 0 THEN iici.unit_cost
                    ELSE 0 
                END
            ), 0) as total_value
        FROM tbl_ppe p
        LEFT JOIN tbl_po_item pi ON p.po_item_id = pi.po_item_id
        LEFT JOIN tbl_incoming_ics_item iici ON p.incoming_ics_item_id = iici.incoming_ics_item_id
        WHERE p.current_condition != 'Disposed'
    ");
    $ppe_data = $stmt_ppe->fetch(PDO::FETCH_ASSOC);
    $ppe_count = $ppe_data['count'];
    $ppe_value = $ppe_data['total_value'];

    // 2. Get category distribution
    $stmt_categories = $pdo->query("
        SELECT 
            c.category_name,
            (
                SELECT COUNT(*)
                FROM tbl_ppe
                WHERE inventory_type_id = c.inventory_type_id
            ) +
            (
                SELECT COUNT(*)
                FROM tbl_sep
                WHERE inventory_type_id = c.inventory_type_id
            ) +
            (
                SELECT COUNT(*)
                FROM tbl_consumable
                WHERE inventory_type_id = c.inventory_type_id
            ) as item_count
        FROM tbl_category c
        GROUP BY c.category_id, c.category_name, c.inventory_type_id
        HAVING item_count > 0
        ORDER BY item_count DESC
    ");
    $category_distribution = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);

    // 3. Get asset counts by condition
    $conditions = ['Serviceable' => 0, 'For Repair' => 0, 'Unserviceable' => 0, 'Disposed' => 0];

    // SEP by condition
    $stmt_sep_condition = $pdo->query("SELECT current_condition, COUNT(*) as count FROM tbl_sep GROUP BY current_condition");
    $sep_conditions = $stmt_sep_condition->fetchAll(PDO::FETCH_KEY_PAIR);
    foreach ($sep_conditions as $condition => $count) {
        if (isset($conditions[$condition])) {
            $conditions[$condition] += $count;
        }
    }

    // PPE by condition
    $stmt_ppe_condition = $pdo->query("SELECT current_condition, COUNT(*) as count FROM tbl_ppe GROUP BY current_condition");
    $ppe_conditions = $stmt_ppe_condition->fetchAll(PDO::FETCH_KEY_PAIR);
    foreach ($ppe_conditions as $condition => $count) {
        if (isset($conditions[$condition])) {
            $conditions[$condition] += $count;
        }
    }

    // 3. Get low-stock consumable items
    $sql_low_stock = "
        SELECT
            c.consumable_id,
            c.stock_number,
            COALESCE(pi.description, iici.description, 'N/A') AS description,
            c.current_stock,
            u.unit_name
        FROM
            tbl_consumable c
        LEFT JOIN tbl_po_item pi ON c.po_item_id = pi.po_item_id
        LEFT JOIN tbl_incoming_ics_item iici ON c.incoming_ics_item_id = iici.incoming_ics_item_id
        JOIN tbl_unit u ON c.unit_id = u.unit_id
        WHERE
            c.current_stock <= ?
            AND c.current_stock > 0
        ORDER BY
            c.current_stock ASC
        LIMIT 10
    ";
    $stmt_low_stock = $pdo->prepare($sql_low_stock);
    $stmt_low_stock->execute([LOW_STOCK_THRESHOLD]);
    $low_stock_items = $stmt_low_stock->fetchAll(PDO::FETCH_ASSOC);

    // 4. Get recent activity feed
    $sql_activity = "
        (SELECT
            d.date_created AS activity_date,
            'IAR' AS activity_type,
            d.po_id AS document_id,
            CONCAT(COALESCE(u.full_name, 'A user'), ' received items from PO #', po.po_number) AS description,
            u.photo AS user_photo
        FROM tbl_delivery d
        LEFT JOIN tbl_user u ON d.received_by_user_id = u.user_id
        JOIN tbl_po po ON d.po_id = po.po_id)
        UNION ALL
        (SELECT
            i.date_created AS activity_date,
            'RIS' AS activity_type,
            i.issuance_id AS document_id,
            CONCAT(COALESCE(u.full_name, 'A user'), ' issued items to ', i.issued_to) AS description,
            u.photo AS user_photo
        FROM tbl_issuance i
        LEFT JOIN tbl_user u ON i.issued_by_user_id = u.user_id)
        UNION ALL
        (SELECT
            ics.date_created AS activity_date,
            'ICS' AS activity_type,
            ics.ics_id AS document_id,
            CONCAT(COALESCE(u_by.full_name, 'A user'), ' assigned property (ICS) to ', COALESCE(u_to.full_name, 'a user')) AS description,
            u_by.photo AS user_photo
        FROM tbl_ics ics
        LEFT JOIN tbl_user u_by ON ics.issued_by_user_id = u_by.user_id
        LEFT JOIN tbl_user u_to ON ics.issued_to_user_id = u_to.user_id)
        UNION ALL
        (SELECT
            iirup.date_created AS activity_date,
            'IIRUP' AS activity_type,
            iirup.iirup_id AS document_id,
            CONCAT(COALESCE(u_by.full_name, 'A user'), ' created IIRUP #', iirup.iirup_number) AS description,
            u_by.photo AS user_photo
        FROM tbl_iirup iirup
        LEFT JOIN tbl_user u_by ON iirup.created_by_user_id = u_by.user_id)
        UNION ALL
        (SELECT
            par.date_created AS activity_date,
            'PAR' AS activity_type,
            par.par_id AS document_id,
            CONCAT(COALESCE(u_by.full_name, 'A user'), ' assigned property (PAR) to ', COALESCE(u_to.full_name, 'a user')) AS description,
            u_by.photo AS user_photo
        FROM tbl_par par
        LEFT JOIN tbl_user u_by ON par.issued_by_user_id = u_by.user_id
        LEFT JOIN tbl_user u_to ON par.issued_to_user_id = u_to.user_id)
        ORDER BY activity_date DESC
        LIMIT 10
    ";
    $stmt_activity = $pdo->query($sql_activity);
    $recent_activity = $stmt_activity->fetchAll(PDO::FETCH_ASSOC);

    $pdo->commit();

    // --- Assemble the final response ---
    $dashboard_data = [
        'success' => true,
        'asset_counts' => [
            'consumable' => (int)$consumable_count,
            'sep' => (int)$sep_count,
            'ppe' => (int)$ppe_count,
        ],
        'asset_values' => [
            'consumable' => (float)$consumable_value,
            'sep' => (float)$sep_value,
            'ppe' => (float)$ppe_value,
            'total' => (float)($consumable_value + $sep_value + $ppe_value)
        ],
        'category_distribution' => $category_distribution,
        'assets_by_condition' => $conditions,
        'low_stock_items' => $low_stock_items,
        'recent_activity' => $recent_activity,
    ];

    // For debugging: if the logged-in user is an Admin, include raw query results
    if (isset($_SESSION['role_name']) && $_SESSION['role_name'] === 'Admin') {
        $dashboard_data['debug_info'] = [
            'consumable_query' => $consumable_data,
            'sep_query' => $sep_data,
            'ppe_query' => $ppe_data,
        ];
    }

    echo json_encode($dashboard_data);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log the full error details
    error_log("Dashboard API Error: " . $e->getMessage());
    error_log("Error trace: " . $e->getTraceAsString());
    
    // Send back a structured error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching dashboard data.',
        'error' => [
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]
    ]);
}
?>