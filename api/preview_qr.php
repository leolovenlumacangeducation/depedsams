<?php
// Lightweight preview endpoint that renders a sticker as an inline SVG so the browser
// preview matches the PDF generator's QR rendering (uses TCPDF2DBarcode).
// Query params:
//  - type (required: ppe|sep)
//  - id (required: int)
//  - label (description|filename|both) optional, default=description
//  - truncate_len (int) optional, default=0 (no truncation)

// No session check here; this endpoint is for admins and uses non-sensitive GET params.
header('Content-Type: image/svg+xml; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate'); // No caching

$basePath = $_SERVER['DOCUMENT_ROOT'] . '/samspikpok/';
require_once $basePath . 'db.php';

$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? 0;
$label_option = $_GET['label'] ?? 'description';
$truncate_len = isset($_GET['truncate_len']) ? (int)$_GET['truncate_len'] : 0;

if (empty($type) || !in_array($type, ['ppe', 'sep']) || !is_numeric($id) || $id <= 0) {
    http_response_code(400);
    echo '<svg xmlns="http://www.w3.org/2000/svg"><text x="4" y="14">Invalid item specified.</text></svg>';
    exit;
}

// Fetch item details from the database to ensure data is fresh
$item = null;
try {
    // Check for optional columns to avoid SQL errors on older schemas
    $res = $pdo->query("SHOW COLUMNS FROM tbl_ppe LIKE 'model_number'");
    $ppe_has_model = ($res && $res->fetch()) ? true : false;
    $res = $pdo->query("SHOW COLUMNS FROM tbl_ppe LIKE 'serial_number'");
    $ppe_has_serial = ($res && $res->fetch()) ? true : false;
    $res = $pdo->query("SHOW COLUMNS FROM tbl_sep LIKE 'model_number'");
    $sep_has_model = ($res && $res->fetch()) ? true : false;
    $res = $pdo->query("SHOW COLUMNS FROM tbl_sep LIKE 'serial_number'");
    $sep_has_serial = ($res && $res->fetch()) ? true : false;

    $ppe_extra = ($ppe_has_model ? "p.model_number" : "'' AS model_number") . ", " . ($ppe_has_serial ? "p.serial_number" : "'' AS serial_number");
    $sep_extra = ($sep_has_model ? "s.model_number" : "'' AS model_number") . ", " . ($sep_has_serial ? "s.serial_number" : "'' AS serial_number");

    if ($type === 'ppe') {
        $stmt = $pdo->prepare("SELECT pi.description, p.property_number, p.photo, {$ppe_extra} FROM tbl_ppe p LEFT JOIN tbl_po_item pi ON p.po_item_id = pi.po_item_id WHERE p.ppe_id = ?");
    } else { // sep
        $stmt = $pdo->prepare("SELECT pi.description, s.property_number, s.photo, {$sep_extra} FROM tbl_sep s LEFT JOIN tbl_po_item pi ON s.po_item_id = pi.po_item_id WHERE s.sep_id = ?");
    }
    $stmt->execute([$id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    http_response_code(500);
    echo '<svg xmlns="http://www.w3.org/2000/svg"><text x="4" y="14">Database error.</text></svg>';
    exit;
}

if (!$item) {
    http_response_code(404);
    echo '<svg xmlns="http://www.w3.org/2000/svg"><text x="4" y="14">Item not found.</text></svg>';
    exit;
}

$property_number = $item['property_number'] ?? '';
$description = $item['description'] ?? '';
$filename = $item['photo'] ?? '';
$model_number = $item['model_number'] ?? '';
$serial_number = $item['serial_number'] ?? '';

// Build label lines similar to the PDF generator
$lines = array();
if ($label_option === 'description' || $label_option === 'both') {
    if ($description !== '') $lines[] = $description;
}
if ($label_option === 'details') {
    if ($description !== '') $lines[] = $description;
    if ($model_number !== '') $lines[] = $model_number;
    if ($serial_number !== '') $lines[] = $serial_number;
}
if ($label_option === 'filename' || $label_option === 'both') {
    if ($filename !== '') $lines[] = $filename;
}
if (empty($lines)) {
    // fallback to description or property number
    if ($description !== '') $lines[] = $description;
    else $lines[] = $property_number;
}

if ($truncate_len > 0) {
    foreach ($lines as &$ln) {
        if (mb_strlen($ln) > $truncate_len) {
            $ln = mb_substr($ln, 0, $truncate_len) . '...';
        }
    }
    unset($ln);
}

// Generate QR SVG using TCPDF's QR generator to match PDF output
$tcpdf_file = $basePath . 'vendor/tecnickcom/tcpdf/tcpdf_barcodes_2d.php';
if (!file_exists($tcpdf_file)) {
    http_response_code(500);
    // fallback: return a simple SVG placeholder
    $label_esc = htmlspecialchars(implode(' / ', $lines), ENT_XML1);
    $pn_esc = htmlspecialchars($property_number, ENT_XML1);
    echo "<svg xmlns='http://www.w3.org/2000/svg' width='220' height='140'>";
    echo "<rect x='0' y='0' width='220' height='140' fill='#fff' stroke='#ddd'/>";
    echo "<text x='110' y='70' font-size='12' text-anchor='middle' fill='#333'>QR Preview (TCPDF missing)</text>";
    echo "<text x='110' y='90' font-size='11' text-anchor='middle' fill='#000'>{$label_esc}</text>";
    echo "<text x='110' y='116' font-weight='bold' font-size='13' text-anchor='middle' fill='#000'>{$pn_esc}</text>";
    echo "</svg>";
    exit;
}

require_once $tcpdf_file;

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$base_url = $protocol . $_SERVER['HTTP_HOST'] . "/samspikpok/scan.php";
$qr_content = $base_url . "?property_number=" . urlencode($property_number);

$barcode = new TCPDF2DBarcode($qr_content, 'QRCODE,H');
// create the raw QR SVG with a small module size; we'll scale in the wrapper
$module_size = 3; // pixels per module in the raw svg
$raw_qr_svg = $barcode->getBarcodeSVGcode($module_size, $module_size, 'black');

// Extract inner content and original dimensions
$inner = $raw_qr_svg;
$orig_width = 0; $orig_height = 0;
if (preg_match('#<svg[^>]*width="([0-9\.]+)"[^>]*height="([0-9\.]+)"#i', $raw_qr_svg, $m)) {
    $orig_width = (float)$m[1];
    $orig_height = (float)$m[2];
}
// remove XML prolog and outer svg tags to keep only inner elements
$inner = preg_replace('#<\?xml.*?\?>#s', '', $inner);
$inner = preg_replace('#<!DOCTYPE[^>]*>#s', '', $inner);
$inner = preg_replace('#^\s*<svg[^>]*>#i', '', $inner);
$inner = preg_replace('#</svg>\s*$#i', '', $inner);

// Prepare wrapper sizes (match the modal mock: 220 x 140)
$W = 220; $H = 140;
$qr_display_size = 88; // px square for QR inside the mock
$qr_x = intval(($W - $qr_display_size) / 2);
$qr_y = 8;

$label_text = htmlspecialchars(implode('\n', $lines), ENT_XML1);
$pn_text = htmlspecialchars($property_number, ENT_XML1);

// Compute scale if we have orig width
$scale = 1.0;
if ($orig_width > 0) {
    $scale = $qr_display_size / $orig_width;
}

// Build SVG output
echo "<svg xmlns='http://www.w3.org/2000/svg' width='" . $W . "' height='" . $H . "' viewBox='0 0 " . $W . " " . $H . "'>";
echo "<rect x='0' y='0' width='".$W."' height='".$H."' fill='#fff' stroke='#ddd'/>";
// Place QR by translating and scaling inner content
echo "<g transform='translate(" . $qr_x . "," . $qr_y . ") scale(" . $scale . ")'>";
// inner svg may contain a <g id="elements"> etc. We can safely output it
echo $inner;
echo "</g>";

// Render label lines (centered). Split by newline and render each line
$label_lines = explode('\n', $label_text);
$start_y = $qr_y + $qr_display_size + 14; // below QR
$font_size = 11;
$i = 0;
foreach ($label_lines as $ln) {
    $ypos = $start_y + ($i * ($font_size + 2));
    echo "<text x='" . ($W/2) . "' y='" . $ypos . "' font-size='".$font_size."' text-anchor='middle' fill='#333'>" . $ln . "</text>";
    $i++;
    if ($i >= 2) break; // avoid too many lines
}

// Property number at bottom bold
$pn_y = $H - 14;
echo "<text x='" . ($W/2) . "' y='" . $pn_y . "' font-size='13' font-weight='bold' text-anchor='middle' fill='#000'>" . $pn_text . "</text>";

echo "</svg>";

exit;

?>
