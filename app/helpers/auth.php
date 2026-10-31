<?php
/**
 * Authentication Helper
 *
 * Helper functions untuk autentikasi dan autorisasi
 * Compatible dengan PHP 7.3+
 */

// Load User model
require_once MODEL_PATH . '/User.php';

/**
 * Cek apakah user sudah login
 *
 * @return bool
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Ambil data user yang sedang login dari session
 *
 * @return array|null
 */
function currentUser()
{
    if (!isLoggedIn()) {
        return null;
    }

    return [
        'id' => $_SESSION['user_id'] ?? null,
        'nama' => $_SESSION['user_nama'] ?? null,
        'no_hp' => $_SESSION['user_no_hp'] ?? null,
        'email' => $_SESSION['user_email'] ?? null,
        'role' => $_SESSION['user_role'] ?? null,
        'desa_id' => $_SESSION['user_desa_id'] ?? null,
        'bank_sampah_id' => $_SESSION['user_bank_sampah_id'] ?? null,
    ];
}

/**
 * Ambil ID user yang sedang login
 *
 * @return int|null
 */
function currentUserId()
{
    return $_SESSION['user_id'] ?? null;
}

/**
 * Ambil role user yang sedang login
 *
 * @return string|null
 */
function currentUserRole()
{
    return $_SESSION['user_role'] ?? null;
}

/**
 * Set session user setelah login berhasil
 *
 * @param array $user Data user dari database
 * @return void
 */
function setUserSession(array $user): void
{
    // Regenerate session ID untuk cegah session fixation
    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_nama'] = $user['nama'];
    $_SESSION['user_no_hp'] = $user['no_hp'];
    $_SESSION['user_email'] = $user['email'] ?? null;
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_desa_id'] = $user['desa_id'] ?? null;
    $_SESSION['user_bank_sampah_id'] = $user['bank_sampah_id'] ?? null;
    $_SESSION['login_time'] = time();
}

/**
 * Hapus session user (logout)
 *
 * @return void
 */
function clearUserSession(): void
{
    // Unset semua session user
    unset($_SESSION['user_id']);
    unset($_SESSION['user_nama']);
    unset($_SESSION['user_no_hp']);
    unset($_SESSION['user_email']);
    unset($_SESSION['user_role']);
    unset($_SESSION['user_desa_id']);
    unset($_SESSION['user_bank_sampah_id']);
    unset($_SESSION['login_time']);
}

/**
 * Destroy semua session
 *
 * @return void
 */
function destroySession(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

/**
 * Require user untuk login
 * Redirect ke login page jika belum login
 *
 * @param string $redirectTo Tujuan redirect (default: login)
 * @return void
 */
function requireLogin(string $redirectTo = 'login'): void
{
    if (!isLoggedIn()) {
        setFlashMessage('error', 'Silakan login terlebih dahulu.');
        redirect($redirectTo);
    }
}

/**
 * Require role tertentu
 * Redirect dengan pesan error jika role tidak sesuai
 *
 * @param array $allowedRoles Array role yang diizinkan
 * @param string $redirectTo Tujuan redirect jika ditolak
 * @return void
 */
function requireRole(array $allowedRoles, string $redirectTo = 'dashboard'): void
{
    requireLogin();

    $userRole = strtoupper(currentUserRole() ?? '');

    if (!in_array($userRole, array_map('strtoupper', $allowedRoles))) {
        http_response_code(403);
        setFlashMessage('error', 'Akses ditolak. Anda tidak memiliki izin untuk mengakses halaman ini.');
        redirect($redirectTo);
    }
}

/**
 * Set flash message untuk ditampilkan setelah redirect
 *
 * @param string $type success|error|warning|info
 * @param string $message
 * @return void
 */
function setFlashMessage(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Ambil dan hapus flash message
 *
 * @return array|null
 */
function getFlashMessage()
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Cek apakah ada flash message
 *
 * @return bool
 */
function hasFlashMessage(): bool
{
    return isset($_SESSION['flash']);
}

/**
 * Redirect ke dashboard sesuai role user
 *
 * @return void
 */
function redirectToRoleDashboard(): void
{
    $role = strtoupper(currentUserRole() ?? '');

    $dashboards = [
        'ADMIN' => 'admin/dashboard',
        'PENGELOLA' => 'pengelola/dashboard',
        'KADES' => 'kades/dashboard',
        'NASABAH' => 'nasabah/dashboard',
    ];

    $redirect = $dashboards[$role] ?? 'login';
    redirect($redirect);
}

/**
 * Get role display name
 *
 * @param string|null $role
 * @return string
 */
function getRoleDisplayName($role): string
{
    $names = [
        'ADMIN' => 'Administrator',
        'PENGELOLA' => 'Pengelola',
        'KADES' => 'Kepala Desa',
        'NASABAH' => 'Nasabah',
    ];

    return $names[strtoupper($role ?? '')] ?? 'Unknown';
}

/**
 * Token CSRF untuk session ini.
 *
 * Token dibuat satu kali per session dan dipakai pada semua form POST.
 *
 * @return string
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Input hidden untuk menyisipkan token CSRF ke dalam form.
 *
 * @return string
 */
function csrfField(): string
{
    return '<input type="hidden" name="_token" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Periksa token CSRF pada request POST.
 * Request ditolak (403) bila token tidak ada atau tidak cocok.
 *
 * @return void
 */
function verifyCsrf(): void
{
    $token = $_POST['_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

    if (!is_string($token) || $token === '' || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        setFlashMessage('error', 'Sesi formulir tidak valid atau sudah kedaluwarsa. Silakan muat ulang halaman dan coba lagi.');
        redirect('dashboard');
    }
}

/**
 * Pastikan request memakai metode POST.
 *
 * @param string $redirectTo Halaman tujuan bila method tidak sesuai
 * @return void
 */
function requirePost(string $redirectTo = 'dashboard'): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        setFlashMessage('error', 'Permintaan tidak valid. Gunakan tombol pada halaman daftar.');
        redirect($redirectTo);
    }
}

