<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die('Forbidden');
}

$issuance_id = $_GET['id'] ?? null;
if (!$issuance_id) {
    http_response_code(400);
    die('Bad Request: Missing ID');
}

try {
    // Fetch RIS header details
    $stmt = $pdo->prepare("
        SELECT 
            i.ris_number, 
            i.date_issued, 
            i.issued_to, 
            u_by.full_name AS issued_by,
            p_by.position_name AS issued_by_position,
            s.school_name,
            s.school_code
        FROM tbl_issuance i
        JOIN tbl_user u_by ON i.issued_by_user_id = u_by.user_id
        LEFT JOIN tbl_position p_by ON u_by.position_id = p_by.position_id
        CROSS JOIN (SELECT school_name, school_code FROM tbl_school LIMIT 1) s
        WHERE i.issuance_id = ?
    ");
    $stmt->execute([$issuance_id]);
    $ris = $stmt->fetch();

    if (!$ris) {
        http_response_code(404);
        die('RIS not found.');
    }

    // Fetch RIS items
    $stmt = $pdo->prepare("
        SELECT 
            ii.quantity_issued,
            c.stock_number,
            pi.description,
            u.unit_name,
            c.unit_cost
        FROM tbl_issuance_item ii
        JOIN tbl_consumable c ON ii.consumable_id = c.consumable_id
        JOIN tbl_po_item pi ON c.po_item_id = pi.po_item_id
        JOIN tbl_unit u ON c.unit_id = u.unit_id
        WHERE ii.issuance_id = ?
    ");
    $stmt->execute([$issuance_id]);
    $items = $stmt->fetchAll();

    // Fetch officer names
    $officers = [];
    $stmt = $pdo->query("
        SELECT o.officer_type, u.full_name, p.position_name
        FROM tbl_officers o
        JOIN tbl_user u ON o.user_id = u.user_id
        LEFT JOIN tbl_position p ON u.position_id = p.position_id
    ");
    while ($row = $stmt->fetch()) {
        $officers[$row['officer_type']] = $row;
    }

} catch (PDOException $e) {
    http_response_code(500);
    die('Database error: ' . $e->getMessage());
}
?>

<div id="ris-view-content">
    <style>
        .ris-container { font-family: Arial, sans-serif; margin: auto; }
        .ris-header, .ris-footer { text-align: center; }
        .ris-header h5, .ris-header h6 { margin: 0; }
        .ris-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .ris-table th, .ris-table td { border: 1px solid black; padding: 5px; }
        .ris-table th { text-align: center; }
        .underline { text-decoration: underline; }
        .ris-signatures { margin-top: 2rem; display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
        .sig-box { text-align: center; }
        .sig-box .name { font-weight: bold; margin-top: 2rem; }
        .sig-box .position { font-style: italic; }
    </style>

    <div class="ris-container p-3">
        <div class="ris-header mb-4">
            <h5>REQUISITION AND ISSUE SLIP</h5>
            <h6><?php echo htmlspecialchars($ris['school_name']); ?></h6>
        </div>

        <div class="d-flex justify-content-between mb-3">
            <div>Entity Name: <?php echo htmlspecialchars($ris['school_name']); ?></div>
            <div>Fund Cluster: ____________</div>
        </div>
        <div class="d-flex justify-content-between mb-3">
            <div>Division: <?php echo htmlspecialchars($ris['issued_to']); ?></div>
            <div>RIS No.: <span class="fw-bold"><?php echo htmlspecialchars($ris['ris_number']); ?></span></div>
        </div>
        <div class="d-flex justify-content-between mb-3">
            <div>Responsibility Center Code: <?php echo htmlspecialchars($ris['school_code']); ?></div>
            <div>Date: <?php echo date('F j, Y', strtotime($ris['date_issued'])); ?></div>
        </div>

        <table class="ris-table">
            <thead>
                <tr>
                    <th colspan="2">Requisition</th>
                    <th colspan="2">Issuance</th>
                </tr>
                <tr>
                    <th>Stock No.</th>
                    <th>Description</th>
                    <th>Quantity</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total_cost = 0;
                foreach ($items as $item): 
                    $total_cost += $item['quantity_issued'] * $item['unit_cost'];
                ?>
                <tr>
                    <td class="text-center"><?php echo htmlspecialchars($item['stock_number']); ?></td>
                    <td><?php echo htmlspecialchars($item['description']); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($item['quantity_issued']); ?></td>
                    <td></td>
                </tr>
                <?php endforeach; ?>
                <?php for ($i = count($items); $i < 10; $i++): // Add blank rows ?>
                <tr><td>&nbsp;</td><td></td><td></td><td></td></tr>
                <?php endfor; ?>
            </tbody>
        </table>

        <div class="ris-signatures mt-4">
            <div class="sig-box">
                <strong>Requested by:</strong>
                <div class="name mt-4"><?php echo htmlspecialchars($ris['issued_to']); ?></div>
                <hr class="m-0">
                <div class="position">Signature over Printed Name</div>
            </div>
            <div class="sig-box">
                <strong>Approved by:</strong>
                <div class="name mt-4"><?php echo htmlspecialchars($officers['Approving Officer']['full_name'] ?? '____________________'); ?></div>
                <hr class="m-0">
                <div class="position"><?php echo htmlspecialchars($officers['Approving Officer']['position_name'] ?? 'Signature over Printed Name'); ?></div>
            </div>
            <div class="sig-box">
                <strong>Issued by:</strong>
                <div class="name mt-4"><?php echo htmlspecialchars($ris['issued_by']); ?></div>
                <hr class="m-0">
                <div class="position"><?php echo htmlspecialchars($ris['issued_by_position'] ?? 'Signature over Printed Name'); ?></div>
            </div>
            <div class="sig-box">
                <strong>Received by:</strong>
                <div class="name mt-4"><?php echo htmlspecialchars($ris['issued_to']); ?></div>
                <hr class="m-0">
                <div class="position">Signature over Printed Name</div>
            </div>
        </div>
    </div>
</div>