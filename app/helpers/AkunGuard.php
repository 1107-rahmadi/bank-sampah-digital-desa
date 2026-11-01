<?php
/**
 * Penjaga akun: status akun dan wajib ganti kata sandi
 * Compatible dengan PHP 7.3+
 *
 * Dipanggil di AKHIR requireLogin(), setelah pengecekan login lolos:
 *
 *     require_once __DIR__ . '/AkunGuard.php';
 *     tegakkanStatusDanSandi();
 *
 * Fungsinya:
 *  1. Akun yang dinonaktifkan langsung dikeluarkan pada permintaan berikutnya
 *     (sesi yang sedang berjalan ikut terputus, bukan hanya login baru yang ditolak).
 *  2. Akun dengan wajib_ganti_sandi = 1 dialihkan ke halaman ganti kata sandi
 *     dan tidak bisa membuka halaman lain sebelum menggantinya.
 */

if (!function_exists('sandi_csrf_token')) {
    /**
     * Token CSRF (satu kunci sesi yang sama dengan AdminController).
     */
    function sandi_csrf_token(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('sandi_csrf_valid')) {
    /**
     * Periksa token CSRF dari POST.
     */
    function sandi_csrf_valid(): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $dikirim = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
        $asli = isset($_SESSION['csrf_token']) && is_string($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '';

        return $asli !== '' && $dikirim !== '' && hash_equals($asli, $dikirim);
    }
}

if (!function_exists('tegakkanStatusDanSandi')) {
    function tegakkanStatusDanSandi(): void
    {
        static $sudahDiperiksa = false;
        if ($sudahDiperiksa) {
            return;
        }
        $sudahDiperiksa = true;

        $id = (int) currentUserId();
        if ($id <= 0) {
            return;
        }

        require_once MODEL_PATH . '/User.php';
        $akun = (new User())->findById($id);

        // Akun hilang atau nonaktif: keluarkan sekarang juga
        if (!$akun || !User::isActive($akun)) {
            $_SESSION = [];
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
            }
            setFlashMessage('error', 'Akun Anda tidak aktif. Hubungi Admin.');
            redirect('login');
            exit;
        }

        // Wajib ganti kata sandi: hanya halaman ganti kata sandi dan keluar yang boleh dibuka
        if ((int) ($akun['wajib_ganti_sandi'] ?? 0) === 1) {
            $path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
            $path = rtrim($path, '/');
            if (!preg_match('#/(ganti-password|logout)$#', $path)) {
                redirect('ganti-password');
                exit;
            }
        }
    }
}