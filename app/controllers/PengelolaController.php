<?php
/**
 * Pengelola Controller
 *
 * Controller untuk fitur Pengelola
 *
 * Alur yang dipegang controller ini (PROSES_BISNIS.md):
 * - Hanya Pengelola yang mencatat pembelian, penjualan, dan memproses
 *   pencairan (bagian 1).
 * - Harga selalu diambil ulang dari server berdasarkan tanggal transaksi
 *   (BR-01). Angka yang dikirim browser tidak pernah dipercaya.
 * - Transaksi tidak dihapus; kesalahan dikoreksi lewat pembatalan dengan
 *   alasan (BR-08).
 * - Setiap perubahan penting menulis audit log (NFR-08).
 *
 * Compatible dengan PHP 7.3+
 */

class PengelolaController
{
    /** @var TransaksiBeli */
    private $transaksiBeli;

    /** @var TransaksiJual */
    private $transaksiJual;

    /** @var KategoriSampah */
    private $kategoriModel;

    /** @var HargaSampah */
    private $hargaModel;

    /** @var Pengepul */
    private $pengepulModel;

    /** @var Nasabah */
    private $nasabahModel;

    /** @var StokSampah */
    private $stokModel;

    /** @var BankSampah */
    private $bankSampahModel;

    /** @var PengajuanPencairan */
    private $pencairanModel;

    /** @var MutasiSaldo */
    private $mutasiModel;

    /** @var Notifikasi */
    private $notifModel;

    /** @var int */
    private $bankSampahId;

    /** @var array<int, string> nama kategori yang sudah dibaca, untuk pesan error */
    private $namaKategoriCache = [];

    public function __construct()
    {
        requireLogin();
        requireRole(['PENGELOLA'], 'dashboard');

        // Load models
        require_once MODEL_PATH . '/TransaksiBeli.php';
        require_once MODEL_PATH . '/TransaksiJual.php';
        require_once MODEL_PATH . '/KategoriSampah.php';
        require_once MODEL_PATH . '/HargaSampah.php';
        require_once MODEL_PATH . '/Pengepul.php';
        require_once MODEL_PATH . '/Nasabah.php';
        require_once MODEL_PATH . '/StokSampah.php';
        require_once MODEL_PATH . '/BankSampah.php';
        require_once MODEL_PATH . '/PengajuanPencairan.php';
        require_once MODEL_PATH . '/MutasiSaldo.php';
        require_once MODEL_PATH . '/Notifikasi.php';
        require_once MODEL_PATH . '/User.php';

        $this->transaksiBeli = new TransaksiBeli();
        $this->transaksiJual = new TransaksiJual();
        $this->kategoriModel = new KategoriSampah();
        $this->hargaModel = new HargaSampah();
        $this->pengepulModel = new Pengepul();
        $this->nasabahModel = new Nasabah();
        $this->stokModel = new StokSampah();
        $this->bankSampahModel = new BankSampah();
        $this->pencairanModel = new PengajuanPencairan();
        $this->mutasiModel = new MutasiSaldo();
        $this->notifModel = new Notifikasi();

                // Ambil bank sampah dari session user
        $this->bankSampahId = (int) ($_SESSION['user_bank_sampah_id'] ?? 0);

        if ($this->bankSampahId <= 0) {
            // Tidak redirect: dashboard kemungkinan memakai controller ini juga
            // sehingga bisa loop. Tampilkan halaman pesan lalu berhenti.
            http_response_code(403);
            $pesan = 'Akun Anda belum terdaftar pada bank sampah mana pun. Hubungi Admin.';
            $urlKeluar = base_url('logout'); // ASUMSI: rute logout bernama 'logout'
            echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8">'
                . '<meta name="viewport" content="width=device-width, initial-scale=1">'
                . '<title>Akses ditolak</title></head>'
                . '<body style="font-family:sans-serif;max-width:480px;margin:15vh auto;padding:0 16px;">'
                . '<h1 style="font-size:1.25rem;">Tidak dapat melanjutkan</h1>'
                . '<p>' . htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8') . '</p>'
                . '<p><a href="' . htmlspecialchars($urlKeluar, ENT_QUOTES, 'UTF-8') . '">Keluar</a></p>'
                . '</body></html>';
            exit;
        }
    }

    /**
     * Koneksi database.
     *
     * @return PDO
     */
    private function db(): PDO
    {
        return getDbConnection();
    }

    /**
     * Cek apakah data (hasil findById) milik bank sampah pengelola yang sedang login.
     * Gagal tertutup: jika data tidak memuat kolom bank_sampah_id, akses ditolak.
     *
     * @param mixed  $row  hasil findById()
     * @param string $nama nama data (untuk catatan error_log)
     */
    private function milikBankIni($row, string $nama): bool
    {
        if (!is_array($row) || !$this->bankSampahId) {
            return false;
        }

        if (!array_key_exists('bank_sampah_id', $row)) {
            error_log('[PengelolaController] findById() ' . $nama . ' tidak memuat kolom bank_sampah_id; akses ditolak.');
            return false;
        }

        return (int) $row['bank_sampah_id'] === (int) $this->bankSampahId;
    }

    /**
     * Pastikan permintaan memakai metode POST.
     */
    private function hanyaPost(string $kembaliKe): bool
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            setFlashMessage('error', 'Gunakan tombol pada halaman daftar.');
            redirect($kembaliKe);
            return false;
        }

        return true;
    }

    /**
     * Pastikan token CSRF pada request POST.
     */
    private function cekToken(string $kembaliKe): bool
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return true;
        }

        $token = $_POST['_token'] ?? '';
        if (!is_string($token) || $token === '' || empty($_SESSION['csrf_token'])
            || !hash_equals($_SESSION['csrf_token'], $token)) {
            http_response_code(403);
            setFlashMessage('error', 'Sesi formulir tidak valid atau sudah kedaluwarsa. Silakan muat ulang halaman.');
            redirect($kembaliKe);

            return false;
        }

        return true;
    }

    /**
     * Tanggal transaksi yang dipakai untuk mencari harga aktif (BR-01).
     * Tidak boleh lebih dari hari ini.
     *
     * @param string|null $tanggal
     * @return string Format Y-m-d
     */
    private function tanggalTransaksi($tanggal = null): string
    {
        if (is_string($tanggal) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
            if ($tanggal <= date('Y-m-d')) {
                return $tanggal;
            }
        }

        return date('Y-m-d');
    }

    /**
     * Baca input berat dari form (kg desimal) menjadi sen-kg (1/100 kg).
     *
     * Input berat di form bertipe type="number", jadi browser mengirim
     * angka polos dengan titik sebagai tanda desimal dan tanpa pemisah
     * ribuan. Karena itu titik dan koma sama-sama dibaca sebagai tanda
     * desimal. Nol di ujung desimal tidak masalah ("1.000" berarti 1 kg),
     * tapi angka bukan nol di posisi desimal ketiga atau lebih ditolak
     * (bukan dibulatkan diam-diam).
     *
     * Hasil:
     * - ['status' => 'OK', 'cents' => int, 'pesan' => ''] berat sah.
     * - ['status' => 'KOSONG', 'cents' => 0, 'pesan' => ''] input kosong:
     *   jenis sampah itu tidak jadi dijual, jadi dilewati tanpa error.
     * - ['status' => 'GALAT', 'cents' => 0, 'pesan' => string] input tidak
     *   sah: pemanggil harus menolak dan menampilkan pesan.
     *
     * @param mixed $raw Nilai mentah dari POST
     * @return array
     */
    private function beratKeCents($raw)
    {
        $galat = function ($pesan) {
            return ['status' => 'GALAT', 'cents' => 0, 'pesan' => $pesan];
        };

        if (is_array($raw)) {
            return $galat('harus berupa angka dan tidak boleh negatif.');
        }

        $teks = trim((string) $raw);
        if ($teks === '') {
            return ['status' => 'KOSONG', 'cents' => 0, 'pesan' => ''];
        }

        // Bentuk yang diterima: "12", "12.5", "12,50". Modifier D dipakai
        // supaya karakter di akhir input (mis. baris baru) tidak lolos.
            $cocok = preg_match('/^(\d+)(?:[.,](\d+))?$/D', $teks, $m);

        if (!$cocok) {
            return $galat('harus berupa angka dan tidak boleh negatif.');
        }

        // Nilai yang terlalu besar ditolak agar tidak membebani perhitungan.
        if (strlen($m[1]) > 7 || (int) $m[1] > 1000000) {
            return $galat('nilai beratnya terlalu besar.');
        }

        $bulat = (int) $m[1];
        $pecahan = isset($m[2]) ? $m[2] : '';

        // Desimal maksimal dua angka. Digit setelah angka kedua hanya boleh
        // nol ("1.000" berarti 1 kg); kalau ada digit bukan nol, ditolak.
        if (strlen($pecahan) > 2) {
            if (preg_match('/[1-9]/', substr($pecahan, 2))) {
                return $galat('maksimal dua angka di belakang desimal (misal 2,50).');
            }

            $pecahan = substr($pecahan, 0, 2);
        }

        $pecahan = (int) str_pad($pecahan, 2, '0');

        return [
            'status' => 'OK',
            'cents' => $bulat * 100 + $pecahan,
            'pesan' => '',
        ];
    }

    /**
     * Nama kategori untuk pesan error. Dibaca sekali per kategori lalu
     * disimpan, supaya tidak berulang query dalam satu request.
     *
     * @param int $kategoriId
     * @return string
     */
    private function namaKategori(int $kategoriId): string
    {
        if (!isset($this->namaKategoriCache[$kategoriId])) {
            $kategori = $this->kategoriModel->findById($kategoriId);
            $this->namaKategoriCache[$kategoriId] = $kategori
                ? (string) $kategori['nama']
                : 'kategori #' . $kategoriId;
        }

        return $this->namaKategoriCache[$kategoriId];
    }

    /**
     * Catat audit log sederhana (NFR-08).
     */
    private function catatAudit(string $aksi, string $tabel, int $recordId, ?array $sebelum = null, ?array $sesudah = null): void
    {
        try {
            $stmt = $this->db()->prepare("
                INSERT INTO audit_log (user_id, aksi, tabel, record_id, sebelum, sesudah)
                VALUES (:user_id, :aksi, :tabel, :record_id, :sebelum, :sesudah)
            ");
            $stmt->execute([
                'user_id' => currentUserId(),
                'aksi' => $aksi,
                'tabel' => $tabel,
                'record_id' => $recordId,
                'sebelum' => $sebelum ? json_encode($sebelum) : null,
                'sesudah' => $sesudah ? json_encode($sesudah) : null,
            ]);
        } catch (Throwable $e) {
            error_log('[PengelolaController] audit log gagal: ' . $e->getMessage());
        }
    }

    /**
     * Kirim notifikasi dalam aplikasi.
     */
    private function kirimNotifikasi(?int $userId, string $judul, string $isi, string $tipe = 'INFO'): void
    {
        if (!$userId) {
            return;
        }

        try {
            $this->notifModel->create([
                'user_id' => $userId,
                'judul' => $judul,
                'isi' => $isi,
                'tipe' => $tipe,
            ]);
        } catch (Throwable $e) {
            error_log('[PengelolaController] notifikasi gagal: ' . $e->getMessage());
        }
    }

    public function index(): void
    {
        $this->dashboard();
    }

    /**
     * Dashboard Pengelola
     */
    public function dashboard(): void
    {
        $statsBeli = $this->transaksiBeli->getStatistik($this->bankSampahId);
        $statsJual = $this->transaksiJual->getStatistik($this->bankSampahId);
        $recentBeli = $this->transaksiBeli->getByBankSampah($this->bankSampahId, 5);
        $recentJual = $this->transaksiJual->getByBankSampah($this->bankSampahId, 5);
        $stokList = $this->stokModel->getByBankSampah($this->bankSampahId);
        $bankInfo = $this->bankSampahId ? $this->bankSampahModel->findById($this->bankSampahId) : null;

        // Statistik pencairan
        $statsPencairan = $this->pencairanModel->getStatistik($this->bankSampahId);

        $user = currentUser();
        $flash = getFlashMessage();

        include VIEW_PATH . '/pengelola/dashboard.php';
    }

    /**
     * Transaksi Beli - List
     */
    public function transaksiBeli(): void
    {
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $tanggalFrom = $_GET['tanggal_from'] ?? null;
        $tanggalTo = $_GET['tanggal_to'] ?? null;

        $transaksis = $this->transaksiBeli->getByBankSampah($this->bankSampahId, $perPage, $offset, $tanggalFrom, $tanggalTo);
        $stats = $this->transaksiBeli->getStatistik($this->bankSampahId, $tanggalFrom, $tanggalTo);

        $user = currentUser();
        $flash = getFlashMessage();

        include VIEW_PATH . '/pengelola/transaksi_beli/index.php';
    }

    /**
     * Form Transaksi Beli
     */
    public function transaksiBeliForm(): void
    {
        $user = currentUser();
        $flash = getFlashMessage();
        $errors = [];
        $oldInput = [];

        // Tanggal transaksi menentukan harga yang berlaku (BR-01).
        $tanggal = $this->tanggalTransaksi($_POST['tanggal'] ?? $_GET['tanggal'] ?? null);

        // Peta harga aktif pada tanggal tersebut (dipakai server untuk
        // menghitung ulang subtotal, BR-01).
        $hargaMap = $this->hargaModel->getPetaHarga($this->bankSampahId, $tanggal);

        // Daftar harga yang sama, bentuk baris, untuk mengisi pilihan
        // kategori di form. Tanpa ini dropdown kategori kosong dan
        // transaksi tidak bisa dicatat sama sekali.
        $hargaList = $this->hargaModel->getLatestByBank($this->bankSampahId, $tanggal);

        // Nasabah aktif bank ini (dipakai untuk dropdown).
        $nasabahList = $this->nasabahModel->getAll(null, 0, $this->bankSampahId);
        $nasabahList = array_values(array_filter($nasabahList, function ($n) {
            return ($n['status'] ?? 'AKTIF') === 'AKTIF';
        }));

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->cekToken('pengelola/transaksi-beli/add')) {
                return;
            }
            $result = $this->prosesTransaksiBeli($hargaMap, $tanggal);
            if ($result['success']) {
                return;
            }
            $errors = $result['errors'];
            $oldInput = $_POST;
        }

        // Tanggal transaksi yang dipakai (harga aktif mengikuti tanggal ini, BR-01).
        $tanggalTransaksi = $tanggal;

        include VIEW_PATH . '/pengelola/transaksi_beli/form.php';
    }

    /**
     * Proses transaksi beli.
     * Harga, subtotal, dan total selalu dihitung ulang di server.
     *
     * @param array  $hargaMap
     * @param string $tanggal
     * @return array
     */
        private function prosesTransaksiBeli(array $hargaMap, string $tanggal): array
    {
        $errors = [];

        $tipe = $_POST['tipe_penjual'] ?? '';
        $metode = $_POST['metode_bayar'] ?? '';

        if (!in_array($tipe, ['NASABAH', 'NON_NASABAH'], true)) {
            $errors[] = 'Tipe penjual harus dipilih.';
        }

        if (!in_array($metode, ['TABUNG', 'TUNAI'], true)) {
            $errors[] = 'Metode bayar harus dipilih.';
        }

        // BR-02: non-nasabah selalu dibayar tunai. Permintaan lain ditolak.
        if ($tipe === 'NON_NASABAH' && $metode === 'TABUNG') {
            $errors[] = 'Pembelian dari non-nasabah selalu dibayar tunai.';
        }

        // Nasabah dari POST tidak dipercaya: harus ada, milik bank sampah ini,
        // dan berstatus AKTIF. Tanpa ini saldo nasabah bank lain bisa bertambah
        // lewat permintaan yang disusun sendiri (BR-03, NFR-10).
        $nasabahId = 0;
        if ($tipe === 'NASABAH') {
            $nasabahId = (int) ($_POST['nasabah_id'] ?? 0);

            if ($nasabahId <= 0) {
                $errors[] = 'Nasabah harus dipilih.';
            } else {
                $nasabahDipilih = $this->nasabahModel->findById($nasabahId);

                if (!$this->milikBankIni($nasabahDipilih, 'nasabah')) {
                    $errors[] = 'Nasabah tidak ditemukan atau bukan anggota bank sampah ini.';
                } elseif (($nasabahDipilih['status'] ?? 'AKTIF') !== 'AKTIF') {
                    $errors[] = 'Nasabah tidak aktif, pembelian tidak dapat dicatat.';
                }
            }
        }

        // BR-10: nama penjual non-nasabah bersifat opsional.
        $namaPenjual = trim((string) ($_POST['nama_penjual'] ?? ''));

        $items = [];
        $totalBeratCents = 0;
        $totalNilaiCents = 0;

        $kategoriIds = $_POST['kategori_id'] ?? [];

        foreach ((array) $kategoriIds as $i => $kategoriId) {
            $kategoriId = (int) $kategoriId;

            // Berat dibaca dengan aturan yang sama seperti penjualan:
            // angka polos dari form, titik atau koma sebagai tanda desimal.
            $hasilBerat = $this->beratKeCents($_POST['berat'][$i] ?? '');

            // Nama untuk pesan: tanpa jenis sampah, pesannya menyebut baris.
            $namaBaris = $kategoriId > 0 ? $this->namaKategori($kategoriId) : 'baris ini';

            // Baris tanpa jenis sampah: kalau beratnya juga kosong, baris ini
            // dilewati; kalau beratnya sudah diisi, jenis sampah wajib dipilih.
            if ($kategoriId <= 0) {
                if ($hasilBerat['status'] === 'KOSONG'
                    || ($hasilBerat['status'] === 'OK' && $hasilBerat['cents'] <= 0)) {
                    continue;
                }

                if ($hasilBerat['status'] === 'GALAT') {
                    $errors[] = 'Berat untuk ' . $namaBaris . ' ' . $hasilBerat['pesan'];
                } else {
                    $errors[] = 'Pilih jenis sampah untuk baris yang beratnya sudah diisi.';
                }
                continue;
            }

            // Berat kosong berarti jenis sampah ini tidak jadi dibeli, jadi
            // dilewati tanpa error.
            if ($hasilBerat['status'] === 'KOSONG') {
                continue;
            }

            if ($hasilBerat['status'] === 'GALAT') {
                $errors[] = 'Berat untuk ' . $namaBaris . ' ' . $hasilBerat['pesan'];
                continue;
            }

            $beratCents = (int) $hasilBerat['cents'];

            if ($beratCents <= 0) {
                continue;
            }

            if (!isset($hargaMap[$kategoriId])) {
                $errors[] = 'Kategori ini belum memiliki harga aktif pada tanggal tersebut. Hubungi admin.';
                continue;
            }

            $harga = $hargaMap[$kategoriId];
            $subtotalCents = hitungSubtotalCents($beratCents, $harga['harga_beli']);

            $items[] = [
                'kategori_id' => $kategoriId,
                'berat_cents' => $beratCents,
                'harga_id' => $harga['id'],
                'harga_cents' => $harga['harga_beli'],
                'subtotal_cents' => $subtotalCents,
            ];

            $totalBeratCents += $beratCents;
            $totalNilaiCents += $subtotalCents;
        }

        // Pesan "minimal 1 item" hanya muncul kalau tidak ada alasan lain
        // yang lebih spesifik (mis. harga belum aktif pada tanggal itu).
        if (empty($items) && empty($errors)) {
            $errors[] = 'Minimal harus ada 1 item sampah.';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $data = [
            'bank_sampah_id' => $this->bankSampahId,
            'tipe_penjual' => $tipe,
            'nasabah_id' => $tipe === 'NASABAH' ? $nasabahId : null,
            'nama_penjual' => $tipe === 'NON_NASABAH' ? $namaPenjual : null,
            'metode_bayar' => $tipe === 'NON_NASABAH' ? 'TUNAI' : $metode,
            'tanggal' => $tanggal . ' ' . date('H:i:s'),
            'total_berat_cents' => $totalBeratCents,
            'total_nilai_cents' => $totalNilaiCents,
            'petugas_id' => currentUserId(),
            'catatan' => trim((string) ($_POST['catatan'] ?? '')),
        ];

        $transaksiId = $this->transaksiBeli->create($data, $items);

        if ($transaksiId) {
            setFlashMessage('success', 'Transaksi berhasil disimpan!');
            redirect('pengelola/transaksi-beli');
            return ['success' => true, 'errors' => []];
        }

        return ['success' => false, 'errors' => [
            $this->transaksiBeli->getLastError() ?: 'Gagal menyimpan transaksi.',
        ]];
    }
    /**
     * Detail/bukti transaksi beli (FR-12).
     */
    public function transaksiBeliDetail(): void
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        $transaksi = $id ? $this->transaksiBeli->findById($id) : null;

        if (!$transaksi || !$this->milikBankIni($transaksi, 'transaksi beli')) {
            setFlashMessage('error', 'Transaksi tidak ditemukan atau tidak dapat diakses.');
            redirect('pengelola/transaksi-beli');
            return;
        }

        $user = currentUser();
        $flash = getFlashMessage();

        include VIEW_PATH . '/pengelola/transaksi_beli/detail.php';
    }

    /**
     * Batalkan transaksi beli dengan alasan (BR-08).
     */
    public function transaksiBeliBatal(): void
    {
        if (!$this->hanyaPost('pengelola/transaksi-beli')) {
            return;
        }
        if (!$this->cekToken('pengelola/transaksi-beli')) {
            return;
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $alasan = trim((string) ($_POST['alasan'] ?? ''));

        $transaksi = $id ? $this->transaksiBeli->findById($id) : null;
        if (!$transaksi || !$this->milikBankIni($transaksi, 'transaksi beli')) {
            setFlashMessage('error', 'Transaksi tidak ditemukan.');
            redirect('pengelola/transaksi-beli');
            return;
        }

        if ($alasan === '') {
            setFlashMessage('error', 'Alasan pembatalan wajib diisi.');
            redirect('pengelola/transaksi-beli/detail?id=' . $id);
            return;
        }

        if ($this->transaksiBeli->cancel($id, $alasan, (int) currentUserId())) {
            setFlashMessage('success', 'Transaksi ' . $transaksi['no_transaksi'] . ' dibatalkan. Stok dan saldo sudah dikembalikan.');
        } else {
            setFlashMessage('error', $this->transaksiBeli->getLastError() ?: 'Gagal membatalkan transaksi.');
        }

        redirect('pengelola/transaksi-beli');
    }

    /**
     * Transaksi Jual - List
     */
    public function transaksiJual(): void
    {
        $transaksis = $this->transaksiJual->getByBankSampah($this->bankSampahId, 50);
        $stats = $this->transaksiJual->getStatistik($this->bankSampahId);
        $stokList = $this->stokModel->getByBankSampah($this->bankSampahId);
        $pengepulList = $this->pengepulModel->getAll($this->bankSampahId, 'AKTIF');

        $user = currentUser();
        $flash = getFlashMessage();

        include VIEW_PATH . '/pengelola/transaksi_jual/index.php';
    }

    /**
     * Form Transaksi Jual
     */
    public function transaksiJualForm(): void
    {
        $user = currentUser();
        $flash = getFlashMessage();
        $errors = [];
        $oldInput = [];

        $stokList = $this->stokModel->getByBankSampah($this->bankSampahId);
        $hargaList = $this->hargaModel->getLatestByBank($this->bankSampahId);
        $pengepulList = $this->pengepulModel->getAll($this->bankSampahId, 'AKTIF');

        // Barang yang sudah dipesan tapi belum dibayar reserving stok.
        $reserved = $this->transaksiJual->getReservedByKategori($this->bankSampahId);

        // Peta harga jual aktif per kategori.
        $hargaMap = [];
        foreach ($hargaList as $h) {
            $hargaMap[(int) $h['kategori_id']] = [
                'id' => (int) $h['id'],
                'harga_jual' => (int) toCents($h['harga_jual']),
                'kategori_nama' => $h['kategori_nama'],
            ];
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->cekToken('pengelola/transaksi-jual/add')) {
                return;
            }
            $result = $this->prosesTransaksiJual($hargaMap, $reserved);
            if ($result['success']) {
                return;
            }
            $errors = $result['errors'];
            $oldInput = $_POST;
        }

        include VIEW_PATH . '/pengelola/transaksi_jual/form.php';
    }

    /**
     * Proses transaksi jual.
     */
    private function prosesTransaksiJual(array $hargaMap, array $reserved): array
    {
        $errors = [];

        // Pengepul dari POST tidak dipercaya: harus ada, milik bank sampah ini,
        // dan berstatus AKTIF (sama seperti daftar yang tampil di form).
        $pengepulId = (int) ($_POST['pengepul_id'] ?? 0);
        if ($pengepulId <= 0) {
            $errors[] = 'Pengepul harus dipilih.';
        } else {
            $pengepulDipilih = $this->pengepulModel->findById($pengepulId);

            if (!$this->milikBankIni($pengepulDipilih, 'pengepul')) {
                $errors[] = 'Pengepul tidak ditemukan atau bukan mitra bank sampah ini.';
            } elseif (($pengepulDipilih['status'] ?? 'AKTIF') !== 'AKTIF') {
                $errors[] = 'Pengepul tidak aktif, penjualan tidak dapat dicatat.';
            }
        }

        $items = [];
        

        $kategoriIds = array_values(array_unique(array_map('intval', (array) ($_POST['kategori_id'] ?? []))));

        foreach ((array) $kategoriIds as $i => $kategoriId) {
            if (empty($kategoriId)) {
                continue;
            }

            // Berat dikirim per kategori (name="berat[<kategori_id>]"), bukan per
            // posisi baris. Membaca per indeks posisi membuat berat kategori
            // satu dipakai untuk kategori berikutnya.
            $kategoriId = (int) $kategoriId;

            // Berat kosong atau nol berarti jenis sampah ini tidak jadi
            // dijual, jadi dilewati tanpa error. Form penjualan selalu
            // mengirim semua kategori yang punya stok, sehingga tanpa
            // aturan ini pengelola tidak bisa menjual satu jenis saja.
            $hasilBerat = $this->beratKeCents($_POST['berat'][$kategoriId] ?? '');

            if ($hasilBerat['status'] === 'KOSONG') {
                continue;
            }

            if ($hasilBerat['status'] === 'GALAT') {
                $errors[] = 'Berat untuk kategori ' . $this->namaKategori($kategoriId)
                    . ' ' . $hasilBerat['pesan'];
                continue;
            }

            $beratCents = (int) $hasilBerat['cents'];

            if ($beratCents <= 0) {
                continue;
            }

            // Harga selalu diambil dari server (BR-01). Jenis sampah yang
            // harganya belum diatur membuat penjualan ini ditolak, bukan
            // dilewati, supaya tidak ada barang yang dijual tanpa harga.
            if (!isset($hargaMap[$kategoriId])) {
                $errors[] = 'Harga jual untuk kategori ' . $this->namaKategori($kategoriId)
                    . ' belum diatur. Hubungi admin.';
                continue;
            }

            $harga = $hargaMap[$kategoriId];

            if ((int) $harga['harga_jual'] <= 0) {
                $errors[] = 'Harga jual untuk kategori ' . $harga['kategori_nama']
                    . ' belum diatur. Hubungi admin.';
                continue;
            }

            // BR-07: stok per kategori, dijumlahkan bila kategori sama
            // muncul di beberapa baris.
            $stokTersedia = $this->stokModel->getStokTersediaCents(
                $this->bankSampahId,
                (int) $kategoriId,
                $reserved
            );

            $sudahDiminta = 0;
            foreach ($items as $item) {
                if ((int) $item['kategori_id'] === (int) $kategoriId) {
                    $sudahDiminta += (int) $item['berat_cents'];
                }
            }

            if ($beratCents + $sudahDiminta > $stokTersedia) {
                $errors[] = 'Stok tidak cukup untuk ' . $harga['kategori_nama']
                    . '. Tersedia ' . formatBerat($stokTersedia) . '.';
                continue;
            }

            $subtotalCents = hitungSubtotalCents($beratCents, $harga['harga_jual']);

            $items[] = [
                'kategori_id' => (int) $kategoriId,
                'berat_cents' => $beratCents,
                'harga_id' => $harga['id'],
                'harga_cents' => $harga['harga_jual'],
                'subtotal_cents' => $subtotalCents,
            ];

            $totalBeratCents += $beratCents;
            $totalNilaiCents += $subtotalCents;
        }

        // Pesan "minimal 1 item" hanya muncul kalau tidak ada alasan lain
        // yang lebih spesifik (mis. harga jual belum diatur).
        if (empty($items) && empty($errors)) {
            $errors[] = 'Minimal harus ada 1 item sampah.';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

                // Total berat dan nilai sengaja tidak dikirim: model menghitungnya dari item.
        $data = [
            'bank_sampah_id' => $this->bankSampahId,
            'pengepul_id' => $pengepulId,
            'petugas_id' => currentUserId(),
            // Checkbox "sudah dibayar": yang tidak dicentang tidak terkirim,
            // jadi nilainya diperiksa satu per satu, bukan dengan isset().
            'status_bayar' => in_array((string) ($_POST['status_bayar'] ?? ''), ['1', 'LUNAS'], true)
                ? 'LUNAS'
                : 'BELUM',
            'catatan' => trim((string) ($_POST['catatan'] ?? '')),
        ];

        $transaksiId = $this->transaksiJual->create($data, $items);

        if ($transaksiId) {
            setFlashMessage('success', 'Transaksi jual berhasil disimpan!');
            redirect('pengelola/transaksi-jual');
            return ['success' => true, 'errors' => []];
        }

        return ['success' => false, 'errors' => [
            $this->transaksiJual->getLastError() ?: 'Gagal menyimpan transaksi.',
        ]];
    }

    /**
     * Detail/bukti transaksi jual (FR-12).
     */
    public function transaksiJualDetail(): void
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        $transaksi = $id ? $this->transaksiJual->findById($id) : null;

        if (!$transaksi || !$this->milikBankIni($transaksi, 'transaksi jual')) {
            setFlashMessage('error', 'Transaksi tidak ditemukan atau tidak dapat diakses.');
            redirect('pengelola/transaksi-jual');
            return;
        }

        $user = currentUser();
        $flash = getFlashMessage();

        include VIEW_PATH . '/pengelola/transaksi_jual/detail.php';
    }

    /**
     * Tandai penjualan sudah dibayar (bagian 2.3).
     */
    public function transaksiJualLunas(): void
    {
        if (!$this->hanyaPost('pengelola/transaksi-jual')) {
            return;
        }
        if (!$this->cekToken('pengelola/transaksi-jual')) {
            return;
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        $transaksi = $id ? $this->transaksiJual->findById($id) : null;
        if (!$transaksi || !$this->milikBankIni($transaksi, 'transaksi jual')) {
            setFlashMessage('error', 'Transaksi tidak ditemukan.');
            redirect('pengelola/transaksi-jual');
            return;
        }

        if ($this->transaksiJual->tandaiLunas($id, (int) currentUserId())) {
            setFlashMessage('success', 'Penjualan ' . $transaksi['no_transaksi'] . ' ditandai lunas. Stok keluar dari gudang.');
        } else {
            setFlashMessage('error', $this->transaksiJual->getLastError() ?: 'Gagal menandai lunas.');
        }

        redirect('pengelola/transaksi-jual');
    }

    /**
     * Batalkan transaksi jual dengan alasan (BR-08).
     */
    public function transaksiJualBatal(): void
    {
        if (!$this->hanyaPost('pengelola/transaksi-jual')) {
            return;
        }
        if (!$this->cekToken('pengelola/transaksi-jual')) {
            return;
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $alasan = trim((string) ($_POST['alasan'] ?? ''));

        $transaksi = $id ? $this->transaksiJual->findById($id) : null;
        if (!$transaksi || !$this->milikBankIni($transaksi, 'transaksi jual')) {
            setFlashMessage('error', 'Transaksi tidak ditemukan.');
            redirect('pengelola/transaksi-jual');
            return;
        }

        if ($alasan === '') {
            setFlashMessage('error', 'Alasan pembatalan wajib diisi.');
            redirect('pengelola/transaksi-jual/detail?id=' . $id);
            return;
        }

        if ($this->transaksiJual->cancel($id, $alasan, (int) currentUserId())) {
            setFlashMessage('success', 'Transaksi ' . $transaksi['no_transaksi'] . ' dibatalkan.');
        } else {
            setFlashMessage('error', $this->transaksiJual->getLastError() ?: 'Gagal membatalkan transaksi.');
        }

        redirect('pengelola/transaksi-jual');
    }

    /**
     * Susun baris stok untuk ditampilkan: stok fisik, barang yang sudah
     * dipesan ke pengepul tetapi belum dibayar (reservasi), dan stok yang
     * benar-benar bisa dijual (BR-07).
     *
     * Dipakai oleh halaman Stok dan oleh Laporan (FR-17) supaya angka
     * di keduanya selalu sama.
     *
     * @return array
     */
    private function susunBarisStok(): array
    {
        $stokList = $this->stokModel->getByBankSampah($this->bankSampahId);
        $reserved = $this->transaksiJual->getReservedByKategori($this->bankSampahId);

        $baris = [];
        foreach ($stokList as $s) {
            $kid = (int) $s['kategori_id'];
            $stokCents = (int) toCents($s['berat_kg']);
            $s['stok_cents'] = $stokCents;
            $s['reserved_cents'] = (int) ($reserved[$kid] ?? 0);
            $s['tersedia_cents'] = max(0, $stokCents - $s['reserved_cents']);
            $baris[] = $s;
        }

        return $baris;
    }

    /**
     * Halaman stok sampah (FR-14).
     */
    public function stok(): void
    {
        $baris = $this->susunBarisStok();

        $user = currentUser();
        $flash = getFlashMessage();

        include VIEW_PATH . '/pengelola/stok/index.php';
    }

    /**
     * Daftar Pengajuan Pencairan
     */
    public function pencairan(): void
    {
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $statusFilter = $_GET['status'] ?? null;

        $pengajuans = $this->pencairanModel->getAll($this->bankSampahId, $statusFilter, $perPage, $offset);
        $totalData = $this->pencairanModel->countAll($this->bankSampahId, $statusFilter);
        $totalPages = max(1, ceil($totalData / $perPage));
        $statistik = $this->pencairanModel->getStatistik($this->bankSampahId);

        $user = currentUser();
        $flash = getFlashMessage();

        include VIEW_PATH . '/pengelola/pencairan/index.php';
    }

    /**
     * Detail Pengajuan Pencairan
     */
    public function pencairanDetail(): void
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        $pengajuan = $id ? $this->pencairanModel->findById($id) : null;

        if (!$pengajuan || (int) $pengajuan['bank_sampah_id'] !== (int) $this->bankSampahId) {
            setFlashMessage('error', 'Pengajuan tidak ditemukan.');
            redirect('pengelola/pencairan');
            return;
        }

        // Data saldo nasabah (khususnasabah yang memproses, lihat bagian 9).
        $nasabah = $this->nasabahModel->findById((int) $pengajuan['nasabah_id']);
        $saldoTersedia = $nasabah ? $this->nasabahModel->getSaldoTersedia((int) $nasabah['id']) : 0;

        $user = currentUser();
        $flash = getFlashMessage();

        include VIEW_PATH . '/pengelola/pencairan/detail.php';
    }

    /**
     * Setujui Pengajuan Pencairan
     */
    public function pencairanSetuju(): void
    {
        if (!$this->hanyaPost('pengelola/pencairan')) {
            return;
        }
        if (!$this->cekToken('pengelola/pencairan')) {
            return;
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        $pengajuan = $id ? $this->pencairanModel->findById($id) : null;

        if (!$pengajuan || (int) $pengajuan['bank_sampah_id'] !== (int) $this->bankSampahId) {
            setFlashMessage('error', 'Pengajuan tidak ditemukan.');
            redirect('pengelola/pencairan');
            return;
        }

        if ($pengajuan['status'] !== PengajuanPencairan::STATUS_DIAJUKAN) {
            setFlashMessage('error', 'Pengajuan sudah diproses sebelumnya.');
            redirect('pengelola/pencairan');
            return;
        }

        // Update status menjadi DISETUJUI
        $success = $this->pencairanModel->updateStatus($id, PengajuanPencairan::STATUS_DISETUJUI, currentUserId());

        if ($success) {
            $this->catatAudit(
                'SETUJUI',
                'pengajuan_pencairan',
                $id,
                ['status' => PengajuanPencairan::STATUS_DIAJUKAN],
                ['status' => PengajuanPencairan::STATUS_DISETUJUI]
            );

            $this->kirimNotifikasi(
                !empty($pengajuan['nasabah_user_id']) ? (int) $pengajuan['nasabah_user_id'] : null,
                'Pengajuan pencairan disetujui',
                'Pengajuan ' . $pengajuan['no_pengajuan'] . ' telah disetujui dan siap dicairkan.',
                'PENCAIRAN'
            );

            setFlashMessage('success', 'Pengajuan ' . $pengajuan['no_pengajuan'] . ' telah disetujui. Silakan proses pencairan.');
        } else {
            setFlashMessage('error', 'Gagal memproses pengajuan.');
        }

        redirect('pengelola/pencairan');
    }

    /**
     * Tolak Pengajuan Pencairan
     */
    public function pencairanTolak(): void
    {
        if (!$this->hanyaPost('pengelola/pencairan')) {
            return;
        }
        if (!$this->cekToken('pengelola/pencairan')) {
            return;
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $alasan = trim((string) ($_POST['alasan'] ?? ''));

        $pengajuan = $id ? $this->pencairanModel->findById($id) : null;

        if (!$pengajuan || (int) $pengajuan['bank_sampah_id'] !== (int) $this->bankSampahId) {
            setFlashMessage('error', 'Pengajuan tidak ditemukan.');
            redirect('pengelola/pencairan');
            return;
        }

        if ($pengajuan['status'] !== PengajuanPencairan::STATUS_DIAJUKAN) {
            setFlashMessage('error', 'Pengajuan sudah diproses sebelumnya.');
            redirect('pengelola/pencairan');
            return;
        }

        if ($alasan === '') {
            setFlashMessage('error', 'Alasan penolakan harus diisi.');
            redirect('pengelola/pencairan/detail?id=' . $id);
            return;
        }

        // Update status menjadi DITOLAK
        $success = $this->pencairanModel->updateStatus($id, PengajuanPencairan::STATUS_DITOLAK, currentUserId(), $alasan);

        if ($success) {
            $this->catatAudit(
                'TOLAK',
                'pengajuan_pencairan',
                $id,
                ['status' => PengajuanPencairan::STATUS_DIAJUKAN],
                ['status' => PengajuanPencairan::STATUS_DITOLAK, 'alasan' => $alasan]
            );

            $this->kirimNotifikasi(
                !empty($pengajuan['nasabah_user_id']) ? (int) $pengajuan['nasabah_user_id'] : null,
                'Pengajuan pencairan ditolak',
                'Pengajuan ' . $pengajuan['no_pengajuan'] . ' ditolak. Alasan: ' . $alasan,
                'PENCAIRAN'
            );

            setFlashMessage('success', 'Pengajuan ' . $pengajuan['no_pengajuan'] . ' telah ditolak.');
        } else {
            setFlashMessage('error', 'Gagal memproses pengajuan.');
        }

        redirect('pengelola/pencairan');
    }

    /**
     * Proses Pencairan (tandai sudah dicairkan, saldo turun saat itu).
     */
    public function pencairanCairkan(): void
    {
        if (!$this->hanyaPost('pengelola/pencairan')) {
            return;
        }
        if (!$this->cekToken('pengelola/pencairan')) {
            return;
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        $pengajuan = $id ? $this->pencairanModel->findById($id) : null;

        if (!$pengajuan || (int) $pengajuan['bank_sampah_id'] !== (int) $this->bankSampahId) {
            setFlashMessage('error', 'Pengajuan tidak ditemukan.');
            redirect('pengelola/pencairan');
            return;
        }

        if ($pengajuan['status'] !== PengajuanPencairan::STATUS_DISETUJUI) {
            setFlashMessage('error', 'Pengajuan belum disetujui atau sudah dicairkan.');
            redirect('pengelola/pencairan');
            return;
        }

        // Pencairan berjalan atomik: saldo nasabah dicek ulang di server,
        // baris nasabah dikunci, mutasi dicatat negatif (BR-03, BR-06).
        if ($this->pencairanModel->cairkan($id, (int) currentUserId())) {
            setFlashMessage('success', 'Pengajuan ' . $pengajuan['no_pengajuan'] . ' telah dicairkan dan saldo nasabah dikurangi.');
        } else {
            setFlashMessage('error', $this->pencairanModel->getLastError() ?: 'Gagal memproses pencairan.');
        }

        redirect('pengelola/pencairan');
    }

    /**
     * Kelola Data Nasabah
     *
     * Pencarian, filter status, dan paginasi dijalankan di server supaya
     * angka ringkasan (jumlah nasabah, saldo) selalu mencakup seluruh data,
     * bukan hanya baris yang sedang ditampilkan.
     */
    public function nasabah(): void
    {
        $cari = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
        $cari = function_exists('mb_substr') ? mb_substr($cari, 0, 60) : substr($cari, 0, 60);

        $status = isset($_GET['status']) ? strtoupper(trim((string) $_GET['status'])) : '';
        if ($status !== 'AKTIF' && $status !== 'NONAKTIF') {
            $status = '';
        }

        $perHalaman = 20;
        $totalData = $this->nasabahModel->countPaginasi($this->bankSampahId, $cari, $status);
        $totalPages = max(1, (int) ceil($totalData / $perHalaman));

        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        if ($page < 1) {
            $page = 1;
        }
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $nasabahList = $this->nasabahModel->getPaginasi(
            $this->bankSampahId,
            $cari,
            $status,
            $perHalaman,
            ($page - 1) * $perHalaman
        );

        // Saldo yang bisa dicairkan per nasabah (dikurangi pengajuan yang tertahan).
        $petaTersedia = $this->nasabahModel->getSaldoTersediaPeta(array_column($nasabahList, 'id'));
        foreach ($nasabahList as $i => $n) {
            $nasabahList[$i]['saldo_tersedia'] = $petaTersedia[(int) $n['id']] ?? (float) $n['saldo'];
        }

        $ringkasan = $this->nasabahModel->getRingkasan($this->bankSampahId, $cari, $status);
        $totalNasabah = $ringkasan['total'];
        $totalAktif = $ringkasan['aktif'];
        $totalNonaktif = $ringkasan['nonaktif'];
        $totalSaldo = $ringkasan['saldo'];

        $user = currentUser();
        $flash = getFlashMessage();

        include VIEW_PATH . '/pengelola/nasabah/index.php';
    }

    /**
     * Saran nomor anggota untuk nasabah baru.
     *
     * Memakai format yang sama dengan data yang sudah ada (N-001, N-002, dst).
     * Bila nomornya sudah dipakai, nomor berikutnya dicoba sampai ketemu
     * yang kosong supaya pengelola tidak perlu mengetik ulang.
     *
     * @return string
     */
    private function saranNomorAnggota(): string
    {
        $jumlah = $this->nasabahModel->countPaginasi($this->bankSampahId);

        for ($i = $jumlah + 1; $i <= $jumlah + 50; $i++) {
            $kandidat = 'N-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            if (!$this->nasabahModel->noAnggotaExists($kandidat, null, $this->bankSampahId)) {
                return $kandidat;
            }
        }

        return 'N-' . str_pad((string) ($jumlah + 1), 3, '0', STR_PAD_LEFT);
    }

    /**
     * Form daftar/ubah nasabah (FR-02).
     */
    public function nasabahForm(): void
    {
        // Id bisa datang dari GET (tautan edit) atau POST (form dikirim ke URL yang sama).
        $id = 0;
        if (isset($_GET['id'])) {
            $id = (int) $_GET['id'];
        } elseif (isset($_POST['id'])) {
            $id = (int) $_POST['id'];
        }

        $editNasabah = null;

        if ($id) {
            $editNasabah = $this->nasabahModel->findById($id);
            if (!$editNasabah || (int) $editNasabah['bank_sampah_id'] !== (int) $this->bankSampahId) {
                setFlashMessage('error', 'Nasabah tidak ditemukan.');
                redirect('pengelola/nasabah');
                return;
            }
        }

        $user = currentUser();
        $flash = getFlashMessage();
        $errors = [];
        $oldInput = [];

        // Nomor anggota berikutnya (hanya saran, boleh diubah pengelola).
        $suggestNoAnggota = $this->saranNomorAnggota();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->cekToken('pengelola/nasabah')) {
                return;
            }
            $errors = $this->prosesNasabahSave($id);
            if (empty($errors)) {
                return;
            }
            $oldInput = $_POST;
        }

        include VIEW_PATH . '/pengelola/nasabah/form.php';
    }

    /**
     * Simpan data nasabah (baru + buat akun, atau ubah data).
     */
    private function prosesNasabahSave($id): array
    {
        $errors = [];

        $nama = trim((string) ($_POST['nama'] ?? ''));
        $noHp = preg_replace('/[\s\-\+]/', '', trim((string) ($_POST['no_hp'] ?? '')));
        $email = trim((string) ($_POST['email'] ?? ''));
        $noAnggota = trim((string) ($_POST['no_anggota'] ?? ''));
        $nik = preg_replace('/[\s\-]/', '', trim((string) ($_POST['nik'] ?? '')));
        $tipe = strtoupper(trim((string) ($_POST['tipe'] ?? 'PERORANGAN'))) === 'INSTANSI'
            ? 'INSTANSI'
            : 'PERORANGAN';
        $status = strtoupper(trim((string) ($_POST['status'] ?? 'AKTIF'))) === 'NONAKTIF'
            ? 'NONAKTIF'
            : 'AKTIF';

        if ($nama === '') {
            $errors[] = 'Nama wajib diisi.';
        } elseif (function_exists('mb_strlen') ? mb_strlen($nama) > 100 : strlen($nama) > 100) {
            $errors[] = 'Nama maksimal 100 karakter.';
        }

        if ($noHp === '' || !ctype_digit($noHp) || strlen($noHp) < 9 || strlen($noHp) > 15) {
            $errors[] = 'Nomor HP harus berupa angka 9 sampai 15 digit.';
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Format email tidak valid.';
        }

        if ($noAnggota === '') {
            $errors[] = 'Nomor anggota wajib diisi.';
        } elseif (function_exists('mb_strlen') ? mb_strlen($noAnggota) > 20 : strlen($noAnggota) > 20) {
            $errors[] = 'Nomor anggota maksimal 20 karakter.';
        } elseif ($this->nasabahModel->noAnggotaExists($noAnggota, $id ? (int) $id : null, $this->bankSampahId)) {
            $errors[] = 'Nomor anggota "' . $noAnggota . '" sudah dipakai nasabah lain.';
        }

        if ($noHp !== '' && $this->nasabahModel->noHpExists($noHp, $id ? (int) $id : null, $this->bankSampahId)) {
            $errors[] = 'Nomor HP tersebut sudah dipakai nasabah lain.';
        }

        if ($nik !== '' && !ctype_digit($nik)) {
            $errors[] = 'NIK harus berupa angka.';
        }

        foreach (['rt' => 'RT', 'rw' => 'RW'] as $k => $label) {
            $v = trim((string) ($_POST[$k] ?? ''));
            if ($v !== '' && !ctype_digit($v)) {
                $errors[] = $label . ' harus berupa angka.';
            }
        }

        $noRekening = preg_replace('/[\s\-]/', '', trim((string) ($_POST['no_rekening'] ?? '')));
        if ($noRekening !== '' && !ctype_digit($noRekening)) {
            $errors[] = 'Nomor rekening harus berupa angka.';
        }

        // Apakah akun aplikasi diminta?
        $buatAkun = !empty($_POST['buat_akun']);
        $password = (string) ($_POST['password'] ?? '');

        $nasabah = $id ? $this->nasabahModel->findById($id) : null;
        $punyaAkun = $nasabah && !empty($nasabah['user_id']);

        // Akun baru harus punya kata sandi. Akun yang sudah ada cukup
        // kata sandi baru bila pengelola memang mengubahnya.
        if (!$punyaAkun && $buatAkun && $password === '') {
            $errors[] = 'Kata sandi wajib diisi untuk membuat akun aplikasi.';
        }
        if ($password !== '' && strlen($password) < 6) {
            $errors[] = 'Kata sandi minimal 6 karakter.';
        }

        if (!empty($errors)) {
            return $errors;
        }

        $data = [
            'bank_sampah_id' => $this->bankSampahId,
            'no_anggota' => $noAnggota,
            'nama' => $nama,
            'no_hp' => $noHp,
            'nik' => $nik,
            'alamat' => trim((string) ($_POST['alamat'] ?? '')),
            'rt' => trim((string) ($_POST['rt'] ?? '')),
            'rw' => trim((string) ($_POST['rw'] ?? '')),
            'tipe' => $tipe,
            'nama_bank' => trim((string) ($_POST['nama_bank'] ?? '')),
            'no_rekening' => $noRekening,
            'email' => $email !== '' ? $email : null,
            'status' => $status,
        ];

        if ($id) {
            $sebelum = [
                'nama' => $nasabah['nama'] ?? null,
                'no_anggota' => $nasabah['no_anggota'] ?? null,
                'status' => $nasabah['status'] ?? null,
            ];

            if (!$this->nasabahModel->update($id, $data)) {
                return ['Gagal menyimpan data nasabah. Silakan coba lagi.'];
            }

            $userModel = new User();

            if ($punyaAkun) {
                // Sinkronkan nama, nomor HP, dan email ke akun Nasabah.
                // Email ikut ditulis ke tabel nasabah supaya keduanya tidak berbeda.
                $userModel->update((int) $nasabah['user_id'], [
                    'nama' => $nama,
                    'no_hp' => $noHp,
                    'email' => $email !== '' ? $email : null,
                ]);

                if ($password !== '') {
                    $userModel->update((int) $nasabah['user_id'], [
                        'password_hash' => User::hashPassword($password),
                    ]);
                }

                // Nasabah yang diaktifkan kembali harus bisa masuk juga.
                if ($status === 'AKTIF') {
                    $userModel->update((int) $nasabah['user_id'], ['status' => 'AKTIF']);
                }
            } elseif ($buatAkun) {
                // Nasabah lama yang belum punya akun: buatkan sekarang.
                $userId = $this->buatAkunNasabah((int) $id, $data, $password);
                if (!$userId) {
                    return ['Data nasabah tersimpan, tetapi akun aplikasi gagal dibuat. Silakan coba lagi.'];
                }
            }

            $this->catatAudit('UPDATE', 'nasabah', $id, $sebelum, [
                'nama' => $nama,
                'no_anggota' => $noAnggota,
                'status' => $status,
            ]);

            setFlashMessage('success', 'Data nasabah berhasil diperbarui.');
            redirect('pengelola/nasabah');
            return [];
        }

        // Nasabah baru.
        if ($buatAkun) {
            $hasil = $this->nasabahModel->createDenganAkun([
                'nama' => $nama,
                'no_hp' => $noHp,
                'email' => $email !== '' ? $email : null,
                'password_hash' => User::hashPassword($password),
                'bank_sampah_id' => $this->bankSampahId,
                'no_anggota' => $noAnggota,
                'nik' => $nik,
                'alamat' => $data['alamat'],
                'rt' => $data['rt'],
                'rw' => $data['rw'],
                'tipe' => $tipe,
                'nama_bank' => $data['nama_bank'],
                'no_rekening' => $noRekening,
                'tgl_daftar' => date('Y-m-d'),
            ]);

            if ($hasil === false) {
                return ['Gagal menyimpan data nasabah. Periksa kembali nomor HP dan nomor anggota.'];
            }

            $this->catatAudit('INSERT', 'nasabah', (int) $hasil['id'], null, [
                'nama' => $nama,
                'no_anggota' => $noAnggota,
                'user_id' => (int) $hasil['user_id'],
            ]);

            setFlashMessage('success', 'Nasabah berhasil didaftarkan beserta akunnya. Sampaikan kata sandinya ke nasabah.');
            redirect('pengelola/nasabah');
            return [];
        }

        // Tanpa akun aplikasi: nasabah tetap bisa masuk ke daftar transaksi.
        $nasabahId = $this->nasabahModel->createTanpaAkun($data + ['tgl_daftar' => date('Y-m-d')]);
        if ($nasabahId === false) {
            return ['Gagal menyimpan data nasabah. Silakan coba lagi.'];
        }

        $this->catatAudit('INSERT', 'nasabah', (int) $nasabahId, null, [
            'nama' => $nama,
            'no_anggota' => $noAnggota,
        ]);

        setFlashMessage('success', 'Nasabah berhasil didaftarkan. Akun aplikasi bisa dibuat nanti lewat menu Ubah.');
        redirect('pengelola/nasabah');
        return [];
    }

    /**
     * Buat akun aplikasi untuk nasabah yang belum punya akun.
     *
     * @param int    $nasabahId
     * @param array  $data Data nasabah
     * @param string $password Kata sandi awal
     * @return int|false Id user, atau false bila gagal
     */
    private function buatAkunNasabah(int $nasabahId, array $data, string $password)
    {
        try {
            $userModel = new User();
            $userId = $userModel->createDenganId([
                'nama' => $data['nama'],
                'no_hp' => $data['no_hp'],
                'email' => !empty($data['email']) ? $data['email'] : null,
                'password_hash' => User::hashPassword($password),
                'role' => 'NASABAH',
                'status' => 'AKTIF',
                'desa_id' => $_SESSION['user_desa_id'] ?? null,
                'bank_sampah_id' => $this->bankSampahId,
            ]);

            if (!$userId) {
                return false;
            }

            $stmt = $this->db()->prepare('UPDATE nasabah SET user_id = :user_id WHERE id = :id');
            $stmt->execute(['user_id' => (int) $userId, 'id' => $nasabahId]);

            return (int) $userId;
        } catch (Throwable $e) {
            error_log('[PengelolaController] buatAkunNasabah gagal: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Nonaktifkan nasabah (tidak menghapus riwayat, BR-08).
     */
    public function nasabahNonaktifkan(): void
    {
        if (!$this->hanyaPost('pengelola/nasabah')) {
            return;
        }
        if (!$this->cekToken('pengelola/nasabah')) {
            return;
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        $nasabah = $id ? $this->nasabahModel->findById($id) : null;
        if (!$nasabah || (int) $nasabah['bank_sampah_id'] !== (int) $this->bankSampahId) {
            setFlashMessage('error', 'Nasabah tidak ditemukan.');
            redirect('pengelola/nasabah');
            return;
        }

        $sebelum = strtoupper((string) ($nasabah['status'] ?? 'AKTIF'));

        if ($sebelum === 'NONAKTIF') {
            setFlashMessage('info', 'Nasabah ' . $nasabah['nama'] . ' sudah tidak aktif.');
            redirect('pengelola/nasabah');
            return;
        }

        if (!$this->nasabahModel->nonaktifkan($id)) {
            setFlashMessage('error', 'Gagal menonaktifkan nasabah. Silakan coba lagi.');
            redirect('pengelola/nasabah');
            return;
        }

        // Akunnya ikut dinonaktifkan supaya tidak bisa masuk.
        if (!empty($nasabah['user_id'])) {
            $userModel = new User();
            $userModel->update((int) $nasabah['user_id'], ['status' => 'NONAKTIF']);
        }

        $this->catatAudit('NONAKTIFKAN', 'nasabah', $id, ['status' => $sebelum], ['status' => 'NONAKTIF']);

        setFlashMessage('success', 'Nasabah ' . $nasabah['nama'] . ' dinonaktifkan. Riwayat transaksinya tetap tersimpan.');
        redirect('pengelola/nasabah');
    }

    /**
     * Aktifkan kembali nasabah yang sebelumnya dinonaktifkan.
     */
    public function nasabahAktifkan(): void
    {
        if (!$this->hanyaPost('pengelola/nasabah')) {
            return;
        }
        if (!$this->cekToken('pengelola/nasabah')) {
            return;
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        $nasabah = $id ? $this->nasabahModel->findById($id) : null;
        if (!$nasabah || (int) $nasabah['bank_sampah_id'] !== (int) $this->bankSampahId) {
            setFlashMessage('error', 'Nasabah tidak ditemukan.');
            redirect('pengelola/nasabah');
            return;
        }

        $sebelum = strtoupper((string) ($nasabah['status'] ?? 'AKTIF'));

        if ($sebelum === 'AKTIF') {
            setFlashMessage('info', 'Nasabah ' . $nasabah['nama'] . ' sudah aktif.');
            redirect('pengelola/nasabah');
            return;
        }

        if (!$this->nasabahModel->aktifkan($id)) {
            setFlashMessage('error', 'Gagal mengaktifkan nasabah. Silakan coba lagi.');
            redirect('pengelola/nasabah');
            return;
        }

        // Akunnya ikut diaktifkan supaya bisa masuk lagi.
        if (!empty($nasabah['user_id'])) {
            $userModel = new User();
            $userModel->update((int) $nasabah['user_id'], ['status' => 'AKTIF']);
        }

        $this->catatAudit('AKTIFKAN', 'nasabah', $id, ['status' => $sebelum], ['status' => 'AKTIF']);

        setFlashMessage('success', 'Nasabah ' . $nasabah['nama'] . ' diaktifkan kembali.');
        redirect('pengelola/nasabah');
    }

    /**
     * Daftar Harga Sampah (hanya lihat).
     *
     * Pengelolaan harga dan kategori menjadi tanggung jawab Admin (FR-19),
     * sesuai tabel peran pada PROSES_BISNIS.md bagian 1.
     */
    public function harga(): void
    {
        $hargaList = $this->hargaModel->getByBankSampah($this->bankSampahId, null);
        $hargaAktif = $this->hargaModel->getPetaHarga($this->bankSampahId, date('Y-m-d'));

        $user = currentUser();
        $flash = getFlashMessage();

        include VIEW_PATH . '/pengelola/harga/index.php';
    }

    /**
     * Kelola Pengepul
     */
    public function pengepul(): void
    {
        $pengepulList = $this->pengepulModel->getAll($this->bankSampahId);

        $user = currentUser();
        $flash = getFlashMessage();

        include VIEW_PATH . '/pengelola/pengepul/index.php';
    }

    /**
     * Form Pengepul
     */
    public function pengepulForm(): void
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $editPengepul = null;

        if ($id) {
            $editPengepul = $this->pengepulModel->findById($id);
            if (!$editPengepul) {
                setFlashMessage('error', 'Pengepul tidak ditemukan.');
                redirect('pengelola/pengepul');
                return;
            }

            if (!$this->milikBankIni($editPengepul, 'pengepul')) {
                setFlashMessage('error', 'Anda tidak memiliki akses ke pengepul ini.');
                redirect('pengelola/pengepul');
                return;
            }
        }

        $user = currentUser();
        $flash = getFlashMessage();
        $errors = [];
        $oldInput = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->cekToken('pengelola/pengepul')) {
                return;
            }
            $errors = $this->prosesPengepulSave($id);
            if (empty($errors)) {
                return;
            }
            $oldInput = $_POST;
        }

        include VIEW_PATH . '/pengelola/pengepul/form.php';
    }

    private function prosesPengepulSave($id): array
    {
        $errors = [];

        if (trim((string) ($_POST['nama'] ?? '')) === '') {
            $errors[] = 'Nama pengepul harus diisi.';
        }

        // Whitelist status: nilai di luar daftar ditolak, bukan disimpan.
        $status = (string) ($_POST['status'] ?? 'AKTIF');
        if (!in_array($status, ['AKTIF', 'NONAKTIF'], true)) {
            $errors[] = 'Status pengepul tidak valid.';
        }

        if (!empty($errors)) {
            return $errors;
        }

        $data = [
            'bank_sampah_id' => $this->bankSampahId,
            'nama' => trim((string) $_POST['nama']),
            'no_hp' => trim((string) ($_POST['no_hp'] ?? '')),
            'alamat' => trim((string) ($_POST['alamat'] ?? '')),
            'status' => $status,
        ];

        if ($id) {
            $sebelum = $this->pengepulModel->findById((int) $id);
            $this->pengepulModel->update($id, $data);
            $this->catatAudit('UPDATE', 'pengepul', (int) $id, $sebelum, $data);
            setFlashMessage('success', 'Pengepul berhasil diupdate.');
        } else {
            $this->pengepulModel->create($data);
            $pengepulBaru = $this->db()->lastInsertId();
            $this->catatAudit('INSERT', 'pengepul', (int) $pengepulBaru, null, $data);
            setFlashMessage('success', 'Pengepul berhasil ditambahkan.');
        }

        redirect('pengelola/pengepul');
        return [];
    }

    /**
     * Nonaktifkan pengepul (riwayat transaksi tidak dihapus, BR-08).
     */
    public function pengepulNonaktifkan(): void
    {
        if (!$this->hanyaPost('pengelola/pengepul')) {
            return;
        }
        if (!$this->cekToken('pengelola/pengepul')) {
            return;
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        $pengepul = $id ? $this->pengepulModel->findById($id) : null;

        if (!$pengepul) {
            setFlashMessage('error', 'Pengepul tidak ditemukan.');
            redirect('pengelola/pengepul');
            return;
        }

        if (!$this->milikBankIni($pengepul, 'pengepul')) {
            setFlashMessage('error', 'Anda tidak memiliki akses ke pengepul ini.');
            redirect('pengelola/pengepul');
            return;
        }

        $this->pengepulModel->delete($id);
        $this->catatAudit(
            'NONAKTIFKAN',
            'pengepul',
            $id,
            ['status' => $pengepul['status'] ?? 'AKTIF'],
            ['status' => 'NONAKTIF']
        );

        setFlashMessage('success', 'Pengepul dinonaktifkan. Riwayat transaksinya tetap tersimpan.');
        redirect('pengelola/pengepul');
    }

    /**
     * Laporan operasional (FR-17).
     *
     * Periode diambil dari GET dan dipakai untuk pembelian, penjualan, dan
     * pencairan, supaya angka di layar sama dengan angka di PDF.
     * Laporan stok memakai posisi stok saat ini, karena stok adalah
     * saldo kumulatif, bukan transaksi per tanggal.
     */
    public function laporan(): void
    {
        $tanggalFrom = $this->tanggalFilter($_GET['tanggal_from'] ?? null);
        $tanggalTo = $this->tanggalFilter($_GET['tanggal_to'] ?? null);

        $statsBeli = $this->transaksiBeli->getStatistik($this->bankSampahId, $tanggalFrom, $tanggalTo);
        $statsJual = $this->transaksiJual->getStatistik($this->bankSampahId, $tanggalFrom, $tanggalTo);

        // Laporan stok: posisi gudang saat ini.
        $barisStok = $this->susunBarisStok();

        // Laporan pencairan: pengajuan yang masuk pada periode yang sama.
        $statsPencairan = $this->pencairanModel->getStatistikPeriode(
            $this->bankSampahId,
            $tanggalFrom,
            $tanggalTo
        );

        $user = currentUser();
        $flash = getFlashMessage();

        include VIEW_PATH . '/pengelola/laporan/index.php';
    }

    /**
     * Unduh laporan operasional dalam PDF (FR-17).
     */
    public function laporanExportPdf(): void
    {
        $tanggalFrom = $this->tanggalFilter($_GET['tanggal_from'] ?? null);
        $tanggalTo = $this->tanggalFilter($_GET['tanggal_to'] ?? null);

        $statsBeli = $this->transaksiBeli->getStatistik($this->bankSampahId, $tanggalFrom, $tanggalTo);
        $statsJual = $this->transaksiJual->getStatistik($this->bankSampahId, $tanggalFrom, $tanggalTo);
        $barisStok = $this->susunBarisStok();
        $statsPencairan = $this->pencairanModel->getStatistikPeriode(
            $this->bankSampahId,
            $tanggalFrom,
            $tanggalTo
        );

        $bankInfo = $this->bankSampahId ? $this->bankSampahModel->findById($this->bankSampahId) : null;
        $bankName = is_array($bankInfo) ? (string) ($bankInfo['nama'] ?? '') : '';

        require_once LIB_PATH . '/LaporanPdf.php';

        $pdf = new LaporanPdf();
        $pdf->generateLaporanOperasional(
            $statsBeli,
            $statsJual,
            $barisStok,
            $statsPencairan,
            $tanggalFrom,
            $tanggalTo,
            $bankName
        );
        exit;
    }

    /**
     * Unduh laporan pencairan dalam PDF (FR-17).
     */
    public function pencairanExportPdf(): void
    {
        $statusFilter = $_GET['status'] ?? null;

        $pengajuans = $this->pencairanModel->getAll($this->bankSampahId, $statusFilter, 500, 0);
        $statistik = $this->pencairanModel->getStatistik($this->bankSampahId);

        $bankInfo = $this->bankSampahId ? $this->bankSampahModel->findById($this->bankSampahId) : null;
        $bankName = is_array($bankInfo) ? (string) ($bankInfo['nama'] ?? '') : '';

        require_once LIB_PATH . '/LaporanPdf.php';

        $pdf = new LaporanPdf();
        $pdf->generateLaporanPencairan($pengajuans, $statistik, $bankName);
        exit;
    }

    /**
     * Tanggal filter laporan (YYYY-MM-DD). Nilai kosong berarti tanpa batas.
     *
     * @param string|null $tanggal
     * @return string|null
     */
    private function tanggalFilter($tanggal): ?string
    {
        if (!is_string($tanggal)) {
            return null;
        }

        $tanggal = trim($tanggal);
        if ($tanggal === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
            return null;
        }

        return $tanggal;
    }

    // __APPEND__
}