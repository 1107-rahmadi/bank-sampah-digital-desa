<?php
/**nasabah/riwayat.php
 * Riwayat Transaksi Nasabah (versi e-wallet)
 *
 * Compatible dengan PHP 7.3+
 * Desain sama dengan dashboard, dengan ukuran huruf & elemen yang lebih ringkas.
 *
 * @var array      $riwayat
 * @var int        $totalData
 * @var int        $totalPages
 * @var int        $page
 * @var int        $offset
 * @var array|null $flash
 */

if (!function_exists('formatRupiah')) {
    function formatRupiah($number): string
    {
        return 'Rp ' . number_format((float) $number, 0, ',', '.');
    }
}

if (!function_exists('formatTanggalIndonesia')) {
    function formatTanggalIndonesia($datetime): string
    {
        $bulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $date = is_string($datetime) ? strtotime($datetime) : $datetime;
        if (!$date) {
            return '-';
        }

        return (int) date('j', $date) . ' ' . $bulan[(int) date('n', $date)] . ' ' . date('Y', $date);
    }
}

if (!function_exists('formatTanggalWaktuIndonesia')) {
    function formatTanggalWaktuIndonesia($datetime): string
    {
        $date = is_string($datetime) ? strtotime($datetime) : $datetime;
        if (!$date) {
            return '-';
        }

        return formatTanggalIndonesia($date) . ', ' . date('H:i', $date);
    }
}

if (!function_exists('kelasBadgeTipe')) {
    function kelasBadgeTipe($tipe): string
    {
        switch (strtoupper((string) $tipe)) {
            case 'SETOR':
                return 'success';
            case 'PENCAIRAN':
                return 'warning';
            case 'KOREKSI':
                return 'info';
            default:
                return 'neutral';
        }
    }
}

if (!function_exists('ikonTipe')) {
    function ikonTipe($tipe): string
    {
        switch (strtoupper((string) $tipe)) {
            case 'SETOR':
                return 'trash';
            case 'PENCAIRAN':
                return 'banknote';
            default:
                return 'history';
        }
    }
}

if (!function_exists('icon')) {
    function icon(string $name, string $class = ''): string
    {
        $paths = [
            'leaf' => '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>',
            'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/>',
            'trash' => '<path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2m2 0v14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V6"/><path d="M10 11v6M14 11v6"/>',
            'history' => '<path d="M3 12a9 9 0 1 0 2.6-6.4"/><polyline points="3 4 3 9 8 9"/><path d="M12 7v5l3 2"/>',
            'banknote' => '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/>',
            'user' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
            'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
            'check-circle' => '<path d="M22 11.1V12a10 10 0 1 1-5.9-9.1"/><polyline points="22 4 12 14.01 9 11.01"/>',
            'x-circle' => '<circle cx="12" cy="12" r="9"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>',
            'alert' => '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
            'info' => '<circle cx="12" cy="12" r="9"/><line x1="12" y1="11" x2="12" y2="16"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
            'inbox' => '<polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.5 5.1 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.5-6.9A2 2 0 0 0 16.8 4H7.2a2 2 0 0 0-1.7 1.1z"/>',
            'chevron' => '<polyline points="9 6 15 12 9 18"/>',
            'chevron-left' => '<polyline points="15 6 9 12 15 18"/>',
        ];

        $body = isset($paths[$name]) ? $paths[$name] : $paths['info'];
        $cls = $class !== '' ? ' class="' . htmlspecialchars($class) . '"' : '';

        return '<svg' . $cls . ' viewBox="0 0 24 24" fill="none" stroke="currentColor"'
            . ' stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"'
            . ' aria-hidden="true" focusable="false">' . $body . '</svg>';
    }
}

$user = currentUser();
$namaUser = (string) ($user['nama'] ?? 'Nasabah');
$inisial = function_exists('mb_substr') ? mb_strtoupper(mb_substr($namaUser, 0, 1)) : strtoupper(substr($namaUser, 0, 1));
$appName = defined('APP_NAME') ? APP_NAME : 'Bank Sampah';

$flashTipe = isset($flash) && $flash && isset($flash['type']) ? strtolower((string) $flash['type']) : '';
$flashClass = in_array($flashTipe, ['success', 'error', 'warning', 'info'], true) ? $flashTipe : 'info';
$riwayat = isset($riwayat) && is_array($riwayat) ? $riwayat : [];

$page = isset($page) ? max(1, (int) $page) : 1;
$totalPages = isset($totalPages) ? (int) $totalPages : 1;
$totalData = isset($totalData) ? (int) $totalData : 0;

// Kelompokkan transaksi per tanggal agar mudah dibaca
$kelompok = [];
if (!empty($riwayat)) {
    foreach ($riwayat as $row) {
        $ts = strtotime((string) $row['created_at']);
        $kunci = $ts ? date('Y-m-d', $ts) : '0000-00-00';
        $kelompok[$kunci][] = $row;
    }
}

// Notifikasi dalam aplikasi (lonceng + pop-up)
require_once VIEW_PATH . '/nasabah/partials/notifikasi.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#1b5e20">
<title>Riwayat Transaksi - <?= htmlspecialchars($appName) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
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
    --sidebar-w: 272px;
    --topbar-h: 68px;
    --ease: cubic-bezier(0.4, 0, 0.2, 1);
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { -webkit-text-size-adjust: 100%; }
body {
    font-family: 'Plus Jakarta Sans', 'Segoe UI', system-ui, -apple-system, sans-serif;
    font-size: 16px; line-height: 1.55;
    color: var(--ink-2); background: var(--paper);
    -webkit-font-smoothing: antialiased;
}
svg { display: block; }
a { color: inherit; }
:focus-visible { outline: 3px solid var(--g-600); outline-offset: 2px; border-radius: 6px; }
button { font-family: inherit; }

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
    width: 42px; height: 42px; border-radius: 13px; flex-shrink: 0;
    background: rgba(255,255,255,.16);
    display: flex; align-items: center; justify-content: center;
}
.side-mark svg { width: 22px; height: 22px; }
.side-name { display: block; font-size: 1rem; font-weight: 700; line-height: 1.25; }
.side-sub { display: block; font-size: .8125rem; color: rgba(255,255,255,.85); }
.side-nav { flex: 1; overflow-y: auto; padding: .5rem .875rem; }
.side-group + .side-group { margin-top: 1.25rem; }
.side-group-label { font-size: .8125rem; font-weight: 600; color: rgba(255,255,255,.8); padding: 0 .75rem; margin-bottom: .375rem; }
.side-link {
    display: flex; align-items: center; gap: .75rem;
    min-height: 50px; padding: .625rem .875rem;
    border-radius: 13px; text-decoration: none;
    font-size: 1rem; font-weight: 500; color: rgba(255,255,255,.95);
    transition: background .18s var(--ease);
}
.side-link + .side-link { margin-top: 3px; }
.side-link svg { width: 20px; height: 20px; flex-shrink: 0; }
.side-link:hover { background: rgba(255,255,255,.1); }
.side-link.active { background: rgba(255,255,255,.2); font-weight: 700; }
.side-foot { padding: .875rem .875rem 1.125rem; border-top: 1px solid rgba(255,255,255,.12); }
.side-link.danger { color: #ffd9d6; }
.side-link.danger:hover { background: rgba(230,90,80,.22); }

body.nav-open .sidebar { transform: translateX(0); }
body.nav-open .scrim { opacity: 1; visibility: visible; }
body.nav-open { overflow: hidden; }

/* ---------- Topbar ---------- */
.main { min-width: 0; transition: margin-left .3s var(--ease); }
.topbar {
    height: var(--topbar-h); display: flex; align-items: center; gap: .875rem;
    padding: 0 2rem; position: sticky; top: 0; z-index: 60;
    background: rgba(247,250,246,.9);
    backdrop-filter: saturate(180%) blur(12px);
    -webkit-backdrop-filter: saturate(180%) blur(12px);
}
.burger {
    width: 46px; height: 46px; flex-shrink: 0; cursor: pointer;
    display: flex; flex-direction: column; justify-content: center; gap: 5px; padding: 0 13px;
    border: 1px solid var(--line); border-radius: 14px; background: var(--card);
    transition: background .18s var(--ease);
}
.burger:hover { background: var(--g-100); }
.burger-bar { display: block; width: 20px; height: 2px; border-radius: 2px; background: var(--g-800); transition: transform .25s var(--ease), opacity .15s var(--ease); }
body.nav-open .burger-bar:nth-child(1) { transform: translateY(7px) rotate(45deg); }
body.nav-open .burger-bar:nth-child(2) { opacity: 0; }
body.nav-open .burger-bar:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }
.topbar-brand { font-size: 1rem; font-weight: 700; color: var(--ink); letter-spacing: -.015em; }
.who { margin-left: auto; display: flex; align-items: center; gap: .625rem; text-decoration: none; padding: .25rem .25rem .25rem .75rem; border-radius: 999px; }
.who-name { font-size: .9375rem; font-weight: 600; color: var(--ink); }
.who-avatar {
    width: 42px; height: 42px; border-radius: 50%; flex-shrink: 0;
    background: linear-gradient(140deg, var(--g-500), var(--g-700)); color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; font-weight: 700;
}

/* ---------- Halaman ---------- */
.page { max-width: 880px; width: 100%; margin: 0 auto; padding: .75rem 2rem 3.5rem; }
.back {
    display: inline-flex; align-items: center; gap: .25rem; min-height: 44px;
    margin-left: -.5rem; padding: 0 .75rem 0 .5rem; border-radius: 12px;
    font-size: .9375rem; font-weight: 600; color: var(--g-700); text-decoration: none;
}
.back:hover { background: var(--g-100); }
.back svg { width: 18px; height: 18px; }
.head { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; margin: .25rem 0 1.5rem; flex-wrap: wrap; }
.title { font-size: 1.75rem; font-weight: 800; color: var(--ink); letter-spacing: -.03em; line-height: 1.2; }
.lead { font-size: .9375rem; color: var(--ink-3); margin-top: .25rem; }
.count {
    display: inline-flex; align-items: center; gap: .5rem; padding: .5rem 1rem;
    border-radius: 999px; background: var(--g-100); color: var(--g-800);
    font-size: .9375rem; font-weight: 700;
}

.flash {
    display: flex; gap: .75rem; align-items: flex-start;
    padding: .875rem 1.125rem; border-radius: 14px; margin-bottom: 1.25rem;
    font-size: .9375rem; font-weight: 500; border: 1px solid transparent;
}
.flash svg { width: 20px; height: 20px; flex-shrink: 0; margin-top: 2px; }
.flash-success { background: var(--g-50); color: #14532d; border-color: var(--g-200); }
.flash-error { background: var(--rose-bg); color: var(--rose); border-color: #f8c9c5; }
.flash-warning { background: var(--amber-bg); color: var(--amber); border-color: #f6d9a0; }
.flash-info { background: var(--sky-bg); color: var(--sky); border-color: #c4d8fa; }

/* ---------- Daftar transaksi ---------- */
.day { font-size: .9375rem; font-weight: 700; color: var(--ink-3); margin: 1.5rem 0 .625rem .25rem; }
.day:first-of-type { margin-top: 0; }
.list { background: var(--card); border: 1px solid var(--line); border-radius: 20px; padding: .25rem 1.25rem; }
.row { display: flex; align-items: center; gap: .875rem; padding: 1rem 0; border-bottom: 1px solid var(--line); }
.row:last-child { border-bottom: 0; }
.row-ico { width: 46px; height: 46px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
.row-ico svg { width: 21px; height: 21px; }
.row-ico.success { background: var(--g-100); color: var(--g-700); }
.row-ico.warning { background: var(--amber-bg); color: var(--amber); }
.row-ico.info { background: var(--sky-bg); color: var(--sky); }
.row-ico.neutral { background: #eef1ec; color: var(--ink-3); }
.row-body { flex: 1; min-width: 0; }
.row-title { display: block; font-size: 1rem; font-weight: 600; color: var(--ink); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.row-sub { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; font-size: .875rem; color: var(--ink-3); margin-top: .125rem; }
.badge { display: inline-block; padding: .125rem .625rem; border-radius: 999px; font-size: .8125rem; font-weight: 600; }
.badge-success { background: var(--g-100); color: var(--g-800); }
.badge-warning { background: var(--amber-bg); color: var(--amber); }
.badge-info { background: var(--sky-bg); color: var(--sky); }
.badge-neutral { background: #eef1ec; color: var(--ink-3); }
.row-end { text-align: right; flex-shrink: 0; }
.row-amount { display: block; font-size: 1rem; font-weight: 700; font-variant-numeric: tabular-nums; }
.row-amount.plus { color: var(--g-700); }
.row-amount.minus { color: var(--amber); }
.row-bal { font-size: .8125rem; color: var(--ink-3); font-variant-numeric: tabular-nums; }

.empty { background: var(--card); border: 1px solid var(--line); border-radius: 20px; text-align: center; padding: 3rem 1.5rem; }
.empty-ico { width: 64px; height: 64px; margin: 0 auto 1rem; border-radius: 50%; background: var(--g-100); color: var(--g-700); display: flex; align-items: center; justify-content: center; }
.empty-ico svg { width: 28px; height: 28px; }
.empty h3 { font-size: 1.0625rem; font-weight: 700; color: var(--ink); }
.empty p { font-size: .9375rem; color: var(--ink-3); margin: .25rem auto 0; max-width: 340px; }

/* ---------- Halaman berikutnya ---------- */
.pager { display: flex; align-items: center; justify-content: center; gap: .375rem; flex-wrap: wrap; margin-top: 2rem; }
.pager a, .pager span {
    min-width: 44px; height: 44px; padding: 0 .75rem;
    display: inline-flex; align-items: center; justify-content: center; gap: .25rem;
    border-radius: 12px; font-size: .9375rem; font-weight: 600; text-decoration: none; color: var(--ink-2);
}
.pager a { background: var(--card); border: 1px solid var(--line); transition: background .18s var(--ease); }
.pager a:hover { background: var(--g-100); border-color: var(--g-200); }
.pager svg { width: 18px; height: 18px; }
.pager .current { background: var(--g-700); color: #fff; }
.pager .off { color: #aab5ab; background: transparent; }
.pager .gap { min-width: 24px; padding: 0; color: var(--ink-3); }

/* ---------- Responsif ---------- */
@media (min-width: 1101px) {
    body.nav-open { overflow: auto; }
    body.nav-open .scrim { opacity: 0; visibility: hidden; }
    body.nav-open .main { margin-left: var(--sidebar-w); }
}
@media (max-width: 720px) {
    :root { --topbar-h: 62px; }
    .topbar { padding: 0 1rem; }
    .page { padding: .5rem 1rem 3rem; }
    .who { padding-left: 0; }
    .who-name { display: none; }
    .title { font-size: 1.5rem; }
    .list { padding: .125rem 1rem; border-radius: 18px; }
    .row { gap: .75rem; padding: .875rem 0; }
    .row-ico { width: 42px; height: 42px; }
    .pager .lbl { display: none; }
}
@media (max-width: 380px) {
    .topbar-brand { display: none; }
}
@media (prefers-reduced-motion: reduce) {
    * { transition-duration: .01ms !important; animation-duration: .01ms !important; }
}
</style>
<?php notifikasiStyle(); ?>
<script>
(function () {
    try {
        window.__navOpen = window.innerWidth > 1100 && localStorage.getItem('nav') !== 'closed';
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

<?php $menuAktif = 'nasabah/riwayat'; include VIEW_PATH . '/nasabah/partials/sidebar.php'; ?>

<?php notifikasiPopup(); ?>

<div class="main">
    <header class="topbar">
        <button type="button" class="burger" id="burger" aria-label="Buka menu" aria-expanded="false" aria-controls="sidebar">
            <span class="burger-bar"></span><span class="burger-bar"></span><span class="burger-bar"></span>
        </button>
        <div class="topbar-brand"><?= htmlspecialchars($appName) ?></div>

        <?php notifikasiLonceng(); ?>

        <a href="<?= base_url('nasabah/profil') ?>" class="who" aria-label="Profil saya">
            <span class="who-name"><?= htmlspecialchars($namaUser) ?></span>
            <span class="who-avatar" aria-hidden="true"><?= htmlspecialchars($inisial) ?></span>
        </a>
    </header>

    <main class="page">
        <a href="<?= base_url('nasabah/dashboard') ?>" class="back"><?= icon('chevron-left') ?> Beranda</a>

        <?php if ($flash && !empty($flash['message'])): ?>
            <div class="flash flash-<?= $flashClass ?>" role="status">
                <?= icon($flashClass === 'success' ? 'check-circle' : ($flashClass === 'error' ? 'x-circle' : ($flashClass === 'warning' ? 'alert' : 'info'))) ?>
                <span><?= htmlspecialchars($flash['message']) ?></span>
            </div>
        <?php endif; ?>

        <div class="head">
            <div>
                <h1 class="title">Riwayat transaksi</h1>
                <p class="lead">Semua setoran dan pencairan saldo Anda</p>
            </div>
            <span class="count"><?= number_format($totalData, 0, ',', '.') ?> transaksi</span>
        </div>

        <?php if (!empty($kelompok)): ?>
            <?php foreach ($kelompok as $tgl => $baris): ?>
                <h2 class="day"><?= formatTanggalIndonesia($baris[0]['created_at']) ?></h2>
                <div class="list">
                    <?php foreach ($baris as $row): ?>
                        <?php
                        $tipe = strtoupper((string) $row['tipe']);
                        $jumlah = (float) $row['jumlah'];
                        $ket = isset($row['keterangan']) ? trim((string) $row['keterangan']) : '';
                        $judul = $ket !== '' ? $ket : ucfirst(strtolower($tipe));
                        $jam = date('H:i', strtotime((string) $row['created_at']));
                        ?>
                        <div class="row">
                            <span class="row-ico <?= kelasBadgeTipe($tipe) ?>" aria-hidden="true"><?= icon(ikonTipe($tipe)) ?></span>
                            <span class="row-body">
                                <span class="row-title"><?= htmlspecialchars($judul) ?></span>
                                <span class="row-sub">
                                    <span class="badge badge-<?= kelasBadgeTipe($tipe) ?>"><?= htmlspecialchars(ucfirst(strtolower($tipe))) ?></span>
                                    <span><?= $jam ?></span>
                                </span>
                            </span>
                            <span class="row-end">
                                <span class="row-amount <?= $jumlah >= 0 ? 'plus' : 'minus' ?>"><?= $jumlah >= 0 ? '+' : '-' ?><?= formatRupiah(abs($jumlah)) ?></span>
                                <span class="row-bal">Saldo <?= formatRupiah($row['saldo_setelah']) ?></span>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

            <?php if ($totalPages > 1): ?>
                <nav class="pager" aria-label="Halaman riwayat">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>" aria-label="Halaman sebelumnya"><?= icon('chevron-left') ?><span class="lbl">Sebelumnya</span></a>
                    <?php else: ?>
                        <span class="off"><?= icon('chevron-left') ?><span class="lbl">Sebelumnya</span></span>
                    <?php endif; ?>

                    <?php
                    $start = max(1, $page - 2);
                    $end = min($totalPages, $page + 2);

                    if ($start > 1) {
                        echo '<a href="?page=1">1</a>';
                        if ($start > 2) {
                            echo '<span class="gap">&hellip;</span>';
                        }
                    }

                    for ($i = $start; $i <= $end; $i++):
                        if ($i === $page): ?>
                            <span class="current" aria-current="page"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?page=<?= $i ?>"><?= $i ?></a>
                        <?php endif;
                    endfor;

                    if ($end < $totalPages) {
                        if ($end < $totalPages - 1) {
                            echo '<span class="gap">&hellip;</span>';
                        }
                        echo '<a href="?page=' . $totalPages . '">' . $totalPages . '</a>';
                    }
                    ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>" aria-label="Halaman berikutnya"><span class="lbl">Berikutnya</span><?= icon('chevron') ?></a>
                    <?php else: ?>
                        <span class="off"><span class="lbl">Berikutnya</span><?= icon('chevron') ?></span>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>

        <?php else: ?>
            <div class="empty">
                <div class="empty-ico" aria-hidden="true"><?= icon('inbox') ?></div>
                <h3>Belum ada transaksi</h3>
                <p>Setor sampah pertama Anda, riwayatnya akan muncul di sini.</p>
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

    function save(v) { try { localStorage.setItem('nav', v); } catch (e) {} }

    function syncBurger() {
        var open = body.classList.contains('nav-open');
        burger.setAttribute('aria-expanded', open ? 'true' : 'false');
        burger.setAttribute('aria-label', open ? 'Tutup menu' : 'Buka menu');
    }
    function setNav(open) {
        body.classList.toggle('nav-open', open);
        if (mq.matches) { save(open ? 'open' : 'closed'); }
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
<?php notifikasiScript(); ?>
</html>