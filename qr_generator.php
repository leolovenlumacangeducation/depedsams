<?php
require_once 'db.php';
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

// Fetch all non-disposed PPE items
$ppe_items = $pdo->query("
    SELECT p.ppe_id, p.property_number, pi.description
    FROM tbl_ppe p
    JOIN tbl_po_item pi ON p.po_item_id = pi.po_item_id
    WHERE p.current_condition != 'Disposed'
    ORDER BY pi.description
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all non-disposed SEP items
$sep_items = $pdo->query("
    SELECT s.sep_id, s.property_number, pi.description
    FROM tbl_sep s
    JOIN tbl_po_item pi ON s.po_item_id = pi.po_item_id
    WHERE s.current_condition != 'Disposed'
    ORDER BY pi.description
")->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Bulk QR Code Generator</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <button type="submit" form="qr-generator-form" id="generate-pdf-btn" class="btn btn-primary">
                <i class="bi bi-file-earmark-pdf-fill"></i> Generate PDF
            </button>
        </div>
    </div>

    <div class="alert alert-info">
        <i class="bi bi-info-circle-fill me-2"></i>
        Select the items for which you want to generate QR codes, then click "Generate PDF". The PDF will be formatted for printing on sticker paper.
    </div>

    <form id="qr-generator-form" action="api/generate_qr_pdf.php" method="POST" target="_blank">
        <ul class="nav nav-tabs" id="item-type-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="ppe-tab" data-bs-toggle="tab" data-bs-target="#ppe-panel" type="button" role="tab">Property, Plant & Equipment (PPE)</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="sep-tab" data-bs-toggle="tab" data-bs-target="#sep-panel" type="button" role="tab">Semi-Expendable Property (SEP)</button>
            </li>
        </ul>

        <div class="tab-content card" id="item-type-tabs-content">
                <div class="tab-pane fade show active p-3" id="ppe-panel" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th style="width: 5%;"><input type="checkbox" class="form-check-input" id="select-all-ppe"></th>
                                <th>Description</th>
                                <th>Property Number</th>
                                <th>Model</th>
                                <th>Serial</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ppe_items as $item): ?>
                                <tr>
                                    <td><input type="checkbox" class="form-check-input ppe-item-checkbox" name="items[]" value="ppe|<?= htmlspecialchars($item['ppe_id']) ?>"></td>
                                    <td><?= htmlspecialchars($item['description']) ?></td>
                                    <td><?= htmlspecialchars($item['property_number']) ?></td>
                                    <td><?= htmlspecialchars($item['model_number'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($item['serial_number'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade p-3" id="sep-panel" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th style="width: 5%;"><input type="checkbox" class="form-check-input" id="select-all-sep"></th>
                                <th>Description</th>
                                <th>Property Number</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sep_items as $item): ?>
                                <tr>
                                    <td><input type="checkbox" class="form-check-input sep-item-checkbox" name="items[]" value="sep|<?= htmlspecialchars($item['sep_id']) ?>"></td>
                                    <td><?= htmlspecialchars($item['description']) ?></td>
                                    <td><?= htmlspecialchars($item['property_number']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </form>
</main>

<script src="assets/js/qr_generator.js"></script>

<?php require_once 'includes/footer.php'; ?>