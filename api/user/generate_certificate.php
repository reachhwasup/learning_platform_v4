<?php
/**
 * Generate PDF Certificate API
 *
 * This script generates a downloadable PDF certificate for the logged-in user.
 * It requires the fpdf.php library to be placed in /includes/lib/
 */

require_once '../../includes/db_connect.php';
require_once '../../includes/functions.php';

// --- FPDF Library ---
$fpdf_path = __DIR__ . '/../../includes/lib/fpdf.php';
if (!file_exists($fpdf_path)) {
    die('Error: FPDF library not found. Please download it and place fpdf.php in the /includes/lib/ folder.');
}
require_once $fpdf_path;

// --- Authentication ---
if (!is_logged_in()) {
    die('Access Denied. Please log in.');
}
$user_id = $_SESSION['user_id'];

// --- Fetch Certificate Data ---
try {
    $sql = "SELECT u.first_name, u.last_name, c.certificate_code, fa.completed_at 
            FROM certificates c
            JOIN users u ON c.user_id = u.id
            JOIN final_assessments fa ON c.assessment_id = fa.id
            WHERE c.user_id = ? 
            ORDER BY fa.completed_at DESC LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $certificate = $stmt->fetch();

    if (!$certificate) {
        die('No certificate found for this user.');
    }
} catch (PDOException $e) {
    error_log("PDF Generation Error: " . $e->getMessage());
    die('A database error occurred.');
}

// --- PDF Generation ---
$pdf = new FPDF('L', 'mm', 'A4'); // Landscape, millimeters, A4 size
$pdf->AddPage();
$pdf->SetTitle("Certificate of Completion");

// Draw a border
$pdf->SetLineWidth(1.5);
$pdf->SetDrawColor(0, 82, 204); // Primary color
$pdf->Rect(5, 5, 287, 200); // x, y, width, height

// Certificate Title
$pdf->SetFont('Arial', 'B', 20);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(0, 35, 'Certificate of Completion', 0, 1, 'C');

// Presented to
$pdf->SetFont('Arial', '', 16);
$pdf->Cell(0, 15, 'This certificate is proudly presented to', 0, 1, 'C');

// User's Name
$pdf->SetFont('Arial', 'B', 48);
$pdf->SetTextColor(0, 82, 204);
$pdf->Cell(0, 30, $certificate['first_name'] . ' ' . $certificate['last_name'], 0, 1, 'C');

// Course Name
$pdf->SetFont('Arial', '', 16);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 15, 'For successfully completing the', 0, 1, 'C');
$pdf->SetFont('Arial', 'B', 22);
$pdf->Cell(0, 10, 'Information Security Awareness Training', 0, 1, 'C');

// Completion Date
$pdf->SetFont('Arial', '', 16);
$pdf->Cell(0, 25, 'on', 0, 1, 'C');
$pdf->SetFont('Arial', '', 18);
$pdf->Cell(0, 5, date('F j, Y', strtotime($certificate['completed_at'])), 0, 1, 'C');

// Signatures and ID at the bottom
$pdf->SetY(-35); // Position from bottom
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(100, 100, 100);

// Authorized Signature
$pdf->Cell(138, 5, 'Authorized Signature', 'T', 0, 'C');
$pdf->Cell(10, 5, '', 0, 0); // Spacer
// Certificate ID
$pdf->Cell(138, 5, 'Certificate ID: ' . $certificate['certificate_code'], 0, 1, 'C');

// Output the PDF
$pdf->Output('D', 'Certificate.pdf'); // 'D' forces download
exit;
?>
