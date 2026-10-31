<?php
/**
 * Dashboard Kepala Desa (versi e-wallet)
 *
 * Compatible dengan PHP 7.3+
 *
 * @var array      $stats      total_bank, total_nilai_beli, total_nilai_jual,
 *                             total_transaksi_beli, total_transaksi_jual
 * @var array      $bankStats  tiap item: bank['nama'], transaksi_beli, nilai_beli,
 *                             transaksi_jual, nilai_jual
 * @var array      $user
 * @var array|null $flash
 */

if (!function_exists('formatRupiah')) {
    function formatRupiah($number): string
    {
        return 'Rp ' . number_format((float) $number, 0, ',', '.');
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
            'wallet' => '<path d="M19 7V6a2 2 0 0 0-2-2H5a2 2 0 0 0 0 4h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6"/><path d="M16 12h.01"/>',
            'building' => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M9 7h.01M15 7h.01M9 11h.01M15 11h.01M9 15h.01M15 15h.01"/>',
            'list' => '<line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>',
            'check-circle' => '<path d="M22 11.1V12a10 10 0 1 1-5.9-9.1"/><polyline points="22 4 12 14.01 9 11.01"/>',
            'x-circle' => '<circle cx="12" cy="12" r="9"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>',
            'alert' => '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
            'info' => '<circle cx="12" cy="12" r="9"/><line x1="12" y1="11" x2="12" y2="16"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
            'inbox' => '<polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.5 5.1 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.5-6.9A2 2 0 0 0 16.8 4H7.2a2 2 0 0 0-1.7 1.1z"/>',
            'chevron' => '<polyline points="9 6 15 12 9 18"/>',
        ];

        $body = isset($paths[$name]) ? $paths[$name] : $paths['info'];
        $cls = $class !== '' ? ' class="' . htmlspecialchars($class) . '"' : '';

        return '<svg' . $cls . ' viewBox="0 0 24 24" fill="none" stroke="currentColor"'
            . ' stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"'
            . ' aria-hidden="true" focusable="false">' . $body . '</svg>';
    }
}

$user = currentUser();
$flash = isset($flash) ? $flash : null;
$stats = (isset($stats) && is_array($stats)) ? $stats : [];
$bankStats = (isset($bankStats) && is_array($bankStats)) ? $bankStats : [];

$totalBank = (float) ($stats['total_bank'] ?? 0);
$totalNilaiBeli = (float) ($stats['total_nilai_beli'] ?? 0);
$totalNilaiJual = (float) ($stats['total_nilai_jual'] ?? 0);
$totalTransaksiBeli = (float) ($stats['total_transaksi_beli'] ?? 0);
$totalTransaksiJual = (float) ($stats['total_transaksi_jual'] ?? 0);

$namaUser = (string) ($user['nama'] ?? 'Kepala Desa');
$namaDepan = trim(explode(' ', trim($namaUser))[0]);
$inisial = function_exists('mb_substr') ? mb_strtoupper(mb_substr($namaUser, 0, 1)) : strtoupper(substr($namaUser, 0, 1));

$jam = (int) date('G');
if ($jam < 11) {
    $sapaan = 'Selamat pagi';
} elseif ($jam < 15) {
    $sapaan = 'Selamat siang';
} elseif ($jam < 18) {
    $sapaan = 'Selamat sore';
} else {
    $sapaan = 'Selamat malam';
}

$flashTipe = $flash && isset($flash['type']) ? strtolower((string) $flash['type']) : '';
$flashClass = in_array($flashTipe, ['success', 'error', 'warning', 'info'], true) ? $flashTipe : 'info';
$flashIcon = $flashClass === 'success' ? 'check-circle'
    : ($flashClass === 'error' ? 'x-circle' : ($flashClass === 'warning' ? 'alert' : 'info'));
$appName = defined('APP_NAME') ? APP_NAME : 'Bank Sampah';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#1b5e20">
<title>Dashboard - <?= htmlspecialchars($appName) ?></title>
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
.sr-only {
    position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
    overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0;
}

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
.page { max-width: 1040px; width: 100%; margin: 0 auto; padding: .5rem 1.75rem 3rem; }
.greet { margin-bottom: 1.25rem; }
.greet-hi { font-size: .9375rem; color: var(--ink-3); }
.greet-name { font-size: 1.75rem; font-weight: 800; color: var(--ink); letter-spacing: -.03em; line-height: 1.2; }
.greet-sub { margin-top: .25rem; font-size: 1rem; color: var(--ink-3); }

.flash {
    display: flex; gap: .625rem; align-items: flex-start;
    padding: .875rem 1.125rem; border-radius: 14px; margin-bottom: 1.25rem;
    font-size: .9375rem; font-weight: 500; border: 1px solid transparent;
}
.flash svg { width: 20px; height: 20px; flex-shrink: 0; margin-top: 2px; }
.flash-success { background: var(--g-50); color: #14532d; border-color: var(--g-200); }
.flash-error { background: var(--rose-bg); color: var(--rose); border-color: #f8c9c5; }
.flash-warning { background: var(--amber-bg); color: var(--amber); border-color: #f6d9a0; }
.flash-info { background: var(--sky-bg); color: var(--sky); border-color: #c4d8fa; }

/* ---------- Kartu ringkasan (hero) ---------- */
.wallet {
    position: relative; overflow: hidden; color: #fff;
    border-radius: 24px; padding: 1.5rem 1.75rem;
    background: linear-gradient(135deg, #43a047 0%, #2e7d32 45%, #14501a 100%);
    box-shadow: 0 14px 32px rgba(27,94,32,.22);
}
.wallet::before, .wallet::after {
    content: ""; position: absolute; border-radius: 50%; pointer-events: none;
    background: rgba(255,255,255,.07);
}
.wallet::before { width: 260px; height: 260px; right: -90px; top: -120px; }
.wallet::after { width: 180px; height: 180px; right: 80px; bottom: -120px; }
.wallet > * { position: relative; z-index: 1; }
.wallet-label { display: flex; align-items: center; gap: .5rem; font-size: 1rem; font-weight: 600; color: rgba(255,255,255,.95); }
.wallet-label svg { width: 20px; height: 20px; }
.wallet-amount {
    margin-top: .5rem; font-size: 2.25rem; font-weight: 800;
    letter-spacing: -.04em; line-height: 1.1; font-variant-numeric: tabular-nums;
}
.wallet-note { margin-top: .375rem; font-size: .9375rem; color: rgba(255,255,255,.92); }
.wallet-split { display: grid; grid-template-columns: repeat(3, 1fr); gap: .875rem; margin-top: 1.25rem; }
.split {
    padding: .75rem 1rem; border-radius: 16px;
    background: rgba(255,255,255,.14);
    border: 1px solid rgba(255,255,255,.18);
}
.split-label { font-size: .875rem; color: rgba(255,255,255,.92); }
.split-value { font-size: 1.1875rem; font-weight: 700; letter-spacing: -.02em; font-variant-numeric: tabular-nums; margin-top: .125rem; }

/* ---------- Aksi cepat ---------- */
.actions { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .875rem; margin: 1.25rem 0 1.5rem; }
.action {
    display: flex; align-items: center; gap: .875rem; text-decoration: none;
    padding: .875rem 1.125rem; border-radius: 20px; min-height: 64px;
    background: var(--card); border: 1px solid var(--line);
    font-size: 1rem; font-weight: 600; color: var(--ink);
    transition: border-color .2s var(--ease), box-shadow .2s var(--ease), transform .2s var(--ease);
}
.action:hover, .action:focus-visible { border-color: var(--g-200); box-shadow: 0 10px 24px rgba(27,94,32,.1); transform: translateY(-2px); }
.action-ico {
    width: 46px; height: 46px; border-radius: 50%; flex-shrink: 0;
    background: var(--g-100); color: var(--g-800);
    display: flex; align-items: center; justify-content: center;
    transition: background .2s var(--ease), color .2s var(--ease);
}
.action-ico svg { width: 21px; height: 21px; }
.action:hover .action-ico, .action:focus-visible .action-ico { background: var(--g-700); color: #fff; }
.action-text { flex: 1; min-width: 0; }
.action-sub { display: block; font-size: .875rem; font-weight: 500; color: var(--ink-3); }
.action > svg { width: 18px; height: 18px; color: var(--ink-3); flex-shrink: 0; }

/* ---------- Rekapitulasi ---------- */
.section-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: .625rem; }
.section-title { font-size: 1.125rem; font-weight: 700; color: var(--ink); letter-spacing: -.02em; }
.panel { background: var(--card); border: 1px solid var(--line); border-radius: 20px; overflow: hidden; }
.table-wrap { overflow-x: auto; }
.rekap { width: 100%; border-collapse: collapse; font-variant-numeric: tabular-nums; }
.rekap th {
    padding: .875rem 1.125rem; text-align: right; white-space: nowrap;
    font-size: .8125rem; font-weight: 600; color: var(--ink-3);
    background: var(--g-50); border-bottom: 1px solid var(--line);
}
.rekap th:first-child, .rekap td:first-child { text-align: left; }
.rekap td { padding: .875rem 1.125rem; text-align: right; font-size: .9375rem; color: var(--ink); border-bottom: 1px solid var(--line); }
.rekap tbody tr:last-child td { border-bottom: 0; }
.rekap tbody tr:hover td { background: var(--g-50); }
.bank-cell { display: flex; align-items: center; gap: .75rem; }
.bank-ico {
    width: 38px; height: 38px; border-radius: 50%; flex-shrink: 0;
    background: var(--g-100); color: var(--g-700);
    display: flex; align-items: center; justify-content: center;
}
.bank-ico svg { width: 18px; height: 18px; }
.bank-name { font-weight: 700; color: var(--ink); }
.rekap tfoot td {
    font-weight: 700; background: var(--g-50);
    border-top: 1px solid var(--line); border-bottom: 0;
}
.num-beli { color: var(--g-700); font-weight: 600; }
.num-jual { color: var(--sky); font-weight: 600; }

.empty { text-align: center; padding: 2.5rem 1rem; }
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
    .greet-name { font-size: 1.5rem; }
    .wallet { padding: 1.25rem 1.125rem; border-radius: 22px; }
    .wallet-amount { font-size: 2rem; }
    .wallet-split { grid-template-columns: 1fr 1fr; gap: .625rem; }
    .split { padding: .75rem .875rem; }
    .split-value { font-size: 1.0625rem; }
    .split:first-child { grid-column: 1 / -1; }
    .actions { grid-template-columns: minmax(0, 1fr); }

    /* Tabel berubah menjadi kartu per bank sampah */
    .rekap, .rekap tbody, .rekap tfoot, .rekap tr, .rekap td { display: block; width: 100%; }
    .rekap thead {
        position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0,0,0,0);
    }
    .rekap tr { padding: .5rem 1rem 1rem; border-bottom: 1px solid var(--line); }
    .rekap tbody tr:last-child { border-bottom: 0; }
    .rekap td { padding: .375rem 0; border: 0; text-align: right; display: flex; justify-content: space-between; gap: 1rem; font-size: .9375rem; }
    .rekap td::before { content: attr(data-label); color: var(--ink-3); font-weight: 500; text-align: left; }
    .rekap td:first-child { padding: .5rem 0 .625rem; text-align: left; }
    .rekap td:first-child::before { display: none; }
    .rekap tbody tr:hover td { background: transparent; }
    .rekap tfoot tr { background: var(--g-50); padding-top: 1rem; border-top: 1px solid var(--line); border-bottom: 0; }
    .rekap tfoot td { background: transparent; border: 0; }
}
@media (max-width: 380px) {
    .wallet-split { grid-template-columns: 1fr; }
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
            <a href="<?= base_url('kades/dashboard') ?>" class="side-link active" aria-current="page"><?= icon('grid') ?> <span>Dashboard</span></a>
            <a href="<?= base_url('kades/monitoring') ?>" class="side-link"><?= icon('trending') ?> <span>Monitoring</span></a>
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
        <?php if ($flash && !empty($flash['message'])): ?>
            <div class="flash flash-<?= $flashClass ?>" role="status">
                <?= icon($flashIcon) ?>
                <span><?= htmlspecialchars($flash['message']) ?></span>
            </div>
        <?php endif; ?>

        <div class="greet">
            <p class="greet-hi"><?= $sapaan ?>,</p>
            <h1 class="greet-name"><?= htmlspecialchars($namaUser !== '' ? $namaUser : 'Kepala Desa') ?></h1>
            <p class="greet-sub">Monitoring kegiatan Bank Sampah di desa Anda</p>
        </div>

        <section class="wallet" aria-label="Ringkasan kegiatan">
            <span class="wallet-label"><?= icon('wallet') ?> Total pembelian sampah</span>
            <div class="wallet-amount"><?= formatRupiah($totalNilaiBeli) ?></div>
            <p class="wallet-note">Dari seluruh bank sampah di desa</p>

            <div class="wallet-split">
                <div class="split">
                    <div class="split-label">Total penjualan</div>
                    <div class="split-value"><?= formatRupiah($totalNilaiJual) ?></div>
                </div>
                <div class="split">
                    <div class="split-label">Total transaksi</div>
                    <div class="split-value"><?= number_format($totalTransaksiBeli + $totalTransaksiJual) ?></div>
                </div>
                <div class="split">
                    <div class="split-label">Bank sampah</div>
                    <div class="split-value"><?= number_format($totalBank) ?></div>
                </div>
            </div>
        </section>

        <nav class="actions" aria-label="Aksi cepat">
            <a href="<?= base_url('kades/monitoring') ?>" class="action">
                <span class="action-ico"><?= icon('trending') ?></span>
                <span class="action-text">Monitoring Kegiatan<span class="action-sub">Pantau transaksi tiap bank sampah</span></span>
                <?= icon('chevron') ?>
            </a>
            <a href="<?= base_url('kades/laporan') ?>" class="action">
                <span class="action-ico"><?= icon('receipt') ?></span>
                <span class="action-text">Laporan Keuangan<span class="action-sub">Lihat pembelian dan penjualan</span></span>
                <?= icon('chevron') ?>
            </a>
        </nav>

        <section aria-labelledby="h-rekap">
            <div class="section-head">
                <h2 class="section-title" id="h-rekap">Rekapitulasi per Bank Sampah</h2>
            </div>

            <div class="panel">
                <?php if (!empty($bankStats)): ?>
                    <div class="table-wrap">
                        <table class="rekap">
                            <thead>
                                <tr>
                                    <th scope="col">Bank Sampah</th>
                                    <th scope="col">Transaksi Beli</th>
                                    <th scope="col">Nilai Beli</th>
                                    <th scope="col">Transaksi Jual</th>
                                    <th scope="col">Nilai Jual</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bankStats as $bs): ?>
                                    <tr>
                                        <td>
                                            <span class="bank-cell">
                                                <span class="bank-ico" aria-hidden="true"><?= icon('building') ?></span>
                                                <span class="bank-name"><?= htmlspecialchars((string) ($bs['bank']['nama'] ?? '-')) ?></span>
                                            </span>
                                        </td>
                                        <td data-label="Transaksi beli"><?= number_format((float) ($bs['transaksi_beli'] ?? 0)) ?></td>
                                        <td data-label="Nilai beli" class="num-beli"><?= formatRupiah($bs['nilai_beli'] ?? 0) ?></td>
                                        <td data-label="Transaksi jual"><?= number_format((float) ($bs['transaksi_jual'] ?? 0)) ?></td>
                                        <td data-label="Nilai jual" class="num-jual"><?= formatRupiah($bs['nilai_jual'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td>Total</td>
                                    <td data-label="Transaksi beli"><?= number_format($totalTransaksiBeli) ?></td>
                                    <td data-label="Nilai beli"><?= formatRupiah($totalNilaiBeli) ?></td>
                                    <td data-label="Transaksi jual"><?= number_format($totalTransaksiJual) ?></td>
                                    <td data-label="Nilai jual"><?= formatRupiah($totalNilaiJual) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty">
                        <div class="empty-ico" aria-hidden="true"><?= icon('inbox') ?></div>
                        <h3>Belum ada data</h3>
                        <p>Belum ada data bank sampah di desa ini.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
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