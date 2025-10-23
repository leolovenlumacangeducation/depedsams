<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo "Unauthorized";
    exit;
}

$par_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$par_id) {
    http_response_code(400);
    echo "Invalid PAR ID.";
    exit;
}

try {
    // Fetch PAR header
    $sql_header = "SELECT 
                    p.par_number, p.date_issued,
                    custodian.full_name AS issued_to_name,
                    issuer.full_name AS issued_by_name,
                    issuer_pos.position_name AS issued_by_position
                   FROM tbl_par p
                   JOIN tbl_user custodian ON p.issued_to_user_id = custodian.user_id
                   JOIN tbl_user issuer ON p.issued_by_user_id = issuer.user_id
                   LEFT JOIN tbl_position issuer_pos ON issuer.position_id = issuer_pos.position_id
                   WHERE p.par_id = ?";
    $stmt_header = $pdo->prepare($sql_header);
    $stmt_header->execute([$par_id]);
    $header = $stmt_header->fetch();

    if (!$header) {
        throw new Exception("PAR not found.");
    }

    // Fetch PAR items
    $sql_items = "SELECT 
                    ppe.property_number,
                    pi.description,
                    pi.unit_cost,
                    u.unit_name
                  FROM tbl_par_item pi_link
                  JOIN tbl_ppe ppe ON pi_link.ppe_id = ppe.ppe_id
                  JOIN tbl_po_item pi ON ppe.po_item_id = pi.po_item_id
                  JOIN tbl_unit u ON pi.unit_id = u.unit_id
                  WHERE pi_link.par_id = ?";
    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute([$par_id]);
    $items = $stmt_items->fetchAll();

    // Fetch School Info
    $school = $pdo->query("SELECT school_name FROM tbl_school LIMIT 1")->fetch();

} catch (Exception $e) {
    http_response_code(500);
    echo "Database error: " . $e->getMessage();
    exit;
}
?>

<div id="par-view-content">
    <style>
        #par-view-content { font-family: Arial, sans-serif; font-size: 10pt; }
        #par-view-content table { width: 100%; border-collapse: collapse; }
        #par-view-content th, #par-view-content td { border: 1px solid black; padding: 4px; text-align: center; }
        #par-view-content .header-table td { border: none; }
        #par-view-content .text-start { text-align: left; }
        #par-view-content .text-end { text-align: right; }
        #par-view-content .fw-bold { font-weight: bold; }
        #par-view-content .underline { text-decoration: underline; }
        #par-view-content .signature-box { height: 80px; vertical-align: bottom; }
    </style>

    <h5 class="text-center fw-bold">PROPERTY ACKNOWLEDGMENT RECEIPT</h5>
    
    <table class="header-table mb-3">
        <tr>
            <td width="70%">Entity Name: <span class="fw-bold underline"><?= htmlspecialchars($school['school_name'] ?? 'N/A') ?></span></td>
            <td width="30%">PAR No.: <span class="fw-bold underline par-number-data"><?= htmlspecialchars($header['par_number']) ?></span></td>
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
                <th width="40%">Description</th>
                <th width="20%">Property Number</th>
                <th width="20%">Date Acquired</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td>1</td>
                    <td><?= htmlspecialchars($item['unit_name']) ?></td>
                    <td class="text-start"><?= htmlspecialchars($item['description']) ?></td>
                    <td><?= htmlspecialchars($item['property_number']) ?></td>
                    <td></td> <!-- Date Acquired could be added if needed -->
                </tr>
            <?php endforeach; ?>
            <?php for ($i = count($items); $i < 10; $i++): ?>
                <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td></tr>
            <?php endfor; ?>
        </tbody>
    </table>

    <table class="header-table mt-4">
        <tr>
            <td width="50%" class="text-start">
                <div class="mb-5">Received by:</div>
                <div class="fw-bold underline text-center"><?= htmlspecialchars($header['issued_to_name']) ?></div>
                <div class="text-center">Signature over Printed Name of End User</div>
            </td>
            <td width="50%" class="text-start">
                <div class="mb-5">Issued by:</div>
                <div class="fw-bold underline text-center"><?= htmlspecialchars($header['issued_by_name']) ?></div>
                <div class="text-center">Signature over Printed Name of Supply Officer</div>
            </td>
        </tr>
        <tr>
            <td class="text-start">
                <div class="mt-3">Position: _________________________</div>
            </td>
            <td class="text-start">
                <div class="mt-3">Position: <span class="underline"><?= htmlspecialchars($header['issued_by_position'] ?? 'N/A') ?></span></div>
            </td>
        </tr>
    </table>

</div>