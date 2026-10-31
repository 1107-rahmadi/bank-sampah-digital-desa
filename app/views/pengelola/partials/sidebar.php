<?php
/**
 * pengelola/partials/sidebar.php
 * Sidebar bersama untuk SEMUA halaman Pengelola.
 * Compatible dengan PHP 7.3+
 *
 * Cara pakai di setiap view pengelola:
 *   $menuAktif = 'pengelola/harga';   // rute halaman yang sedang dibuka
 *   <?php include VIEW_PATH . '/pengelola/partials/sidebar.php'; ?>
 *
 * Catatan:
 * - Cukup ubah daftar menu di $navMenu di bawah; semua halaman ikut berubah.
 * - Gaya sidebar ada di file ini (kelas .pg-*), sehingga tidak bergantung pada CSS tiap halaman.
 * - Buka/tutup memakai kelas "nav-open" pada <html> ATAU <body>, sama seperti halaman yang sudah ada.
 * - Lebar mengikuti variabel --nav-w (atau --sidebar-w) milik halaman, supaya pas dengan margin konten.
 */

if (!function_exists('pengelolaNavIcon')) {
    /**
     * Ikon SVG garis (stroke 1.75), khusus untuk sidebar.
     */
    function pengelolaNavIcon(string $name): string
    {
        $paths = [
            'leaf'     => '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>',
            'grid'     => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/>',
            'cart'     => '<circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.7 12.4a2 2 0 0 0 2 1.6h8.1a2 2 0 0 0 2-1.5L21.5 7H6"/>',
            'upload'   => '<line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/>',
            'tag'      => '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L2 12V2h10l8.6 8.6a2 2 0 0 1 0 2.8Z"/><circle cx="7" cy="7" r="1.5"/>',
            'users'    => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
            'truck'    => '<rect x="1" y="6" width="14" height="11" rx="1.5"/><path d="M15 9h4l3 3v5h-7"/><circle cx="6" cy="18" r="2"/><circle cx="18" cy="18" r="2"/>',
            'banknote' => '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/>',
            'chart'    => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
            'logout'   => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
        ];

        $body = isset($paths[$name]) ? $paths[$name] : $paths['grid'];

        return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
            . ' stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"'
            . ' aria-hidden="true" focusable="false">' . $body . '</svg>';
    }
}

// Daftar menu (satu-satunya tempat untuk mengubah menu pengelola).
// Format: [judul grup, [[rute, label, ikon], ...]]
$navMenu = [
    ['Menu utama', [
        ['pengelola/dashboard',      'Dashboard',       'grid'],
    ]],
    ['Transaksi', [
        ['pengelola/transaksi-beli', 'Transaksi Beli',  'cart'],
        ['pengelola/transaksi-jual', 'Transaksi Jual',  'upload'],
    ]],
    ['Data', [
        ['pengelola/stok',          'Stok Sampah',     'box'],
        ['pengelola/harga',          'Harga Sampah',    'tag'],
        ['pengelola/nasabah',        'Data Nasabah',    'users'],
        ['pengelola/pengepul',       'Pengepul',        'truck'],
    ]],
    ['Keuangan', [
        ['pengelola/pencairan',      'Pencairan',       'banknote'],
        ['pengelola/laporan',        'Laporan',         'chart'],
    ]],
];

$navAktif = isset($menuAktif) ? (string) $menuAktif : '';
$navApp = defined('APP_NAME') ? APP_NAME : 'Bank Sampah';
?>
<style>
.pg-side, .pg-side * { box-sizing: border-box; }
.pg-side {
    position: fixed; top: 0; left: 0; bottom: 0; z-index: 100;
    width: var(--nav-w, var(--sidebar-w, 280px)); max-width: 86vw;
    display: flex; flex-direction: column;
    color: #fff;
    font-family: 'Plus Jakarta Sans', 'Segoe UI', system-ui, sans-serif;
    font-size: 16px; line-height: 1.5;
    background: linear-gradient(170deg, #2e7d32 0%, #1b5e20 60%, #0f3d14 100%);
    transform: translateX(-100%);
    transition: transform .25s ease;
}
.nav-open .pg-side { transform: none; }
.pg-side svg { display: block; flex: none; width: 20px; height: 20px; }
.pg-side a { text-decoration: none; color: inherit; }

.pg-side-head { padding: 20px 14px 12px; }
.pg-brand { display: flex; align-items: center; gap: 12px; padding: 4px 8px; border-radius: 14px; }
.pg-brand-mark { width: 42px; height: 42px; border-radius: 14px; background: rgba(255,255,255,.16); display: grid; place-items: center; flex-shrink: 0; }
.pg-brand-mark svg { width: 22px; height: 22px; }
.pg-brand-name { display: block; font-weight: 800; line-height: 1.2; overflow-wrap: anywhere; }
.pg-brand-sub { display: block; font-size: .85rem; opacity: .8; }

.pg-side-nav { flex: 1; min-height: 0; overflow-y: auto; padding: 4px 14px 8px; }
.pg-group + .pg-group { margin-top: 20px; }
.pg-group-label { margin: 0 0 6px; padding: 0 12px; font-size: .8125rem; font-weight: 600; color: rgba(255,255,255,.8); }
.pg-link { display: flex; align-items: center; gap: 12px; min-height: 50px; padding: 0 14px; border-radius: 14px; color: rgba(255,255,255,.95); font-weight: 500; transition: background .15s; }
.pg-link + .pg-link { margin-top: 3px; }
.pg-link:hover { background: rgba(255,255,255,.1); color: #fff; }
.pg-link.active { background: rgba(255,255,255,.2); color: #fff; font-weight: 700; }

.pg-side-foot { padding: 12px 14px 16px; border-top: 1px solid rgba(255,255,255,.14); }
.pg-link.danger { color: #ffd9d6; }
.pg-link.danger:hover { background: rgba(230,90,80,.22); color: #ffd9d6; }

.pg-side a:focus-visible { outline: 3px solid #fff; outline-offset: 2px; }
@media (prefers-reduced-motion: reduce) { .pg-side, .pg-link { transition: none; } }
</style>

<aside class="pg-side" id="sidebar" aria-label="Menu utama">
    <div class="pg-side-head">
        <a href="<?= base_url('pengelola/dashboard') ?>" class="pg-brand">
            <span class="pg-brand-mark"><?= pengelolaNavIcon('leaf') ?></span>
            <span>
                <span class="pg-brand-name"><?= htmlspecialchars($navApp, ENT_QUOTES, 'UTF-8') ?></span>
                <span class="pg-brand-sub">Pengelola</span>
            </span>
        </a>
    </div>

    <nav class="pg-side-nav" aria-label="Navigasi pengelola">
        <?php foreach ($navMenu as $navGrup): ?>
            <div class="pg-group">
                <div class="pg-group-label"><?= htmlspecialchars($navGrup[0], ENT_QUOTES, 'UTF-8') ?></div>
                <?php foreach ($navGrup[1] as $navItem): $navOn = ($navItem[0] === $navAktif); ?>
                    <a href="<?= base_url($navItem[0]) ?>" class="pg-link<?= $navOn ? ' active' : '' ?>"<?= $navOn ? ' aria-current="page"' : '' ?>><?= pengelolaNavIcon($navItem[2]) ?><span><?= htmlspecialchars($navItem[1], ENT_QUOTES, 'UTF-8') ?></span></a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </nav>

    <div class="pg-side-foot">
        <a href="<?= base_url('logout') ?>" class="pg-link danger"><?= pengelolaNavIcon('logout') ?><span>Keluar</span></a>
    </div>
</aside>