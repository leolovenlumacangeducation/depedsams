<?php
require_once '../db.php';
require_once '../vendor/autoload.php';

// Security check
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die('Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['ris_number'])) {
    http_response_code(400);
    die('Bad Request');
}

$ris_number = $_POST['ris_number'];

try {
    // Fetch RIS data
    $stmt = $pdo->prepare("
        SELECT 
            i.issuance_id,
            i.ris_number,
            i.issued_to,
            i.date_issued,
            i.issued_by_user_id,
            u.full_name as issued_by,
            ii.issuance_item_id,
            ii.quantity_issued,
            c.description,
            c.stock_number,
            c.unit_cost,
            un.unit_name
        FROM tbl_issuance i
        JOIN tbl_issuance_item ii ON i.issuance_id = ii.issuance_id
        JOIN tbl_consumable c ON ii.consumable_id = c.consumable_id
        JOIN tbl_unit un ON c.unit_id = un.unit_id
        LEFT JOIN tbl_user u ON i.issued_by_user_id = u.user_id
        WHERE i.ris_number = ?
        ORDER BY ii.issuance_item_id
    ");
    $stmt->execute([$ris_number]);
    $items = $stmt->fetchAll();

    if (empty($items)) {
        http_response_code(404);
        die('RIS not found');
    }

    // Get school info
    $school = $pdo->query("SELECT * FROM tbl_school LIMIT 1")->fetch();

    // Get officer assignments
    $officers_stmt = $pdo->query("
        SELECT o.officer_type, u.full_name, u.position_id, p.position_name
        FROM tbl_officers o
        JOIN tbl_user u ON o.user_id = u.user_id
        JOIN tbl_position p ON u.position_id = p.position_id
    ");
    $officers = $officers_stmt->fetchAll(PDO::FETCH_ASSOC);
    $officer_map = [];
    foreach ($officers as $officer) {
        $officer_map[$officer['officer_type']] = $officer;
    }

    // Create PDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator('SAMS');
    $pdf->SetAuthor($school['school_name']);
    $pdf->SetTitle('RIS ' . $ris_number);

    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // Set margins
    $pdf->SetMargins(15, 15, 15);

    // Add a page
    $pdf->AddPage();

    // Set font
    $pdf->SetFont('helvetica', '', 10);

    // Header
    $pdf->Cell(0, 5, 'REQUISITION AND ISSUE SLIP', 0, 1, 'C');
    $pdf->Ln(5);

    // Entity Name and Fund Cluster
    $pdf->SetFont('helvetica', '', 8);
    $pdf->Cell(95, 5, 'Entity Name: ' . $school['school_name'], 'LTR', 0, 'L');
    $pdf->Cell(95, 5, 'Fund Cluster: Regular Agency Fund', 'LTR', 1, 'L');
    
    // Division and RIS No
    $pdf->Cell(95, 5, 'Division: ' . $school['division_name'], 'LR', 0, 'L');
    $pdf->Cell(95, 5, 'RIS No.: ' . $ris_number, 'LR', 1, 'L');

    // Office/Section and Date
    $pdf->Cell(95, 5, 'Office/Section: ' . ($items[0]['issued_to'] ?? ''), 'LBR', 0, 'L');
    $pdf->Cell(95, 5, 'Date: ' . date('F j, Y', strtotime($items[0]['date_issued'])), 'LBR', 1, 'L');
    
    $pdf->Ln(5);

    // Table header
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->Cell(10, 5, 'Item', 1, 0, 'C');
    $pdf->Cell(15, 5, 'Qty', 1, 0, 'C');
    $pdf->Cell(15, 5, 'Unit', 1, 0, 'C');
    $pdf->Cell(85, 5, 'Description', 1, 0, 'C');
    $pdf->Cell(30, 5, 'Stock No.', 1, 0, 'C');
    $pdf->Cell(35, 5, 'Amount', 1, 1, 'C');

    // Reset font
    $pdf->SetFont('helvetica', '', 8);

    // Table content
    $total = 0;
    foreach ($items as $index => $item) {
        $amount = $item['quantity_issued'] * $item['unit_cost'];
        $total += $amount;

        $pdf->Cell(10, 5, $index + 1, 1, 0, 'C');
        $pdf->Cell(15, 5, $item['quantity_issued'], 1, 0, 'C');
        $pdf->Cell(15, 5, $item['unit_name'], 1, 0, 'C');
        $pdf->Cell(85, 5, $item['description'], 1, 0, 'L');
        $pdf->Cell(30, 5, $item['stock_number'], 1, 0, 'C');
        $pdf->Cell(35, 5, number_format($amount, 2), 1, 1, 'R');
    }

    // Total row
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->Cell(155, 5, 'Total Amount:', 1, 0, 'R');
    $pdf->Cell(35, 5, number_format($total, 2), 1, 1, 'R');

    $pdf->Ln(10);

    // Signatures section
    $pdf->SetFont('helvetica', '', 8);
    
    // Requested by
    $pdf->Cell(95, 5, 'Requested by:', 'LTR', 0, 'L');
    $pdf->Cell(95, 5, 'Approved by:', 'LTR', 1, 'L');

    $pdf->Cell(95, 15, '', 'LR', 0, 'C');
    $pdf->Cell(95, 15, '', 'LR', 1, 'C');

    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->Cell(95, 5, strtoupper($items[0]['issued_to']), 'LR', 0, 'C');
    $pdf->Cell(95, 5, strtoupper($officer_map['Approving Officer']['full_name'] ?? ''), 'LR', 1, 'C');

    $pdf->SetFont('helvetica', '', 8);
    $pdf->Cell(95, 5, 'Signature over Printed Name', 'LR', 0, 'C');
    $pdf->Cell(95, 5, 'Signature over Printed Name', 'LR', 1, 'C');

    $pdf->Cell(95, 5, 'Position: N/A', 'LBR', 0, 'C');
    $pdf->Cell(95, 5, 'Position: ' . ($officer_map['Approving Officer']['position_name'] ?? ''), 'LBR', 1, 'C');

    $pdf->Ln(5);

    // Issued by
    $pdf->Cell(95, 5, 'Issued by:', 'LTR', 0, 'L');
    $pdf->Cell(95, 5, 'Received by:', 'LTR', 1, 'L');

    $pdf->Cell(95, 15, '', 'LR', 0, 'C');
    $pdf->Cell(95, 15, '', 'LR', 1, 'C');

    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->Cell(95, 5, strtoupper($items[0]['issued_by']), 'LR', 0, 'C');
    $pdf->Cell(95, 5, strtoupper($items[0]['issued_to']), 'LR', 1, 'C');

    $pdf->SetFont('helvetica', '', 8);
    $pdf->Cell(95, 5, 'Signature over Printed Name', 'LR', 0, 'C');
    $pdf->Cell(95, 5, 'Signature over Printed Name', 'LR', 1, 'C');

    $pdf->Cell(95, 5, 'Date: ' . date('m/d/Y'), 'LBR', 0, 'C');
    $pdf->Cell(95, 5, 'Date: ' . date('m/d/Y'), 'LBR', 1, 'C');

    // Output PDF
    $pdf->Output('RIS_' . $ris_number . '.pdf', 'D');

} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    die('Server Error');
}