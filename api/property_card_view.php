<?php
session_start();
require_once '../db.php';

// Security check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo "Unauthorized";
    exit;
}

$ppe_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$ppe_id) {
    http_response_code(400);
    echo "Invalid PPE ID.";
    exit;
}

try {
    // 1. Fetch PPE Header Details
    $sql_header = "SELECT 
                        p.property_number,
                        p.date_acquired,
                        COALESCE(pi.description, iici.description) AS description,
                        COALESCE(pi.unit_cost, iici.unit_cost) AS unit_cost
                   FROM tbl_ppe p
                   LEFT JOIN tbl_po_item pi ON p.po_item_id = pi.po_item_id
                   LEFT JOIN tbl_incoming_ics_item iici ON p.incoming_ics_item_id = iici.incoming_ics_item_id
                   WHERE p.ppe_id = ?";
    $stmt_header = $pdo->prepare($sql_header);
    $stmt_header->execute([$ppe_id]);
    $header = $stmt_header->fetch();

    if (!$header) {
        http_response_code(404);
        echo "PPE item not found.";
        exit;
    }

    // 2. Fetch PPE Movement History
    $sql_history = "SELECT 
                        h.transaction_date,
                        h.reference,
                        h.transaction_type,
                        h.notes,
                        uf.full_name AS from_user,
                        ut.full_name AS to_user
                    FROM tbl_ppe_history h
                    LEFT JOIN tbl_user uf ON h.from_user_id = uf.user_id
                    LEFT JOIN tbl_user ut ON h.to_user_id = ut.user_id
                    WHERE h.ppe_id = ?
                    ORDER BY h.transaction_date ASC, h.history_id ASC";
    $stmt_history = $pdo->prepare($sql_history);
    $stmt_history->execute([$ppe_id]);
    $history = $stmt_history->fetchAll();

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Property Card View Error: " . $e->getMessage());
    echo "Database error occurred.";
    exit;
}

?>
<div id="property-card-content">
    <style>
        #property-card-content { font-family: Arial, sans-serif; font-size: 9pt; }
        .pc-header { text-align: center; margin-bottom: 20px; }
        .pc-header h5, .pc-header h6 { margin: 0; }
        .pc-details-grid { display: grid; grid-template-columns: 1fr 1fr; margin-bottom: 5px; }
        .pc-details-grid div { padding: 2px 0; }
        .pc-table { width: 100%; border-collapse: collapse; }
        .pc-table th, .pc-table td { border: 1px solid black; padding: 4px; text-align: center; }
        .pc-table th { font-weight: bold; }
        .pc-table .text-left { text-align: left; }
        .pc-table .text-right { text-align: right; }
        .appendix { float: right; font-weight: bold; }
    </style>
    <div class="appendix">Appendix 69</div>
    <div class="pc-header">
        <h5>PROPERTY CARD</h5>
    </div>
    <div class="pc-details-grid">
        <div><strong>Entity Name:</strong> Pagadian City National Comprehensive High School</div>
        <div><strong>Fund Cluster:</strong> ______________________</div>
    </div>
    <div class="pc-details-grid">
        <div><strong>Property, Plant and Equipment:</strong> <?= htmlspecialchars($header['description']) ?></div>
        <div><strong>Property Number:</strong> <span class="property-card-number-data"><?= htmlspecialchars($header['property_number']) ?></span></div>
    </div>
     <div class="pc-details-grid">
        <div><strong>Description:</strong> <?= htmlspecialchars($header['description']) ?></div>
        <div></div>
    </div>

    <table class="pc-table">
        <thead>
            <tr>
                <th rowspan="2">Date</th>
                <th rowspan="2">Reference / PAR No.</th>
                <th colspan="3">Receipt</th>
                <th colspan="2">Issue/Transfer/Adjustment</th>
                <th colspan="2">Balance</th>
                <th rowspan="2">Remarks</th>
            </tr>
            <tr>
                <th>Qty</th>
                <th>Unit Cost</th>
                <th>Total Cost</th>
                <th>Qty</th>
                <th>Office/Officer</th>
                <th>Qty</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $balance_qty = 0;
            foreach ($history as $row):
                $issue_qty = '';
                $receipt_unit_cost = '';
                $receipt_total_cost = '';
                $issue_officer = '';
                $receipt_qty = '';
                
                if ($row['transaction_type'] == 'Receipt') {
                    $balance_qty += 1;
                    $receipt_qty = 1;
                } elseif ($row['transaction_type'] == 'Assignment') {
                    $receipt_unit_cost = '';
                    $receipt_total_cost = '';
                    $balance_qty -= 1;
                    $issue_qty = 1;
                    $issue_officer = htmlspecialchars($row['to_user']);
                    $receipt_qty = '';
                } elseif ($row['transaction_type'] == 'Return') {
                    $balance_qty += 1;
                    // A return is conceptually a receipt back into the property office
                    $receipt_unit_cost = number_format($header['unit_cost'], 2);
                    $receipt_total_cost = number_format($header['unit_cost'], 2);
                    $receipt_qty = 1;
                    $issue_officer = htmlspecialchars($row['from_user']);
                }
            ?>
            <tr>
                <td><?= htmlspecialchars(date('m/d/Y', strtotime($row['transaction_date']))) ?></td>
                <td class="text-left"><?= htmlspecialchars($row['reference']) ?></td> <!-- Ref/PAR No. -->
                <td><?= $receipt_qty ?></td>
                <td class="text-right"><?= $receipt_qty ? number_format($header['unit_cost'], 2) : '' ?></td>
                <td class="text-right"><?= $receipt_qty ? number_format($header['unit_cost'], 2) : '' ?></td>
                <td><?= $issue_qty ?></td>
                <td class="text-left"><?= $issue_officer ?></td>
                <td><?= $balance_qty > 0 ? $balance_qty : '0' ?></td>
                <td class="text-right"><?= number_format($balance_qty * $header['unit_cost'], 2) ?></td> <!-- Amount -->
                <td class="text-left"><?= htmlspecialchars($row['notes']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($history)): ?>
                <tr><td colspan="10">No history found for this item.</td></tr>
            <?php endif; ?>

        </tbody>
    </table>
</div>