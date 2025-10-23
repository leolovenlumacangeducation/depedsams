<?php
session_start();

/**
 * API: generate_qr_pdf.php
 * Generates a PDF (Long Bond size) containing QR stickers for selected items.
 * POST parameters:
 *  - items: array of strings in the format "type|id" (e.g., "ppe|123", "sep|456")
 *  - include_filename: optional (1 or 0). When 1, prints the stored photo/filename under the QR code.
 *
 * Notes: requires TCPDF in vendor and a valid user session.
 */

// Use an absolute path from the document root to reliably locate the vendor directory.
$basePath = $_SERVER['DOCUMENT_ROOT'] . '/samspikpok/';
require_once $basePath . 'db.php';

// Security Check
if (!isset($_SESSION['user_id'])) {
    die('Unauthorized access.');
}

$selected_items = $_POST['items'] ?? [];
$include_filename = isset($_POST['include_filename']) && ($_POST['include_filename'] == '1' || $_POST['include_filename'] === 1);
// label option: 'description', 'filename', 'both'
$label_option = $_POST['label'] ?? 'description';
// truncate length (0 = no truncation)
$truncate_len = isset($_POST['truncate_len']) ? (int)$_POST['truncate_len'] : 0;

if (empty($selected_items) || !is_array($selected_items)) {
    die('No items selected.');
}

$ppe_ids = [];
$sep_ids = [];

// 1. Collect all IDs first to avoid N+1 query problem
foreach ($selected_items as $item_identifier) {
    list($type, $id) = explode('|', $item_identifier);
    if (is_numeric($id)) {
        if ($type === 'ppe') {
            $ppe_ids[] = (int)$id;
        } elseif ($type === 'sep') {
            $sep_ids[] = (int)$id;
        }
    }
}

$items_to_print = [];

// 2. Fetch all items in more efficient queries
// Check for optional columns to avoid SQL errors on older schemas
$res = $pdo->query("SHOW COLUMNS FROM tbl_ppe LIKE 'model_number'");
$ppe_has_model = ($res && $res->fetch()) ? true : false;
$res = $pdo->query("SHOW COLUMNS FROM tbl_ppe LIKE 'serial_number'");
$ppe_has_serial = ($res && $res->fetch()) ? true : false;
$res = $pdo->query("SHOW COLUMNS FROM tbl_sep LIKE 'model_number'");
$sep_has_model = ($res && $res->fetch()) ? true : false;
$res = $pdo->query("SHOW COLUMNS FROM tbl_sep LIKE 'serial_number'");
$sep_has_serial = ($res && $res->fetch()) ? true : false;

// Build select fragments with safe aliases when columns are missing
$ppe_extra = ($ppe_has_model ? "p.model_number" : "'' AS model_number") . ", " . ($ppe_has_serial ? "p.serial_number" : "'' AS serial_number");
$sep_extra = ($sep_has_model ? "s.model_number" : "'' AS model_number") . ", " . ($sep_has_serial ? "s.serial_number" : "'' AS serial_number");

if (!empty($ppe_ids)) {
    $placeholders = implode(',', array_fill(0, count($ppe_ids), '?'));
    // include id to allow mapping back to the requested order
    $sql = "SELECT p.ppe_id AS id, pi.description, p.property_number, p.photo, {$ppe_extra} FROM tbl_ppe p JOIN tbl_po_item pi ON p.po_item_id = pi.po_item_id WHERE p.ppe_id IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($ppe_ids);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $map = [];
    foreach ($rows as $r) {
        if (isset($r['id'])) $map[(int)$r['id']] = $r;
    }
    // preserve requested order and avoid extras
    foreach ($ppe_ids as $pid) {
        $pid = (int)$pid;
        if (isset($map[$pid])) $items_to_print[] = $map[$pid];
    }
}
if (!empty($sep_ids)) {
    $placeholders = implode(',', array_fill(0, count($sep_ids), '?'));
    $sql = "SELECT s.sep_id AS id, pi.description, s.property_number, s.photo, {$sep_extra} FROM tbl_sep s JOIN tbl_po_item pi ON s.po_item_id = pi.po_item_id WHERE s.sep_id IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($sep_ids);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $map = [];
    foreach ($rows as $r) {
        if (isset($r['id'])) $map[(int)$r['id']] = $r;
    }
    foreach ($sep_ids as $sid) {
        $sid = (int)$sid;
        if (isset($map[$sid])) $items_to_print[] = $map[$sid];
    }
}

if (empty($items_to_print)) {
    die('No valid items found to print.');
}

// --- PDF Generation ---

// Load TCPDF library right before it's needed.
$require_tcpdf = $basePath . 'vendor/tecnickcom/tcpdf/tcpdf.php';
if (!file_exists($require_tcpdf)) {
    die('TCPDF library not found.');
}
require_once $require_tcpdf;

// Use Long Bond Paper size (8.5 x 13 inches = 215.9 x 330.2 mm)
$long_bond = array(215.9, 330.2);
$pdf = new TCPDF('L', 'mm', $long_bond, true, 'UTF-8', false); // Landscape
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('SAMS');
$pdf->SetTitle('Item QR Codes');
$pdf->SetSubject('QR Codes for Asset Tagging');

$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(5, 10, 5); // Set smaller horizontal margins
$pdf->SetAutoPageBreak(TRUE, 10);
$pdf->AddPage();

// --- Sticker Layout Settings (auto-fit up to 5 columns) ---
// We'll compute sticker width so the layout auto-fits up to $max_cols per row.
$max_cols = 5;
$default_sticker_width = 48; // fallback
$sticker_height = 38; // mm (slightly taller to accommodate QR + text)
$margin_x = 6;      // mm horizontal gap between stickers
$margin_y = 6;      // mm vertical gap between stickers

// printable width considering left/right margins (we previously set margins to 5mm)
$page_width = $pdf->getPageWidth();
$left_margin = 5; $right_margin = 5;
$printable_width = $page_width - $left_margin - $right_margin;

// determine number of columns (start with max_cols, reduce if default width doesn't fit)
$cols = $max_cols;
if (($default_sticker_width * $cols) + ($margin_x * ($cols - 1)) > $printable_width) {
    // compute how many default-width stickers fit
    $cols = (int) floor(($printable_width + $margin_x) / ($default_sticker_width + $margin_x));
    if ($cols < 1) $cols = 1;
    if ($cols > $max_cols) $cols = $max_cols;
}

// compute sticker width to evenly distribute across printable area
$sticker_width = ($printable_width - ($margin_x * ($cols - 1))) / $cols;

// make QR size proportional but not exceeding sticker height minus text area
$qr_code_size = min($sticker_width * 0.8, $sticker_height - 10);

$current_x = $pdf->GetX();
$current_y = $pdf->GetY();
$col_count = 0;

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$base_url = $protocol . $_SERVER['HTTP_HOST'] . "/samspikpok/scan.php";

foreach ($items_to_print as $item) {
    if ($col_count >= $cols) {
        $current_y += $sticker_height + $margin_y;
        // reset to left margin
        $current_x = $left_margin;
        $col_count = 0;
    }

    // Check if we need a new page
    if ($current_y + $sticker_height > $pdf->getPageHeight() - $pdf->getBreakMargin()) {
        $pdf->AddPage();
        $current_x = $pdf->GetX();
        $col_count = 0;
        $current_y = $pdf->GetY();
    }

    // Draw a border for the sticker
    $pdf->Rect($current_x, $current_y, $sticker_width, $sticker_height);

    // --- Draw QR Code ---
    $qr_content = $base_url . "?property_number=" . urlencode($item['property_number']);
    $qr_x = $current_x + (($sticker_width - $qr_code_size) / 2);
    // vertically center QR in the top portion, leaving room for description and property number
    $qr_y = $current_y + 4; // small top padding
    $pdf->write2DBarcode($qr_content, 'QRCODE,H', $qr_x, $qr_y, $qr_code_size, $qr_code_size);

    // --- Draw Text ---
    // Decide which label(s) to print based on $label_option
    $pdf->SetFont('helvetica', '', 7);
    $text_y_pos = $qr_y + $qr_code_size + 2; // 2mm gap below QR
    $available_text_height = $sticker_height - ($text_y_pos - $current_y) - 8; // reserve 8mm for property number
    if ($available_text_height < 3) $available_text_height = 3;

    $lines = [];
    if ($label_option === 'description' || $label_option === 'both') {
        $lines[] = $item['description'] ?? '';
    }
    if ($label_option === 'details') {
        // Include description, model number, and serial number if present
        if (!empty($item['description'])) $lines[] = $item['description'];
        if (!empty($item['model_number'])) $lines[] = $item['model_number'];
        if (!empty($item['serial_number'])) $lines[] = $item['serial_number'];
    }
    if ($label_option === 'filename' || $label_option === 'both') {
        if (!empty($item['photo'])) {
            $lines[] = $item['photo'];
        }
    }

    // If no label lines (e.g., filename requested but none exist), fall back to description or property number
    if (empty($lines)) {
        $lines[] = $item['description'] ?? $item['property_number'];
    }

    // Apply truncation if requested
    if ($truncate_len > 0) {
        foreach ($lines as &$ln) {
            if (mb_strlen($ln) > $truncate_len) {
                $ln = mb_substr($ln, 0, $truncate_len) . '...';
            }
        }
        unset($ln);
    }

    // Render label lines, center-aligned; if multiple lines, join with newline
    $label_text = implode("\n", array_map('htmlspecialchars', $lines));
    $pdf->MultiCell($sticker_width, 3, $label_text, 0, 'C', false, 1, $current_x, $text_y_pos, true, 0, false, true, $available_text_height, 'M', false);

    // Property Number at the bottom
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetXY($current_x, $current_y + $sticker_height - 6);
    $pdf->Cell($sticker_width, 6, htmlspecialchars($item['property_number']), 0, 0, 'C');

    // Move to the next sticker position
    $current_x += $sticker_width + $margin_x;
    $col_count++;
}

$pdf->Output('SAMS_QR_Codes.pdf', 'I');