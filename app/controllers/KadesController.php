<?php
/**
 * Kades Controller
 *
 * Controller untuk fitur Kepala Desa
 * Compatible dengan PHP 7.3+
 */

class KadesController
{
    /** @var TransaksiBeli */
    private $transaksiBeli;

    /** @var TransaksiJual */
    private $transaksiJual;

    /** @var BankSampah */
    private $bankSampahModel;

    /** @var LaporanDesa */
    private $laporanDesa;

    /** @var int */
    private $desaId;

    public function __construct()
    {
        requireLogin();
        requireRole(['KADES'], 'dashboard');

        require_once MODEL_PATH . '/TransaksiBeli.php';
        require_once MODEL_PATH . '/TransaksiJual.php';
        require_once MODEL_PATH . '/BankSampah.php';
        require_once MODEL_PATH . '/Desa.php';
        require_once MODEL_PATH . '/LaporanDesa.php';

        $this->transaksiBeli = new TransaksiBeli();
        $this->transaksiJual = new TransaksiJual();
        $this->bankSampahModel = new BankSampah();
        $this->laporanDesa = new LaporanDesa();

        // 0 berarti akun belum terhubung ke desa manapun
        $this->desaId = isset($_SESSION['user_desa_id']) ? (int) $_SESSION['user_desa_id'] : 0;
    }

    public function index(): void
    {
        $this->dashboard();
    }

    /**
     * Pastikan akun kades terhubung ke sebuah desa.
     * Mencegah getAll(null) mengembalikan bank sampah milik desa lain.
     */
    private function pastikanDesa(): void
    {
        if ($this->desaId <= 0) {
            setFlashMessage('error', 'Akun Anda belum terhubung dengan desa. Hubungi admin.');
            redirect('logout');
            exit;
        }
    }

    /**
     * Dashboard Kepala Desa
     *
     * Periode dipilih lewat ?minggu=4 atau ?minggu=12 (FR-22, UC13).
     * Semua angka bersifat agregat tanpa data pribadi warga (BR-09).
     */
    public function dashboard(): void
    {
        $this->pastikanDesa();

        // Periode: 4 atau 12 minggu terakhir (termasuk minggu berjalan)
        $minggu = (int) ($_GET['minggu'] ?? 4);
        if (!in_array($minggu, [4, 12], true)) {
            $minggu = 4;
        }
        $tanggalTo = date('Y-m-d');
        $tanggalFrom = date('Y-m-d', strtotime('monday this week -' . ($minggu - 1) . ' weeks'));

        // Periode pembanding: rentang sama panjang tepat sebelum periode ini
        $prevTo = date('Y-m-d', strtotime($tanggalFrom . ' -1 day'));
        $prevFrom = date('Y-m-d', strtotime($tanggalFrom . ' -' . $minggu . ' weeks'));

        // Ambil semua bank sampah di desa ini
        $banks = $this->bankSampahModel->getAll($this->desaId);
        $bankIds = array_column($banks, 'id');

        // Statistik agregat
        $stats = [
            'total_bank' => count($banks),
            'total_transaksi_beli' => 0,
            'total_transaksi_jual' => 0,
            'total_nilai_beli' => 0,
            'total_nilai_jual' => 0,
            'total_berat_beli' => 0,
            'berat_beli_sebelumnya' => 0,
        ];

        // Hitung statistik per bank
        $bankStats = [];
        foreach ($banks as $bank) {
            $statBeli = $this->transaksiBeli->getStatistik($bank['id'], $tanggalFrom, $tanggalTo);
            $statJual = $this->transaksiJual->getStatistik($bank['id'], $tanggalFrom, $tanggalTo);
            $statLalu = $this->transaksiBeli->getStatistik($bank['id'], $prevFrom, $prevTo);

            $bankStats[$bank['id']] = [
                'bank' => $bank,
                'transaksi_beli' => $statBeli['total_transaksi'],
                'transaksi_jual' => $statJual['total_transaksi'],
                'nilai_beli' => $statBeli['total_nilai'],
                'nilai_jual' => $statJual['total_nilai'],
            ];

            $stats['total_transaksi_beli'] += $statBeli['total_transaksi'];
            $stats['total_transaksi_jual'] += $statJual['total_transaksi'];
            $stats['total_nilai_beli'] += $statBeli['total_nilai'];
            $stats['total_nilai_jual'] += $statJual['total_nilai'];
            $stats['total_berat_beli'] += $statBeli['total_berat'];
            $stats['berat_beli_sebelumnya'] += $statLalu['total_berat'];
        }

        // Tren transaksi mingguan untuk grafik
        $trend = $this->laporanDesa->trenMingguan($bankIds, $minggu);

        // Data tambahan (hanya agregat). Tampil otomatis begitu method-nya
        // tersedia di LaporanDesa; selama belum ada, bagiannya disembunyikan.
        $lap = $this->laporanDesa;
        $nasabahAktif = method_exists($lap, 'nasabahAktif')
            ? (int) $lap->nasabahAktif($bankIds, $tanggalFrom, $tanggalTo) : null;
        $komposisi = method_exists($lap, 'komposisiJenis')
            ? $lap->komposisiJenis($bankIds, $tanggalFrom, $tanggalTo) : null;
        $partisipasiRw = method_exists($lap, 'partisipasiRw')
            ? $lap->partisipasiRw($bankIds, $tanggalFrom, $tanggalTo) : null;

        $user = currentUser();
        $flash = getFlashMessage();

        include VIEW_PATH . '/kades/dashboard.php';
    }

    /**
     * Monitoring Kegiatan
     */
    public function monitoring(): void
    {
        $this->pastikanDesa();

        $banks = $this->bankSampahModel->getAll($this->desaId);

        // Ambil 20 transaksi terbaru per jenis dari tiap bank, lalu gabungkan.
        // Batas per jenis dibuat 20 supaya 20 terbaru keseluruhan pasti terwakili.
        $recentTrans = [];
        foreach ($banks as $bank) {
            $beli = $this->transaksiBeli->getByBankSampah($bank['id'], 20);
            foreach ($beli as $b) {
                // BR-08: transaksi yang dibatalkan tidak ikut dipantau
                if (isset($b['status']) && $b['status'] !== 'SAH') {
                    continue;
                }
                $b['jenis'] = 'BELI';
                $recentTrans[] = $b;
            }
            $jual = $this->transaksiJual->getByBankSampah($bank['id'], 20);
            foreach ($jual as $j) {
                if (isset($j['status']) && $j['status'] !== 'SAH') {
                    continue;
                }
                $j['jenis'] = 'JUAL';
                $recentTrans[] = $j;
            }
        }

        // Urutkan dari yang terbaru
        usort($recentTrans, function ($a, $b) {
            return strtotime($b['tanggal']) - strtotime($a['tanggal']);
        });
        $recentTrans = array_slice($recentTrans, 0, 20);

        // BR-09: kepala desa tidak boleh melihat data pribadi warga.
        // Dibuang di controller supaya tidak pernah sampai ke view.
        $recentTrans = array_map(function ($t) {
            unset($t['nasabah_nama'], $t['nama_penjual'], $t['nasabah_id'], $t['nik'], $t['no_hp']);
            return $t;
        }, $recentTrans);

        $user = currentUser();
        $flash = getFlashMessage();

        include VIEW_PATH . '/kades/monitoring.php';
    }

    /**
     * Laporan Keuangan
     */
    public function laporan(): void
    {
        $this->pastikanDesa();

        $tanggalFrom = $this->tanggalValid($_GET['tanggal_from'] ?? '', date('Y-m-01'));
        $tanggalTo = $this->tanggalValid($_GET['tanggal_to'] ?? '', date('Y-m-d'));

        // Tanggal di masa depan tidak bermakna: batasi sampai hari ini
        $hariIni = date('Y-m-d');
        if ($tanggalFrom > $hariIni) {
            $tanggalFrom = $hariIni;
        }
        if ($tanggalTo > $hariIni) {
            $tanggalTo = $hariIni;
        }
        if ($tanggalFrom > $tanggalTo) {
            // Tukar bila terbalik agar rentang selalu masuk akal
            list($tanggalFrom, $tanggalTo) = [$tanggalTo, $tanggalFrom];
        }
        $bankId = isset($_GET['bank_id']) ? (int) $_GET['bank_id'] : null;

        $banks = $this->bankSampahModel->getAll($this->desaId);

        // bank_id yang bukan milik desa ini diabaikan (tampilkan semua bank desa ini)
        if ($bankId && !in_array($bankId, array_map('intval', array_column($banks, 'id')), true)) {
            $bankId = null;
        }

        // Ambil bank yang dipilih atau semua (hanya dari desa ini)
        if ($bankId) {
            $selectedBanks = array_filter($banks, function ($b) use ($bankId) {
                return (int) $b['id'] === $bankId;
            });
        } else {
            $selectedBanks = $banks;
        }

        $stats = [
            'total_transaksi_beli' => 0,
            'total_berat_beli' => 0,
            'total_nilai_beli' => 0,
            'total_tabungan' => 0,
            'total_tunai' => 0,
            'total_transaksi_jual' => 0,
            'total_berat_jual' => 0,
            'total_nilai_jual' => 0,
            'total_piutang' => 0,
            'keuntungan' => 0,
        ];

        foreach ($selectedBanks as $bank) {
            $beli = $this->transaksiBeli->getStatistik($bank['id'], $tanggalFrom, $tanggalTo);
            // Penjualan ikut difilter periode yang sama dengan pembelian.
            // Pastikan TransaksiJual::getStatistik() menerima parameter tanggal.
            $jual = $this->transaksiJual->getStatistik($bank['id'], $tanggalFrom, $tanggalTo);

            $stats['total_transaksi_beli'] += $beli['total_transaksi'];
            $stats['total_berat_beli'] += $beli['total_berat'];
            $stats['total_nilai_beli'] += $beli['total_nilai'];
            $stats['total_tabungan'] += $beli['total_tabungan'];
            $stats['total_tunai'] += $beli['total_tunai'];
            $stats['total_transaksi_jual'] += $jual['total_transaksi'];
            $stats['total_berat_jual'] += $jual['total_berat'];
            $stats['total_nilai_jual'] += $jual['total_nilai'];
            $stats['total_piutang'] += $jual['total_belum_bayar'];
        }

        // Selisih penjualan dikurangi pembelian pada periode ini.
        // Bukan laba bersih: pembelian yang belum terjual masih berupa stok.
        $stats['keuntungan'] = $stats['total_nilai_jual'] - $stats['total_nilai_beli'];

        // FR-17: stok, pencairan, dan komposisi (hanya agregat). View sudah
        // menangani nilai null, jadi bagian ini tampil begitu method-nya ada.
        $idDipilih = array_column($selectedBanks, 'id');
        $lap = $this->laporanDesa;
        $stok = method_exists($lap, 'stokPerJenis')
            ? $lap->stokPerJenis($idDipilih) : null;
        $pencairan = method_exists($lap, 'ringkasPencairan')
            ? $lap->ringkasPencairan($idDipilih, $tanggalFrom, $tanggalTo) : null;
        $komposisi = method_exists($lap, 'komposisiJenis')
            ? $lap->komposisiJenis($idDipilih, $tanggalFrom, $tanggalTo) : null;

        $user = currentUser();
        $flash = getFlashMessage();

        include VIEW_PATH . '/kades/laporan.php';
    }

    /**
     * Export Laporan (Placeholder)
     */
    public function export(): void
    {
        $this->pastikanDesa();

        $format = $_GET['format'] ?? 'pdf';

        // Placeholder untuk export
        setFlashMessage('info', 'Fitur export ' . strtoupper($format) . ' sedang dalam pengembangan.');
        redirect('kades/laporan');
    }

    /**
     * Validasi format tanggal Y-m-d, kembalikan default bila tidak valid.
     */
    private function tanggalValid($nilai, string $default): string
    {
        $nilai = (string) $nilai;
        $d = DateTime::createFromFormat('Y-m-d', $nilai);
        return ($d && $d->format('Y-m-d') === $nilai) ? $nilai : $default;
    }
}