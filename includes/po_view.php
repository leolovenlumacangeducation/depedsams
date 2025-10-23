<?php
session_start();
require_once '../db.php';

// Security check: ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die("Unauthorized access.");
}

// Get PO ID from the query string
$po_id = $_GET['id'] ?? null;
if (!$po_id) {
    http_response_code(400);
    die("Purchase Order ID is required.");
}

try {
    // --- Fetch Main PO Data ---
    $sql_po = "SELECT p.*, s.supplier_name, s.address AS supplier_address, s.tin, 
                      pm.mode_name, dp.place_name, dt.term_description AS delivery_term, pt.term_description AS payment_term
               FROM tbl_po p
               JOIN tbl_supplier s ON p.supplier_id = s.supplier_id
               JOIN tbl_purchase_mode pm ON p.purchase_mode_id = pm.purchase_mode_id
               JOIN tbl_delivery_place dp ON p.delivery_place_id = dp.delivery_place_id
               JOIN tbl_delivery_term dt ON p.delivery_term_id = dt.delivery_term_id
               JOIN tbl_payment_term pt ON p.payment_term_id = pt.payment_term_id
               WHERE p.po_id = ?";
    $stmt_po = $pdo->prepare($sql_po);
    $stmt_po->execute([$po_id]);
    $po = $stmt_po->fetch();

    if (!$po) {
        http_response_code(404);
        die("Purchase Order not found.");
    }

    // --- Fetch PO Items ---
    $sql_items = "SELECT i.*, u.unit_name
                  FROM tbl_po_item i
                  JOIN tbl_unit u ON i.unit_id = u.unit_id
                  WHERE i.po_id = ?
                  ORDER BY i.po_item_id";
    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute([$po_id]);
    $items = $stmt_items->fetchAll();

    // --- Fetch School and Officer Info ---
    $school = $pdo->query("SELECT * FROM tbl_school LIMIT 1")->fetch();

    // Optimized Officer Fetch: Get all required officers in one query
    $sql_officers = "SELECT o.officer_type, u.full_name, pos.position_name 
                     FROM tbl_officers o
                     JOIN tbl_user u ON o.user_id = u.user_id
                     JOIN tbl_position pos ON u.position_id = pos.position_id
                     WHERE o.officer_type IN ('Approving Officer', 'Funds Available Officer')";
    $stmt_officers = $pdo->query($sql_officers);
    $officers_data = $stmt_officers->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE);

    $approving_officer = $officers_data['Approving Officer'] ?? null;
    $funds_officer = $officers_data['Funds Available Officer'] ?? null;

    // --- Calculate Total ---
    $grand_total = 0;
    foreach ($items as $item) {
        $grand_total += $item['quantity'] * $item['unit_cost'];
    }

    // --- Helper function to convert number to words ---
    function numberToWords($number) {
        $ones = array("", "One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine", "Ten", "Eleven", "Twelve", "Thirteen", "Fourteen", "Fifteen", "Sixteen", "Seventeen", "Eighteen", "Nineteen");
        $tens = array("", "", "Twenty", "Thirty", "Forty", "Fifty", "Sixty", "Seventy", "Eighty", "Ninety");
        $hundreds = array("Hundred", "Thousand", "Million", "Billion", "Trillion"); // Add more if needed

        $num = number_format($number, 2, ".", ",");
        $num_arr = explode(".", $num);
        $wholenum = $num_arr[0];
        $decnum = $num_arr[1];

        $whole_arr = array_reverse(explode(",", $wholenum));
        krsort($whole_arr, 1);

        $rettxt = "";
        foreach ($whole_arr as $key => $i) {
            if ($i < 20) {
                $rettxt .= $ones[$i];
            } elseif ($i < 100) {
                $rettxt .= $tens[substr($i, 0, 1)];
                $rettxt .= " " . $ones[substr($i, 1, 1)];
            } else {
                $rettxt .= $ones[substr($i, 0, 1)] . " " . $hundreds[0];
                $rettxt .= " " . (substr($i, 1, 2) < 20 ? $ones[(int)substr($i, 1, 2)] : $tens[substr($i, 1, 1)] . " " . $ones[substr($i, 2, 1)]);
            }
            if ($key > 0) {
                $rettxt .= " " . $hundreds[$key] . " ";
            }
        }

        $rettxt = $rettxt . " Pesos";

        if ($decnum > 0) {
            $rettxt .= " and " . $decnum . "/100 Only";
        } else {
            $rettxt .= " Only";
        }
        return ucwords(trim(str_replace('  ', ' ', $rettxt)));
    }

} catch (PDOException $e) {
    http_response_code(500);
    error_log("PO View Error: " . $e->getMessage());
    die("A database error occurred: " . htmlspecialchars($e->getMessage()));
}

?>
<style>
    .po-container { font-family: Arial, sans-serif; font-size: 10pt; margin: 0 auto; max-width: 800px; }
    .po-header, .po-footer { text-align: center; }
    .po-header h4, .po-header h5 { margin: 0; }
    .po-details-table, .po-items-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .po-details-table td, .po-items-table th, .po-items-table td { border: 1px solid black; padding: 4px; }
    .po-items-table th { text-align: center; background-color: #f2f2f2; }
    .po-items-table td { vertical-align: top; }
    .text-end { text-align: right; }
    .text-center { text-align: center; }
    .fw-bold { font-weight: bold; }
    .signature-block { width: 45%; text-align: center; margin-top: 40px; }
    .signature-block .name { margin-top: 30px; font-weight: bold; text-transform: uppercase; }
    .signature-block .title { border-top: 1px solid black; padding-top: 2px; }
    .d-flex { display: flex; }
    .justify-content-between { justify-content: space-between; }
    .w-50 { width: 50%; }
    .p-2 { padding: 0.5rem; }
</style>

<div id="po-view-content" class="po-container">
    <div class="po-header">
        <h4>PURCHASE ORDER</h4>
        <h5><?= htmlspecialchars($school['school_name'] ?? 'School Name Not Set') ?></h5>
        <h6><?= htmlspecialchars($school['address'] ?? 'School Address Not Set') ?></h6>
    </div>

    <table class="po-details-table">
        <tr>
            <td style="width: 65%;">
                <strong>Supplier:</strong> <?= htmlspecialchars($po['supplier_name']) ?><br>
                <strong>Address:</strong> <?= htmlspecialchars($po['supplier_address']) ?><br>
                <strong>TIN:</strong> <?= htmlspecialchars($po['tin']) ?>
            </td>
            <td style="width: 35%;">
                <strong>PO No.:</strong> <?= htmlspecialchars($po['po_number']) ?><br>
                <strong>Date:</strong> <?= date('F j, Y', strtotime($po['order_date'])) ?><br>
                <strong>Mode of Procurement:</strong> <?= htmlspecialchars($po['mode_name']) ?>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                Gentlemen:<br>
                Please furnish this Office the following articles subject to the terms and conditions contained herein:
            </td>
        </tr>
        <tr>
            <td>
                <strong>Place of Delivery:</strong> <?= htmlspecialchars($po['place_name']) ?><br>
                <strong>Delivery Term:</strong> <?= htmlspecialchars($po['delivery_term']) ?>
            </td>
            <td>
                <strong>Payment Term:</strong> <?= htmlspecialchars($po['payment_term']) ?>
            </td>
        </tr>
    </table>

    <table class="po-items-table">
        <thead>
            <tr>
                <th style="width:15%;">Stock/Property No.</th>
                <th style="width:10%;">Unit</th>
                <th style="width:35%;">Description</th>
                <th style="width:10%;">Quantity</th>
                <th style="width:15%;">Unit Cost</th>
                <th style="width:15%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <?php $item_total = $item['quantity'] * $item['unit_cost']; ?>
                <tr>
                    <td></td> <!-- Stock/Property No. is assigned on receipt -->
                    <td class="text-center"><?= htmlspecialchars($item['unit_name']) ?></td>
                    <td><?= nl2br(htmlspecialchars($item['description'])) ?></td>
                    <td class="text-center"><?= $item['quantity'] ?></td>
                    <td class="text-end"><?= number_format($item['unit_cost'], 2) ?></td>
                    <td class="text-end"><?= number_format($item_total, 2) ?></td>
                </tr>
            <?php endforeach; ?>
            <!-- Add empty rows for spacing if needed -->
            <?php for ($i = count($items); $i < 10; $i++): ?>
                <tr>
                    <td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td>
                </tr>
            <?php endfor; ?>
            <tr>
                <td colspan="5" class="text-end fw-bold">Total</td>
                <td class="text-end fw-bold"><?= number_format($grand_total, 2) ?></td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 15px; padding: 5px; border: 1px solid black;">
        <em>(Total Amount in Words): </em>
        <strong>
            <?php
                echo htmlspecialchars(numberToWords($grand_total));
            ?>
        </strong>
    </div>

    <div style="margin-top: 15px;">
        In case of failure to make the full delivery within the time specified above, a penalty of one-tenth (1/10) of one percent for every day of delay shall be imposed on the undelivered item/s.
    </div>

    <div class="d-flex justify-content-between">
        <div class="signature-block" style="text-align: left;">
            <p>Conforme:</p>
            <div class="name" style="text-align: center;">_________________________</div>
            <div class="title" style="text-align: center;">Signature over Printed Name of Supplier</div>
            <div style="text-align: center; margin-top: 10px;">_________________________</div>
            <div class="title" style="text-align: center;">Date</div>
        </div>
        <div class="signature-block" style="text-align: right;">
            <p style="text-align: left;">Very truly yours,</p>
            <div class="name" style="text-align: center;">
                <?= htmlspecialchars($approving_officer['full_name'] ?? '_________________________') ?>
            </div>
            <div class="title" style="text-align: center;">
                <?= htmlspecialchars($approving_officer['position_name'] ?? 'Signature over Printed Name of Authorized Official') ?>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between" style="margin-top: 20px;">
        <div class="w-50 p-2" style="border: 1px solid black;">
            <strong>Funds Available:</strong>
            <div class="signature-block" style="width: 100%; margin-top: 20px;">
                <div class="name">
                    <?= htmlspecialchars($funds_officer['full_name'] ?? '_________________________') ?>
                </div>
                <div class="title">
                    <?= htmlspecialchars($funds_officer['position_name'] ?? 'Signature over Printed Name') ?>
                </div>
            </div>
        </div>
        <div class="w-50 p-2" style="border: 1px solid black; border-left: none;">
            <strong>ORS/BURS No.:</strong> _________________________<br>
            <strong>Date of the ORS/BURS:</strong> ____________________<br>
            <strong>Amount:</strong> _________________________
        </div>
    </div>
</div>