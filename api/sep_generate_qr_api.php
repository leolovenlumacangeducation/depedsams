<?php
header('Content-Type: application/json');
require_once '../db.php';

// Use an absolute path from the document root to reliably locate the vendor directory.
$basePath = $_SERVER['DOCUMENT_ROOT'] . '/samspikpok/';
require_once $basePath . 'vendor/phpqrcode/qrlib.php';

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['sep_id'], $data['property_number'])) {
        $sepId = $data['sep_id'];
        $propertyNumber = $data['property_number'];

        // --- QR Code Generation ---
        $qrCodeDir = '../assets/uploads/qr_codes/';
        if (!is_dir($qrCodeDir)) {
            mkdir($qrCodeDir, 0777, true);
        }

        $safePropertyNumber = preg_replace('/[^a-zA-Z0-9_-]/', '_', $propertyNumber);
        $filename = 'sep_qr_' . $sepId . '_' . $safePropertyNumber . '_' . time() . '.png';
        $filepath = $qrCodeDir . $filename;

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $scanUrl = $protocol . $_SERVER['HTTP_HOST'] . '/samspikpok/scan_sep.php?property_number=' . urlencode($propertyNumber);

        QRcode::png($scanUrl, $filepath, QR_ECLEVEL_L, 10, 2);

        if (file_exists($filepath)) {
            // --- Database Update ---
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("UPDATE tbl_sep SET photo = ? WHERE sep_id = ?");
                $stmt->execute([$filename, $sepId]);
                $pdo->commit();

                $response['success'] = true;
                $response['message'] = 'QR Code generated and set as item photo.';
                $response['new_photo_path'] = 'qr_codes/' . $filename;

            } catch (PDOException $e) {
                $pdo->rollBack();
                if (file_exists($filepath)) unlink($filepath);
                $response['message'] = 'Database error: ' . $e->getMessage();
            }
        } else {
            $response['message'] = 'Failed to create QR code image file.';
        }
    } else {
        $response['message'] = 'Required data (sep_id, property_number) not provided.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>
