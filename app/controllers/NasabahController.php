<?php
/**
 * Nasabah Controller
 *
 * Controller untuk modul Nasabah
 * Compatible dengan PHP 7.3+
 */

class NasabahController
{
    /** @var Nasabah */
    private $nasabahModel;

    /** @var MutasiSaldo */
    private $mutasiModel;

    /** @var PengajuanPencairan */
    private $pencairanModel;

    /** @var Notifikasi */
    private $notifikasiModel;

    public function __construct()
    {
        // Require login untuk semua method
        requireLogin();
        // Require role NASABAH
        requireRole(['NASABAH'], 'dashboard');
            $this->notifikasiModel = null;
        try {
            $this->notifikasiModel = new Notifikasi();
        } catch (\Throwable $e) {
            error_log('Notifikasi tidak dapat dimuat: ' . $e->getMessage());
        }

        // Load models
        require_once MODEL_PATH . '/Nasabah.php';
        require_once MODEL_PATH . '/MutasiSaldo.php';
        require_once MODEL_PATH . '/PengajuanPencairan.php';
        require_once MODEL_PATH . '/Notifikasi.php';

        $this->notifikasiModel = new Notifikasi();
        $this->nasabahModel = new Nasabah();
        $this->mutasiModel = new MutasiSaldo();
        $this->pencairanModel = new PengajuanPencairan();
    }

    /**
     * Get data Nasabah dari session user_id
     * Mengambil dari database untuk memastikan data valid
     *
     * @return array|null
     */
    private function getCurrentNasabah()
    {
        $userId = currentUserId();
        if (!$userId) {
            return null;
        }
        return $this->nasabahModel->findByUserId($userId);
    }

    /**
     * Dashboard - Tampilkan ringkasan saldo dan aktivitas terbaru
     */
    public function dashboard(): void
    {
        $nasabah = $this->getCurrentNasabah();

        if (!$nasabah) {
            setFlashMessage('error', 'Data akun tidak ditemukan.');
            redirect('logout');
            return;
        }

        $saldo = $this->nasabahModel->getSaldo($nasabah['id']);
        $saldoTersedia = $this->nasabahModel->getSaldoTersedia($nasabah['id']);
        $riwayatTerbaru = $this->mutasiModel->getRiwayat($nasabah['id'], 5);

        // Ambil pengajuan aktif
        $pengajuanAktif = $this->pencairanModel->getAktifByNasabah($nasabah['id']);
        $totalTerikat = $this->pencairanModel->getTotalTerikat($nasabah['id']);

        $user = currentUser();
        $flash = getFlashMessage();

        include VIEW_PATH . '/nasabah/dashboard.php';
    }

    /**
     * Riwayat - Tampilkan semua riwayat mutasi saldo
     */
    public function riwayat(): void
    {
        $nasabah = $this->getCurrentNasabah();

        if (!$nasabah) {
            setFlashMessage('error', 'Data akun tidak ditemukan.');
            redirect('logout');
            return;
        }

        // Pagination
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        // Ambil data
        $riwayat = $this->mutasiModel->getRiwayatLengkap($nasabah['id'], $perPage, $offset);
        $totalData = $this->mutasiModel->countRiwayat($nasabah['id']);
        $totalPages = (int) max(1, ceil($totalData / $perPage));

        $user = currentUser();
        $flash = getFlashMessage();

        include VIEW_PATH . '/nasabah/riwayat.php';
    }

    /**
     * Cairkan - Form dan proses pengajuan pencairan
     */
    public function cairkan(): void
    {
        $nasabah = $this->getCurrentNasabah();

        if (!$nasabah) {
            setFlashMessage('error', 'Data akun tidak ditemukan.');
            redirect('logout');
            return;
        }

        $saldo = $this->nasabahModel->getSaldo($nasabah['id']);
        $saldoTersedia = $this->nasabahModel->getSaldoTersedia($nasabah['id']);

        // Ambil pengajuan aktif
        $pengajuanAktif = $this->pencairanModel->getAktifByNasabah($nasabah['id']);
        $totalTerikat = $this->pencairanModel->getTotalTerikat($nasabah['id']);

        $user = currentUser();
        $flash = getFlashMessage();
        $errors = [];
        $oldInput = [];

        // Proses form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = $this->prosesPengajuan($nasabah, $saldoTersedia, $oldInput);
        }

        // Jika tidak ada error dan berhasil
        if (empty($errors) && isset($_POST['submit'])) {
            // Akan redirect, tidak perlu render view
            return;
        }

        $notifikasi = [];
        $jumlahBelumDibaca = 0;
        
        if ($this->notifikasiModel !== null && method_exists($this->notifikasiModel, 'terbaru')) {
        $notifikasi = $this->notifikasiModel->terbaru((int) currentUserId(), 8);
        $jumlahBelumDibaca = $this->notifikasiModel->hitungBelumDibaca((int) currentUserId());
}
        include VIEW_PATH . '/nasabah/cairkan.php';
    }

    /**
     * Proses pengajuan pencairan
     *
     * @param array $nasabah
     * @param float $saldoTersedia
     * @param array $oldInput
     * @return array Error messages
     */
    private function prosesPengajuan(array $nasabah, float $saldoTersedia, array &$oldInput): array
    {
        // Ambil data dari form
        $oldInput = [
            'jumlah' => $_POST['jumlah'] ?? '',
            'metode' => $_POST['metode'] ?? '',
            'tujuan_transfer' => $_POST['tujuan_transfer'] ?? ''
        ];

        // Normalisasi jumlah dari format Indonesia (100.000) ke numeric (100000)
        // str_replace: hapus titik thousand separator, ubah koma ke titik desimal
        $jumlah = (float) str_replace(['.', ','], ['', '.'], $oldInput['jumlah']);

        // Minimal pencairan
        if ($jumlah < PengajuanPencairan::MINIMAL_PENCAIRAN) {
            return ['Jumlah minimal pencairan adalah Rp ' . number_format(PengajuanPencairan::MINIMAL_PENCAIRAN, 0, ',', '.')];
        }

        // Validasi tidak melebihi saldo
        if ($jumlah > $saldoTersedia) {
            return ['Jumlah pencairan melebihi saldo yang tersedia (Rp ' . number_format($saldoTersedia, 0, ',', '.') . ').'];
        }

        // Validasi metode
        if (!in_array($oldInput['metode'], ['TUNAI', 'TRANSFER'], true)) {
            return ['Metode pencairan harus dipilih.'];
        }

        // Validasi tujuan transfer jika metode TRANSFER
        if ($oldInput['metode'] === 'TRANSFER') {
            $tujuan = trim($oldInput['tujuan_transfer'] ?? '');
            if ($tujuan === '') {
                return ['Tujuan transfer (nomor rekening/e-wallet) wajib diisi.'];
            }
            if (strlen($tujuan) < 8) {
                return ['Nomor tujuan transfer terlalu pendek.'];
            }
        }

        // Siapkan data untuk disimpan
        $data = [
            'nasabah_id' => $nasabah['id'],
            'jumlah' => $jumlah,
            'metode' => $oldInput['metode'],
            'tujuan_transfer' => ($oldInput['metode'] === 'TRANSFER') ? trim($oldInput['tujuan_transfer']) : null
        ];

        // Simpan pengajuan
        $pengajuanId = $this->pencairanModel->create($data);

        if ($pengajuanId) {
            // Catatan: nomor tidak ditampilkan karena generateNoPengajuan() membuat
            // nomor baru, belum tentu sama dengan nomor yang tersimpan.

            try {
            $this->notifikasiModel->buat(
                    (int) currentUserId(),
                    'PENCAIRAN_DIAJUKAN',
                    'Pengajuan pencairan diterima',
                    'Pengajuan sebesar Rp ' . number_format($jumlah, 0, ',', '.')
                        . ' (' . $oldInput['metode'] . ') menunggu persetujuan pengelola.'
                );
            } catch (\Throwable $e) {
                error_log('Notifikasi gagal dibuat: ' . $e->getMessage());
            }
            setFlashMessage('success', 'Pengajuan pencairan berhasil diajukan.');
            redirect('nasabah/cairkan');
            return [];
        }

        return ['Gagal menyimpan pengajuan. Silakan coba lagi.'];
    }

    /**
     * Setor - Form dan proses penyetoran sampah
     */
    public function setor(): void
    {
        $nasabah = $this->getCurrentNasabah();

        if (!$nasabah) {
            setFlashMessage('error', 'Data akun tidak ditemukan.');
            redirect('logout');
            return;
        }

        $user = currentUser();
        $flash = getFlashMessage();
        $errors = [];
        $oldInput = [];
        $hargaSampah = $this->getHargaSampahAktif((int) ($nasabah['bank_sampah_id'] ?? 0));

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!defined('FITUR_SETOR_NASABAH') || FITUR_SETOR_NASABAH !== true) {
        http_response_code(403);
        exit('Fitur ini dinonaktifkan. Setoran dicatat oleh pengelola.');
    }
    // ... kode setor yang lama tetap di bawah sini
}


        // Proses form submission
        //if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//            $errors = $this->prosesSetor($nasabah, $hargaSampah, $oldInput);

            // Tanpa error berarti berhasil dan sudah redirect
  //          if (empty($errors)) {
      //          return;
    //        }
        //}

        $saldo = $this->nasabahModel->getSaldo($nasabah['id']);

    //    var_dump($hargaSampah); exit;
        include VIEW_PATH . '/nasabah/setor.php';
    }

    /**
     * Ambil daftar harga sampah aktif terbaru untuk satu bank sampah.
     *
     * @param int $bankSampahId
     * @return array
     */
    private function getHargaSampahAktif(int $bankSampahId): array
    {
        if ($bankSampahId <= 0) {
            error_log('NasabahController::setor - bank_sampah_id nasabah kosong.');
            return [];
        }

        $file = MODEL_PATH . '/HargaSampah.php';
        if (!file_exists($file)) {
            error_log('NasabahController::setor - file model tidak ditemukan: ' . $file);
            return [];
        }

        require_once $file;

        $model = new HargaSampah();
        return $model->getLatestByBank($bankSampahId);
    }

    /**
     * Proses penyetoran sampah.
     * Nilai setoran SELALU dihitung ulang di server dari harga di database,
     * bukan dari angka yang dikirim browser.
     *
     * ASUMSI (sesuaikan dengan model Anda):
     *   file  : MODEL_PATH/Setoran.php
     *   class : Setoran
     *   method: create(array $data) -> id setoran atau false
     *           $data: nasabah_id, kategori_id, berat, harga_per_kg, nilai
     *           Method ini sebaiknya menambah saldo dan mencatat mutasi
     *           dalam satu transaksi database.
     *
     * @param array $nasabah
     * @param array $hargaSampah
     * @param array $oldInput
     * @return array Error messages (kosong jika berhasil)
     */
    private function prosesSetor(array $nasabah, array $hargaSampah, array &$oldInput): array
    {
        $oldInput = [
            'kategori_id' => $_POST['kategori_id'] ?? '',
            'berat' => $_POST['berat'] ?? ''
        ];

        if ($oldInput['kategori_id'] === '') {
            return ['Jenis sampah harus dipilih.'];
        }

        // Cari harga kategori dari data server
        $harga = null;
        foreach ($hargaSampah as $h) {
            if ((string) $h['kategori_id'] === (string) $oldInput['kategori_id']) {
                $harga = (float) $h['harga_beli'];
                break;
            }
        }
        if ($harga === null || $harga <= 0) {
            return ['Jenis sampah tidak valid atau harganya belum ditetapkan.'];
        }

        // Berat: terima koma sebagai desimal (2,5) maupun titik (2.5)
        $beratText = str_replace(',', '.', trim((string) $oldInput['berat']));
        if ($beratText === '' || !is_numeric($beratText)) {
            return ['Berat sampah harus diisi dengan angka.'];
        }
        $berat = round((float) $beratText, 2);
        if ($berat <= 0) {
            return ['Berat sampah harus lebih dari 0 kg.'];
        }
        if ($berat > 10000) {
            return ['Berat sampah terlalu besar. Periksa kembali angka yang dimasukkan.'];
        }

        $nilai = round($berat * $harga);

        // Load model setoran
        $file = MODEL_PATH . '/Setoran.php';
        if (!file_exists($file)) {
            error_log('NasabahController::setor - file model tidak ditemukan: ' . $file);
            return ['Fitur setoran belum siap digunakan. Hubungi pengelola.'];
        }
        require_once $file;

        if (!class_exists('Setoran') || !method_exists('Setoran', 'create')) {
            error_log('NasabahController::setor - Setoran::create() belum tersedia.');
            return ['Fitur setoran belum siap digunakan. Hubungi pengelola.'];
        }

        $setoranModel = new Setoran();
        $setoranId = $setoranModel->create([
            'nasabah_id' => $nasabah['id'],
            'kategori_id' => $oldInput['kategori_id'],
            'berat' => $berat,
            'harga_per_kg' => $harga,
            'nilai' => $nilai
        ]);

        if ($setoranId) {
            setFlashMessage(
                'success',
                'Setoran berhasil. Saldo bertambah Rp ' . number_format($nilai, 0, ',', '.') . '.'
            );
            redirect('nasabah/dashboard');
            return [];
        }

        return ['Gagal menyimpan setoran. Silakan coba lagi.'];
    }

    /**
     * Profil - Lihat dan ubah data pribadi nasabah
     */
    public function profil(): void
    {
        $nasabah = $this->getCurrentNasabah();

        if (!$nasabah) {
            setFlashMessage('error', 'Data akun tidak ditemukan.');
            redirect('logout');
            return;
        }

        $user = currentUser();
        $flash = getFlashMessage();
        $errors = [];
        $oldInput = [];

        // Proses form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = $this->prosesProfil($nasabah, $oldInput);

            // Tanpa error berarti berhasil dan sudah redirect
            if (empty($errors)) {
                return;
            }
        }

        include VIEW_PATH . '/nasabah/profil.php';
    }

    /**
     * Proses simpan perubahan profil.
     *
     * ASUMSI (sesuaikan dengan model Anda):
     *   Nasabah::updateProfil(int $id, array $data) -> bool
     *   $data: nama, no_hp, email, alamat, rt, rw, nama_bank, no_rekening
     *
     * @param array $nasabah
     * @param array $oldInput
     * @return array Error messages (kosong jika berhasil)
     */
    private function prosesProfil(array $nasabah, array &$oldInput): array
    {
        $kolom = ['nama', 'no_hp', 'email', 'alamat', 'rt', 'rw', 'nama_bank', 'no_rekening'];
        foreach ($kolom as $k) {
            $oldInput[$k] = trim((string) ($_POST[$k] ?? ''));
        }

        $errors = [];

        if ($oldInput['nama'] === '') {
            $errors[] = 'Nama lengkap wajib diisi.';
        } elseif (strlen($oldInput['nama']) > 100) {
            $errors[] = 'Nama lengkap maksimal 100 karakter.';
        }

        // Nomor HP: abaikan spasi, strip, dan tanda + saat memeriksa
        $hpBersih = preg_replace('/[\s\-\+]/', '', $oldInput['no_hp']);
        if ($hpBersih === '') {
            $errors[] = 'Nomor HP wajib diisi.';
        } elseif (!ctype_digit($hpBersih) || strlen($hpBersih) < 9 || strlen($hpBersih) > 15) {
            $errors[] = 'Nomor HP harus berupa angka, 9 sampai 15 digit.';
        }

        if ($oldInput['email'] !== '' && !filter_var($oldInput['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Format email tidak valid.';
        }

        foreach (['rt' => 'RT', 'rw' => 'RW'] as $k => $label) {
            if ($oldInput[$k] !== '' && !ctype_digit($oldInput[$k])) {
                $errors[] = $label . ' harus berupa angka.';
            }
        }

        if ($oldInput['no_rekening'] !== '' && !ctype_digit(preg_replace('/[\s\-]/', '', $oldInput['no_rekening']))) {
            $errors[] = 'Nomor rekening harus berupa angka.';
        }

        if (!empty($errors)) {
            return $errors;
        }

        if (!method_exists($this->nasabahModel, 'updateProfil')) {
            error_log('NasabahController::profil - Nasabah::updateProfil() belum ada.');
            return ['Fitur ubah profil belum siap digunakan. Hubungi pengelola.'];
        }

        $data = $oldInput;
        $data['no_hp'] = $hpBersih;

        if ($this->nasabahModel->updateProfil($nasabah['id'], $data)) {
            setFlashMessage('success', 'Profil berhasil diperbarui.');
            redirect('nasabah/profil');
            return [];
        }

        return ['Gagal menyimpan perubahan. Silakan coba lagi.'];
    }


        /**
     * Tandai semua notifikasi nasabah sebagai sudah dibaca (dipanggil via fetch POST)
     */
    public function notifikasiBaca(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false]);
            return;
        }

        $ok = $this->notifikasiModel->tandaiSemuaDibaca((int) currentUserId());
        echo json_encode(['ok' => (bool) $ok]);
    }

    /**
     * Redirect index ke dashboard
     */
    public function index(): void
    {
        $this->dashboard();
    }
}