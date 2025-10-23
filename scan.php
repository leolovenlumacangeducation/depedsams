<?php
// DEBUG: Show all errors for local development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// This flag allows the header to be displayed without requiring a login.
$is_public_page = true;
require_once 'db.php';
require_once 'includes/header.php';

$stock_number = $_GET['stock_number'] ?? $_GET['property_number'] ?? null;
$item = null;

if ($stock_number) {
    // Sanitize the input
    $stock_number = htmlspecialchars(trim($stock_number));

    // Use a single UNION ALL query to search across all relevant tables efficiently.
    $stmt = $pdo->prepare("
        (SELECT 
            pi.description, c.stock_number, NULL as serial_number, c.photo, c.unit_cost, c.date_received as date_received, NULL as current_condition, NULL as assigned_to, u.unit_name, c.current_stock, 'Consumable' as item_type
        FROM tbl_consumable c
        JOIN tbl_unit u ON c.unit_id = u.unit_id
        JOIN tbl_po_item pi ON c.po_item_id = pi.po_item_id
        WHERE c.stock_number = ?)
        
        UNION ALL
        
        (SELECT 
            pi.description, p.property_number as stock_number, p.serial_number, p.photo, pi.unit_cost, p.date_acquired as date_received, p.current_condition, u.full_name as assigned_to, NULL as unit_name, NULL as current_stock, 'PPE' as item_type
        FROM tbl_ppe p
        JOIN tbl_po_item pi ON p.po_item_id = pi.po_item_id
        LEFT JOIN tbl_user u ON p.assigned_to_user_id = u.user_id
        WHERE p.property_number = ?)
        
        UNION ALL
        
        (SELECT 
            pi.description, s.property_number as stock_number, s.serial_number, s.photo, pi.unit_cost, s.date_acquired as date_received, s.current_condition, u.full_name as assigned_to, NULL as unit_name, NULL as current_stock, 'SEP' as item_type
        FROM tbl_sep s
        JOIN tbl_po_item pi ON s.po_item_id = pi.po_item_id
        LEFT JOIN tbl_user u ON s.assigned_to_user_id = u.user_id
        WHERE s.property_number = ?)
        LIMIT 1
    ");
    $stmt->execute([$stock_number, $stock_number, $stock_number]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<style>
    body {
        background: linear-gradient(135deg, #f8fafc 0%, #e0e7ef 100%);
        min-height: 100vh;
    }
    .scan-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .scan-card {
        box-shadow: 0 4px 32px rgba(0,0,0,0.08), 0 1.5px 6px rgba(0,0,0,0.04);
        border-radius: 1.25rem;
        background: #fff;
        overflow: hidden;
        max-width: 700px;
        width: 100%;
        margin: 2rem auto;
        border: none;
    }
    .scan-card .card-img {
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 320px;
        border-top-left-radius: 1.25rem;
        border-bottom-left-radius: 1.25rem;
    }
    .scan-card .img-fluid {
        max-height: 260px;
        object-fit: contain;
    }
    .scan-card .card-body {
        padding: 2rem 2rem 1.5rem 2rem;
    }
    .scan-card .card-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: #1a202c;
    }
    .scan-card .badge {
        font-size: 1rem;
        padding: 0.5em 1em;
    }
    .scan-card .list-group-item {
        font-size: 1.08rem;
        border: none;
        background: transparent;
        padding-left: 0;
        padding-right: 0;
    }
    .scan-card .fw-bold {
        color: #2563eb;
    }
    .scan-card .alert {
        margin-bottom: 0;
        border-radius: 1.25rem;
    }
    @media (max-width: 767px) {
        .scan-card .card-body {
            padding: 1.25rem 1rem 1rem 1rem;
        }
        .scan-card {
            border-radius: 0.75rem;
        }
    }
</style>

<main class="scan-container">
    <div class="scan-card">
        <div class="p-3">
            <div class="d-flex flex-column flex-sm-row justify-content-between gap-2 mb-3">
                <div class="d-flex gap-2">
                    <button id="start-camera" class="btn btn-success btn-sm px-3">Start Camera</button>
                    <button id="stop-camera" class="btn btn-outline-secondary btn-sm px-3" disabled>Stop</button>
                    <label class="btn btn-primary btn-sm mb-0 px-3">
                        Upload Image <input type="file" id="qr-image-input" accept="image/*" capture="environment" hidden>
                    </label>
                </div>
                <div class="text-sm-end mt-2 mt-sm-0">
                    <small id="scan-status" class="text-muted">Ready to scan. Use camera or upload an image.</small>
                </div>
            </div>

            <div class="mb-3" id="camera-area" style="display:none;">
                <div class="ratio ratio-16x9 bg-dark rounded overflow-hidden">
                    <video id="qr-video" autoplay muted playsinline style="width:100%;height:100%;object-fit:cover;"></video>
                </div>
            </div>

            <div class="alert alert-info small">
                <strong>How to scan</strong>
                <ul class="mb-0 mt-1">
                    <li>On mobile: tap <em>Start Camera</em> or <em>Upload Image</em> and choose <em>Camera</em> to take a photo.</li>
                    <li>On desktop: click <em>Start Camera</em> (if supported) or use <em>Upload Image</em> to select a saved QR image.</li>
                    <li>When a QR code is recognized, you'll be redirected to the item details automatically.</li>
                </ul>
            </div>

            <canvas id="qr-canvas" style="display:none;"></canvas>
        </div>
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
            <div class="row g-0">
                <div class="col-md-5 card-img">
                    <img src="<?= $photo_path ?>" class="img-fluid rounded" alt="Item Photo">
                </div>
                <div class="col-md-7">
                    <div class="card-body">
                        <h4 class="card-title mb-2"><?= htmlspecialchars($item['description']) ?></h4>
                        <span class="badge bg-primary mb-3"><?= htmlspecialchars($item['item_type']) ?></span>
                        <ul class="list-group list-group-flush mb-3">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Stock/Property No.
                                <span class="fw-bold"><?= htmlspecialchars($item['stock_number'] ?? '') ?></span>
                            </li>
                            <?php if (isset($item['serial_number'])): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Serial Number
                                <span class="fw-bold"><?= htmlspecialchars($item['serial_number'] ?: 'N/A') ?></span>
                            </li>
                            <?php endif; ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Unit Cost
                                <span class="fw-bold">₱<?= is_numeric($item['unit_cost']) ? number_format($item['unit_cost'], 2) : 'N/A' ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Date Acquired
                                <span class="fw-bold"><?= !empty($item['date_received']) ? date('M d, Y', strtotime($item['date_received'])) : 'N/A' ?></span>
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
        <?php elseif ($stock_number): ?>
            <div class="alert alert-danger text-center p-4">
                <h4 class="alert-heading">Item Not Found!</h4>
                <p>No item could be found with the Stock/Property Number: <strong><?= htmlspecialchars($stock_number) ?></strong>.</p>
                <hr>
                <p class="mb-0">Please check the number and try again, or ensure the item has been registered in the system.</p>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center p-4">
                <h4 class="alert-heading">Ready to Scan</h4>
                <p>Scan a valid item QR code to display its details here.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>
<script>
// QR scan helpers
(function(){
    const startBtn = document.getElementById('start-camera');
    const stopBtn = document.getElementById('stop-camera');
    const video = document.getElementById('qr-video');
    const canvas = document.getElementById('qr-canvas');
    const ctx = canvas.getContext('2d');
    const imgInput = document.getElementById('qr-image-input');
    const status = document.getElementById('scan-status');

    let stream = null;
    let scanning = false;
    let detector = null;

    async function ensureJsQR() {
        if (window.jsQR) return;
        // Load jsQR from CDN
        await new Promise((resolve, reject) => {
            const s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js';
            s.onload = resolve;
            s.onerror = reject;
            document.head.appendChild(s);
        });
    }

    async function initDetector() {
        if ('BarcodeDetector' in window) {
            try {
                const formats = await BarcodeDetector.getSupportedFormats();
                detector = new BarcodeDetector({formats: formats.includes('qr_code') ? ['qr_code'] : formats});
            } catch (e) {
                detector = null;
            }
        }
    }

    function parseAndRedirect(decodedText) {
        if (!decodedText) return;
        // If the QR contains a full URL to scan.php, open it directly
        try {
            const url = new URL(decodedText, window.location.href);
            if (url.pathname.endsWith('/scan.php') || url.searchParams.has('property_number') || url.searchParams.has('stock_number')) {
                window.location.href = url.href;
                return;
            }
        } catch (e) {
            // not a URL -- fallthrough
        }

        // Heuristic: If it looks like a property number (contains letters and digits) prefer property_number
        const candidate = decodedText.trim();
        // Choose param name based on common prefixes (PN/PNO or numeric for stock)
        let paramName = 'stock_number';
        if (/^[A-Za-z].*\d/.test(candidate) || /^PN|PNO|PROP|PROPERTY/i.test(candidate)) {
            paramName = 'property_number';
        }
        const q = encodeURIComponent(candidate);
        window.location.href = `scan.php?${paramName}=${q}`;
    }

    async function scanImageBitmap(imageBitmap) {
        canvas.width = imageBitmap.width;
        canvas.height = imageBitmap.height;
        ctx.drawImage(imageBitmap, 0, 0);
        const imageData = ctx.getImageData(0,0,canvas.width,canvas.height);
        // Try native detector first
        if (detector) {
            try {
                const barcodes = await detector.detect(canvas);
                if (barcodes && barcodes.length) {
                    parseAndRedirect(barcodes[0].rawValue || barcodes[0].rawData || barcodes[0].displayValue);
                    return true;
                }
            } catch (e) {
                // ignore and fallback
            }
        }

        // Fallback to jsQR
        await ensureJsQR();
        const code = jsQR(imageData.data, imageData.width, imageData.height);
        if (code && code.data) {
            parseAndRedirect(code.data);
            return true;
        }
        return false;
    }

    async function captureAndScan() {
        if (!stream) return;
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        const imageData = ctx.getImageData(0,0,canvas.width,canvas.height);

        if (detector) {
            try {
                const barcodes = await detector.detect(canvas);
                if (barcodes && barcodes.length) {
                    parseAndRedirect(barcodes[0].rawValue || barcodes[0].rawData || barcodes[0].displayValue);
                    return true;
                }
            } catch (e) {
                // continue to fallback
            }
        }

        await ensureJsQR();
        const code = jsQR(imageData.data, imageData.width, imageData.height);
        if (code && code.data) {
            parseAndRedirect(code.data);
            return true;
        }
        return false;
    }

    async function startCamera() {
        if (scanning) return;
        try {
            stream = await navigator.mediaDevices.getUserMedia({video: {facingMode: 'environment'}});
            video.srcObject = stream;
            document.getElementById('camera-area').style.display = 'block';
            startBtn.disabled = true;
            stopBtn.disabled = false;
            status.textContent = 'Scanning...';
            scanning = true;
            await initDetector();
            requestAnimationFrame(tick);
        } catch (e) {
            console.error('Camera start failed', e);
            status.textContent = 'Camera not available';
        }
    }

    function stopCamera() {
        if (!scanning) return;
        scanning = false;
        startBtn.disabled = false;
        stopBtn.disabled = true;
        status.textContent = 'Stopped';
        if (stream) {
            stream.getTracks().forEach(t => t.stop());
            stream = null;
        }
        video.srcObject = null;
        document.getElementById('camera-area').style.display = 'none';
    }

    async function tick() {
        if (!scanning) return;
        if (video.readyState === video.HAVE_ENOUGH_DATA) {
            const found = await captureAndScan();
            if (found) {
                stopCamera();
                return;
            }
        }
        requestAnimationFrame(tick);
    }

    startBtn.addEventListener('click', startCamera);
    stopBtn.addEventListener('click', stopCamera);

    imgInput.addEventListener('change', async (ev) => {
        const file = ev.target.files && ev.target.files[0];
        if (!file) return;
        status.textContent = 'Decoding image...';
        try {
            const img = await createImageBitmap(file);
            const ok = await scanImageBitmap(img);
            if (!ok) {
                alert('No QR code detected in the selected image.');
                status.textContent = 'No QR found';
            }
        } catch (e) {
            console.error(e);
            alert('Failed to process image.');
            status.textContent = 'Error';
        }
        // Clear input so same file can be selected again
        imgInput.value = '';
    });

})();
</script>