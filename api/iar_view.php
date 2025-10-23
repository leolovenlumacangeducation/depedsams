<?php
session_start();
require_once '../db.php';

// Security check: ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die("Unauthorized access.");
}

$id = $_GET['id'] ?? null;
if (!$id) {
    http_response_code(400);
    die("PO ID is required.");
}

try {
    // Fetch PO header and supplier
    $stmt_po = $pdo->prepare("SELECT p.*, s.supplier_name FROM tbl_po p LEFT JOIN tbl_supplier s ON p.supplier_id = s.supplier_id WHERE p.po_id = ? LIMIT 1");
    $stmt_po->execute([$id]);
    $po = $stmt_po->fetch();
    if (!$po) {
        http_response_code(404);
        die("Purchase Order not found.");
    }

    // Fetch the latest delivery for this PO (used for DR No. and date)
    $stmt_delivery = $pdo->prepare("SELECT * FROM tbl_delivery WHERE po_id = ? ORDER BY date_received DESC, delivery_id DESC LIMIT 1");
    $stmt_delivery->execute([$id]);
    $delivery = $stmt_delivery->fetch();

    // Fetch delivery items (join to po_item for description and unit)
    if ($delivery) {
        $stmt_items = $pdo->prepare("SELECT di.*, pi.description, u.unit_name, pi.quantity as quantity_ordered FROM tbl_delivery_item di LEFT JOIN tbl_po_item pi ON di.po_item_id = pi.po_item_id LEFT JOIN tbl_unit u ON pi.unit_id = u.unit_id WHERE di.delivery_id = ? ORDER BY di.delivery_item_id");
        $stmt_items->execute([$delivery['delivery_id']]);
        $items = $stmt_items->fetchAll();
    } else {
        // No delivery yet — show zero items
        $items = [];
    }

    $date_received = $delivery['date_received'] ?? null;

    // Fetch School and Officer Info
    $school = $pdo->query("SELECT * FROM tbl_school LIMIT 1")->fetch();
    $accountable_officer = $pdo->query(
        "SELECT u.full_name, p.position_name 
         FROM tbl_officers o
         JOIN tbl_user u ON o.user_id = u.user_id
         JOIN tbl_position p ON u.position_id = p.position_id
         WHERE o.officer_type = 'Accountable Officer' LIMIT 1"
    )->fetch();

} catch (PDOException $e) {
    http_response_code(500);
    error_log("IAR View Error: " . $e->getMessage());
    die("A database error occurred: " . htmlspecialchars($e->getMessage()));
}
?>
<style>
    .iar-container { font-family: Arial, sans-serif; font-size: 10pt; margin: 0 auto; max-width: 800px; border: 1px solid #000; padding: 15px; }
    .iar-header { text-align: center; }
    .iar-header h4, .iar-header h5 { margin: 0; }
    .iar-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .iar-table th, .iar-table td { border: 1px solid black; padding: 4px; vertical-align: top; }
    .iar-table th { text-align: center; }
    .text-center { text-align: center; }
    .signature-block { margin-top: 20px; }
    .signature-block .name { margin-top: 30px; font-weight: bold; text-transform: uppercase; }
    .signature-block .title { border-top: 1px solid black; padding-top: 2px; }
    .d-flex { display: flex; }
    .justify-content-between { justify-content: space-between; }
    .w-50 { width: 50%; }
    .p-2 { padding: 0.5rem; }
</style>

<div id="iar-view-content" class="iar-container">
    <div class="iar-header">
        <h5>INSPECTION AND ACCEPTANCE REPORT</h5>
    </div>

    <table class="iar-table">
        <tr>
            <td colspan="2"><strong>Supplier:</strong> <?= htmlspecialchars($po['supplier_name'] ?? 'N/A') ?></td>
            <td><strong>IAR No.:</strong> <?= htmlspecialchars(($delivery['delivery_receipt_no'] ?? '')) ?></td>
        </tr>
        <tr>
            <td colspan="2"><strong>PO No./Date:</strong> <?= htmlspecialchars($po['po_number']) ?> / <?= date('m-d-Y', strtotime($po['order_date'])) ?></td>
            <td><strong>Date:</strong> <?= $date_received ? date('m-d-Y', strtotime($date_received)) : '_________________' ?></td>
        </tr>
        <tr>
            <td colspan="2"><strong>Requisitioning Office/Dept:</strong> <?= htmlspecialchars($school['school_name'] ?? 'N/A') ?></td>
            <td><strong>Invoice No. / DR No.:</strong> <?= htmlspecialchars($delivery['delivery_receipt_no'] ?? '') ?></td>
        </tr>
        <tr>
            <td colspan="3"><strong>Responsibility Center Code:</strong> _________________</td>
        </tr>
    </table>

    <table class="iar-table">
        <thead>
            <tr>
                <th style="width:15%;">Stock/ Property No.</th>
                <th style="width:45%;">Description</th>
                <th style="width:15%;">Unit</th>
                <th style="width:15%;">Quantity</th>
            </tr>
        </thead>
        <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['stock_number'] ?? $item['property_number'] ?? '') ?></td>
                        <td><?= htmlspecialchars($item['description'] ?? '') ?></td>
                        <td class="text-center"><?= htmlspecialchars($item['unit_name'] ?? '') ?></td>
                        <td class="text-center"><?= htmlspecialchars($item['quantity_delivered'] ?? $item['quantity'] ?? '') ?></td>
                    </tr>
            <?php endforeach; ?>
            <?php for ($i = count($items); $i < 8; $i++): ?>
                <tr><td>&nbsp;</td><td></td><td></td><td></td></tr>
            <?php endfor; ?>
        </tbody>
    </table>

    <table class="iar-table">
        <tr>
            <td class="w-50" style="vertical-align: top;">
                <strong>INSPECTION</strong>
                <p style="margin-top: 1rem;">
                    <strong>Date Inspected:</strong> <?= $date_received ?>
                </p>
                <div class="text-center" style="margin-top: 1rem;">
                    <p style="margin-bottom: 0;">
                        <input type="checkbox"> Inspected, verified and found in order as to quantity and specifications.
                    </p>
                </div>
                <div class="text-center signature-block">
                    <div class="name">_________________________</div>
                    <div class="title">Inspection Officer/Inspection Committee</div>
                </div>
            </td>
            <td class="w-50" style="vertical-align: top;">
                <strong>ACCEPTANCE</strong>
                <p style="margin-top: 1rem;">
                    <strong>Date Received:</strong> <?= $date_received ?>
                </p>
                <div style="margin-top: 1rem;">
                    <p style="margin-bottom: 0;">
                        <input type="checkbox"> Complete
                    </p>
                    <p style="margin-bottom: 0;">
                        <input type="checkbox"> Partial (pls. specify quantity)
                    </p>
                </div>
                <div class="text-center signature-block">
                    <div class="name">
                        <?= htmlspecialchars($accountable_officer['full_name'] ?? '_________________________') ?>
                    </div>
                    <div class="title">
                        <?= htmlspecialchars($accountable_officer['position_name'] ?? 'Signature over Printed Name') ?>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</div>
