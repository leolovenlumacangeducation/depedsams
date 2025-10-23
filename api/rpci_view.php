<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit("Unauthorized");
}

$rpci_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$rpci_id) {
    http_response_code(400);
    exit("Invalid RPCI ID.");
}

try {
    // Fetch RPCI header
    $sql_header = "SELECT r.rpci_number, r.as_of_date, u.full_name AS created_by FROM tbl_rpci r JOIN tbl_user u ON r.created_by_user_id = u.user_id WHERE r.rpci_id = ?";
    $stmt_header = $pdo->prepare($sql_header);
    $stmt_header->execute([$rpci_id]);
    $header = $stmt_header->fetch();

    if (!$header) throw new Exception("RPCI not found.");

    // Fetch RPCI items
    $sql_items = "SELECT 
                    ri.balance_per_card, ri.on_hand_per_count, ri.shortage_qty, ri.shortage_value, ri.remarks,
                    c.stock_number, pi.description, u.unit_name, c.unit_cost
                  FROM tbl_rpci_item ri
                  JOIN tbl_consumable c ON ri.consumable_id = c.consumable_id
                  JOIN tbl_po_item pi ON c.po_item_id = pi.po_item_id
                  JOIN tbl_unit u ON c.unit_id = u.unit_id
                  WHERE ri.rpci_id = ? ORDER BY pi.description ASC";
    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute([$rpci_id]);
    $items = $stmt_items->fetchAll();

    // Fetch school and officer info
    $school = $pdo->query("SELECT school_name FROM tbl_school LIMIT 1")->fetch();
    $property_custodian = $pdo->query("SELECT u.full_name FROM tbl_officers o JOIN tbl_user u ON o.user_id = u.user_id WHERE o.officer_type = 'Accountable Officer' LIMIT 1")->fetchColumn();
    $approving_officer = $pdo->query("SELECT u.full_name FROM tbl_officers o JOIN tbl_user u ON o.user_id = u.user_id WHERE o.officer_type = 'Approving Officer' LIMIT 1")->fetchColumn();

} catch (Exception $e) {
    http_response_code(500);
    exit("Database error: " . $e->getMessage());
}

// Use the existing template from rpci_report.php by including it and populating it
ob_start();
?>
<div id="printable-rpci-report">
    <style>
        #printable-rpci-report { font-family: Arial, sans-serif; font-size: 9pt; }
        .report-header { text-align: center; margin-bottom: 20px; }
        .report-header h5, .report-header h6 { margin: 0; }
        .report-table { width: 100%; border-collapse: collapse; }
        .report-table th, .report-table td { border: 1px solid black; padding: 4px; }
        .report-table th { text-align: center; }
        .text-end { text-align: right; }
        .signature-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 40px; }
        .signature-line { border-top: 1px solid black; margin-top: 40px; }
    </style>
    <div class="report-header">
        <h5>REPORT ON THE PHYSICAL COUNT OF INVENTORIES</h5>
        <h6>As of <?= htmlspecialchars(date('F j, Y', strtotime($header['as_of_date']))) ?></h6>
    </div>
    <div class="d-flex justify-content-between mb-3">
        <span>Fund Cluster: _______________</span>
        <span>For which <span class="fw-bold"><?= htmlspecialchars($property_custodian) ?></span>, <span class="fw-bold"><?= htmlspecialchars($school['school_name']) ?></span> is accountable, having assumed such accountability on _______________.</span>
    </div>
    <table class="report-table">
        <thead>
            <tr>
                <th rowspan="2">Article</th><th rowspan="2">Description</th><th rowspan="2">Stock Number</th><th rowspan="2">Unit of Measurement</th><th rowspan="2">Unit Value</th><th>Balance Per Card</th><th>On Hand Per Count</th><th colspan="2">Shortage/Overage</th><th rowspan="2">Remarks</th>
            </tr>
            <tr><th>(Quantity)</th><th>(Quantity)</th><th>(Quantity)</th><th>(Value)</th></tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td></td>
                <td><?= htmlspecialchars($item['description']) ?></td>
                <td><?= htmlspecialchars($item['stock_number']) ?></td>
                <td><?= htmlspecialchars($item['unit_name']) ?></td>
                <td class="text-end"><?= number_format($item['unit_cost'], 2) ?></td>
                <td class="text-end"><?= htmlspecialchars($item['balance_per_card']) ?></td>
                <td class="text-end"><?= htmlspecialchars($item['on_hand_per_count']) ?></td>
                <td class="text-end"><?= htmlspecialchars($item['shortage_qty']) ?></td>
                <td class="text-end"><?= number_format($item['shortage_value'], 2) ?></td>
                <td><?= htmlspecialchars($item['remarks']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="signature-grid">
        <div>Certified Correct by:<div class="signature-line text-center"><?= htmlspecialchars($approving_officer) ?></div><div class="text-center">Signature Over Printed Name of<br>Inventory Committee Chair and Members</div></div>
        <div>Approved by:<div class="signature-line text-center">__________________________</div><div class="text-center">Signature Over Printed Name of Head of Agency/Entity or His/Her Authorized Representative</div></div>
    </div>
</div>
<?php
echo ob_get_clean();
?>