<?php
session_start();
require 'koneksi.php';
require 'vendor/fpdf.php';

// =====================================
// Wajib login
// =====================================
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id_user = (int)$_SESSION['user_id'];

// Ambil itinerary_id dari query string
$itinerary_id = (int)($_GET['itinerary_id'] ?? 0);
if ($itinerary_id <= 0) {
    header("Location: itineraries.php");
    exit;
}

// =====================================
// Ambil data itinerary (cek kepemilikan)
// =====================================
$sqlIt = "
    SELECT itinerary_id, id_user, title, start_date, end_date, preferences, total_cost, created_at
    FROM itineraries
    WHERE itinerary_id = ? AND id_user = ?
    LIMIT 1
";
$stmtIt = mysqli_prepare($koneksi, $sqlIt);
if (!$stmtIt) {
    die("Query error (itinerary): " . mysqli_error($koneksi));
}
mysqli_stmt_bind_param($stmtIt, "ii", $itinerary_id, $id_user);
mysqli_stmt_execute($stmtIt);
$resIt = mysqli_stmt_get_result($stmtIt);
$itinerary = mysqli_fetch_assoc($resIt);
mysqli_stmt_close($stmtIt);

if (!$itinerary) {
    die("Itinerary tidak ditemukan.");
}

// =====================================
// Ambil item itinerary
// =====================================
$sqlItems = "
    SELECT it.item_id,
           it.visit_date,
           it.start_time,
           it.end_time,
           it.order_number,
           it.notes,
           d.destination_id,
           d.name AS destination_name,
           d.address,
           d.description
    FROM itinerary_items it
    JOIN destinations d ON it.destination_id = d.destination_id
    WHERE it.itinerary_id = ?
    ORDER BY
        it.visit_date IS NULL, it.visit_date,
        it.order_number,
        it.start_time
";
$stmtItems = mysqli_prepare($koneksi, $sqlItems);
if (!$stmtItems) {
    die("Query error (items): " . mysqli_error($koneksi));
}
mysqli_stmt_bind_param($stmtItems, "i", $itinerary_id);
mysqli_stmt_execute($stmtItems);
$resItems = mysqli_stmt_get_result($stmtItems);

$items = [];
if ($resItems) {
    while ($row = mysqli_fetch_assoc($resItems)) {
        $items[] = $row;
    }
}
mysqli_stmt_close($stmtItems);

//Helper functions
function fmt_date($date) {
    if ($date === null || $date === '0000-00-00' || $date === '') return '-';
    $dt = new DateTime($date);
    return $dt->format('d M Y');
}

function fmt_time($time) {
    if ($time === null || $time === '' || $time === '00:00:00') return '';
    return substr($time, 0, 5);
}

function fmt_rupiah($angka) {
    if ($angka === null) return '-';
    return "Rp " . number_format($angka, 0, ',', '.');
}

class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(13, 110, 253); // Bootstrap Primary Blue
        $this->Cell(0, 10, 'Smart Tour Bandung', 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 5, 'Itinerary Perjalanan', 0, 1, 'C');
        $this->Ln(10);
        $this->SetDrawColor(13, 110, 253);
        $this->SetLineWidth(0.5);
        $this->Line(10, 28, 200, 28);
        $this->Ln(5);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0, 10, 'Halaman ' . $this->PageNo() . '/{nb} - Dibuat oleh Smart Tour Bandung', 0, 0, 'C');
    }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();

// Title
$pdf->SetFont('Arial', 'B', 14);
$pdf->SetTextColor(13, 110, 253);
$pdf->MultiCell(0, 10, $itinerary['title'], 0, 'L');
$pdf->Ln(5);

// Info Section
$pdf->SetFillColor(248, 249, 250);
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(50, 50, 50);

$pdf->Cell(40, 8, 'Tanggal:', 0, 0, 'L', true);
$pdf->Cell(0, 8, fmt_date($itinerary['start_date']) . ' - ' . fmt_date($itinerary['end_date']), 0, 1, 'L', true);

$pdf->Cell(40, 8, 'Total Destinasi:', 0, 0, 'L', true);
$pdf->Cell(0, 8, count($items) . ' lokasi', 0, 1, 'L', true);

$pdf->Cell(40, 8, 'Estimasi Biaya:', 0, 0, 'L', true);
$pdf->Cell(0, 8, fmt_rupiah($itinerary['total_cost']), 0, 1, 'L', true);

$pdf->Ln(10);

// Preferences
if (!empty($itinerary['preferences'])) {
    $pdf->SetFillColor(255, 243, 205); // Warning color light
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor(133, 100, 4);
    $pdf->Cell(0, 8, ' Preferensi & Catatan', 0, 1, 'L', true);
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(50, 50, 50);
    $pdf->MultiCell(0, 6, $itinerary['preferences'], 0, 'L', true);
    $pdf->Ln(10);
}

// Items
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(13, 110, 253);
$pdf->Cell(0, 10, 'Rencana Kunjungan', 0, 1, 'L');
$pdf->Ln(2);

if (empty($items)) {
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0, 10, 'Belum ada destinasi dalam itinerary ini.', 0, 1, 'C');
} else {
    $currentDay = null;
    $dayCounter = 0;

    foreach ($items as $item) {
        $dayLabel = $item['visit_date'] ? fmt_date($item['visit_date']) : 'Tanpa tanggal spesifik';

        if ($dayLabel !== $currentDay) {
            $dayCounter++;
            $pdf->Ln(5);
            $pdf->SetFillColor(13, 110, 253);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(0, 8, '  ' . $dayLabel . ' (Hari ' . $dayCounter . ')', 0, 1, 'L', true);
            $pdf->Ln(2);
            $currentDay = $dayLabel;
        }

        // Item Box
        $pdf->SetFillColor(248, 249, 250);
        $pdf->SetDrawColor(220, 220, 220);
        
        // Destination Name
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetTextColor(13, 110, 253);
        $pdf->Cell(0, 8, $item['destination_name'], 'LRT', 1, 'L', true);
        
        // Details
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(50, 50, 50);
        
        // Address
        if ($item['address']) {
            $pdf->Cell(25, 6, 'Alamat:', 'L', 0, 'L', true);
            $pdf->Cell(0, 6, $item['address'], 'R', 1, 'L', true);
        }
        
        // Time
        if ($item['start_time'] || $item['end_time']) {
            $timeStr = '';
            if ($item['start_time']) $timeStr .= fmt_time($item['start_time']);
            if ($item['start_time'] && $item['end_time']) $timeStr .= ' - ';
            if ($item['end_time']) $timeStr .= fmt_time($item['end_time']);
            
            $pdf->Cell(25, 6, 'Waktu:', 'L', 0, 'L', true);
            $pdf->Cell(0, 6, $timeStr, 'R', 1, 'L', true);
        }
        
        // Order
        $pdf->Cell(25, 6, 'Urutan:', 'L', 0, 'L', true);
        $pdf->Cell(0, 6, '#' . $item['order_number'], 'R', 1, 'L', true);
        
        // Notes
        if (!empty($item['notes'])) {
            $pdf->SetFont('Arial', 'I', 9);
            $pdf->Cell(25, 6, 'Catatan:', 'L', 0, 'L', true);
            $pdf->MultiCell(0, 6, $item['notes'], 'R', 'L', true);
        }
        
        // Bottom border
        $pdf->Cell(0, 1, '', 'T', 1, 'L', true);
        $pdf->Ln(2);
    }
}

$pdf->Output('D', 'Itinerary-' . preg_replace('/[^a-zA-Z0-9]/', '-', $itinerary['title']) . '.pdf');
?>
