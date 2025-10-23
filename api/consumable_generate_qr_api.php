<?php
header('Content-Type: application/json');
require_once '../db.php';

// Use an absolute path from the document root to reliably locate the vendor directory.
$basePath = $_SERVER['DOCUMENT_ROOT'] . '/samspikpok/';
require_once $basePath . 'vendor/phpqrcode/qrlib.php';

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['consumable_id'], $data['stock_number'])) {
        $consumableId = $data['consumable_id'];
        $stockNumber = $data['stock_number'];

        // --- QR Code Generation ---
        $qrCodeDir = '../assets/uploads/qr_codes/';
        if (!is_dir($qrCodeDir)) {
            mkdir($qrCodeDir, 0777, true);
        }

        // Sanitize stock number for filename and generate a unique name
        $safeStockNumber = preg_replace('/[^a-zA-Z0-9_-]/', '_', $stockNumber);
        $filename = 'consumable_qr_' . $consumableId . '_' . $safeStockNumber . '_' . time() . '.png';
        $filepath = $qrCodeDir . $filename;

        // --- Create the URL for the QR code ---
        // This makes the QR code scannable and actionable.
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $scanUrl = $protocol . $_SERVER['HTTP_HOST'] . '/samspikpok/scan.php?stock_number=' . urlencode($stockNumber);

        // Generate the QR code image
        QRcode::png($scanUrl, $filepath, QR_ECLEVEL_L, 10, 2);

        if (file_exists($filepath)) {
            // --- Database Update ---
            try {
                $pdo->beginTransaction();

                // Update the photo for the consumable item
                $stmt = $pdo->prepare("UPDATE tbl_consumable SET photo = ? WHERE consumable_id = ?");
                $stmt->execute([$filename, $consumableId]);

                $pdo->commit();

                $response['success'] = true;
                $response['message'] = 'QR Code generated and set as item photo.';
                $response['new_photo_path'] = 'qr_codes/' . $filename; // Send back the relative path

            } catch (PDOException $e) {
                $pdo->rollBack();
                // If DB fails, delete the generated file
                if (file_exists($filepath)) {
                    unlink($filepath);
                }
                $response['message'] = 'Database error: ' . $e->getMessage();
            }
        } else {
            $response['message'] = 'Failed to create QR code image file.';
        }
    } else {
        $response['message'] = 'Required data (consumable_id, stock_number) not provided.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);

?>