<?php
// Start output buffering to prevent early output
ob_start();
date_default_timezone_set('Asia/Makassar');
$bulan = $_POST['bulan'] ?? '';
$tahun = $_POST['tahun'] ?? date('Y');

// Mapping bulan
$nama_bulan = [
    '01' => 'Januari',
    '02' => 'Februari',
    '03' => 'Maret',
    '04' => 'April',
    '05' => 'Mei',
    '06' => 'Juni',
    '07' => 'Juli',
    '08' => 'Agustus',
    '09' => 'September',
    '10' => 'Oktober',
    '11' => 'November',
    '12' => 'Desember'
];

// Jika kosong → Semua
$bulan_text = $bulan && isset($nama_bulan[$bulan])
    ? $nama_bulan[$bulan]
    : 'Semua';

// Format waktu
$jam = date('G.i');

// Nama file
$nama_file = "Laporan_Survey_{$bulan_text}_{$tahun}_{$jam}.pdf";

// Load TCPDF library without relying on config constants
require_once __DIR__ . '/tcpdf/tcpdf.php';

// Ambil data dari POST
$data = json_decode($_POST['data'] ?? '[]', true);
$penilaian = json_decode($_POST['penilaian'] ?? '{}', true);

// Hitung statistik aspek
$counts = array();
foreach ($data as $row) {
    $aspek = $row['Aspek Pelayanan'] ?? 'N/A';
    if (!isset($counts[$aspek])) {
        $counts[$aspek] = 0;
    }
    $counts[$aspek]++;
}

// Buat PDF (tanpa bergantung pada konstanta)
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetDefaultMonospacedFont('courier');
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(TRUE, 15);
$pdf->AddPage();

// KOP DINAS (TABEL)
$logo = __DIR__ . '/logo-hss.png';

$html = '
<table width="100%" cellpadding="0" cellspacing="0" border="0">
    <tr>
        <td style="width: 100%; text-align: center;">
            <table width="100%" cellpadding="1">
                <tr>
                    <!-- Kolom Logo -->
                    <td width="15%" align="right">
                        <img src="'.$logo.'" height="50">
                    </td>
                    <!-- Kolom Teks -->
                    <td width="77%" align="center" style="line-height:1.2;">
                        <span style="font-size:12px;">PEMERINTAH KABUPATEN HULU SUNGAI SELATAN</span><br>
                        <span style="font-size:14px; font-weight:bold;">DINAS PERTANIAN, PERIKANAN DAN PANGAN</span><br>
                        <span style="font-size:10px;">Jalan Singakarsa No.38 Kandangan 71213</span><br>
                        <span style="font-size:10px;">e-mail : hsspertanian@gmail.com</span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<table width="100%" cellspacing="0" cellpadding="0">
    <tr>
        <td width="5%"></td> <!-- Spasi kosong kiri agar garis center (100-85)/2 -->
        <td width="85%" style="border-bottom: 2px solid black;"></td> 
        <td width="7.7%"></td> <!-- Sisa kanan -->
    </tr>
</table>
<div style="height:5px;"></div>
';

$pdf->writeHTML($html, true, false, true, false, '');
// Judul Laporan
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'LAPORAN HASIL SURVEY KEPUASAN PEGAWAI', 0, 1, 'C');
$pdf->Ln(3);

// Skor SKM
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 6, 'Indeks Kepuasan Masyarakat (SKM)', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 24);
$pdf->SetTextColor(76, 175, 80);
$pdf->Cell(0, 12, number_format($penilaian['skm'] ?? 0, 2), 0, 1, 'C');
$pdf->SetTextColor(0, 0, 0);

$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, 'Kategori: ' . ($penilaian['kategori'] ?? 'N/A'), 0, 1, 'C');
$pdf->Cell(0, 5, 'Total Responden: ' . ($penilaian['total'] ?? 0), 0, 1, 'C');
$pdf->Ln(5);

// Tabel Aspek Pelayanan
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor(41, 128, 185);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(120, 7, 'Aspek Pelayanan', 1, 0, 'L', true);
$pdf->Cell(25, 7, 'Jumlah', 1, 0, 'C', true);
$pdf->Cell(25, 7, 'Persentase', 1, 1, 'C', true);

$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFillColor(240, 240, 240);

$total = count($data);
foreach ($counts as $aspek => $count) {
    $persen = $total > 0 ? (($count / $total) * 100) : 0;
    $pdf->Cell(120, 6, $aspek, 1, 0, 'L');
    $pdf->Cell(25, 6, $count, 1, 0, 'C');
    $pdf->Cell(25, 6, number_format($persen, 1) . '%', 1, 1, 'C');
}

// Ruang spasi sebelum TTD
$pdf->Ln(10);
$pdf->SetFont('helvetica', '', 10);

// Ambil posisi Y saat ini
$currentY = $pdf->GetY();

// --- BARIS 1: Tanggal (Kanan) dan Baris Kosong (Kiri) ---
// Kolom Kiri (Kosong agar sejajar tanggal)
$pdf->MultiCell(90, 5, "", 0, 'C', false, 0, 15, $currentY);
// Kolom Kanan (Tempat & Tanggal)
$pdf->MultiCell(90, 5, 'Kandangan, ' . date('d F Y'), 0, 'C', false, 1, 105, $currentY);

// --- BARIS 2: Jabatan ---
$currentY = $pdf->GetY(); // Update posisi Y setelah baris tanggal
// Kolom Kiri (Mengetahui, Kepala Dinas)
$pdf->MultiCell(90, 5, "Mengetahui,\nKepala Dinas", 0, 'C', false, 0, 15, $currentY);
// Kolom Kanan (Admin Sistem)
$pdf->MultiCell(90, 5, "\nAdmin Sistem", 0, 'C', false, 1, 105, $currentY);

// --- BARIS 3: Tempat Tanda Tangan (Spasi Kosong) ---
$pdf->Ln(20); 

// --- BARIS 4: Garis Bawah / Nama Pejabat ---
$pdf->SetFont('helvetica', 'B', 10);
$currentY = $pdf->GetY();
// Nama di Kiri
$pdf->MultiCell(90, 5, "( ____________________ )", 0, 'C', false, 0, 15, $currentY);

// Nama di Kanan
$pdf->MultiCell(90, 5, "( ____________________ )", 0, 'C', false, 1, 105, $currentY);


$pdf->SetFont('helvetica', '', 10);

// Ambil posisi Y terbaru setelah garis tanda tangan
$currentY = $pdf->GetY();

// NIP kiri
$pdf->MultiCell(90, 5, "NIP. 196xxxxxxxxxxxx", 0, 'C', false, 0, 15, $currentY);

// NIP kanan
$pdf->MultiCell(90, 5, "NIP. 197xxxxxxxxxxxx", 0, 'C', false, 1, 105, $currentY);

// Output PDF
ob_clean();
$pdf->Output($nama_file, 'D');
ob_end_flush();
?>

