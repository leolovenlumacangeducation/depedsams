<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo "Unauthorized";
    exit;
}

$ics_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$ics_id) {
    http_response_code(400);
    echo "Invalid ICS ID.";
    exit;
}

try {
    // Fetch ICS header
    $sql_header = "SELECT 
                    i.ics_number, i.date_issued, i.location,
                    custodian.full_name AS issued_to_name,
                    issuer.full_name AS issued_by_name,
                    issuer_pos.position_name AS issued_by_position
                   FROM tbl_ics i
                   JOIN tbl_user custodian ON i.issued_to_user_id = custodian.user_id
                   JOIN tbl_user issuer ON i.issued_by_user_id = issuer.user_id
                   LEFT JOIN tbl_position issuer_pos ON issuer.position_id = issuer_pos.position_id
                   WHERE i.ics_id = ?";
    $stmt_header = $pdo->prepare($sql_header);
    $stmt_header->execute([$ics_id]);
    $header = $stmt_header->fetch();

    if (!$header) {
        throw new Exception("ICS not found.");
    }

    // Fetch ICS items
    $sql_items = "SELECT 
                    s.property_number,
                    pi.description,
                    pi.unit_cost,
                    u.unit_name
                  FROM tbl_ics_item ii
                  JOIN tbl_sep s ON ii.sep_id = s.sep_id
                  JOIN tbl_po_item pi ON s.po_item_id = pi.po_item_id
                  JOIN tbl_unit u ON pi.unit_id = u.unit_id
                  WHERE ii.ics_id = ?";
    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute([$ics_id]);
    $items = $stmt_items->fetchAll();

    // Fetch School Info
    $school = $pdo->query("SELECT school_name FROM tbl_school LIMIT 1")->fetch();

} catch (PDOException $e) {
    http_response_code(500);
    echo "Database error: " . $e->getMessage();
    exit;
}
?>

<style>
    #ics-view-content { font-family: Arial, sans-serif; font-size: 10pt; }
    #ics-view-content table { width: 100%; border-collapse: collapse; }
    #ics-view-content th, #ics-view-content td { border: 1px solid black; padding: 4px; text-align: center; }
    #ics-view-content .header-table td { border: none; }
    #ics-view-content .text-start { text-align: left; }
    #ics-view-content .text-end { text-align: right; }
    #ics-view-content .fw-bold { font-weight: bold; }
    #ics-view-content .underline { text-decoration: underline; }
    #ics-view-content .signature-box { height: 80px; vertical-align: bottom; }
    @media print {
        body * { visibility: hidden; }
        #ics-view-content, #ics-view-content * { visibility: visible; }
        #ics-view-content { position: absolute; left: 0; top: 0; width: 100%; }
    }
</style>

<div id="ics-view-content">
    <h5 class="text-center fw-bold">INVENTORY CUSTODIAN SLIP</h5>
    
    <table class="header-table mb-3">
        <tr>
            <td width="70%">Entity Name: <span class="fw-bold underline"><?= htmlspecialchars($school['school_name'] ?? 'N/A') ?></span></td>
            <td width="30%">ICS No.: <span class="fw-bold underline" data-template-id="ics_number"><?= htmlspecialchars($header['ics_number']) ?></span></td>
        </tr>
        <tr>
            <td>Fund Cluster: _______________</td>
            <td>Date: <span class="fw-bold underline"><?= date('F j, Y', strtotime($header['date_issued'])) ?></span></td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th width="10%">Quantity</th>
                <th width="10%">Unit</th>
                <th width="15%" class="text-end">Unit Cost</th>
                <th width="15%" class="text-end">Total Cost</th>
                <th width="30%">Description</th>
                <th width="20%">Inventory Property No.</th>
            </tr>
        </thead>
        <tbody>
            <?php $grand_total = 0; ?>
            <?php foreach ($items as $item): ?>
                <?php 
                    $quantity = 1; // Each SEP is one item
                    $total_cost = $quantity * $item['unit_cost'];
                    $grand_total += $total_cost;
                ?>
                <tr>
                    <td><?= $quantity ?></td>
                    <td><?= htmlspecialchars($item['unit_name']) ?></td>
                    <td class="text-end"><?= number_format($item['unit_cost'], 2) ?></td>
                    <td class="text-end"><?= number_format($total_cost, 2) ?></td>
                    <td class="text-start"><?= htmlspecialchars($item['description']) ?></td>
                    <td><?= htmlspecialchars($item['property_number']) ?></td>
                </tr>
            <?php endforeach; ?>
            <!-- Add empty rows for spacing if needed -->
            <?php for ($i = count($items); $i < 10; $i++): ?>
                <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td></tr>
            <?php endfor; ?>
        </tbody>
    </table>

    <table class="mt-3">
        <tr>
            <td width="50%" class="text-start fw-bold">Received from:</td>
            <td width="50%" class="text-start fw-bold">Received by:</td>
        </tr>
        <tr>
            <td class="signature-box text-center"><span class="fw-bold underline"><?= strtoupper(htmlspecialchars($header['issued_by_name'])) ?></span><br><?= htmlspecialchars($header['issued_by_position']) ?></td>
            <td class="signature-box text-center"><span class="fw-bold underline"><?= strtoupper(htmlspecialchars($header['issued_to_name'])) ?></span><br>Signature over Printed Name of End User</td>
        </tr>
    </table>
</div>