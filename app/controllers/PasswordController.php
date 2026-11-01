<?php

/**
 * Password Controller
 *
 * Halaman ganti kata sandi untuk semua peran (wajib saat login pertama
 * atau setelah reset, dan sukarela kapan saja).
 * Compatible dengan PHP 7.3+
 */

class PasswordController
{
    private const MIN_PANJANG = 8;
    private const MAX_PANJANG = 72; // batas bcrypt (byte)

    /** @var User */
    private $userModel;

    public function __construct()
    {
        requireLogin();

        require_once MODEL_PATH . '/User.php';
        require_once MODEL_PATH . '/AuditLog.php'; // menyediakan helper audit_log()

        $this->userModel = new User();
    }

    /**
     * Tampilkan dan proses form ganti kata sandi.
     */
    public function ganti(): void
    {
        $id = (int) currentUserId();
        $akun = $this->userModel->findById($id);

        if (!$akun) {
            redirect('login');
            return;
        }

        $wajib = (int) ($akun['wajib_ganti_sandi'] ?? 0) === 1;
        $errors = [];

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $errors = $this->prosesGanti($akun);
            if (empty($errors)) {
                return; // Redirect sudah dilakukan
            }
        }

        $user = currentUser();
        $flash = getFlashMessage();
        $csrfToken = csrfToken();
        $minPanjang = self::MIN_PANJANG;

        include VIEW_PATH . '/auth/GantiPassword.php';
    }

    /**
     * Periksa token CSRF dari POST (token dibuat oleh csrfToken() di helpers/auth.php).
     */
    private function csrfValid(): bool
    {
        $dikirim = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
        $asli = isset($_SESSION['csrf_token']) && is_string($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '';

        return $asli !== '' && $dikirim !== '' && hash_equals($asli, $dikirim);
    }

    /**
     * Validasi dan simpan kata sandi baru.
     */
    private function prosesGanti(array $akun): array
    {
        if (!$this->csrfValid()) {
            return ['umum' => 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.'];
        }

        $lama = isset($_POST['password_lama']) && is_string($_POST['password_lama']) ? $_POST['password_lama'] : '';
        $baru = isset($_POST['password_baru']) && is_string($_POST['password_baru']) ? $_POST['password_baru'] : '';
        $konf = isset($_POST['password_konfirmasi']) && is_string($_POST['password_konfirmasi']) ? $_POST['password_konfirmasi'] : '';

        $errors = [];

        if ($lama === '') {
            $errors['password_lama'] = 'Kata sandi saat ini wajib diisi.';
        } elseif (!User::verifyPassword($lama, (string) $akun['password_hash'])) {
            $errors['password_lama'] = 'Kata sandi saat ini salah.';
        }

        if ($baru === '') {
            $errors['password_baru'] = 'Kata sandi baru wajib diisi.';
        } elseif (strlen($baru) < self::MIN_PANJANG) {
            $errors['password_baru'] = 'Kata sandi baru minimal ' . self::MIN_PANJANG . ' karakter.';
        } elseif (strlen($baru) > self::MAX_PANJANG) {
            $errors['password_baru'] = 'Kata sandi baru maksimal ' . self::MAX_PANJANG . ' karakter.';
        } elseif (!preg_match('/[A-Za-z]/', $baru) || !preg_match('/[0-9]/', $baru)) {
            $errors['password_baru'] = 'Kata sandi baru harus mengandung huruf dan angka.';
        } elseif ($lama !== '' && hash_equals($lama, $baru)) {
            $errors['password_baru'] = 'Kata sandi baru tidak boleh sama dengan kata sandi saat ini.';
        }

        if ($konf === '') {
            $errors['password_konfirmasi'] = 'Konfirmasi kata sandi wajib diisi.';
        } elseif (!hash_equals($baru, $konf)) {
            $errors['password_konfirmasi'] = 'Konfirmasi kata sandi tidak sama.';
        }

        if (!empty($errors)) {
            return $errors;
        }

        $id = (int) $akun['id'];

        try {
            $this->userModel->update($id, [
                'password_hash' => User::hashPassword($baru),
                'wajib_ganti_sandi' => 0,
            ]);

            // Audit log: hanya penanda, tidak ada kata sandi maupun hash
            audit_log(
                'GANTI_SANDI',
                'users',
                $id,
                ['wajib_ganti_sandi' => (int) ($akun['wajib_ganti_sandi'] ?? 0)],
                ['wajib_ganti_sandi' => 0]
            );
        } catch (Throwable $ex) {
            error_log('[Ganti Sandi] ' . $ex->getMessage());
            return ['umum' => 'Kata sandi gagal disimpan. Silakan coba lagi.'];
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        setFlashMessage('success', 'Kata sandi berhasil diganti.');
        redirect('dashboard');
        return [];
    }
}