<?php
/**
 * PDF Export Helper for Bank Sampah Reports
 *
 * Uses TCPDF library for professional PDF output
 * Download TCPDF from: https://github.com/tecnickcom/TCPDF
 * Extract to: lib/tcpdf/
 */

$tcpdfPath = __DIR__ . '/tcpdf/tcpdf.php';

if (!file_exists($tcpdfPath)) {
    die('TCPDF not found. Please run: php lib/install_tcpdf.php');
}

require_once $tcpdfPath;

class LaporanPdf
{
    private $pdf;
    private $appName;
    private $bankName;

    public function __construct()
    {
        // Create PDF document
        $this->pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Document info
        $this->pdf->SetCreator('Bank Sampah Digital');
        $this->pdf->SetAuthor(APP_NAME);
        $this->pdf->SetTitle('Laporan Operasional Bank Sampah');
        $this->pdf->SetSubject('Laporan Operasional');

        // Set margins
        $this->pdf->SetMargins(15, 20, 15);
        $this->pdf->SetHeaderMargin(5);
        $this->pdf->SetFooterMargin(10);

        // Set auto page breaks
        $this->pdf->SetAutoPageBreak(true, 25);

        // Set default font
        $this->pdf->SetFont('helvetica', '', 10);

        $this->appName = defined('APP_NAME') ? APP_NAME : 'Bank Sampah Digital';
    }

    /**
     * Generate Laporan Operasional PDF
     *
     * Isi laporan sesuai FR-17: pembelian, penjualan, stok, dan pencairan.
     * $tanggalFrom / $tanggalTo boleh null (artinya semua periode).
     */
    public function generateLaporanOperasional(
        $statsBeli,
        $statsJual,
        $barisStok = [],
        $statsPencairan = [],
        $tanggalFrom = null,
        $tanggalTo = null,
        $bankName = ''
    ) {
        $this->bankName = $bankName;

        // Add page
        $this->pdf->AddPage();

        // Header
        $this->renderHeader('LAPORAN OPERASIONAL', $bankName);

        // Periode: nama periode pakai bahasa sehari-hari, bukan tanggal kosong.
        $this->pdf->SetFont('helvetica', '', 9);
        $this->pdf->Cell(0, 6, 'Periode: ' . $this->labelPeriode($tanggalFrom, $tanggalTo), 0, 1, 'L');
        $this->pdf->Cell(0, 6, 'Dicetak: ' . date('d/m/Y H:i'), 0, 1, 'L');
        $this->pdf->Ln(5);

        // A. Transaksi Beli
        $this->renderSectionHeader('A. TRANSAKSI BELI');
        $this->renderStatsBeli($statsBeli);
        $this->pdf->Ln(8);

        // B. Transaksi Jual
        $this->renderSectionHeader('B. TRANSAKSI JUAL');
        $this->renderStatsJual($statsJual);
        $this->pdf->Ln(8);

        // C. Stok Sampah
        $this->renderSectionHeader('C. STOK SAMPAH SAAT INI');
        $this->renderStok($barisStok);
        $this->pdf->Ln(8);

        // D. Pengajuan Pencairan
        $this->renderSectionHeader('D. PENGAJUAN PENCAIRAN');
        $this->renderStatsPencairan($statsPencairan);
        $this->pdf->Ln(8);

        // Footer signature
        $this->renderFooter();

        // Output
        return $this->pdf->Output('laporan_operasional_' . date('Ymd') . '.pdf', 'D');
    }

    /**
     * Tulisan periode untuk kepala laporan.
     * Tanpa batas tanggal berarti "Semua periode", bukan 01/01/1970.
     */
    private function labelPeriode($tanggalFrom, $tanggalTo): string
    {
        $adaFrom = is_string($tanggalFrom) && $tanggalFrom !== '';
        $adaTo = is_string($tanggalTo) && $tanggalTo !== '';

        if ($adaFrom && $adaTo) {
            return date('d/m/Y', strtotime($tanggalFrom)) . ' - ' . date('d/m/Y', strtotime($tanggalTo));
        }
        if ($adaFrom) {
            return 'Mulai ' . date('d/m/Y', strtotime($tanggalFrom));
        }
        if ($adaTo) {
            return 'Sampai ' . date('d/m/Y', strtotime($tanggalTo));
        }

        return 'Semua periode';
    }

    /**
     * Generate Laporan Pencairan PDF
     */
    public function generateLaporanPencairan($pengajuans, $statistik, $bankName = '')
    {
        $this->bankName = $bankName;

        $this->pdf->AddPage();

        $this->renderHeader('LAPORAN PENGAJUAN PENCAIRAN', $bankName);
        $this->pdf->SetFont('helvetica', '', 9);
        $this->pdf->Cell(0, 6, 'Dicetak: ' . date('d/m/Y H:i'), 0, 1, 'L');
        $this->pdf->Ln(5);

        // Summary
        $this->renderSectionHeader('RINGKASAN');
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(60, 7, 'Menunggu Persetujuan:', 1, 0, 'L');
        $this->pdf->Cell(0, 7, ($statistik['DIAJUKAN']['jumlah'] ?? 0) . ' pengajuan', 1, 1, 'L');
        $this->pdf->Cell(60, 7, 'Disetujui:', 1, 0, 'L');
        $this->pdf->Cell(0, 7, ($statistik['DISETUJUI']['jumlah'] ?? 0) . ' pengajuan', 1, 1, 'L');
        $this->pdf->Cell(60, 7, 'Ditolak:', 1, 0, 'L');
        $this->pdf->Cell(0, 7, ($statistik['DITOLAK']['jumlah'] ?? 0) . ' pengajuan', 1, 1, 'L');
        $this->pdf->Cell(60, 7, 'Dicairkan:', 1, 0, 'L');
        $this->pdf->Cell(0, 7, ($statistik['DICAIRKAN']['jumlah'] ?? 0) . ' pengajuan', 1, 1, 'L');
        $this->pdf->Ln(8);

        // Detail table
        if (!empty($pengajuans)) {
            $this->renderSectionHeader('DETAIL PENGAJUAN');

            // Table header
            $this->pdf->SetFont('helvetica', 'B', 8);
            $this->pdf->SetFillColor(46, 125, 50);
            $this->pdf->SetTextColor(255);
            $this->pdf->Cell(30, 7, 'No. Pengajuan', 1, 0, 'C', true);
            $this->pdf->Cell(45, 7, 'Nasabah', 1, 0, 'C', true);
            $this->pdf->Cell(30, 7, 'Jumlah', 1, 0, 'C', true);
            $this->pdf->Cell(25, 7, 'Metode', 1, 0, 'C', true);
            $this->pdf->Cell(25, 7, 'Status', 1, 0, 'C', true);
            $this->pdf->Cell(30, 7, 'Tanggal', 1, 1, 'C', true);

            // Table rows
            $this->pdf->SetFont('helvetica', '', 8);
            $this->pdf->SetTextColor(0);

            foreach ($pengajuans as $i => $p) {
                $fill = ($i % 2 == 0);
                $this->pdf->SetFillColor(245, 245, 245);

                $this->pdf->Cell(30, 6, $p['no_pengajuan'], 1, 0, 'C', $fill);
                $this->pdf->Cell(45, 6, substr($p['nama_nasabah'] ?? '-', 0, 25), 1, 0, 'L', $fill);
                $this->pdf->Cell(30, 6, 'Rp ' . number_format($p['jumlah'], 0, ',', '.'), 1, 0, 'R', $fill);
                $this->pdf->Cell(25, 6, $this->labelMetode($p['metode'] ?? ''), 1, 0, 'C', $fill);
                $this->pdf->Cell(25, 6, $this->labelStatus($p['status'] ?? ''), 1, 0, 'C', $fill);
                $this->pdf->Cell(30, 6, date('d/m/Y', strtotime($p['diajukan_at'])), 1, 1, 'C', $fill);
            }
        }

        $this->renderFooter();

        return $this->pdf->Output('laporan_pencairan_' . date('Ymd') . '.pdf', 'D');
    }

    private function renderHeader($title, $subtitle = '')
    {
        // Title
        $this->pdf->SetFont('helvetica', 'B', 16);
        $this->pdf->SetTextColor(46, 125, 50);
        $this->pdf->Cell(0, 10, $this->appName, 0, 1, 'C');

        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->SetTextColor(0);
        $this->pdf->Cell(0, 8, $title, 0, 1, 'C');

        if ($subtitle) {
            $this->pdf->SetFont('helvetica', '', 10);
            $this->pdf->Cell(0, 6, $subtitle, 0, 1, 'C');
        }

        // Line
        $this->pdf->SetDrawColor(46, 125, 50);
        $this->pdf->SetLineWidth(0.5);
        $this->pdf->Line(15, $this->pdf->GetY(), 195, $this->pdf->GetY());
        $this->pdf->Ln(5);
    }

    private function renderSectionHeader($text)
    {
        $this->pdf->SetFont('helvetica', 'B', 11);
        $this->pdf->SetFillColor(232, 245, 233);
        $this->pdf->Cell(0, 8, $text, 0, 1, 'L', true);
        $this->pdf->Ln(2);
    }

    private function renderStatsBeli($stats)
    {
        $this->pdf->SetFont('helvetica', '', 10);

        $cols = [
            ['label' => 'Total Transaksi', 'value' => number_format($stats['total_transaksi'] ?? 0)],
            ['label' => 'Total Berat', 'value' => number_format($stats['total_berat'] ?? 0, 1) . ' kg'],
            ['label' => 'Total Nilai Beli', 'value' => 'Rp ' . number_format($stats['total_nilai'] ?? 0, 0, ',', '.')],
            ['label' => 'Ke Tabungan', 'value' => 'Rp ' . number_format($stats['total_tabungan'] ?? 0, 0, ',', '.')],
            ['label' => 'Ke Tunai', 'value' => 'Rp ' . number_format($stats['total_tunai'] ?? 0, 0, ',', '.')],
        ];

        foreach ($cols as $col) {
            $this->pdf->Cell(50, 6, $col['label'] . ':', 0, 0, 'L');
            $this->pdf->Cell(0, 6, $col['value'], 0, 1, 'L');
        }
    }

    private function renderStatsJual($stats)
    {
        $this->pdf->SetFont('helvetica', '', 10);

        $cols = [
            ['label' => 'Total Transaksi', 'value' => number_format($stats['total_transaksi'] ?? 0)],
            ['label' => 'Total Berat', 'value' => number_format($stats['total_berat'] ?? 0, 1) . ' kg'],
            ['label' => 'Total Penjualan', 'value' => 'Rp ' . number_format($stats['total_nilai'] ?? 0, 0, ',', '.')],
            ['label' => 'Piutang', 'value' => 'Rp ' . number_format($stats['total_belum_bayar'] ?? 0, 0, ',', '.')],
        ];

        foreach ($cols as $col) {
            $this->pdf->Cell(50, 6, $col['label'] . ':', 0, 0, 'L');
            $this->pdf->Cell(0, 6, $col['value'], 0, 1, 'L');
        }
    }

    private function renderFooter()
    {
        $this->pdf->Ln(15);
        $this->pdf->SetFont('helvetica', '', 9);
        $this->pdf->Cell(0, 6, 'Mengetahui,', 0, 1, 'R');
        $this->pdf->Ln(15);
        $this->pdf->Cell(0, 6, '________________________', 0, 1, 'R');
        $this->pdf->Cell(0, 5, 'Pencetak Laporan', 0, 1, 'R');
    }

    /**
     * Tabel stok per kategori (bagian C laporan).
     *
     * @param array $baris tiap item: kategori_nama, stok_cents,
     *                     reserved_cents, tersedia_cents
     */
    private function renderStok($baris)
    {
        if (empty($baris)) {
            $this->pdf->SetFont('helvetica', '', 10);
            $this->pdf->Cell(0, 6, 'Belum ada stok tercatat.', 0, 1, 'L');
            return;
        }

        $this->pdf->SetFont('helvetica', 'B', 8);
        $this->pdf->SetFillColor(46, 125, 50);
        $this->pdf->SetTextColor(255);
        $this->pdf->Cell(70, 7, 'Kategori', 1, 0, 'L', true);
        $this->pdf->Cell(35, 7, 'Stok', 1, 0, 'R', true);
        $this->pdf->Cell(35, 7, 'Dipesan', 1, 0, 'R', true);
        $this->pdf->Cell(40, 7, 'Bisa Dijual', 1, 1, 'R', true);

        $this->pdf->SetFont('helvetica', '', 8);
        $this->pdf->SetTextColor(0);

        foreach ($baris as $i => $b) {
            $fill = ($i % 2 == 0);
            $this->pdf->SetFillColor(245, 245, 245);

            $this->pdf->Cell(70, 6, substr((string) ($b['kategori_nama'] ?? '-'), 0, 40), 1, 0, 'L', $fill);
            $this->pdf->Cell(35, 6, $this->beratKg((int) ($b['stok_cents'] ?? 0)), 1, 0, 'R', $fill);
            $this->pdf->Cell(35, 6, $this->beratKg((int) ($b['reserved_cents'] ?? 0)), 1, 0, 'R', $fill);
            $this->pdf->Cell(40, 6, $this->beratKg((int) ($b['tersedia_cents'] ?? 0)), 1, 1, 'R', $fill);
        }
    }

    /**
     * Ringkasan pengajuan pencairan per status (bagian D laporan).
     *
     * @param array $statistik status => ['jumlah' => int, 'total_nilai' => float]
     */
    private function renderStatsPencairan($statistik)
    {
        $this->pdf->SetFont('helvetica', '', 10);

        $labelStatus = [
            'DIAJUKAN' => 'Diajukan',
            'DISETUJUI' => 'Disetujui',
            'DITOLAK' => 'Ditolak',
            'DICAIRKAN' => 'Dicairkan',
            'DIBATALKAN' => 'Dibatalkan',
        ];

        $ada = false;
        foreach ($labelStatus as $status => $label) {
            $jumlah = (int) ($statistik[$status]['jumlah'] ?? 0);
            if ($jumlah === 0) {
                continue;
            }
            $ada = true;
            $nilai = (float) ($statistik[$status]['total_nilai'] ?? 0);
            $this->pdf->Cell(50, 6, $label . ':', 0, 0, 'L');
            $this->pdf->Cell(0, 6, $jumlah . ' pengajuan (' . $this->rupiah($nilai) . ')', 0, 1, 'L');
        }

        if (!$ada) {
            $this->pdf->Cell(0, 6, 'Tidak ada pengajuan pencairan pada periode ini.', 0, 1, 'L');
            return;
        }

        $total = (int) ($statistik['TOTAL']['jumlah'] ?? 0);
        $totalNilai = (float) ($statistik['TOTAL']['total_nilai'] ?? 0);
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->Cell(50, 6, 'Total pengajuan:', 0, 0, 'L');
        $this->pdf->Cell(0, 6, $total . ' pengajuan (' . $this->rupiah($totalNilai) . ')', 0, 1, 'L');
    }

    /**
     * Format sen-kg menjadi "12,50 kg".
     */
    private function beratKg(int $senKg): string
    {
        $teks = number_format(intdiv($senKg, 100), 0, ',', '.') . ',' . str_pad((string) ($senKg % 100), 2, '0', STR_PAD_LEFT);
        $teks = rtrim(rtrim($teks, '0'), ',');

        return ($teks === '' ? '0' : $teks) . ' kg';
    }

    /**
     * Format nominal menjadi "Rp 12.500".
     */
    private function rupiah(float $nilai): string
    {
        return 'Rp ' . number_format($nilai, 0, ',', '.');
    }

    /**
     * Metode pencairan dalam bahasa sehari-hari.
     */
    private function labelMetode($metode): string
    {
        $label = [
            'TUNAI' => 'Tunai',
            'TRANSFER' => 'Transfer',
        ];

        $metode = (string) $metode;

        return $label[$metode] ?? ($metode !== '' ? $metode : '-');
    }

    /**
     * Status pencairan dalam bahasa sehari-hari.
     */
    private function labelStatus($status): string
    {
        $label = [
            'DIAJUKAN' => 'Diajukan',
            'DISETUJUI' => 'Disetujui',
            'DITOLAK' => 'Ditolak',
            'DICAIRKAN' => 'Dicairkan',
            'DIBATALKAN' => 'Dibatalkan',
        ];

        $status = (string) $status;

        return $label[$status] ?? ($status !== '' ? $status : '-');
    }
}
