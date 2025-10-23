<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit("Unauthorized");
}

$po_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$po_id) {
    http_response_code(400);
    exit("Invalid PO ID.");
}

try {
    // Fetch PO Header
    $sql_header = "SELECT p.*, s.supplier_name, pm.mode_name, dpl.place_name, dt.term_description AS delivery_term, pt.term_description AS payment_term
                   FROM tbl_po p
                   JOIN tbl_supplier s ON p.supplier_id = s.supplier_id
                   JOIN tbl_purchase_mode pm ON p.purchase_mode_id = pm.purchase_mode_id
                   JOIN tbl_delivery_place dpl ON p.delivery_place_id = dpl.delivery_place_id
                   JOIN tbl_delivery_term dt ON p.delivery_term_id = dt.delivery_term_id
                   JOIN tbl_payment_term pt ON p.payment_term_id = pt.payment_term_id
                   WHERE p.po_id = ?";
    $stmt_header = $pdo->prepare($sql_header);
    $stmt_header->execute([$po_id]);
    $header = $stmt_header->fetch();

    if (!$header) {
        throw new Exception("Purchase Order not found.");
    }

    // Fetch PO Items
    $sql_items = "SELECT pi.*, u.unit_name, it.inventory_type_name
                  FROM tbl_po_item pi
                  JOIN tbl_unit u ON pi.unit_id = u.unit_id
                  JOIN tbl_category c ON pi.category_id = c.category_id
                  JOIN tbl_inventory_type it ON c.inventory_type_id = it.inventory_type_id
                  WHERE pi.po_id = ?";
    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute([$po_id]);
    $items = $stmt_items->fetchAll();

    // If the PO is delivered, fetch the generated stock/property numbers
    if ($header['status'] === 'Delivered') {
        foreach ($items as &$item) {
            $item['generated_numbers'] = [];
            if ($item['inventory_type_name'] === 'Consumable') {
                $stmt = $pdo->prepare("SELECT stock_number FROM tbl_consumable WHERE po_item_id = ?");
                $stmt->execute([$item['po_item_id']]);
                $item['generated_numbers'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } elseif ($item['inventory_type_name'] === 'SEP') {
                $stmt = $pdo->prepare("SELECT property_number FROM tbl_sep WHERE po_item_id = ?");
                $stmt->execute([$item['po_item_id']]);
                $item['generated_numbers'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } elseif ($item['inventory_type_name'] === 'PPE') {
                $stmt = $pdo->prepare("SELECT property_number FROM tbl_ppe WHERE po_item_id = ?");
                $stmt->execute([$item['po_item_id']]);
                $item['generated_numbers'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
            }
        }
        unset($item); // Unset reference
    }

} catch (Exception $e) {
    http_response_code(500);
    exit("Database error: " . $e->getMessage());
}
?>

<div id="po-view-content">
    <style>
        #po-view-content { font-size: 0.9rem; }
        .po-header-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0 1rem; }
        .po-header-grid strong { display: inline-block; width: 120px; }
        .item-list-table th, .item-list-table td { vertical-align: middle; }
    </style>
    <h4 class="text-center">Purchase Order</h4>
    <p class="text-center text-muted">PO Number: <strong><?= htmlspecialchars($header['po_number']) ?></strong></p>

    <div class="po-header-grid mb-4">
        <div><strong>Supplier:</strong> <?= htmlspecialchars($header['supplier_name']) ?></div>
        <div><strong>Order Date:</strong> <?= date('F j, Y', strtotime($header['order_date'])) ?></div>
        <div><strong>Purchase Mode:</strong> <?= htmlspecialchars($header['mode_name']) ?></div>
        <div><strong>Delivery Place:</strong> <?= htmlspecialchars($header['place_name']) ?></div>
        <div><strong>Delivery Term:</strong> <?= htmlspecialchars($header['delivery_term']) ?></div>
        <div><strong>Payment Term:</strong> <?= htmlspecialchars($header['payment_term']) ?></div>
    </div>

    <h5 class="mt-4">Items</h5>
    <table class="table table-bordered item-list-table">
        <thead class="table-light">
            <tr>
                <th>Qty</th>
                <th>Unit</th>
                <th>Description</th>
                <?php if ($header['status'] === 'Delivered'): ?>
                    <th>Stock/Property No.</th>
                <?php endif; ?>
                <th class="text-end">Unit Cost</th>
                <th class="text-end">Total Cost</th>
            </tr>
        </thead>
        <tbody>
            <?php $grand_total = 0; ?>
            <?php foreach ($items as $item): ?>
                <?php 
                    $total_cost = $item['quantity'] * $item['unit_cost'];
                    $grand_total += $total_cost;
                ?>
                <tr>
                    <td class="text-center"><?= $item['quantity'] ?></td>
                    <td><?= htmlspecialchars($item['unit_name']) ?></td>
                    <td><?= htmlspecialchars($item['description']) ?></td>
                    <?php if ($header['status'] === 'Delivered'): ?>
                        <td>
                            <?php if (!empty($item['generated_numbers'])): ?>
                                <ul class="list-unstyled mb-0 small">
                                    <?php foreach ($item['generated_numbers'] as $num): ?>
                                        <li><?= htmlspecialchars($num) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <span class="text-muted">N/A</span>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                    <td class="text-end"><?= number_format($item['unit_cost'], 2) ?></td>
                    <td class="text-end"><?= number_format($total_cost, 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="<?= ($header['status'] === 'Delivered') ? '5' : '4' ?>" class="text-end fw-bold">Grand Total:</td>
                <td class="text-end fw-bold">₱ <?= number_format($grand_total, 2) ?></td>
            </tr>
        </tfoot>
    </table>
</div>