<?php
session_start();
// Produces a PDF version of the PAR (Property Acknowledgment Receipt).
// Accepts GET id (par_id).

require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo 'Unauthorized';
    exit;
}

$par_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$par_id) {
    http_response_code(400);
    echo 'Invalid PAR ID.';
    exit;
}

// Capture the HTML produced by par_view.php
ob_start();
// par_view.php expects to be called from the api folder and requires ../db.php internally
include __DIR__ . '/par_view.php';
$html = ob_get_clean();

// Load TCPDF
$tcpdf_file = $_SERVER['DOCUMENT_ROOT'] . '/samspikpok/vendor/tecnickcom/tcpdf/tcpdf.php';
if (!file_exists($tcpdf_file)) {
    // If TCPDF missing, return raw HTML as fallback
    header('Content-Type: text/html; charset=utf-8');
    echo $html;
    exit;
}
require_once $tcpdf_file;

// Create PDF
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('SAMS');
$pdf->SetTitle('PAR');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(10, 10, 10);
$pdf->AddPage();

// TCPDF writeHTML expects UTF-8; par_view.php output should be UTF-8
$pdf->writeHTML($html, true, false, true, false, '');

// Output PDF to browser inline
$filename = 'PAR_' . $par_id . '.pdf';
$pdf->Output($filename, 'I');

exit;

?>
