<?php
session_start();
require_once '../db.php';

// Security Check: Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo "Unauthorized access.";
    exit;
}

$iirup_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

if (!$iirup_id) {
    http_response_code(400);
    echo "IIRUP ID is required.";
    exit;
}

try {
    // Fetch IIRUP header details
    $stmt_iirup = $pdo->prepare("
        SELECT
            i.iirup_number,
            i.as_of_date,
            i.disposal_method,
            i.status,
            u.full_name AS created_by,
            i.date_created,
            s.school_name
        FROM tbl_iirup i
        JOIN tbl_user u ON i.created_by_user_id = u.user_id
        LEFT JOIN tbl_school s ON s.school_id = 1 -- Assuming school_id 1 is the main school
        WHERE i.iirup_id = ?
    ");
    $stmt_iirup->execute([$iirup_id]);
    $iirup = $stmt_iirup->fetch(PDO::FETCH_ASSOC);

    if (!$iirup) {
        http_response_code(404);
        echo "IIRUP document not found.";
        exit;
    }

    // Fetch IIRUP items
    $stmt_items = $pdo->prepare("
        SELECT
            ii.asset_id,
            ii.asset_type,
            ii.remarks,
            COALESCE(ppe.property_number, sep.property_number) AS property_number,
            COALESCE(po_ppe.description, po_sep.description) AS description,
            COALESCE(ppe.serial_number, sep.serial_number) AS serial_number,
            COALESCE(ppe.date_acquired, sep.date_acquired) AS date_acquired,
            COALESCE(po_ppe.unit_cost, po_sep.unit_cost) AS unit_cost
        FROM tbl_iirup_item ii
        LEFT JOIN tbl_ppe ppe ON ii.asset_id = ppe.ppe_id AND ii.asset_type = 'PPE'
        LEFT JOIN tbl_sep sep ON ii.asset_id = sep.sep_id AND ii.asset_type = 'SEP'
        LEFT JOIN tbl_po_item po_ppe ON ppe.po_item_id = po_ppe.po_item_id
        LEFT JOIN tbl_po_item po_sep ON sep.po_item_id = po_sep.po_item_id
        WHERE ii.iirup_id = ?
    ");
    $stmt_items->execute([$iirup_id]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total book value
    $total_book_value = 0;
    foreach ($items as $item) {
        $total_book_value += $item['unit_cost']; // Assuming unit_cost is the book value for simplicity here
    }

    // Output HTML for the IIRUP document
    ?>
    <div class="iirup-document-content" id="iirup-document-<?= $iirup['iirup_number'] ?>">
        <style>
            .iirup-document-content { font-family: Arial, sans-serif; font-size: 9pt; }
            .report-header { text-align: center; margin-bottom: 20px; }
            .report-header h5, .report-header h6 { margin: 0; }
            .report-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
            .report-table th, .report-table td { border: 1px solid black; padding: 4px; }
            .report-table th { text-align: center; background-color: #f2f2f2; }
            .text-end { text-align: right; }
            .signature-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 40px; page-break-inside: avoid; }
            .signature-block { page-break-inside: avoid; }
            .signature-line { border-top: 1px solid black; margin-top: 40px; }
        </style>
        <div class="report-header">
            <h5>INVENTORY AND INSPECTION REPORT OF UNSERVICEABLE PROPERTY</h5>
            <h6>IIRUP No.: <?= htmlspecialchars($iirup['iirup_number']) ?></h6>
            <h6>As of: <?= date('F j, Y', strtotime($iirup['as_of_date'])) ?></h6>
        </div>
        <div class="d-flex justify-content-between mb-3">
            <span>Entity Name: <span class="fw-bold"><?= htmlspecialchars($iirup['school_name']) ?></span></span>
            <span>Fund Cluster: _______________</span>
        </div>

        <table class="report-table">
            <thead>
                <tr>
                    <th>Date Acquired</th>
                    <th>Article</th>
                    <th>Property No.</th>
                    <th>Serial No.</th>
                    <th>Unit Cost</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="6" class="text-center">No items in this IIRUP.</td></tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= date('Y-m-d', strtotime($item['date_acquired'])) ?></td>
                            <td><?= htmlspecialchars($item['description']) ?></td>
                            <td><?= htmlspecialchars($item['property_number']) ?></td>
                            <td><?= htmlspecialchars($item['serial_number']) ?></td>
                            <td class="text-end"><?= number_format($item['unit_cost'], 2) ?></td>
                            <td><?= htmlspecialchars($item['remarks']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="4" class="text-end">Total Book Value:</th>
                    <th class="text-end"><?= number_format($total_book_value, 2) ?></th>
                    <th></th>
                </tr>
            </tfoot>
        </table>

        <div class="signature-grid">
            <div class="signature-block">
                I HEREBY request inspection and disposal of the foregoing property.
                <div class="signature-line text-center mt-5">__________________________</div>
                <div class="text-center">Signature over Printed Name of<br>Property and/or Supply Custodian</div>
            </div>
            <div class="signature-block">
                I CERTIFY that I have inspected the property and that it is unserviceable.
                <div class="signature-line text-center mt-5">__________________________</div>
                <div class="text-center">Signature over Printed Name of<br>Inspection Officer</div>
            </div>
        </div>
        <div class="mt-4 signature-block">
            <p>Disposal Method: <strong><?= htmlspecialchars($iirup['disposal_method'] ?? 'N/A') ?></strong></p>
            <p>IIRUP Status: <strong><?= htmlspecialchars($iirup['status']) ?></strong></p>
            <p>Created By: <strong><?= htmlspecialchars($iirup['created_by']) ?></strong> on <?= date('F j, Y', strtotime($iirup['date_created'])) ?></p>
        </div>
    </div>
    <?php

} catch (PDOException $e) {
    http_response_code(500);
    echo "Database error: " . $e->getMessage();
}
?>