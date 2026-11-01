<?php

require_once __DIR__ . '/../helpers/render.php';

/**
 * Admin Controller
 *
 * Controller untuk fitur Admin
 * Compatible dengan PHP 7.3+
 */

class AdminController
{
    /** Peran yang tampil dan dikelola di menu Kelola Pengguna (Nasabah lewat Kelola Nasabah) */
    private const ROLE_DIKELOLA = ['ADMIN', 'PENGELOLA', 'KADES'];

    /** Peran yang boleh dipilih saat membuat/mengubah akun lewat form */
    private const ROLE_BISA_DIBUAT = ['PENGELOLA', 'KADES'];

    private const STATUS_VALID = ['AKTIF', 'NONAKTIF'];

    /** @var User */
    private $userModel;

    /** @var Desa */
    private $desaModel;

    /** @var BankSampah */
    private $bankSampahModel;

    /** @var Nasabah */
    private $nasabahModel;

    public function __construct()
    {
        requireLogin();
        requireRole(['ADMIN'], 'dashboard');

        // Load models
        require_once MODEL_PATH . '/User.php';
        require_once MODEL_PATH . '/Desa.php';
        require_once MODEL_PATH . '/BankSampah.php';
        require_once MODEL_PATH . '/Nasabah.php';
        require_once MODEL_PATH . '/AuditLog.php'; // menyediakan helper audit_log()

        $this->userModel = new User();
        $this->desaModel = new Desa();
        $this->bankSampahModel = new BankSampah();
        $this->nasabahModel = new Nasabah();
    }

    public function index(): void
    {
        $this->dashboard();
    }

    /**
     * Dashboard Admin - Statistik Overview
     */
    public function dashboard(): void
    {
        // Statistik
        $stats = [
            'total_users' => $this->userModel->count(),
            'total_desa' => $this->desaModel->count(),
            'total_bank' => $this->bankSampahModel->count(),
            'bank_aktif' => $this->bankSampahModel->count('AKTIF'),
        ];

        // User terbaru
        $recentUsers = $this->userModel->getAll(5);

        $user = currentUser();
        $flash = getFlashMessage();

        // render('/admin/dashboard.php');
        include VIEW_PATH . '/admin/dashboard.php';
    }

    /* =====================================================================
     * Kelola Pengguna - helper internal
     * ===================================================================== */

    /**
     * Pastikan session aktif (dipakai untuk token CSRF).
     */
    private function mulaiSesi(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    /**
     * Token CSRF untuk form Kelola Pengguna.
     */
    private function csrfToken(): string
    {
        $this->mulaiSesi();
        if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Periksa token CSRF dari POST.
     */
    private function csrfValid(): bool
    {
        $this->mulaiSesi();
        $dikirim = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
        $asli = isset($_SESSION['csrf_token']) && is_string($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '';

        return $asli !== '' && $dikirim !== '' && hash_equals($asli, $dikirim);
    }

    /**
     * Validasi kata sandi sementara yang diketik Admin.
     * Mengembalikan pesan error, atau string kosong bila valid.
     * (Pengguna tetap wajib menggantinya saat login berikutnya.)
     */
    private function validasiKataSandiSementara(string $password): string
    {
        if ($password === '') {
            return 'Kata sandi wajib diisi.';
        }
        if (strlen($password) < 8) {
            return 'Kata sandi minimal 8 karakter.';
        }
        if (strlen($password) > 72) {
            return 'Kata sandi maksimal 72 karakter.';
        }
        if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            return 'Kata sandi harus mengandung huruf dan angka.';
        }
        return '';
    }

    /**
     * Cuplikan data user untuk audit log. Tidak pernah memuat kata sandi maupun hash-nya.
     */
    private function cuplikanUser($u): array
    {
        $hasil = [];
        if (!is_array($u)) {
            return $hasil;
        }
        foreach (['nama', 'no_hp', 'email', 'role', 'status', 'desa_id', 'bank_sampah_id', 'wajib_ganti_sandi'] as $k) {
            if (array_key_exists($k, $u)) {
                $hasil[$k] = $u[$k];
            }
        }
        return $hasil;
    }

    /**
     * Alamat daftar pengguna untuk kembali setelah sebuah aksi.
     * Hanya dibangun dari kunci yang diizinkan (q, role, status, page).
     */
    private function urlKembali(): string
    {
        $mentah = isset($_POST['kembali']) && is_string($_POST['kembali']) ? $_POST['kembali'] : '';
        $p = [];
        parse_str($mentah, $p);

        $q = [];
        if (isset($p['q']) && is_string($p['q']) && trim($p['q']) !== '') {
            $teks = trim($p['q']);
            $q['q'] = function_exists('mb_substr') ? mb_substr($teks, 0, 100, 'UTF-8') : substr($teks, 0, 100);
        }
        if (isset($p['role']) && is_string($p['role']) && in_array($p['role'], self::ROLE_DIKELOLA, true)) {
            $q['role'] = $p['role'];
        }
        if (isset($p['status']) && is_string($p['status']) && in_array($p['status'], self::STATUS_VALID, true)) {
            $q['status'] = $p['status'];
        }
        if (isset($p['page']) && is_scalar($p['page']) && (int) $p['page'] > 1) {
            $q['page'] = (int) $p['page'];
        }

        return 'admin/users' . ($q ? '?' . http_build_query($q) : '');
    }

    /**
     * Ambil user target dari POST (method, CSRF, id, peran). Mengembalikan null
     * dan mengalihkan bila ada yang tidak valid.
     */
    private function targetDariPost(): ?array
    {
        $kembali = $this->urlKembali();

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            setFlashMessage('error', 'Permintaan tidak valid.');
            redirect($kembali);
            return null;
        }
        if (!$this->csrfValid()) {
            setFlashMessage('error', 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.');
            redirect($kembali);
            return null;
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $target = $id > 0 ? $this->userModel->findById($id) : null;

        if (!$target || !in_array($target['role'], self::ROLE_DIKELOLA, true)) {
            setFlashMessage('error', 'Pengguna tidak ditemukan.');
            redirect($kembali);
            return null;
        }

        return $target;
    }

    /* =====================================================================
     * Kelola Pengguna - aksi
     * ===================================================================== */

    /**
     * Kelola Pengguna - List
     */
    public function users(): void
    {
        // Filter (disaring dengan whitelist)
        $filters = ['q' => '', 'role' => '', 'status' => ''];

        $q = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
        if ($q !== '') {
            $filters['q'] = function_exists('mb_substr') ? mb_substr($q, 0, 100, 'UTF-8') : substr($q, 0, 100);
        }
        $role = isset($_GET['role']) && is_string($_GET['role']) ? $_GET['role'] : '';
        if (in_array($role, self::ROLE_DIKELOLA, true)) {
            $filters['role'] = $role;
        }
        $status = isset($_GET['status']) && is_string($_GET['status']) ? $_GET['status'] : '';
        if (in_array($status, self::STATUS_VALID, true)) {
            $filters['status'] = $status;
        }

        $perPage = 20;
        $totalUsers = $this->userModel->countSearch($filters);
        $totalPages = max(1, (int) ceil($totalUsers / $perPage));
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        $users = $this->userModel->search($filters, $perPage, $offset);

        $csrfToken = $this->csrfToken();

        $user = currentUser();
        $flash = getFlashMessage();

        include VIEW_PATH . '/admin/users/index.php';
    }

    /**
     * Kelola Pengguna - Form Tambah/Edit
     */
    public function userForm(): void
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
        $editUser = null;

        if ($id !== null && $id <= 0) {
            setFlashMessage('error', 'Pengguna tidak ditemukan.');
            redirect('admin/users');
            return;
        }

        if ($id) {
            $editUser = $this->userModel->findById($id);
            // Nasabah dikelola lewat menu Kelola Nasabah
            if (!$editUser || !in_array($editUser['role'], self::ROLE_DIKELOLA, true)) {
                setFlashMessage('error', 'Pengguna tidak ditemukan.');
                redirect('admin/users');
                return;
            }
        }

        // Untuk dropdown
        $desas = $this->desaModel->getAll();
        $banks = $this->bankSampahModel->getAll();

        $user = currentUser();
        $flash = getFlashMessage();
        $errors = [];
        $oldInput = [];
        $csrfToken = $this->csrfToken();

        // Proses simpan
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = $this->processUserSave($id, $editUser);
            if (empty($errors)) {
                return; // Redirect sudah dilakukan
            }
            $oldInput = $_POST;
            unset($oldInput['password'], $oldInput['csrf_token']);
        }

        include VIEW_PATH . '/admin/users/form.php';
    }

    /**
     * Proses simpan user (Tambah/Edit)
     *
     * Saat ubah, kata sandi hanya diganti bila kotak "Reset kata sandi"
     * (reset_password=1) dicentang. Kata sandi disimpan dalam satu UPDATE
     * bersama data lainnya dan pengguna wajib menggantinya saat login berikutnya.
     *
     * @param int|null $id
     * @param array|null $editUser Data user yang sedang diubah (null saat tambah)
     */
    private function processUserSave($id, $editUser): array
    {
        if (!$this->csrfValid()) {
            return ['umum' => 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.'];
        }

        $data = [];
        $errors = $this->validateUserData($_POST, $id, $editUser, $data);

        if (!empty($errors)) {
            return $errors;
        }

        try {
            if ($id) {
                // Update
                $reset = isset($_POST['reset_password']) && $_POST['reset_password'] === '1';
                $sebelum = $this->cuplikanUser($editUser);

                if ($reset) {
                    $sementara = (string) ($_POST['password'] ?? '');
                    $data['password_hash'] = User::hashPassword($sementara);
                    $data['wajib_ganti_sandi'] = 1;
                }

                if (!$this->userModel->update($id, $data)) {
                    return ['umum' => 'Pengguna gagal disimpan. Silakan coba lagi.'];
                }
                $sesudah = $this->cuplikanUser($this->userModel->findById($id));

                // Penanda wajib_ganti_sandi dicatat lewat RESET_SANDI, bukan UBAH
                $sebelumUbah = $sebelum;
                $sesudahUbah = $sesudah;
                if ($reset) {
                    unset($sebelumUbah['wajib_ganti_sandi'], $sesudahUbah['wajib_ganti_sandi']);
                }
                if ($sebelumUbah !== $sesudahUbah) {
                    audit_log('UBAH', 'users', $id, $sebelumUbah, $sesudahUbah);
                }

                if ($reset) {
                    // Audit log: hanya penanda wajib_ganti_sandi, tidak ada kata sandi maupun hash
                    audit_log(
                        'RESET_SANDI',
                        'users',
                        $id,
                        ['wajib_ganti_sandi' => (int) ($sebelum['wajib_ganti_sandi'] ?? 0)],
                        ['wajib_ganti_sandi' => 1]
                    );
                    setFlashMessage('success', 'Data pengguna berhasil diperbarui dan kata sandi direset. Pengguna wajib menggantinya saat login berikutnya.');
                } else {
                    setFlashMessage('success', 'Data pengguna berhasil diperbarui.');
                }
            } else {
                // Create dengan kata sandi sementara yang diketik Admin
                $sementara = (string) ($_POST['password'] ?? '');
                $data['password_hash'] = User::hashPassword($sementara);
                $data['wajib_ganti_sandi'] = 1;

                $newId = $this->userModel->createDenganId($data);
                if (!$newId) {
                    return ['umum' => 'Pengguna gagal disimpan. Silakan coba lagi.'];
                }

                audit_log('TAMBAH', 'users', $newId, null, $this->cuplikanUser($this->userModel->findById($newId)));

                setFlashMessage('success', 'Pengguna berhasil ditambahkan. Pengguna wajib mengganti kata sandi saat login pertama.');
            }
        } catch (Throwable $ex) {
            error_log('[Kelola Pengguna] ' . $ex->getMessage());
            return ['umum' => 'Pengguna gagal disimpan. Silakan coba lagi.'];
        }

        redirect('admin/users');
        return [];
    }

    /**
     * Validasi data user. Mengembalikan error per kolom dan mengisi $data
     * dengan nilai yang sudah bersih.
     *
     * @param array $post
     * @param int|null $id
     * @param array|null $target Data user yang diubah (null saat tambah)
     * @param array $data Diisi data siap simpan
     */
    private function validateUserData(array $post, $id, $target, array &$data): array
    {
        $errors = [];

        $targetAdmin = is_array($target) && ($target['role'] ?? '') === 'ADMIN';
        $diriSendiri = is_array($target) && (int) $target['id'] === (int) currentUserId();
        $kunciRole = is_array($target) && ($diriSendiri || $targetAdmin);
        $kunciStatus = $diriSendiri;
        $exceptId = $id ? (int) $id : 0;

        // Nama
        $nama = trim((string) ($post['nama'] ?? ''));
        $panjangNama = function_exists('mb_strlen') ? mb_strlen($nama, 'UTF-8') : strlen($nama);
        if ($nama === '') {
            $errors['nama'] = 'Nama lengkap wajib diisi.';
        } elseif ($panjangNama > 100) {
            $errors['nama'] = 'Nama lengkap maksimal 100 karakter.';
        }

        // No HP
        $noHp = trim((string) ($post['no_hp'] ?? ''));
        if ($noHp === '') {
            $errors['no_hp'] = 'No HP wajib diisi.';
        } elseif (!preg_match('/^[0-9]+$/', $noHp)) {
            $errors['no_hp'] = 'No HP hanya boleh berisi angka.';
        } elseif (!preg_match('/^(08|62)[0-9]{8,13}$/', $noHp)) {
            $errors['no_hp'] = 'No HP harus diawali 08 atau 62 dan terdiri dari 10 sampai 15 digit.';
        } elseif ($this->userModel->existsNoHp($noHp, $exceptId)) {
            $errors['no_hp'] = 'No HP sudah dipakai pengguna lain.';
        }

        // Email (opsional)
        $email = trim((string) ($post['email'] ?? ''));
        if ($email !== '') {
            if (strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Format email tidak valid.';
            } elseif ($this->userModel->existsEmail($email, $exceptId)) {
                $errors['email'] = 'Email sudah dipakai pengguna lain.';
            }
        }

        // Kata sandi sementara: wajib saat tambah. Saat ubah hanya diperiksa bila
        // "Reset kata sandi" dicentang, dan tidak boleh untuk akun sendiri.
        if (!$id) {
            $pesanSandi = $this->validasiKataSandiSementara(
                isset($post['password']) && is_string($post['password']) ? $post['password'] : ''
            );
            if ($pesanSandi !== '') {
                $errors['password'] = $pesanSandi;
            }
        } elseif (isset($post['reset_password']) && $post['reset_password'] === '1') {
            if ($diriSendiri) {
                $errors['umum'] = 'Anda tidak dapat mereset kata sandi akun sendiri. Gunakan halaman Ganti kata sandi.';
            } else {
                $pesanSandi = $this->validasiKataSandiSementara(
                    isset($post['password']) && is_string($post['password']) ? $post['password'] : ''
                );
                if ($pesanSandi !== '') {
                    $errors['password'] = $pesanSandi;
                }
            }
        }

        // Peran
        if ($kunciRole) {
            $role = (string) $target['role'];
        } else {
            $role = (string) ($post['role'] ?? '');
            if ($role === '') {
                $errors['role'] = 'Peran wajib dipilih.';
            } elseif (!in_array($role, self::ROLE_BISA_DIBUAT, true)) {
                $errors['role'] = 'Peran tidak valid. Pilih Pengelola atau Kepala Desa.';
            }
        }

        // Status
        if ($kunciStatus) {
            $status = (string) $target['status'];
        } else {
            $status = (string) ($post['status'] ?? 'AKTIF');
            if ($status === '') {
                $status = 'AKTIF';
            }
            if (!in_array($status, self::STATUS_VALID, true)) {
                $errors['status'] = 'Status tidak valid.';
            } elseif (
                $targetAdmin && $status === 'NONAKTIF' && ($target['status'] ?? '') === 'AKTIF'
                && $this->userModel->countAdminAktif() <= 1
            ) {
                $errors['status'] = 'Admin aktif terakhir tidak dapat dinonaktifkan.';
            }
        }

        // Desa dan Bank sampah mengikuti peran
        $desaId = null;
        $bankId = null;
        $desaPost = trim((string) ($post['desa_id'] ?? ''));
        $bankPost = trim((string) ($post['bank_sampah_id'] ?? ''));

        if ($targetAdmin) {
            // Akun Admin: desa dan bank sampah tidak diubah dari form ini
            $desaId = $target['desa_id'];
            $bankId = $target['bank_sampah_id'];
        } elseif ($role === 'PENGELOLA') {
            if ($bankPost === '' || !ctype_digit($bankPost)) {
                $errors['bank_sampah_id'] = 'Bank sampah wajib dipilih untuk Pengelola.';
            } else {
                $bank = $this->bankSampahModel->findById((int) $bankPost);
                if (!$bank) {
                    $errors['bank_sampah_id'] = 'Bank sampah tidak ditemukan.';
                } elseif (empty($bank['desa_id'])) {
                    $errors['bank_sampah_id'] = 'Bank sampah ini belum terhubung ke desa.';
                } else {
                    $bankId = (int) $bankPost;
                    $desaId = (int) $bank['desa_id']; // desa otomatis mengikuti bank sampah
                }
            }
        } elseif ($role === 'KADES') {
            if ($desaPost === '' || !ctype_digit($desaPost)) {
                $errors['desa_id'] = 'Desa wajib dipilih untuk Kepala Desa.';
            } else {
                $desa = $this->desaModel->findById((int) $desaPost);
                if (!$desa) {
                    $errors['desa_id'] = 'Desa tidak ditemukan.';
                } else {
                    $desaId = (int) $desaPost;
                }
            }
            if ($bankPost !== '') {
                $errors['bank_sampah_id'] = 'Bank sampah harus kosong untuk Kepala Desa.';
            }
        }

        $data = [
            'nama' => $nama,
            'no_hp' => $noHp,
            'email' => $email === '' ? null : $email,
        ];
        if (!$kunciRole) {
            $data['role'] = $role;
        }
        if (!$kunciStatus) {
            $data['status'] = $status;
        }
        if (!$targetAdmin) {
            $data['desa_id'] = $desaId;
            $data['bank_sampah_id'] = $bankId;
        }

        return $errors;
    }

    /**
     * Nonaktifkan / Aktifkan kembali pengguna (POST + CSRF).
     * Menggantikan hapus permanen agar riwayat transaksi dan audit log tetap utuh.
     */
    public function userToggleStatus(): void
    {
        $target = $this->targetDariPost();
        if ($target === null) {
            return;
        }
        $kembali = $this->urlKembali();
        $id = (int) $target['id'];

        $aksi = isset($_POST['aksi']) && is_string($_POST['aksi']) ? $_POST['aksi'] : '';
        if (!in_array($aksi, ['nonaktifkan', 'aktifkan'], true)) {
            setFlashMessage('error', 'Aksi tidak valid.');
            redirect($kembali);
            return;
        }

        // Admin tidak boleh mengubah status dirinya sendiri
        if ($id === (int) currentUserId()) {
            setFlashMessage('error', 'Anda tidak dapat mengubah status akun sendiri.');
            redirect($kembali);
            return;
        }

        $statusBaru = $aksi === 'nonaktifkan' ? 'NONAKTIF' : 'AKTIF';

        if (($target['status'] ?? '') === $statusBaru) {
            setFlashMessage('info', 'Status akun ' . $target['nama'] . ' sudah ' . ($statusBaru === 'AKTIF' ? 'aktif' : 'nonaktif') . '.');
            redirect($kembali);
            return;
        }

        // Admin aktif terakhir tidak boleh dinonaktifkan
        if (
            $statusBaru === 'NONAKTIF' && $target['role'] === 'ADMIN' && $target['status'] === 'AKTIF'
            && $this->userModel->countAdminAktif() <= 1
        ) {
            setFlashMessage('error', 'Admin aktif terakhir tidak dapat dinonaktifkan.');
            redirect($kembali);
            return;
        }

        try {
            $sebelum = $this->cuplikanUser($target);
            $this->userModel->update($id, ['status' => $statusBaru]);
            $sesudah = $this->cuplikanUser($this->userModel->findById($id));

            audit_log($aksi === 'nonaktifkan' ? 'NONAKTIFKAN' : 'AKTIFKAN', 'users', $id, $sebelum, $sesudah);
        } catch (Throwable $ex) {
            error_log('[Kelola Pengguna] ' . $ex->getMessage());
            setFlashMessage('error', 'Status pengguna gagal diubah. Silakan coba lagi.');
            redirect($kembali);
            return;
        }

        setFlashMessage('success', 'Akun ' . $target['nama'] . ($statusBaru === 'AKTIF' ? ' diaktifkan kembali.' : ' dinonaktifkan.'));
        redirect($kembali);
    }

    /**
     * Reset kata sandi pengguna (POST + CSRF). Admin mengetik kata sandi sementara
     * baru, dan pengguna wajib menggantinya saat login berikutnya.
     *
     * Catatan: tombol reset sudah dihapus dari daftar pengguna. Reset kini dilakukan
     * lewat kotak "Reset kata sandi" di form Ubah (lihat processUserSave). Method ini
     * dipertahankan agar rute lama tidak error.
     */
    public function userResetPassword(): void
    {
        $target = $this->targetDariPost();
        if ($target === null) {
            return;
        }
        $kembali = $this->urlKembali();
        $id = (int) $target['id'];

        if ($id === (int) currentUserId()) {
            setFlashMessage('error', 'Anda tidak dapat mereset kata sandi akun sendiri.');
            redirect($kembali);
            return;
        }

        $sementara = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
        $pesanSandi = $this->validasiKataSandiSementara($sementara);
        if ($pesanSandi !== '') {
            setFlashMessage('error', 'Kata sandi ' . $target['nama'] . ' tidak direset. ' . $pesanSandi);
            redirect($kembali);
            return;
        }

        try {
            $this->userModel->update($id, [
                'password_hash' => User::hashPassword($sementara),
                'wajib_ganti_sandi' => 1,
            ]);

            // Audit log: hanya penanda wajib_ganti_sandi, tidak ada kata sandi maupun hash
            audit_log(
                'RESET_SANDI',
                'users',
                $id,
                ['wajib_ganti_sandi' => (int) ($target['wajib_ganti_sandi'] ?? 0)],
                ['wajib_ganti_sandi' => 1]
            );
        } catch (Throwable $ex) {
            error_log('[Kelola Pengguna] ' . $ex->getMessage());
            setFlashMessage('error', 'Kata sandi gagal direset. Silakan coba lagi.');
            redirect($kembali);
            return;
        }

        setFlashMessage('success', 'Kata sandi ' . $target['nama'] . ' berhasil direset. Pengguna wajib menggantinya saat login berikutnya.');
        redirect($kembali);
    }

    /**
     * Hapus User - DINONAKTIFKAN.
     * Akun tidak boleh dihapus (riwayat transaksi dan audit log harus utuh).
     * Method ini dipertahankan hanya agar rute lama tidak error; tidak menghapus apa pun.
     */
    public function userDelete(): void
    {
        setFlashMessage('error', 'Pengguna tidak dapat dihapus. Gunakan tombol Nonaktifkan agar riwayat data tetap utuh.');
        redirect('admin/users');
    }

    /**
     * Kelola Desa - List
     */
    public function desa(): void
    {
        $desas = $this->desaModel->getAll();

        $user = currentUser();
        $flash = getFlashMessage();

        include VIEW_PATH . '/admin/desa/index.php';
    }

    /**
     * Form Desa
     */
    public function desaForm(): void
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
        $editDesa = null;

        if ($id) {
            $editDesa = $this->desaModel->findById($id);
            if (!$editDesa) {
                setFlashMessage('error', 'Desa tidak ditemukan.');
                redirect('admin/desa');
                return;
            }
        }

        $user = currentUser();
        $flash = getFlashMessage();
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = $this->processDesaSave($id);
            if (empty($errors)) {
                return;
            }
        }

        include VIEW_PATH . '/admin/desa/form.php';
    }

    /**
     * Proses simpan desa
     */
    private function processDesaSave($id): array
    {
        $errors = [];

        if (empty(trim($_POST['nama'] ?? ''))) {
            $errors[] = 'Nama desa harus diisi.';
        }
        if (empty(trim($_POST['kecamatan'] ?? ''))) {
            $errors[] = 'Kecamatan harus diisi.';
        }
        if (empty(trim($_POST['kabupaten'] ?? ''))) {
            $errors[] = 'Kabupaten harus diisi.';
        }

        if (!empty($errors)) {
            return $errors;
        }

        $data = [
            'kode_wilayah' => trim($_POST['kode_wilayah'] ?? ''),
            'nama' => trim($_POST['nama']),
            'kecamatan' => trim($_POST['kecamatan']),
            'kabupaten' => trim($_POST['kabupaten']),
            'provinsi' => trim($_POST['provinsi'] ?? 'Jawa Barat'),
        ];

        if ($id) {
            $this->desaModel->update($id, $data);
            setFlashMessage('success', 'Desa berhasil diupdate.');
        } else {
            $this->desaModel->create($data);
            setFlashMessage('success', 'Desa berhasil ditambahkan.');
        }

        redirect('admin/desa');
        return [];
    }

    /**
     * Hapus Desa
     */
    public function desaDelete(): void
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;

        if (!$id) {
            setFlashMessage('error', 'ID tidak valid.');
            redirect('admin/desa');
            return;
        }

        $this->desaModel->delete($id);
        setFlashMessage('success', 'Desa berhasil dihapus.');
        redirect('admin/desa');
    }

    /**
     * Kelola Bank Sampah - List
     */
    public function bankSampah(): void
    {
        $banks = $this->bankSampahModel->getAll();

        // Jumlah Pengelola dan nasabah aktif per bank sampah, untuk peringatan
        // saat menonaktifkan. Hanya membantu tampilan; penjagaan ada di server.
        $relasiAktif = $this->bankSampahModel->getRelasiAktifPeta(array_column($banks, 'id'));

        $csrfToken = $this->csrfToken();

        $user = currentUser();
        $flash = getFlashMessage();

        include VIEW_PATH . '/admin/bank_sampah/index.php';
    }

    /**
     * Form Bank Sampah
     */
    public function bankSampahForm(): void
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
        $editBank = null;

        if ($id) {
            $editBank = $this->bankSampahModel->findById($id);
            if (!$editBank) {
                setFlashMessage('error', 'Bank Sampah tidak ditemukan.');
                redirect('admin/bank-sampah');
                return;
            }
        }

        $desas = $this->desaModel->getAll();

        $user = currentUser();
        $flash = getFlashMessage();
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = $this->processBankSampahSave($id);
            if (empty($errors)) {
                return;
            }
        }

        include VIEW_PATH . '/admin/bank_sampah/form.php';
    }

    /**
     * Proses simpan bank sampah
     */
    private function processBankSampahSave($id): array
    {
        $errors = [];

        if (empty(trim($_POST['nama'] ?? ''))) {
            $errors[] = 'Nama bank sampah harus diisi.';
        }
        if (empty($_POST['desa_id'])) {
            $errors[] = 'Desa harus dipilih.';
        }

        if (!empty($errors)) {
            return $errors;
        }

        $data = [
            'desa_id' => (int) $_POST['desa_id'],
            'nama' => trim($_POST['nama']),
            'alamat' => trim($_POST['alamat'] ?? ''),
            'latitude' => !empty($_POST['latitude']) ? (float) $_POST['latitude'] : null,
            'longitude' => !empty($_POST['longitude']) ? (float) $_POST['longitude'] : null,
            'penanggung_jawab' => trim($_POST['penanggung_jawab'] ?? ''),
            'no_hp' => trim($_POST['no_hp'] ?? ''),
            'status' => $_POST['status'] ?? 'AKTIF',
        ];

        if ($id) {
            $this->bankSampahModel->update($id, $data);
            setFlashMessage('success', 'Bank Sampah berhasil diupdate.');
        } else {
            $this->bankSampahModel->create($data);
            setFlashMessage('success', 'Bank Sampah berhasil ditambahkan.');
        }

        redirect('admin/bank-sampah');
        return [];
    }

    /**
     * Nonaktifkan / Aktifkan kembali bank sampah (POST + CSRF).
     *
     * Hanya kolom status bank sampah yang berubah. Pengelola, nasabah, dan
     * riwayat transaksinya tidak diubah. Perubahan dicatat di audit log.
     */
    public function bankSampahToggleStatus(): void
    {
        $kembali = 'admin/bank-sampah';

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            setFlashMessage('error', 'Permintaan tidak valid.');
            redirect($kembali);
            return;
        }

        if (!$this->csrfValid()) {
            setFlashMessage('error', 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.');
            redirect($kembali);
            return;
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $aksi = isset($_POST['aksi']) && is_string($_POST['aksi']) ? $_POST['aksi'] : '';

        if (!in_array($aksi, ['nonaktifkan', 'aktifkan'], true)) {
            setFlashMessage('error', 'Aksi tidak valid.');
            redirect($kembali);
            return;
        }

        $bank = $id > 0 ? $this->bankSampahModel->findById($id) : null;
        if (!$bank) {
            setFlashMessage('error', 'Bank Sampah tidak ditemukan.');
            redirect($kembali);
            return;
        }

        $statusBaru = $aksi === 'nonaktifkan' ? 'NONAKTIF' : 'AKTIF';
        $statusLama = strtoupper((string) ($bank['status'] ?? 'AKTIF'));

        if ($statusLama === $statusBaru) {
            setFlashMessage('info', 'Bank Sampah ' . $bank['nama'] . ' sudah '
                . ($statusBaru === 'AKTIF' ? 'aktif' : 'nonaktif') . '.');
            redirect($kembali);
            return;
        }

        try {
            if (!$this->bankSampahModel->ubahStatus($id, $statusBaru)) {
                throw new RuntimeException('Update status bank sampah gagal.');
            }

            audit_log(
                $aksi === 'nonaktifkan' ? 'NONAKTIFKAN' : 'AKTIFKAN',
                'bank_sampah',
                $id,
                ['status' => $statusLama],
                ['status' => $statusBaru]
            );
        } catch (Throwable $ex) {
            error_log('[Kelola Bank Sampah] ' . $ex->getMessage());
            setFlashMessage('error', 'Status bank sampah gagal diubah. Silakan coba lagi.');
            redirect($kembali);
            return;
        }

        setFlashMessage('success', 'Bank Sampah ' . $bank['nama']
            . ($statusBaru === 'AKTIF' ? ' diaktifkan kembali.' : ' dinonaktifkan. Datanya tetap tersimpan.'));
        redirect($kembali);
    }

    /**
     * Hapus Bank Sampah
     */
    public function bankSampahDelete(): void
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;

        if (!$id) {
            setFlashMessage('error', 'ID tidak valid.');
            redirect('admin/bank-sampah');
            return;
        }

        $this->bankSampahModel->delete($id);
        setFlashMessage('success', 'Bank Sampah berhasil dihapus.');
        redirect('admin/bank-sampah');
    }

    /**
     * Kelola Nasabah - List
     */
    public function nasabah(): void
    {
        $perPage = 20;

        // Total harus menghitung baris tabel nasabah. countAktif() menghitung
        // user ber-role NASABAH, jadi angkanya tidak sama dengan jumlah nasabah.
        $totalNasabah = $this->nasabahModel->countPaginasi(null);
        $totalPages = max(1, (int) ceil($totalNasabah / $perPage));

        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        $nasabahList = $this->nasabahModel->getAll($perPage, $offset);

        // Pengajuan pencairan yang masih tertahan (DIAJUKAN atau DISETUJUI).
        // Dipakai untuk peringatan saat menonaktifkan nasabah.
        $pengajuanAktif = $this->nasabahModel->getPengajuanAktifPeta(array_column($nasabahList, 'id'));

        $csrfToken = $this->csrfToken();

        $user = currentUser();
        $flash = getFlashMessage();

        include VIEW_PATH . '/admin/nasabah/index.php';
    }

    /**
     * Form Nasabah
     */
    public function nasabahForm(): void
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
        $editNasabah = null;

        if ($id) {
            $editNasabah = $this->nasabahModel->findById($id);
            if (!$editNasabah) {
                setFlashMessage('error', 'Nasabah tidak ditemukan.');
                redirect('admin/nasabah');
                return;
            }
        }

        $banks = $this->bankSampahModel->getAll();

        $user = currentUser();
        $flash = getFlashMessage();
        $errors = [];
        $oldInput = [];
        $csrfToken = $this->csrfToken();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = $this->processNasabahSave($id);
            if (empty($errors)) {
                return;
            }
            $oldInput = $_POST;
        }

        include VIEW_PATH . '/admin/nasabah/form.php';
    }

    /**
     * Proses simpan nasabah
     *
     * Status nasabah tidak ikut diubah dari sini. Status hanya diubah lewat
     * tombol Nonaktifkan atau Aktifkan di halaman daftar, supaya peringatan
     * soal saldo dan pengajuan yang berjalan selalu tampil.
     *
     * Saat ubah dengan "Reset kata sandi" dicentang, perubahan data nasabah,
     * kata sandi akun, dan audit log disimpan dalam satu transaksi. Bila salah
     * satu gagal, semuanya dibatalkan.
     */
    private function processNasabahSave($id): array
    {
        $errors = [];

        if (!$this->csrfValid()) {
            return ['Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.'];
        }

        $nama = trim((string) ($_POST['nama'] ?? ''));
        $noHp = preg_replace('/[\s\-\+]/', '', trim((string) ($_POST['no_hp'] ?? '')));
        $noAnggota = trim((string) ($_POST['no_anggota'] ?? ''));
        $nik = preg_replace('/[\s\-]/', '', trim((string) ($_POST['nik'] ?? '')));
        $bankSampahId = isset($_POST['bank_sampah_id']) ? (int) $_POST['bank_sampah_id'] : 0;
        $tipe = strtoupper(trim((string) ($_POST['tipe'] ?? 'PERORANGAN')));
        if (!in_array($tipe, ['PERORANGAN', 'INSTANSI'], true)) {
            $tipe = 'PERORANGAN';
        }

        $rt = trim((string) ($_POST['rt'] ?? ''));
        $rw = trim((string) ($_POST['rw'] ?? ''));
        $namaBank = trim((string) ($_POST['nama_bank'] ?? ''));
        $noRekening = preg_replace('/[\s\-]/', '', trim((string) ($_POST['no_rekening'] ?? '')));

        if ($nama === '') {
            $errors[] = 'Nama harus diisi.';
        } elseif (function_exists('mb_strlen') ? mb_strlen($nama) > 100 : strlen($nama) > 100) {
            $errors[] = 'Nama maksimal 100 karakter.';
        }

        if ($noHp === '') {
            $errors[] = 'No HP harus diisi.';
        } elseif (!ctype_digit($noHp) || strlen($noHp) < 9 || strlen($noHp) > 15) {
            $errors[] = 'No HP harus berupa angka 9 sampai 15 digit.';
        }

        if ($noAnggota === '') {
            $errors[] = 'No Anggota harus diisi.';
        } elseif (function_exists('mb_strlen') ? mb_strlen($noAnggota) > 20 : strlen($noAnggota) > 20) {
            $errors[] = 'No Anggota maksimal 20 karakter.';
        } elseif ($this->nasabahModel->noAnggotaExists($noAnggota, $id ? (int) $id : null, $bankSampahId)) {
            $errors[] = 'No anggota "' . $noAnggota . '" sudah dipakai nasabah lain.';
        }

        if ($bankSampahId <= 0) {
            $errors[] = 'Bank Sampah harus dipilih.';
        }

        if ($this->nasabahModel->noHpExists($noHp, $id ? (int) $id : null, $bankSampahId)) {
            $errors[] = 'No HP tersebut sudah dipakai nasabah lain.';
        }

        if ($nik !== '' && !ctype_digit($nik)) {
            $errors[] = 'NIK harus berupa angka.';
        }

        if ($rt !== '' && !ctype_digit($rt)) {
            $errors[] = 'RT harus berupa angka.';
        }

        if ($rw !== '' && !ctype_digit($rw)) {
            $errors[] = 'RW harus berupa angka.';
        }

        if ($noRekening !== '' && !ctype_digit($noRekening)) {
            $errors[] = 'No rekening harus berupa angka.';
        }

        // Validasi tambahan untuk password saat tambah
        $password = trim((string) ($_POST['password'] ?? ''));
        if ($id === null || $id === 0) {
            $pesanSandi = $this->validasiKataSandiSementara($password);
            if ($pesanSandi !== '') {
                $errors[] = $pesanSandi;
            }
        }

        if (!empty($errors)) {
            return $errors;
        }

        $data = [
            'bank_sampah_id' => $bankSampahId,
            'no_anggota' => $noAnggota,
            'nama' => $nama,
            'no_hp' => $noHp,
            'nik' => $nik !== '' ? $nik : null,
            'alamat' => trim((string) ($_POST['alamat'] ?? '')),
            'rt' => $rt !== '' ? $rt : null,
            'rw' => $rw !== '' ? $rw : null,
            'tipe' => $tipe,
            'nama_bank' => $namaBank !== '' ? $namaBank : null,
            'no_rekening' => $noRekening !== '' ? $noRekening : null,
        ];

        $resetPassword = isset($_POST['reset_password']) && $_POST['reset_password'] === '1';
        $resetPasswordValue = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';

        if ($id) {
            // Data lengkap nasabah dari database. Disimpan di variabel tersendiri
            // supaya user_id (penghubung ke akun login) tidak hilang.
            $nasabahLama = $this->nasabahModel->findById((int) $id);
            if (!$nasabahLama) {
                setFlashMessage('error', 'Nasabah tidak ditemukan.');
                redirect('admin/nasabah');
                return ['Nasabah tidak ditemukan.'];
            }

            $userIdAkun = (int) ($nasabahLama['user_id'] ?? 0);

            // Cuplikan untuk audit log (tanpa kolom sensitif)
            $sebelum = [
                'bank_sampah_id' => $nasabahLama['bank_sampah_id'] ?? null,
                'no_anggota' => $nasabahLama['no_anggota'] ?? null,
                'nama' => $nasabahLama['nama'] ?? null,
                'no_hp' => $nasabahLama['no_hp'] ?? null,
                'tipe' => $nasabahLama['tipe'] ?? null,
            ];

            $akun = null;
            if ($resetPassword) {
                if ($userIdAkun <= 0) {
                    $errors[] = 'Nasabah belum punya akun login. Tidak dapat mereset kata sandi.';
                } else {
                    $akun = $this->userModel->findById($userIdAkun);
                    if (!$akun || ($akun['role'] ?? '') !== 'NASABAH') {
                        $errors[] = 'Akun login nasabah tidak ditemukan. Tidak dapat mereset kata sandi.';
                    } else {
                        $pesanSandi = $this->validasiKataSandiSementara($resetPasswordValue);
                        if ($pesanSandi !== '') {
                            $errors[] = 'Kata sandi sementara nasabah tidak valid. ' . $pesanSandi;
                        }
                    }
                }
            }

            if (!empty($errors)) {
                return $errors;
            }

            // Form ini tidak punya kolom email. Pertahankan email yang sudah ada
            // supaya menyimpan perubahan tidak menghapusnya (di tabel nasabah dan di akun).
            $data['email'] = $nasabahLama['email'] ?? null;

            $db = $this->nasabahModel->getDb();

            try {
                $db->beginTransaction();

                if (!$this->nasabahModel->update((int) $id, $data)) {
                    throw new RuntimeException('Update data nasabah gagal.');
                }

                if ($resetPassword) {
                    $berhasilSandi = $this->userModel->update($userIdAkun, [
                        'password_hash' => User::hashPassword($resetPasswordValue),
                        'wajib_ganti_sandi' => 1,
                    ]);
                    if (!$berhasilSandi) {
                        throw new RuntimeException('Update kata sandi akun nasabah gagal.');
                    }
                }

                audit_log(
                    'UBAH',
                    'nasabah',
                    (int) $id,
                    $sebelum,
                    [
                        'bank_sampah_id' => $bankSampahId,
                        'no_anggota' => $noAnggota,
                        'nama' => $nama,
                        'no_hp' => $noHp,
                        'tipe' => $tipe,
                    ]
                );

                if ($resetPassword) {
                    // Audit log: hanya penanda wajib_ganti_sandi, tidak ada kata sandi maupun hash
                    audit_log(
                        'RESET_SANDI',
                        'nasabah',
                        (int) $id,
                        ['wajib_ganti_sandi' => (int) ($akun['wajib_ganti_sandi'] ?? 0)],
                        ['wajib_ganti_sandi' => 1]
                    );
                }

                $db->commit();
            } catch (Throwable $ex) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                error_log('[Kelola Nasabah] ' . $ex->getMessage());
                return ['Gagal menyimpan data nasabah. Silakan coba lagi.'];
            }

            setFlashMessage('success', 'Nasabah berhasil diupdate.' . ($resetPassword ? ' Kata sandi nasabah direset dan wajib diganti saat login berikutnya.' : ''));
        } else {
            $data['tgl_daftar'] = date('Y-m-d');
            $data['saldo'] = 0;
            $data['status'] = 'AKTIF';

            $hasil = $this->nasabahModel->createDenganAkun([
                'nama' => $nama,
                'no_hp' => $noHp,
                'email' => null,
                'password_hash' => User::hashPassword($password),
                'bank_sampah_id' => $bankSampahId,
                'no_anggota' => $noAnggota,
                'nik' => $nik !== '' ? $nik : null,
                'alamat' => trim((string) ($_POST['alamat'] ?? '')),
                'rt' => $rt !== '' ? $rt : null,
                'rw' => $rw !== '' ? $rw : null,
                'tipe' => $tipe,
                'nama_bank' => $namaBank !== '' ? $namaBank : null,
                'no_rekening' => $noRekening !== '' ? $noRekening : null,
                'tgl_daftar' => date('Y-m-d'),
                'wajib_ganti_sandi' => 1,
            ]);

            if ($hasil === false) {
                return ['Gagal menyimpan data nasabah. Silakan coba lagi.'];
            }

            $nasabahId = (int) $hasil['id'];
            audit_log(
                'TAMBAH',
                'nasabah',
                $nasabahId,
                null,
                [
                    'bank_sampah_id' => $bankSampahId,
                    'no_anggota' => $noAnggota,
                    'nama' => $nama,
                    'no_hp' => $noHp,
                    'tipe' => $tipe,
                    'user_id' => (int) $hasil['user_id'],
                ]
            );

            setFlashMessage('success', 'Nasabah berhasil ditambahkan beserta akunnya. Sampaikan kata sandi baru kepada nasabah.');
        }

        redirect('admin/nasabah');
        return [];
    }

    /**
     * Nonaktifkan / Aktifkan kembali nasabah (POST + CSRF).
     *
     * Menggantikan hapus permanen. Riwayat transaksi dan audit log harus
     * tetap utuh, jadi nasabah tidak pernah dihapus, hanya dinonaktifkan.
     *
     * Status pada tabel nasabah, status akun aplikasi, dan audit log ditulis
     * dalam satu transaksi database. Bila salah satu langkah gagal, semua
     * perubahan dibatalkan.
     */
    public function nasabahToggleStatus(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            setFlashMessage('error', 'Permintaan tidak valid.');
            redirect('admin/nasabah');
            return;
        }

        if (!$this->csrfValid()) {
            setFlashMessage('error', 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.');
            redirect('admin/nasabah');
            return;
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $aksi = isset($_POST['aksi']) && is_string($_POST['aksi']) ? $_POST['aksi'] : '';

        if (!in_array($aksi, ['nonaktifkan', 'aktifkan'], true)) {
            setFlashMessage('error', 'Aksi tidak valid.');
            redirect('admin/nasabah');
            return;
        }

        $nasabah = $id > 0 ? $this->nasabahModel->findById($id) : null;
        if (!$nasabah) {
            setFlashMessage('error', 'Nasabah tidak ditemukan.');
            redirect('admin/nasabah');
            return;
        }

        $statusBaru = $aksi === 'nonaktifkan' ? 'NONAKTIF' : 'AKTIF';
        $statusLama = strtoupper((string) ($nasabah['status'] ?? 'AKTIF'));

        if ($statusLama === $statusBaru) {
            setFlashMessage('info', 'Nasabah ' . $nasabah['nama'] . ' sudah '
                . ($statusBaru === 'AKTIF' ? 'aktif' : 'nonaktif') . '.');
            redirect('admin/nasabah');
            return;
        }

        $db = $this->nasabahModel->getDb();

        try {
            $db->beginTransaction();

            if ($statusBaru === 'NONAKTIF') {
                $this->nasabahModel->nonaktifkan($id);
            } else {
                $this->nasabahModel->aktifkan($id);
            }

            // Akun aplikasi ikut mengikuti status nasabah, supaya tidak bisa
            // login lagi padahal nasabahnya sudah dinonaktifkan.
            if (!empty($nasabah['user_id'])) {
                $this->userModel->update((int) $nasabah['user_id'], ['status' => $statusBaru]);
            }

            audit_log(
                $aksi === 'nonaktifkan' ? 'NONAKTIFKAN' : 'AKTIFKAN',
                'nasabah',
                $id,
                ['status' => $statusLama],
                ['status' => $statusBaru]
            );

            $db->commit();
        } catch (Throwable $ex) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('[Kelola Nasabah] ' . $ex->getMessage());
            setFlashMessage('error', 'Status nasabah gagal diubah. Silakan coba lagi.');
            redirect('admin/nasabah');
            return;
        }

        setFlashMessage('success', 'Nasabah ' . $nasabah['nama']
            . ($statusBaru === 'AKTIF'
                ? ' diaktifkan kembali.'
                : ' dinonaktifkan. Riwayatnya tetap tersimpan.'));
        redirect('admin/nasabah');
    }

    /**
     * Reset kata sandi nasabah dari daftar (POST + CSRF). Admin mengetik kata sandi
     * sementara baru, dan nasabah wajib menggantinya saat login berikutnya.
     */
    public function nasabahResetPassword(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            setFlashMessage('error', 'Permintaan tidak valid.');
            redirect('admin/nasabah');
            return;
        }

        if (!$this->csrfValid()) {
            setFlashMessage('error', 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.');
            redirect('admin/nasabah');
            return;
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id <= 0) {
            setFlashMessage('error', 'Nasabah tidak ditemukan.');
            redirect('admin/nasabah');
            return;
        }

        $nasabah = $this->nasabahModel->findById($id);
        if (!$nasabah) {
            setFlashMessage('error', 'Nasabah tidak ditemukan.');
            redirect('admin/nasabah');
            return;
        }

        $userId = isset($nasabah['user_id']) ? (int) $nasabah['user_id'] : 0;
        if ($userId <= 0) {
            setFlashMessage('error', 'Nasabah ini belum punya akun login untuk direset.');
            redirect('admin/nasabah');
            return;
        }

        if ($userId === (int) currentUserId()) {
            setFlashMessage('error', 'Anda tidak dapat mereset kata sandi akun sendiri di sini.');
            redirect('admin/nasabah');
            return;
        }

        // Akun harus ada dan benar-benar akun Nasabah
        $akun = $this->userModel->findById($userId);
        if (!$akun || ($akun['role'] ?? '') !== 'NASABAH') {
            setFlashMessage('error', 'Akun login nasabah tidak ditemukan.');
            redirect('admin/nasabah');
            return;
        }

        $sementara = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
        $pesanSandi = $this->validasiKataSandiSementara($sementara);
        if ($pesanSandi !== '') {
            setFlashMessage('error', 'Kata sandi ' . $nasabah['nama'] . ' tidak direset. ' . $pesanSandi);
            redirect('admin/nasabah');
            return;
        }

        try {
            $berhasil = $this->userModel->update($userId, [
                'password_hash' => User::hashPassword($sementara),
                'wajib_ganti_sandi' => 1,
            ]);
            if (!$berhasil) {
                throw new RuntimeException('Update kata sandi akun nasabah gagal.');
            }

            // Audit log: hanya penanda wajib_ganti_sandi, tidak ada kata sandi maupun hash
            audit_log(
                'RESET_SANDI',
                'nasabah',
                $id,
                ['wajib_ganti_sandi' => (int) ($akun['wajib_ganti_sandi'] ?? 0)],
                ['wajib_ganti_sandi' => 1]
            );
        } catch (Throwable $ex) {
            error_log('[Kelola Nasabah] ' . $ex->getMessage());
            setFlashMessage('error', 'Kata sandi gagal direset. Silakan coba lagi.');
            redirect('admin/nasabah');
            return;
        }

        setFlashMessage('success', 'Kata sandi ' . $nasabah['nama'] . ' berhasil direset. Nasabah wajib menggantinya saat login berikutnya.');
        redirect('admin/nasabah');
    }

    /**
     * Audit Log
     */
    public function auditLog(): void
    {
        require_once MODEL_PATH . '/AuditLog.php';

        $auditModel = new AuditLog();

        $filters = [];
        if (!empty($_GET['tabel'])) {
            $filters['tabel'] = $_GET['tabel'];
        }
        if (!empty($_GET['aksi'])) {
            $filters['aksi'] = $_GET['aksi'];
        }
        if (!empty($_GET['tanggal_from'])) {
            $filters['tanggal_from'] = $_GET['tanggal_from'];
        }
        if (!empty($_GET['tanggal_to'])) {
            $filters['tanggal_to'] = $_GET['tanggal_to'];
        }

        $logs = $auditModel->getLogs($filters, 100);

        $user = currentUser();
        $flash = getFlashMessage();

        include VIEW_PATH . '/admin/audit_log.php';
    }

    /**
     * Pengaturan Sistem (Placeholder)
     */
    public function pengaturan(): void
    {
        $user = currentUser();
        $flash = getFlashMessage();

        include VIEW_PATH . '/admin/pengaturan/index.php';
    }
}