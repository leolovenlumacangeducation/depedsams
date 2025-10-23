<?php
session_start();
require_once '../db.php';
require_once '../includes/functions.php';

// Security check: ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die('Forbidden: You do not have permission.');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    die('Bad Request: Invalid or missing consumable ID.');
}

$consumable_id = intval($_GET['id']);

try {
    // 1. Fetch item details including current stock
    $item_stmt = $pdo->prepare("
        SELECT poi.description, c.stock_number, u.unit_name, c.current_stock
        FROM tbl_consumable c
        JOIN tbl_unit u ON c.unit_id = u.unit_id
        LEFT JOIN tbl_po_item poi ON c.po_item_id = poi.po_item_id
        WHERE c.consumable_id = ?
    ");
    $item_stmt->execute([$consumable_id]);
    $item = $item_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        http_response_code(404);
        die('Item not found.');
    }

    // 2. Fetch transaction history from the correct view
    $transactions_stmt = $pdo->prepare("
        SELECT
            transaction_date,
            reference,
            quantity_in,
            quantity_out,
            person_in_charge,
            transaction_datetime
        FROM vw_consumable_stock_card
        WHERE consumable_id = ?
        ORDER BY transaction_date ASC, transaction_datetime ASC
    ");
    $transactions_stmt->execute([$consumable_id]);
    $transactions = $transactions_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Calculate running balance
    $transactions_with_balance = [];
    if (!empty($transactions)) {
        $total_in = array_sum(array_column($transactions, 'quantity_in'));
        $total_out = array_sum(array_column($transactions, 'quantity_out'));
        $starting_balance = $item['current_stock'] - ($total_in - $total_out);

        $running_balance = $starting_balance;
        foreach ($transactions as $tx) {
            $qty_in = (int)($tx['quantity_in'] ?? 0);
            $qty_out = (int)($tx['quantity_out'] ?? 0);
            $running_balance += ($qty_in - $qty_out);
            $tx['balance'] = $running_balance;
            $transactions_with_balance[] = $tx;
        }
    }

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Stock Card PDF View Error: " . $e->getMessage());
    die('Database error occurred.');
}
?>

<!-- This is the HTML content that will be loaded into the modal -->
<style id="sc-pdf-style">
    .stock-card-table {
        width: 100%;
        border-collapse: collapse;
        font-family: Arial, sans-serif;
        font-size: 10pt;
    }
    .stock-card-table th, .stock-card-table td {
        border: 1px solid #000;
        padding: 4px;
        text-align: center;
    }
    .stock-card-table th {
        background-color: #f2f2f2;
        font-weight: bold;
    }
    .stock-card-header {
        text-align: center;
        margin-bottom: 15px;
    }
    .stock-card-header h5, .stock-card-header h6 {
        margin: 0;
        padding: 0;
    }
    .stock-card-details {
        width: 100%;
        margin-bottom: 10px;
        font-size: 10pt;
    }
    .stock-card-details td {
        padding: 2px 5px;
    }
    .text-left { text-align: left; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
</style>

<div id="sc-pdf-content">
    <div class="stock-card-header">
        <h5>STOCK CARD</h5>
        <h6>Appendix 58</h6>
    </div>

    <table class="stock-card-details">
        <tr>
            <td width="15%"><strong>Entity Name:</strong></td>
            <td width="55%" style="border-bottom: 1px solid #000;"></td>
            <td width="15%"><strong>Fund Cluster:</strong></td>
            <td width="15%" style="border-bottom: 1px solid #000;"></td>
        </tr>
    </table>

    <table class="stock-card-details">
        <tr>
            <td width="10%"><strong>Item:</strong></td>
            <td width="40%" style="border-bottom: 1px solid #000;"><?= htmlspecialchars($item['description']) ?></td>
            <td width="15%"><strong>Stock No.:</strong></td>
            <td width="35%" style="border-bottom: 1px solid #000;"><?= htmlspecialchars($item['stock_number']) ?></td>
        </tr>
        <tr>
            <td><strong>Unit:</strong></td>
            <td style="border-bottom: 1px solid #000;"><?= htmlspecialchars($item['unit_name']) ?></td>
            <td><strong>Re-order Point:</strong></td>
            <td style="border-bottom: 1px solid #000;"></td>
        </tr>
    </table>

    <table class="stock-card-table">
        <thead>
            <tr>
                <th rowspan="2">Date</th>
                <th rowspan="2">Reference</th>
                <th colspan="3">Receipt</th>
                <th colspan="2">Issue</th>
                <th rowspan="2">Balance</th>
            </tr>
            <tr>
                <th>Qty.</th>
                <th>Qty.</th>
                <th>Amount</th>
                <th>Qty.</th>
                <th>Office</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions_with_balance as $tx): ?>
            <tr>
                <td><?= htmlspecialchars(date('m/d/Y', strtotime($tx['transaction_date']))) ?></td>
                <td><?= htmlspecialchars($tx['reference']) ?></td>
                <td><?= $tx['quantity_in'] > 0 ? htmlspecialchars($tx['quantity_in']) : '' ?></td>
                <td></td> <!-- Receipt Qty -->
                <td></td> <!-- Receipt Amount -->
                <td><?= $tx['quantity_out'] > 0 ? htmlspecialchars($tx['quantity_out']) : '' ?></td>
                <td><?= htmlspecialchars($tx['person_in_charge']) ?></td> <!-- Issue Office -->
                <td><?= htmlspecialchars($tx['balance']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($transactions_with_balance)): ?>
            <tr>
                <td colspan="8" class="text-center">No transaction history found.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>