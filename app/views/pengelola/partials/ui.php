<?php
/**
 * pengelola/partials/ui.php
 *
 * Pembantu tampilan bersama untuk halaman pengelola.
 * Compatible dengan PHP 7.3+
 *
 * ---------------------------------------------------------------
 * KERANGKA SATU HALAMAN PENGELOLA
 * ---------------------------------------------------------------
 *   <?php
 *   include_once VIEW_PATH . '/pengelola/partials/ui.php';   // paling atas
 *   $menuAktif = 'pengelola/harga';                          // rute halaman ini
 *   $judulHalaman = 'Harga Sampah';
 *   ?>
 *   <!DOCTYPE html>
 *   <html lang="id">
 *   <head>
 *       <?= pengelolaHead($judulHalaman) ?>
 *       <style> ...hanya gaya khusus halaman ini... </style>
 *   </head>
 *   <body>
 *       <?php include VIEW_PATH . '/pengelola/partials/sidebar.php'; ?>
 *       <div class="main">
 *           <?php include VIEW_PATH . '/pengelola/partials/topbar.php'; ?>
 *           <main class="content"><div class="wrap"> ...isi... </div></main>
 *       </div>
 *       <?= pengelolaScripts() ?>
 *   </body>
 *   </html>
 *
 * ---------------------------------------------------------------
 * ISI FILE INI
 * ---------------------------------------------------------------
 * - icon($nama, $ukuranAtauKelas)   satu-satunya fungsi ikon (SVG garis, tanpa emoji)
 * - esc()                           htmlspecialchars singkat
 * - formatRupiah(), formatBeratKg(), tanggalPanjangIndonesia(), tanggalSingkatIndonesia()
 * - pengelolaHead(), pengelolaScripts()
 * - variabel header: $user, $namaUser, $inisial, $appName, $flash, $flashClass, $flashIcon
 *
 * Semua fungsi dibuat hanya bila belum ada, jadi aman dipakai bersama view lama
 * yang masih punya definisi sendiri. Catatan: bila view lama mendefinisikan icon()
 * SEBELUM file ini dimuat, definisi lama yang berlaku. Hapus definisi lama itu.
 *
 * Opsional: define('ASSET_VERSION', '1'); di konfigurasi, lalu naikkan angkanya
 * setiap CSS/JS berubah supaya peramban memuat ulang file baru.
 */

if (!function_exists('icon')) {
    /**
     * Ikon SVG garis (stroke 1.75) yang konsisten di seluruh aplikasi pengelola.
     *
     * Pemakaian:
     *   icon('plus')              ukuran mengikuti CSS (bawaan 20px)
     *   icon('plus', 18)          ukuran 18px (dipakai view lama)
     *   icon('plus', 'nama-kelas') menambahkan kelas CSS
     *
     * Nama lama yang masih diterima: 'back' (= 'arrow-left').
     */
    function icon(string $name, $arg = ''): string
    {
        $paths = [
            'menu'         => '<path d="M4 7h16M4 12h16M4 17h16"/>',
            'close'        => '<path d="M6 6l12 12M18 6L6 18"/>',
            'leaf'         => '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>',
            'grid'         => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
            'cart'         => '<circle cx="9" cy="20" r="1.25"/><circle cx="18" cy="20" r="1.25"/><path d="M2 3h3l2.6 12.4a1 1 0 0 0 1 .8h8.8a1 1 0 0 0 1-.76L20 8H6"/>',
            'upload'       => '<path d="M12 16V4M7 9l5-5 5 5"/><path d="M4 16v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3"/>',
            'box'          => '<path d="M21 8l-9-5-9 5v8l9 5 9-5V8Z"/><path d="M3.3 7.5L12 12l8.7-4.5M12 12v9"/>',
            'tag'          => '<path d="M20.6 13.4l-7.2 7.2a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6v2.8Z"/><path d="M7.5 7.5h.01"/>',
            'users'        => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
            'truck'        => '<path d="M1 6h13v11H1zM14 10h4l3 3v4h-7"/><circle cx="6" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',
            'banknote'     => '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/>',
            'chart'        => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
            'logout'       => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/>',
            'arrow-left'   => '<path d="M19 12H5M12 19l-7-7 7-7"/>',
            'search'       => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>',
            'download'     => '<path d="M12 4v12M7 11l5 5 5-5"/><path d="M4 20h16"/>',
            'calendar'     => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
            'receipt'      => '<path d="M6 2h12v20l-3-2-3 2-3-2-3 2V2Z"/><path d="M9 8h6M9 12h6"/>',
            'scale'        => '<path d="M12 3v18M5 21h14M5 7h14"/><path d="M5 7l-3 7a3 3 0 0 0 6 0L5 7ZM19 7l-3 7a3 3 0 0 0 6 0l-3-7Z"/>',
            'coins'        => '<ellipse cx="12" cy="6" rx="8" ry="3"/><path d="M4 6v6c0 1.66 3.58 3 8 3s8-1.34 8-3V6"/><path d="M4 12v6c0 1.66 3.58 3 8 3s8-1.34 8-3v-6"/>',
            'wallet'       => '<path d="M19 7V6a2 2 0 0 0-2-2H5a2 2 0 0 0 0 4h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6"/><path d="M16 12h.01"/>',
            'cash'         => '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/>',
            'clock'        => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
            'file'         => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Z"/><path d="M14 2v6h6M9 14h6M9 18h4"/>',
            'info'         => '<circle cx="12" cy="12" r="9"/><line x1="12" y1="11" x2="12" y2="16"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
            'check'        => '<path d="M20 6L9 17l-5-5"/>',
            'check-circle' => '<circle cx="12" cy="12" r="9"/><path d="M8.5 12.5l2.5 2.5 4.5-5"/>',
            'x-circle'     => '<circle cx="12" cy="12" r="9"/><path d="M15 9l-6 6M9 9l6 6"/>',
            'alert'        => '<path d="M12 3l9.5 17h-19L12 3Z"/><path d="M12 10v4M12 17h.01"/>',
            'trash'        => '<path d="M4 7h16M9 7V5h6v2M6 7l1 13h10l1-13"/>',
            'edit'         => '<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z"/>',
            'plus'         => '<path d="M12 5v14M5 12h14"/>',
            'save'         => '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8M7 3v5h8"/>',
            'inbox'        => '<path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.5 5.5h13l3.5 6.5v6a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-6l3.5-6.5Z"/>',
            'user-plus'    => '<path d="M15 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/>',
            'refresh'      => '<path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 4v5h-5"/>',
            'id'           => '<rect x="2" y="4" width="20" height="16" rx="2"/><circle cx="9" cy="10" r="2.5"/><path d="M5.5 17c.8-1.8 2-2.5 3.5-2.5s2.7.7 3.5 2.5M15 9h4M15 13h4"/>',
            'phone'        => '<path d="M21 16.9v2a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 1 3.1 2 2 0 0 1 3 1h2a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L6.1 8.9a16 16 0 0 0 6 6l1.3-1.1a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.8.7A2 2 0 0 1 21 16.9Z"/>',
            'chevron-left' => '<path d="M15 18l-6-6 6-6"/>',
            'chevron-right'=> '<path d="M9 18l6-6-6-6"/>',
            'eye'          => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',
            'eye-off'      => '<path d="M9.9 5.2A9.8 9.8 0 0 1 12 5c6.4 0 10 7 10 7a17 17 0 0 1-2.6 3.6M6.6 6.6A17 17 0 0 0 2 12s3.6 7 10 7a9.6 9.6 0 0 0 4.2-.9"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2M2 2l20 20"/>',
            'ban'          => '<circle cx="12" cy="12" r="9"/><path d="M5.6 5.6l12.8 12.8"/>',
            'user'         => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
            'mail'         => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2.5 6.5 9.5 7 9.5-7"/>',
        ];

        // Nama lama dari view sebelumnya
        $alias = ['back' => 'arrow-left'];
        if (isset($alias[$name])) {
            $name = $alias[$name];
        }

        // Argumen kedua: angka = ukuran, teks = kelas CSS
        $size = 0;
        $cls = '';
        if (is_int($arg) || (is_string($arg) && ctype_digit($arg))) {
            $size = (int) $arg;
        } elseif (is_string($arg) && $arg !== '') {
            $cls = $arg;
        }

        $body = isset($paths[$name]) ? $paths[$name] : $paths['info'];
        $atr = '';
        if ($size > 0) {
            $atr .= ' width="' . $size . '" height="' . $size . '"';
        }
        if ($cls !== '') {
            $atr .= ' class="' . htmlspecialchars($cls, ENT_QUOTES, 'UTF-8') . '"';
        }

        return '<svg' . $atr . ' viewBox="0 0 24 24" fill="none" stroke="currentColor"'
            . ' stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"'
            . ' aria-hidden="true" focusable="false">' . $body . '</svg>';
    }
}

if (!function_exists('esc')) {
    /**
     * htmlspecialchars singkat untuk keluaran ke HTML.
     */
    function esc($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('formatRupiah')) {
    /**
     * Format nominal DECIMAL dari database menjadi "Rp 12.500".
     */
    function formatRupiah($number): string
    {
        return 'Rp ' . number_format((float) $number, 0, ',', '.');
    }
}

if (!function_exists('formatBeratKg')) {
    /**
     * Format berat dari database, contoh: "12,50 kg".
     */
    function formatBeratKg($number): string
    {
        return number_format((float) $number, 2, ',', '.') . ' kg';
    }
}

if (!function_exists('tanggalPanjangIndonesia')) {
    /**
     * Ubah "2026-10-24 13:05:00" menjadi "24 Oktober 2026, 13:05".
     */
    function tanggalPanjangIndonesia($datetime): string
    {
        $ts = strtotime((string) $datetime);
        if (!$ts) {
            return (string) $datetime;
        }

        $bulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return date('j', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts)
            . ', ' . date('H:i', $ts);
    }
}

if (!function_exists('tanggalSingkatIndonesia')) {
    /**
     * Ubah tanggal menjadi "24/10/2026". Mengembalikan "-" bila tidak valid.
     */
    function tanggalSingkatIndonesia($date): string
    {
        $ts = strtotime((string) $date);

        return $ts ? date('d/m/Y', $ts) : '-';
    }
}

if (!function_exists('pengelolaHead')) {
    /**
     * Isi <head> standar halaman pengelola: meta, judul, skrip kondisi awal menu,
     * font, dan assets/css/pengelola.css.
     *
     * Skrip kecil di dalamnya memasang kelas "nav-open" sebelum halaman tampil,
     * supaya sidebar tidak berkedip.
     */
    function pengelolaHead(string $judul = ''): string
    {
        $app = defined('APP_NAME') ? APP_NAME : 'Bank Sampah';
        $title = ($judul !== '' ? $judul . ' - ' : '') . $app;
        $ver = defined('ASSET_VERSION') ? '?v=' . rawurlencode((string) ASSET_VERSION) : '';

        return '<meta charset="UTF-8">' . "\n"
            . '    <meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n"
            . '    <meta name="theme-color" content="#1b5e20">' . "\n"
            . '    <title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>' . "\n"
            . '    <script>(function(){var r=document.documentElement;r.classList.add("no-anim");'
            . 'var s=null;try{s=localStorage.getItem("nav")}catch(e){}'
            . 'if(window.innerWidth>1100&&s!=="closed"){r.classList.add("nav-open")}})();</script>' . "\n"
            . '    <link rel="preconnect" href="https://fonts.googleapis.com">' . "\n"
            . '    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n"
            . '    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">' . "\n"
            . '    <link rel="stylesheet" href="' . htmlspecialchars(base_url('assets/css/Pengelola.css') . $ver, ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('pengelolaScripts')) {
    /**
     * Tag <script> standar di akhir <body>: app.js (ajax, toast, konfirmasi form)
     * lalu pengelola.js (menu, filter, cegah kirim ganda). Urutan ini penting:
     * pengelola.js memeriksa apakah konfirmasi dari app.js sudah membatalkan form.
     */
    function pengelolaScripts(): string
    {
        $ver = defined('ASSET_VERSION') ? '?v=' . rawurlencode((string) ASSET_VERSION) : '';

        return '<script src="' . htmlspecialchars(base_url('assets/js/app.js') . $ver, ENT_QUOTES, 'UTF-8') . '" defer></script>' . "\n"
            . '    <script src="' . htmlspecialchars(base_url('assets/js/Pengelola.js') . $ver, ENT_QUOTES, 'UTF-8') . '" defer></script>';
    }
}

// ---------------------------------------------------------------
// Variabel header (dipakai topbar.php dan view)
// ---------------------------------------------------------------
$user = function_exists('currentUser') ? currentUser() : [];
if (!is_array($user)) {
    $user = [];
}

// Bila controller tidak mengirim $flash, ambil dari helper global (sekali saja)
if (!isset($flash) && function_exists('getFlashMessage')) {
    $flash = getFlashMessage();
}
$flash = isset($flash) && is_array($flash) ? $flash : null;

$namaUser = 'Pengelola';
foreach (['nama', 'username', 'name'] as $kunciNama) {
    if (!empty($user[$kunciNama])) {
        $namaUser = (string) $user[$kunciNama];
        break;
    }
}
$inisial = function_exists('mb_substr')
    ? mb_strtoupper(mb_substr($namaUser, 0, 1, 'UTF-8'), 'UTF-8')
    : strtoupper(substr($namaUser, 0, 1));
$appName = defined('APP_NAME') ? APP_NAME : 'Bank Sampah';

$flashTipe = $flash && isset($flash['type']) ? strtolower((string) $flash['type']) : '';
$flashClass = in_array($flashTipe, ['success', 'error', 'warning', 'info'], true) ? $flashTipe : 'info';
$flashIcon = $flashClass === 'success' ? 'check-circle'
    : ($flashClass === 'error' ? 'x-circle' : ($flashClass === 'warning' ? 'alert' : 'info'));