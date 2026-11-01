<?php
/**
 * Monitoring Kegiatan (Kepala Desa)
 *
 * Compatible dengan PHP 7.3+
 *
 * BR-09: halaman ini tidak menampilkan nama, NIK, atau nomor HP warga.
 * Pembelian hanya diberi keterangan jenis penjual (nasabah / bukan nasabah).
 *
 * @var array $recentTrans  tiap item: tanggal, jenis (BELI|JUAL), bank_nama,
 *                          tipe_penjual, pengepul_nama, total_nilai
 */

if (!function_exists('formatRupiah')) {
    function formatRupiah($number): string
    {
        return 'Rp ' . number_format((float) $number, 0, ',', '.');
    }
}

if (!function_exists('namaBulanIndonesia')) {
    function namaBulanIndonesia(int $n): string
    {
        $bulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        return isset($bulan[$n]) ? $bulan[$n] : '';
    }
}

if (!function_exists('icon')) {
    /**
     * Ikon SVG garis yang konsisten (tanpa emoji).
     */
    function icon(string $name, string $class = ''): string
    {
        $paths = [
            'leaf' => '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>',
            'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/>',
            'trending' => '<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>',
            'receipt' => '<path d="M4 2v20l3-2 3 2 3-2 3 2 3-2V2z"/><path d="M8 8h8M8 12h8"/>',
            'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
            'cart' => '<circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.7 12.4a2 2 0 0 0 2 1.6h8.1a2 2 0 0 0 2-1.5L21.5 7H6"/>',
            'send' => '<line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/>',
            'arrow-left' => '<line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>',
            'check-circle' => '<path d="M22 11.1V12a10 10 0 1 1-5.9-9.1"/><polyline points="22 4 12 14.01 9 11.01"/>',
            'x-circle' => '<circle cx="12" cy="12" r="9"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>',
            'alert' => '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
            'info' => '<circle cx="12" cy="12" r="9"/><line x1="12" y1="11" x2="12" y2="16"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
            'inbox' => '<polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.5 5.1 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.5-6.9A2 2 0 0 0 16.8 4H7.2a2 2 0 0 0-1.7 1.1z"/>',
        ];

        $body = isset($paths[$name]) ? $paths[$name] : $paths['info'];
        $cls = $class !== '' ? ' class="' . htmlspecialchars($class) . '"' : '';

        return '<svg' . $cls . ' viewBox="0 0 24 24" fill="none" stroke="currentColor"'
            . ' stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"'
            . ' aria-hidden="true" focusable="false">' . $body . '</svg>';
    }
}

$user = currentUser();
$recentTrans = (isset($recentTrans) && is_array($recentTrans)) ? $recentTrans : [];

$namaUser = (string) ($user['nama'] ?? 'Kepala Desa');
$inisial = function_exists('mb_substr') ? mb_strtoupper(mb_substr($namaUser, 0, 1)) : strtoupper(substr($namaUser, 0, 1));
$appName = defined('APP_NAME') ? APP_NAME : 'Bank Sampah';

/* Kelompokkan per tanggal (data diasumsikan sudah terurut terbaru lebih dulu) */
$grup = [];
foreach ($recentTrans as $t) {
    $ts = isset($t['tanggal']) ? strtotime((string) $t['tanggal']) : false;
    if ($ts) {
        $kunci = date('Y-m-d', $ts);
        $label = (int) date('j', $ts) . ' ' . namaBulanIndonesia((int) date('n', $ts)) . ' ' . date('Y', $ts);
        $jamTampil = date('H:i', $ts);
    } else {
        $kunci = 'tanpa-tanggal';
        $label = 'Tanggal tidak diketahui';
        $jamTampil = '-';
    }
    if (!isset($grup[$kunci])) {
        $grup[$kunci] = ['label' => $label, 'items' => []];
    }
    $t['_jam'] = $jamTampil;
    $grup[$kunci]['items'][] = $t;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#1b5e20">
<title>Monitoring - <?= htmlspecialchars($appName) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
    --g-900: #0f3d14;
    --g-800: #1b5e20;
    --g-700: #2e7d32;
    --g-600: #43a047;
    --g-500: #66bb6a;
    --g-200: #c8e6c9;
    --g-100: #e8f5e9;
    --g-50: #f4faf4;
    --paper: #f7faf6;
    --card: #ffffff;
    --ink: #17231a;
    --ink-2: #3b4a3e;
    --ink-3: #5b6a5e;
    --line: #e6ece5;
    --amber: #a85a00;
    --amber-bg: #fff1d6;
    --rose: #b3261e;
    --rose-bg: #fde8e6;
    --sky: #1a56c4;
    --sky-bg: #e2edff;
    --sidebar-w: 268px;
    --topbar-h: 68px;
    --ease: cubic-bezier(0.4, 0, 0.2, 1);
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { -webkit-text-size-adjust: 100%; }
body {
    font-family: 'Plus Jakarta Sans', 'Segoe UI', system-ui, -apple-system, sans-serif;
    font-size: 16px; line-height: 1.5;
    color: var(--ink-2); background: var(--paper);
    -webkit-font-smoothing: antialiased;
}
svg { display: block; }
a { color: inherit; }
button { font-family: inherit; }
:focus-visible { outline: 3px solid var(--g-600); outline-offset: 2px; border-radius: 6px; }

/* ---------- Sidebar ---------- */
.scrim {
    position: fixed; inset: 0; background: rgba(15,30,18,0.5);
    opacity: 0; visibility: hidden; z-index: 90;
    transition: opacity .25s var(--ease), visibility .25s var(--ease);
}
.sidebar {
    position: fixed; top: 0; left: 0; bottom: 0; z-index: 100;
    width: var(--sidebar-w); display: flex; flex-direction: column;
    background: linear-gradient(170deg, var(--g-700) 0%, var(--g-800) 60%, var(--g-900) 100%);
    color: #fff; transform: translateX(-100%);
    transition: transform .3s var(--ease);
}
.side-head { padding: 1.25rem 1.25rem 1rem; }
.side-brand { display: flex; align-items: center; gap: .75rem; text-decoration: none; }
.side-mark {
    width: 42px; height: 42px; border-radius: 12px; flex-shrink: 0;
    background: rgba(255,255,255,.16);
    display: flex; align-items: center; justify-content: center;
}
.side-mark svg { width: 22px; height: 22px; }
.side-name { display: block; font-size: 1rem; font-weight: 700; line-height: 1.25; }
.side-sub { display: block; font-size: .75rem; color: rgba(255,255,255,.85); }
.side-nav { flex: 1; overflow-y: auto; padding: .5rem .875rem; }
.side-group + .side-group { margin-top: 1.25rem; }
.side-group-label { font-size: .8125rem; font-weight: 600; color: rgba(255,255,255,.8); padding: 0 .75rem; margin-bottom: .375rem; }
.side-link {
    display: flex; align-items: center; gap: .75rem;
    min-height: 50px; padding: .625rem .875rem;
    border-radius: 12px; text-decoration: none;
    font-size: 1rem; font-weight: 500; color: rgba(255,255,255,.95);
    transition: background .18s var(--ease);
}
.side-link + .side-link { margin-top: 3px; }
.side-link svg { width: 20px; height: 20px; flex-shrink: 0; }
.side-link:hover { background: rgba(255,255,255,.1); }
.side-link.active { background: rgba(255,255,255,.2); font-weight: 700; }
.side-foot { padding: .875rem .875rem 1rem; border-top: 1px solid rgba(255,255,255,.12); }
.side-link.danger { color: #ffd9d6; }
.side-link.danger:hover { background: rgba(230,90,80,.22); }

body.nav-open .sidebar { transform: translateX(0); }
body.nav-open .scrim { opacity: 1; visibility: visible; }
body.nav-open { overflow: hidden; }

/* ---------- Topbar ---------- */
.main { min-width: 0; transition: margin-left .3s var(--ease); }
.topbar {
    height: var(--topbar-h); display: flex; align-items: center; gap: .875rem;
    padding: 0 1.75rem; position: sticky; top: 0; z-index: 60;
    background: rgba(247,250,246,.9);
    backdrop-filter: saturate(180%) blur(12px);
    -webkit-backdrop-filter: saturate(180%) blur(12px);
}
.burger {
    width: 46px; height: 46px; flex-shrink: 0; cursor: pointer;
    display: flex; flex-direction: column; justify-content: center; gap: 6px; padding: 0 12px;
    border: 1px solid var(--line); border-radius: 14px; background: var(--card);
    transition: background .18s var(--ease);
}
.burger:hover { background: var(--g-100); }
.burger-bar { display: block; width: 20px; height: 2.5px; border-radius: 2px; background: var(--g-800); transition: transform .25s var(--ease), opacity .15s var(--ease); }
body.nav-open .burger-bar:nth-child(1) { transform: translateY(8.5px) rotate(45deg); }
body.nav-open .burger-bar:nth-child(2) { opacity: 0; }
body.nav-open .burger-bar:nth-child(3) { transform: translateY(-8.5px) rotate(-45deg); }
.topbar-brand { font-size: 1.0625rem; font-weight: 700; color: var(--ink); letter-spacing: -.015em; }
.who { margin-left: auto; display: flex; align-items: center; gap: .625rem; padding: .25rem .25rem .25rem .875rem; }
.who-name { font-size: .9375rem; font-weight: 600; color: var(--ink); }
.who-avatar {
    width: 42px; height: 42px; border-radius: 50%; flex-shrink: 0;
    background: linear-gradient(140deg, var(--g-500), var(--g-700)); color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; font-weight: 700;
}

/* ---------- Halaman ---------- */
.page { max-width: 880px; width: 100%; margin: 0 auto; padding: .5rem 1.75rem 3rem; }
.back {
    display: inline-flex; align-items: center; gap: .5rem;
    min-height: 44px; padding: 0 .75rem; margin: 0 0 .5rem -.75rem;
    border-radius: 12px; font-size: .9375rem; font-weight: 600;
    color: var(--g-700); text-decoration: none;
    transition: background .18s var(--ease);
}
.back:hover { background: var(--g-100); }
.back svg { width: 18px; height: 18px; }
.page-title { font-size: 1.75rem; font-weight: 800; color: var(--ink); letter-spacing: -.03em; line-height: 1.2; }
.page-sub { margin-top: .25rem; margin-bottom: 1.5rem; font-size: 1rem; color: var(--ink-3); }

/* ---------- Daftar aktivitas ---------- */
.group-label { margin: 1.5rem 0 .5rem; font-size: .875rem; font-weight: 600; color: var(--ink-3); }
.group-label:first-of-type { margin-top: 0; }
.list { background: var(--card); border: 1px solid var(--line); border-radius: 20px; padding: .25rem 1.125rem; }
.row { display: flex; align-items: center; gap: .875rem; padding: .875rem 0; border-bottom: 1px solid var(--line); }
.row:last-child { border-bottom: 0; }
.row-ico {
    width: 46px; height: 46px; border-radius: 50%; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
}
.row-ico svg { width: 21px; height: 21px; }
.row-ico.beli { background: var(--g-100); color: var(--g-700); }
.row-ico.jual { background: var(--sky-bg); color: var(--sky); }
.row-body { flex: 1; min-width: 0; }
.row-title { display: block; font-size: 1rem; font-weight: 600; color: var(--ink); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.row-sub { display: block; font-size: .875rem; color: var(--ink-3); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.row-end { text-align: right; flex-shrink: 0; }
.row-amount { display: block; font-size: 1rem; font-weight: 700; font-variant-numeric: tabular-nums; color: var(--ink); }
.badge {
    display: inline-block; margin-top: .25rem; padding: .1875rem .625rem; border-radius: 999px;
    font-size: .8125rem; font-weight: 600;
}
.badge-beli { background: var(--g-100); color: var(--g-800); }
.badge-jual { background: var(--sky-bg); color: var(--sky); }

.empty { text-align: center; padding: 2.5rem 1rem; background: var(--card); border: 1px solid var(--line); border-radius: 20px; }
.empty-ico {
    width: 56px; height: 56px; margin: 0 auto .75rem; border-radius: 50%;
    background: var(--g-100); color: var(--g-700);
    display: flex; align-items: center; justify-content: center;
}
.empty-ico svg { width: 26px; height: 26px; }
.empty h3 { font-size: 1rem; font-weight: 700; color: var(--ink); }
.empty p { font-size: .9375rem; color: var(--ink-3); margin-top: .25rem; }

/* ---------- Responsif ---------- */
@media (min-width: 1101px) {
    body.nav-open { overflow: auto; }
    body.nav-open .scrim { opacity: 0; visibility: hidden; }
    body.nav-open .main { margin-left: var(--sidebar-w); }
}
@media (max-width: 720px) {
    .topbar { padding: 0 1rem; }
    .page { padding: .5rem 1rem 3rem; }
    .who { padding-left: 0; }
    .who-name { display: none; }
    .page-title { font-size: 1.5rem; }
    .list { padding: .25rem 1rem; border-radius: 18px; }
    .row { gap: .75rem; }
}
@media (max-width: 380px) {
    .topbar-brand { display: none; }
}
@media (prefers-reduced-motion: reduce) {
    * { transition-duration: .01ms !important; animation-duration: .01ms !important; }
}
</style>
<script>
/* Atur kondisi awal sidebar sebelum tampil, supaya tidak berkedip */
(function () {
    try {
        var pref = localStorage.getItem('nav');
        window.__navOpen = window.innerWidth > 1100 && pref !== 'closed';
    } catch (e) {
        window.__navOpen = window.innerWidth > 1100;
    }
})();
</script>
</head>
<body>
<script>
if (window.__navOpen) { document.body.classList.add('nav-open'); }
</script>

<div class="scrim" id="scrim"></div>

<aside class="sidebar" id="sidebar" aria-label="Navigasi utama">
    <div class="side-head">
        <a href="<?= base_url('kades/dashboard') ?>" class="side-brand">
            <span class="side-mark" aria-hidden="true"><?= icon('leaf') ?></span>
            <span>
                <span class="side-name"><?= htmlspecialchars($appName) ?></span>
                <span class="side-sub">Panel Kepala Desa</span>
            </span>
        </a>
    </div>

    <nav class="side-nav">
        <div class="side-group">
            <div class="side-group-label">Menu utama</div>
            <a href="<?= base_url('kades/dashboard') ?>" class="side-link"><?= icon('grid') ?> <span>Dashboard</span></a>
            <a href="<?= base_url('kades/monitoring') ?>" class="side-link active" aria-current="page"><?= icon('trending') ?> <span>Monitoring</span></a>
        </div>
        <div class="side-group">
            <div class="side-group-label">Keuangan</div>
            <a href="<?= base_url('kades/laporan') ?>" class="side-link"><?= icon('receipt') ?> <span>Laporan Keuangan</span></a>
        </div>
    </nav>

    <div class="side-foot">
        <a href="<?= base_url('logout') ?>" class="side-link danger"><?= icon('logout') ?> <span>Keluar</span></a>
    </div>
</aside>

<div class="main">
    <header class="topbar">
        <button type="button" class="burger" id="burger" aria-label="Buka menu" aria-expanded="false" aria-controls="sidebar">
            <span class="burger-bar"></span><span class="burger-bar"></span><span class="burger-bar"></span>
        </button>
        <div class="topbar-brand"><?= htmlspecialchars($appName) ?></div>
        <div class="who">
            <span class="who-name"><?= htmlspecialchars($namaUser) ?></span>
            <span class="who-avatar" aria-hidden="true"><?= htmlspecialchars($inisial) ?></span>
        </div>
    </header>

    <main class="page">
        <a href="<?= base_url('kades/dashboard') ?>" class="back"><?= icon('arrow-left') ?> Dashboard</a>

        <h1 class="page-title">Monitoring Kegiatan</h1>
        <p class="page-sub">Aktivitas terbaru &middot; 20 transaksi terakhir</p>

        <?php if (!empty($grup)): ?>
            <?php foreach ($grup as $g): ?>
                <h2 class="group-label"><?= htmlspecialchars($g['label']) ?></h2>
                <div class="list">
                    <?php foreach ($g['items'] as $t): ?>
                        <?php
                        $isBeli = (isset($t['jenis']) && $t['jenis'] === 'BELI');
                        if ($isBeli) {
                            // BR-09: tanpa nama warga, hanya jenis penjual
                            $ket = (isset($t['tipe_penjual']) && $t['tipe_penjual'] === 'NASABAH')
                                ? 'Pembelian dari nasabah'
                                : 'Pembelian dari warga (bukan nasabah)';
                        } else {
                            // Pengepul adalah mitra usaha, bukan data pribadi warga
                            $ket = (string) ($t['pengepul_nama'] ?? '-');
                        }
                        $bank = (string) ($t['bank_nama'] ?? '-');
                        ?>
                        <div class="row">
                            <span class="row-ico <?= $isBeli ? 'beli' : 'jual' ?>" aria-hidden="true"><?= icon($isBeli ? 'cart' : 'send') ?></span>
                            <span class="row-body">
                                <span class="row-title"><?= htmlspecialchars($ket) ?></span>
                                <span class="row-sub"><?= htmlspecialchars($bank) ?> &middot; <?= htmlspecialchars($t['_jam']) ?></span>
                            </span>
                            <span class="row-end">
                                <span class="row-amount"><?= formatRupiah($t['total_nilai'] ?? 0) ?></span>
                                <span class="badge <?= $isBeli ? 'badge-beli' : 'badge-jual' ?>"><?= $isBeli ? 'Beli' : 'Jual' ?></span>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty">
                <div class="empty-ico" aria-hidden="true"><?= icon('inbox') ?></div>
                <h3>Belum ada aktivitas</h3>
                <p>Transaksi pembelian dan penjualan akan muncul di sini.</p>
            </div>
        <?php endif; ?>
    </main>
</div>

<script>
(function () {
    var body = document.body;
    var burger = document.getElementById('burger');
    var scrim = document.getElementById('scrim');
    var mq = window.matchMedia('(min-width: 1101px)');

    function save(k, v) { try { localStorage.setItem(k, v); } catch (e) {} }

    function syncBurger() {
        var open = body.classList.contains('nav-open');
        burger.setAttribute('aria-expanded', open ? 'true' : 'false');
        burger.setAttribute('aria-label', open ? 'Tutup menu' : 'Buka menu');
    }
    function setNav(open) {
        body.classList.toggle('nav-open', open);
        if (mq.matches) { save('nav', open ? 'open' : 'closed'); }
        syncBurger();
    }
    burger.addEventListener('click', function () { setNav(!body.classList.contains('nav-open')); });
    scrim.addEventListener('click', function () { setNav(false); });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && body.classList.contains('nav-open') && !mq.matches) {
            setNav(false);
            burger.focus();
        }
    });
    var onChange = function () {
        var saved = null;
        try { saved = localStorage.getItem('nav'); } catch (e) {}
        body.classList.toggle('nav-open', mq.matches && saved !== 'closed');
        syncBurger();
    };
    if (mq.addEventListener) { mq.addEventListener('change', onChange); }
    else if (mq.addListener) { mq.addListener(onChange); }
    syncBurger();
})();
</script>
</body>
</html>