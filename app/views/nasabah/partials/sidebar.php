<?php
/**
 * nasabah/partials/sidebar.php
 * Sidebar bersama untuk SEMUA halaman Nasabah.
 * Compatible dengan PHP 7.3+
 *
 * Cara pakai di setiap view nasabah:
 *   <?php include VIEW_PATH . '/nasabah/partials/sidebar.php'; ?>
 *   (menu aktif dideteksi otomatis dari URL; $menuAktif opsional sebagai cadangan)
 *
 * Nilai $menuAktif yang tersedia:
 *   nasabah/dashboard, nasabah/setor (Harga Sampah), nasabah/riwayat,
 *   nasabah/cairkan, nasabah/profil
 *
 * Catatan:
 * - Cukup ubah daftar menu di $navMenu di bawah; semua halaman ikut berubah.
 * - Gaya sidebar ada di file ini (kelas .ns-*), jadi tidak bergantung pada CSS tiap halaman.
 * - Buka/tutup memakai kelas "nav-open" pada <body> (atau <html>), sama seperti halaman yang sudah ada.
 * - Lebar mengikuti variabel --nav-w atau --sidebar-w milik halaman.
 * - Elemen berisi id="sidebar" supaya tombol burger (aria-controls) tetap menemukannya.
 */

if (!function_exists('nasabahNavIcon')) {
    /**
     * Ikon SVG garis (stroke 1.75), khusus untuk sidebar nasabah.
     */
    function nasabahNavIcon(string $name): string
    {
        $paths = [
            'leaf'     => '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>',
            'grid'     => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/>',
            'tag'      => '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L2 12V2h10l8.6 8.6a2 2 0 0 1 0 2.8Z"/><circle cx="7" cy="7" r="1.5"/>',
            'history'  => '<path d="M3 12a9 9 0 1 0 2.6-6.4"/><polyline points="3 4 3 9 8 9"/><path d="M12 7v5l3 2"/>',
            'banknote' => '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/>',
            'user'     => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
            'logout'   => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
        ];

        $body = isset($paths[$name]) ? $paths[$name] : $paths['grid'];

        return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
            . ' stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"'
            . ' aria-hidden="true" focusable="false">' . $body . '</svg>';
    }
}

// Daftar menu (satu-satunya tempat untuk mengubah menu nasabah).
// Format: [judul grup, [[rute, label, ikon], ...]]
$navMenu = [
    ['Menu utama', [
        ['nasabah/dashboard', 'Beranda',           'grid'],
        ['nasabah/setor',     'Harga Sampah',      'tag'],
        ['nasabah/riwayat',   'Riwayat Transaksi', 'history'],
    ]],
    ['Keuangan', [
        ['nasabah/cairkan',   'Cairkan Saldo',     'banknote'],
    ]],
    ['Akun', [
        ['nasabah/profil',    'Profil Saya',       'user'],
    ]],
];

// Menu aktif dideteksi otomatis dari URL, jadi tidak bergantung pada $menuAktif.
// $menuAktif hanya dipakai sebagai cadangan bila URL tidak cocok dengan menu mana pun.
$navAktif = '';
$navPath = trim((string) parse_url(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '', PHP_URL_PATH), '/');
foreach ($navMenu as $navGrupCek) {
    foreach ($navGrupCek[1] as $navItemCek) {
        $navRute = $navItemCek[0];
        if ($navPath === $navRute
            || substr($navPath, -(strlen($navRute) + 1)) === '/' . $navRute
            || strpos($navPath, $navRute . '/') !== false) {
            $navAktif = $navRute;
            break 2;
        }
    }
}
if ($navAktif === '' && isset($menuAktif)) {
    $navAktif = (string) $menuAktif;
}
$navApp = defined('APP_NAME') ? APP_NAME : 'Bank Sampah';
?>
<style>
.ns-side, .ns-side * { box-sizing: border-box; }
.ns-side {
    position: fixed; top: 0; left: 0; bottom: 0; z-index: 100;
    width: var(--nav-w, var(--sidebar-w, 272px)); max-width: 86vw;
    display: flex; flex-direction: column;
    color: #fff;
    font-family: 'Plus Jakarta Sans', 'Segoe UI', system-ui, sans-serif;
    font-size: 16px; line-height: 1.5;
    background: linear-gradient(170deg, #2e7d32 0%, #1b5e20 60%, #0f3d14 100%);
    transform: translateX(-100%);
    transition: transform .25s ease;
}
.nav-open .ns-side { transform: none; }
.ns-side svg { display: block; flex: none; width: 20px; height: 20px; }
.ns-side a { text-decoration: none; color: inherit; }

.ns-side-head { padding: 20px 14px 12px; }
.ns-brand { display: flex; align-items: center; gap: 12px; padding: 4px 8px; border-radius: 14px; }
.ns-brand-mark { width: 42px; height: 42px; border-radius: 14px; background: rgba(255,255,255,.16); display: grid; place-items: center; flex-shrink: 0; }
.ns-brand-mark svg { width: 22px; height: 22px; }
.ns-brand-name { display: block; font-weight: 800; line-height: 1.2; overflow-wrap: anywhere; }
.ns-brand-sub { display: block; font-size: .85rem; opacity: .8; }

.ns-side-nav { flex: 1; min-height: 0; overflow-y: auto; padding: 4px 14px 8px; }
.ns-group + .ns-group { margin-top: 20px; }
.ns-group-label { margin: 0 0 6px; padding: 0 12px; font-size: .8125rem; font-weight: 600; color: rgba(255,255,255,.8); }
.ns-link { display: flex; align-items: center; gap: 12px; min-height: 50px; padding: 0 14px; border-radius: 14px; color: rgba(255,255,255,.95); font-weight: 500; transition: background .15s; }
.ns-link + .ns-link { margin-top: 3px; }
.ns-link:hover { background: rgba(255,255,255,.1); color: #fff; }
.ns-link.active { background: rgba(255,255,255,.2); color: #fff; font-weight: 700; }

.ns-side-foot { padding: 12px 14px 16px; border-top: 1px solid rgba(255,255,255,.14); }
.ns-link.danger { color: #ffd9d6; }
.ns-link.danger:hover { background: rgba(230,90,80,.22); color: #ffd9d6; }

.ns-side a:focus-visible { outline: 3px solid #fff; outline-offset: 2px; }
@media (prefers-reduced-motion: reduce) { .ns-side, .ns-link { transition: none; } }
</style>

<aside class="ns-side" id="sidebar" aria-label="Menu utama">
    <div class="ns-side-head">
        <a href="<?= base_url('nasabah/dashboard') ?>" class="ns-brand">
            <span class="ns-brand-mark"><?= nasabahNavIcon('leaf') ?></span>
            <span>
                <span class="ns-brand-name"><?= htmlspecialchars($navApp, ENT_QUOTES, 'UTF-8') ?></span>
                <span class="ns-brand-sub">Tabungan sampah digital</span>
            </span>
        </a>
    </div>

    <nav class="ns-side-nav" aria-label="Navigasi nasabah">
        <?php foreach ($navMenu as $navGrup): ?>
            <div class="ns-group">
                <div class="ns-group-label"><?= htmlspecialchars($navGrup[0], ENT_QUOTES, 'UTF-8') ?></div>
                <?php foreach ($navGrup[1] as $navItem): $navOn = ($navItem[0] === $navAktif); ?>
                    <a href="<?= base_url($navItem[0]) ?>" class="ns-link<?= $navOn ? ' active' : '' ?>"<?= $navOn ? ' aria-current="page"' : '' ?>><?= nasabahNavIcon($navItem[2]) ?><span><?= htmlspecialchars($navItem[1], ENT_QUOTES, 'UTF-8') ?></span></a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </nav>

    <div class="ns-side-foot">
        <a href="<?= base_url('logout') ?>" class="ns-link danger"><?= nasabahNavIcon('logout') ?><span>Keluar</span></a>
    </div>
</aside>