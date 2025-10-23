<?php
// This flag allows the header and sidebar to be displayed without requiring a login.
$is_public_page = true;
require_once 'db.php';

require_once 'includes/header.php';
require_once 'includes/sidebar.php';

$property_number = $_GET['property_number'] ?? null;
$item = null;
$item_type = 'Not Found';

if ($property_number) {
    // Sanitize the input
    $property_number = htmlspecialchars(trim($property_number));

    // --- Search for the item in the SEP table ---
    $stmt = $pdo->prepare("
        SELECT 
            pi.description, s.property_number as stock_number, s.serial_number, s.photo, pi.unit_cost, s.date_acquired as date_received,
            s.current_condition, u.full_name as assigned_to, NULL as unit_name, NULL as current_stock,
            'SEP' as item_type
        FROM tbl_sep s
        JOIN tbl_po_item pi ON s.po_item_id = pi.po_item_id
        LEFT JOIN tbl_user u ON s.assigned_to_user_id = u.user_id
        WHERE s.property_number = ?
    ");
    $stmt->execute([$property_number]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
}

?>

<style>
    /* Custom styles for the public scan page */
    body {
        background-color: #f8f9fa; /* Light grey background */
    }
    .scan-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>

<main class="col-12 col-md-10 col-lg-8 mx-auto p-3 scan-container">
    <div class="container-fluid">
        <?php if ($item): ?>
            <?php
            // Determine the correct photo path. QR codes are stored in a different folder.
            // Check for any file containing '_qr_' which is common to all our generated QR codes.
            if (!empty($item['photo']) && strpos($item['photo'], '_qr_') !== false) {
                $photo_path = "assets/uploads/qr_codes/" . $item['photo'];
            } else {
                // It's a regular photo, determine folder by item type
                $photo_folder = 'consumables'; // Default folder
                $default_photo = 'consumable_default.png'; // Default photo

                if ($item['item_type'] === 'PPE') {
                    $photo_folder = 'ppe';
                    $default_photo = 'ppe_default.png';
                } elseif ($item['item_type'] === 'SEP') {
                    $photo_folder = 'sep';
                    $default_photo = 'sep_default.png';
                }
                $photo_path = "assets/uploads/{$photo_folder}/" . ($item['photo'] ?: $default_photo);
            }
            ?>
            <div class="card shadow-lg border-0">
                <div class="row g-0">
                    <div class="col-md-5 d-flex align-items-center justify-content-center bg-light p-3">
                        <img src="<?= $photo_path ?>" class="img-fluid rounded" alt="Item Photo" style="max-height: 300px; object-fit: contain;">
                    </div>
                    <div class="col-md-7">
                        <div class="card-body">
                            <h4 class="card-title"><?= htmlspecialchars($item['description']) ?></h4>
                            <p class="card-text">
                                <span class="badge bg-primary fs-6"><?= htmlspecialchars($item['item_type']) ?></span>
                            </p>
                            <hr>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Stock/Property No.
                                    <span class="fw-bold"><?= htmlspecialchars($item['stock_number']) ?></span>
                                </li>
                                <?php if (isset($item['serial_number'])): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Serial Number
                                    <span class="fw-bold"><?= htmlspecialchars($item['serial_number'] ?: 'N/A') ?></span>
                                </li>
                                <?php endif; ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Unit Cost
                                    <span class="fw-bold"><?= number_format($item['unit_cost'], 2) ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Date Acquired
                                    <span class="fw-bold"><?= date('M d, Y', strtotime($item['date_received'])) ?></span>
                                </li>
                                <?php if ($item['item_type'] === 'Consumable'): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Stock on Hand
                                    <span class="badge bg-success rounded-pill fs-6"><?= htmlspecialchars($item['current_stock']) . ' ' . htmlspecialchars($item['unit_name']) ?></span>
                                </li>
                                <?php else: ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Current Condition
                                    <span class="fw-bold"><?= htmlspecialchars($item['current_condition']) ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Assigned To
                                    <span class="badge bg-info text-dark rounded-pill"><?= htmlspecialchars($item['assigned_to'] ?: 'Unassigned') ?></span>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif ($property_number): ?>
            <div class="alert alert-danger text-center shadow" role="alert">
                <h4 class="alert-heading">Item Not Found!</h4>
                <p>No item could be found with the Property Number: <strong><?= htmlspecialchars($property_number) ?></strong>.</p>
                <hr>
                <p class="mb-0">Please check the number and try again, or ensure the item has been registered in the system.</p>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center shadow" role="alert">
                <h4 class="alert-heading">Ready to Scan</h4>
                <p>Scan a valid SEP QR code to display its details here.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>