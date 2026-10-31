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

    /**
     * Kelola Pengguna - List
     */
    public function users(): void
    {
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $users = $this->userModel->getAll($perPage, $offset);
        $totalUsers = $this->userModel->count();
        $totalPages = max(1, ceil($totalUsers / $perPage));

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

        if ($id) {
            $editUser = $this->userModel->findById($id);
            if (!$editUser) {
                setFlashMessage('error', 'User tidak ditemukan.');
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

        // Proses simpan
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = $this->processUserSave($id);
            if (empty($errors)) {
                return; // Redirect sudah dilakukan
            }
        }

        include VIEW_PATH . '/admin/users/form.php';
    }

    /**
     * Proses simpan user (Tambah/Edit)
     */
    private function processUserSave($id): array
    {
        $errors = $this->validateUserData($_POST, $id);

        if (!empty($errors)) {
            return $errors;
        }

        $data = [
            'nama' => trim($_POST['nama']),
            'no_hp' => trim($_POST['no_hp']),
            'email' => trim($_POST['email'] ?? null),
            'role' => $_POST['role'],
            'status' => $_POST['status'] ?? 'AKTIF',
            'desa_id' => !empty($_POST['desa_id']) ? (int) $_POST['desa_id'] : null,
            'bank_sampah_id' => !empty($_POST['bank_sampah_id']) ? (int) $_POST['bank_sampah_id'] : null,
        ];

        // Jika password diisi
        if (!empty($_POST['password'])) {
            $data['password_hash'] = User::hashPassword($_POST['password']);
        }

        if ($id) {
            // Update
            $this->userModel->update($id, $data);
            setFlashMessage('success', 'User berhasil diupdate.');
        } else {
            // Create
            $data['password_hash'] = User::hashPassword($_POST['password']);
            $this->userModel->create($data);
            setFlashMessage('success', 'User berhasil ditambahkan.');
        }

        redirect('admin/users');
        return [];
    }

    /**
     * Validasi data user
     */
    private function validateUserData(array $post, $id = null): array
    {
        $errors = [];

        if (empty(trim($post['nama'] ?? ''))) {
            $errors[] = 'Nama harus diisi.';
        }
        if (empty(trim($post['no_hp'] ?? ''))) {
            $errors[] = 'No HP harus diisi.';
        }
        if (empty($post['role'])) {
            $errors[] = 'Role harus dipilih.';
        }

        // Validasi password untuk user baru
        if (!$id && empty($post['password'])) {
            $errors[] = 'Password harus diisi untuk user baru.';
        }

        // Validasi password length
        if (!empty($post['password']) && strlen($post['password']) < 6) {
            $errors[] = 'Password minimal 6 karakter.';
        }

        return $errors;
    }

    /**
     * Hapus User
     */
    public function userDelete(): void
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;

        if (!$id) {
            setFlashMessage('error', 'ID user tidak valid.');
            redirect('admin/users');
            return;
        }

        // Tidak boleh hapus diri sendiri
        if ($id === currentUserId()) {
            setFlashMessage('error', 'Anda tidak dapat menghapus akun sendiri.');
            redirect('admin/users');
            return;
        }

        $this->userModel->delete($id);
        setFlashMessage('success', 'User berhasil dihapus.');
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
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $nasabahList = $this->nasabahModel->getAll($perPage, $offset);
        $totalNasabah = $this->nasabahModel->countAktif();
        $totalPages = max(1, ceil($totalNasabah / $perPage));

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

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = $this->processNasabahSave($id);
            if (empty($errors)) {
                return;
            }
        }

        include VIEW_PATH . '/admin/nasabah/form.php';
    }

    /**
     * Proses simpan nasabah
     */
    private function processNasabahSave($id): array
    {
        $errors = [];

        if (empty(trim($_POST['nama'] ?? ''))) {
            $errors[] = 'Nama harus diisi.';
        }
        if (empty(trim($_POST['no_hp'] ?? ''))) {
            $errors[] = 'No HP harus diisi.';
        }
        if (empty($_POST['bank_sampah_id'])) {
            $errors[] = 'Bank Sampah harus dipilih.';
        }
        if (empty(trim($_POST['no_anggota'] ?? ''))) {
            $errors[] = 'No Anggota harus diisi.';
        }

        if (!empty($errors)) {
            return $errors;
        }

        $data = [
            'bank_sampah_id' => (int) $_POST['bank_sampah_id'],
            'no_anggota' => trim($_POST['no_anggota']),
            'nama' => trim($_POST['nama']),
            'no_hp' => trim($_POST['no_hp']),
            'nik' => trim($_POST['nik'] ?? ''),
            'alamat' => trim($_POST['alamat'] ?? ''),
            'rt' => trim($_POST['rt'] ?? ''),
            'rw' => trim($_POST['rw'] ?? ''),
            'tipe' => $_POST['tipe'] ?? 'PERORANGAN',
            'nama_bank' => trim($_POST['nama_bank'] ?? ''),
            'no_rekening' => trim($_POST['no_rekening'] ?? ''),
            'status' => $_POST['status'] ?? 'AKTIF',
        ];

        if ($id) {
            $this->nasabahModel->update($id, $data);
            setFlashMessage('success', 'Nasabah berhasil diupdate.');
        } else {
            $data['tgl_daftar'] = date('Y-m-d');
            $data['saldo'] = 0;
            $this->nasabahModel->create($data);
            setFlashMessage('success', 'Nasabah berhasil ditambahkan.');
        }

        redirect('admin/nasabah');
        return [];
    }

    /**
     * Hapus Nasabah
     */
    public function nasabahDelete(): void
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;

        if (!$id) {
            setFlashMessage('error', 'ID tidak valid.');
            redirect('admin/nasabah');
            return;
        }

        $this->nasabahModel->delete($id);
        setFlashMessage('success', 'Nasabah berhasil dihapus.');
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
