<?php
session_start();
header('Content-Type: application/json');
require_once '../db.php';

/**
 * API Endpoint for Receiving Transferred Items via an ICS from another entity.
 *
 * This script handles the creation of inventory items (SEP/PPE/Consumables) that are not
 * from a purchase order but are transferred from another office (e.g., Division).
 * It allows for specifying existing property numbers for SEP/PPE and correctly
 * handles consumables without stock numbers.
 */

// --- Security & Method Check ---
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// --- Data Validation ---
$header = $input['header'] ?? [];
$items = $input['items'] ?? [];

if (empty($header['ics_number']) || empty($header['source_office']) || empty($header['date_received']) || empty($header['issued_by_name']) || empty($items)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required data: ICS No., Source Office, Date, Issuer, and at least one item are required.']);
    exit;
}

// --- Database Transaction ---
try {
    $pdo->beginTransaction();

    // 1. Insert a record into tbl_incoming_ics (header)
    $sql_header = "INSERT INTO tbl_incoming_ics (ics_number, source_office, issued_by_name, issued_by_position, date_received, received_by_user_id) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_header = $pdo->prepare($sql_header);
    // Validate session user exists; if not, insert NULL for received_by_user_id to avoid FK errors
    $received_by_user_id = null;
    if (!empty($_SESSION['user_id'])) {
        $stmt_check_user = $pdo->prepare("SELECT user_id FROM tbl_user WHERE user_id = ? LIMIT 1");
        $stmt_check_user->execute([$_SESSION['user_id']]);
        if ($stmt_check_user->fetchColumn()) {
            $received_by_user_id = $_SESSION['user_id'];
        } else {
            error_log("incoming_ics_receive: session user_id {$_SESSION['user_id']} not found in tbl_user; inserting NULL for received_by_user_id");
        }
    }

    $stmt_header->execute([
        $header['ics_number'],
        $header['source_office'],
        $header['issued_by_name'],
        $header['issued_by_position'] ?? null,
        $header['date_received'],
        $received_by_user_id
    ]);
    $incoming_ics_id = $pdo->lastInsertId();

    // Prepare statements for items
    $sql_item = "INSERT INTO tbl_incoming_ics_item (incoming_ics_id, category_id, description, quantity, unit_id, unit_cost) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_item = $pdo->prepare($sql_item);

    $sql_sep = "INSERT INTO tbl_sep (po_item_id, incoming_ics_item_id, property_number, pn_number_id, serial_number, brand_name, estimated_useful_life, date_acquired, current_condition) VALUES (NULL, ?, ?, NULL, ?, ?, ?, ?, ?)";
    $stmt_sep = $pdo->prepare($sql_sep);

    $sql_ppe = "INSERT INTO tbl_ppe (po_item_id, incoming_ics_item_id, property_number, pn_number_id, model_number, serial_number, date_acquired, current_condition) VALUES (NULL, ?, ?, NULL, ?, ?, ?, ?)";
    $stmt_ppe = $pdo->prepare($sql_ppe);

    $sql_consumable = "INSERT INTO tbl_consumable (po_item_id, incoming_ics_item_id, stock_number, stock_number_id, quantity_received, unit_id, unit_cost, current_stock, date_received) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_consumable = $pdo->prepare($sql_consumable);

    // 2. Process each item
    foreach ($items as $item) {
        // Validate item data
        if (empty($item['description']) || empty($item['quantity']) || empty($item['unit_id']) || !isset($item['unit_cost']) || empty($item['category_id']) || empty($item['inventory_type'])) {
            throw new Exception("One of the items is missing required details (description, quantity, unit, cost, category, or type).");
        }

        // 2a. Insert into tbl_incoming_ics_item
        $stmt_item->execute([
            $incoming_ics_id,
            $item['category_id'],
            $item['description'],
            $item['quantity'],
            $item['unit_id'],
            $item['unit_cost']
        ]);
        $incoming_ics_item_id = $pdo->lastInsertId();

        // 2b. Insert into the correct inventory table
        if ($item['inventory_type'] === 'Consumable') {
            // For consumables, insert one record for the whole quantity
            $stmt_consumable->execute([
                null, // po_item_id
                $incoming_ics_item_id,
                null, // stock_number
                null, // stock_number_id
                $item['quantity'],
                $item['unit_id'],
                $item['unit_cost'],
                $item['quantity'], // current_stock is same as quantity_received initially
                $header['date_received']
            ]);
        } else {
            // For SEP and PPE, loop through each individual item
            for ($i = 0; $i < $item['quantity']; $i++) {
                $details = $item['details'][$i] ?? [];
                $property_number = $details['property_number'] ?? null;

                if (empty($property_number)) {
                    throw new Exception("Property Number is required for all transferred SEP/PPE items.");
                }

                if ($item['inventory_type'] === 'SEP') {
                    $stmt_sep->execute([$incoming_ics_item_id, $property_number, $details['serial_number'] ?? null, $details['brand_name'] ?? null, empty($details['useful_life']) ? null : $details['useful_life'], $header['date_received'], $details['condition'] ?? 'Serviceable']);
                } elseif ($item['inventory_type'] === 'PPE') {
                    $stmt_ppe->execute([$incoming_ics_item_id, $property_number, $details['model_number'] ?? null, $details['serial_number'] ?? null, $header['date_received'], $details['condition'] ?? 'Serviceable']);
                }
            }
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Items from Incoming ICS have been successfully received into inventory!']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Incoming ICS Receive API Error: " . $e->getMessage());
    $statusCode = ($e instanceof PDOException) ? 500 : 400;
    http_response_code($statusCode);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>