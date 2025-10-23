<?php
// Test script to verify TCPDF installation
require_once 'vendor/autoload.php';

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('SAMSPIKPOK');
$pdf->SetAuthor('System');
$pdf->SetTitle('TCPDF Test');

// Set margins
$pdf->SetMargins(15, 15, 15);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 14);

// Add content
$pdf->Cell(0, 10, 'TCPDF is working!', 0, 1, 'C');

// Create a QR code
$style = array(
    'border' => 2,
    'padding' => 'auto',
    'fgcolor' => array(0, 0, 0),
    'bgcolor' => array(255, 255, 255)
);

$pdf->write2DBarcode('Test QR Code', 'QRCODE,H', 80, 50, 50, 50, $style);

// Output the PDF
$pdf->Output('test.pdf', 'I');