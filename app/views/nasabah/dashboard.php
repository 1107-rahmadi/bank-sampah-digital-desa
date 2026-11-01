<?php
/**nasabah/dashboard.php
 * Dashboard Nasabah (versi e-wallet)
 *
 * Compatible dengan PHP 7.3+
 *
 * Konsep desain:
 * - Kartu saldo utama bergradasi hijau (gaya e-wallet), saldo bisa disembunyikan
 * - Tombol aksi bulat besar di bawah kartu saldo (Harga Sampah, Cairkan, Riwayat)
 * - Riwayat berbentuk daftar (bukan tabel) agar nyaman di HP
 * - Sidebar bersama (nasabah/partials/sidebar.php), bisa dibuka/ditutup lewat hamburger
 * - Font Plus Jakarta Sans, dasar 17px, tombol minimal 56px
 *
 * @var float      $saldo
 * @var float      $saldoTersedia
 * @var float      $totalTerikat
 * @var array      $riwayatTerbaru
 * @var array      $pengajuanAktif
 * @var array      $nasabah
 * @var array      $user
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

        return (int) date('j', $date) . ' ' . $bulan[(int) date('n', $date)] . ' '
            . date('Y', $date) . ', ' . date('H:i', $date);
    }
}

if (!function_exists('statusPencairan')) {
    /**
     * Status pengajuan pencairan sesuai proses bisnis:
     * DIAJUKAN, DISETUJUI, DITOLAK, DICAIRKAN, DIBATALKAN.
     *
     * @return array [label, kelas warna, ikon, keterangan singkat]
     */
    function statusPencairan($status): array
    {
        switch (strtoupper((string) $status)) {
            case 'DISETUJUI':
                return ['Disetujui', 'info', 'clock', 'Menunggu uang diserahkan'];
            case 'DICAIRKAN':
                return ['Dicairkan', 'success', 'check-circle', 'Uang sudah diserahkan'];
            case 'DITOLAK':
                return ['Ditolak', 'danger', 'x-circle', ''];
            case 'DIBATALKAN':
                return ['Dibatalkan', 'neutral', 'x-circle', ''];
            case 'DIPROSES': // nilai lama, setara DISETUJUI
                return ['Diproses', 'info', 'clock', ''];
            default: // DIAJUKAN
                return ['Menunggu', 'warning', 'clock', 'Menunggu persetujuan'];
        }
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
    /**
     * Ikon SVG garis yang konsisten (tanpa emoji).
     */
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
            'wallet' => '<path d="M19 7V6a2 2 0 0 0-2-2H5a2 2 0 0 0 0 4h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6"/><path d="M16 12h.01"/>',
            'check-circle' => '<path d="M22 11.1V12a10 10 0 1 1-5.9-9.1"/><polyline points="22 4 12 14.01 9 11.01"/>',
            'clock' => '<circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/>',
            'x-circle' => '<circle cx="12" cy="12" r="9"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>',
            'alert' => '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
            'info' => '<circle cx="12" cy="12" r="9"/><line x1="12" y1="11" x2="12" y2="16"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
            'inbox' => '<polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.5 5.1 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.5-6.9A2 2 0 0 0 16.8 4H7.2a2 2 0 0 0-1.7 1.1z"/>',
            'chevron' => '<polyline points="9 6 15 12 9 18"/>',
            'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
            'eye-off' => '<path d="M17.9 17.9A10.1 10.1 0 0 1 12 19c-6.5 0-10-7-10-7a18 18 0 0 1 4.1-5.1M9.9 5.2A9.7 9.7 0 0 1 12 5c6.5 0 10 7 10 7a18 18 0 0 1-2.2 3.2"/><path d="M14.1 14.1a3 3 0 1 1-4.2-4.2"/><line x1="2" y1="2" x2="22" y2="22"/>',
            'tag' => '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L2 12V2h10l8.6 8.6a2 2 0 0 1 0 2.8Z"/><circle cx="7" cy="7" r="1.5"/>',
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

$flashTipe = isset($flash) && $flash && isset($flash['type']) ? strtolower((string) $flash['type']) : '';
$flashClass = in_array($flashTipe, ['success', 'error', 'warning', 'info'], true) ? $flashTipe : 'info';
$appName = defined('APP_NAME') ? APP_NAME : 'Bank Sampah';

require_once VIEW_PATH . '/nasabah/partials/notifikasi.php';

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#1b5e20">
<title>Beranda - <?= htmlspecialchars($appName) ?></title>
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
:focus-visible { outline: 3px solid var(--g-600); outline-offset: 2px; border-radius: 6px; }
.sr-only {
    position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
    overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0;
}
button { font-family: inherit; }

/* ---------- Latar sidebar (isi sidebar ada di nasabah/partials/sidebar.php) ---------- */
.scrim {
    position: fixed; inset: 0; background: rgba(15,30,18,0.5);
    opacity: 0; visibility: hidden; z-index: 90;
    transition: opacity .25s var(--ease), visibility .25s var(--ease);
}
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
.who {
    margin-left: auto; display: flex; align-items: center; gap: .625rem;
    text-decoration: none; padding: .25rem .25rem .25rem .875rem; border-radius: 999px;
}
.who-name { font-size: .9375rem; font-weight: 600; color: var(--ink); }
.who-avatar {
    width: 42px; height: 42px; border-radius: 50%; flex-shrink: 0;
    background: linear-gradient(140deg, var(--g-500), var(--g-700)); color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; font-weight: 700;
}

/* ---------- Page ---------- */
.page { max-width: 1040px; width: 100%; margin: 0 auto; padding: .5rem 1.75rem 3rem; }
.greet { margin-bottom: 1.25rem; }
.greet-hi { font-size: .9375rem; color: var(--ink-3); }
.greet-name { font-size: 1.75rem; font-weight: 800; color: var(--ink); letter-spacing: -.03em; line-height: 1.2; }

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

/* ---------- Kartu saldo (hero) ---------- */
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
.wallet-top { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
.wallet-label { display: flex; align-items: center; gap: .5rem; font-size: 1rem; font-weight: 600; color: rgba(255,255,255,.95); }
.wallet-label svg { width: 20px; height: 20px; }
.eye {
    width: 46px; height: 46px; border-radius: 50%; cursor: pointer; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    border: 0; background: rgba(255,255,255,.18); color: #fff;
    transition: background .18s var(--ease);
}
.eye:hover { background: rgba(255,255,255,.28); }
.eye svg { width: 22px; height: 22px; }
.eye .i-off { display: none; }
body.money-hidden .eye .i-on { display: none; }
body.money-hidden .eye .i-off { display: block; }
.wallet-amount {
    margin-top: .5rem; font-size: 2.5rem; font-weight: 800;
    letter-spacing: -.04em; line-height: 1.1; font-variant-numeric: tabular-nums;
}
.wallet-note { margin-top: .375rem; font-size: .9375rem; color: rgba(255,255,255,.92); }
.wallet-split { display: grid; grid-template-columns: 1fr 1fr; gap: .875rem; margin-top: 1.25rem; }
.split {
    padding: .75rem 1rem; border-radius: 16px;
    background: rgba(255,255,255,.14);
    border: 1px solid rgba(255,255,255,.18);
}
.split-label { font-size: .875rem; color: rgba(255,255,255,.92); }
.split-value { font-size: 1.1875rem; font-weight: 700; letter-spacing: -.02em; font-variant-numeric: tabular-nums; margin-top: .125rem; }
body.money-hidden .money { letter-spacing: .12em; }

/* ---------- Aksi cepat (3 tombol: Harga Sampah, Cairkan, Riwayat) ---------- */
.actions { display: grid; grid-template-columns: repeat(3, 1fr); gap: .875rem; margin: 1.5rem 0 1.75rem; }
.action {
    display: flex; flex-direction: column; align-items: center; gap: .5rem;
    text-decoration: none; padding: .375rem .25rem; border-radius: 18px;
    font-size: .9375rem; font-weight: 600; line-height: 1.3; color: var(--ink); text-align: center;
}
.action-ico {
    width: 58px; height: 58px; border-radius: 50%;
    background: var(--g-100); color: var(--g-800);
    display: flex; align-items: center; justify-content: center;
    transition: background .2s var(--ease), color .2s var(--ease), transform .2s var(--ease);
}
.action-ico svg { width: 24px; height: 24px; }
.action:hover .action-ico, .action:focus-visible .action-ico { background: var(--g-700); color: #fff; transform: translateY(-2px); }

/* ---------- Panel & daftar ---------- */
.cols { display: grid; grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr); gap: 1.5rem; align-items: start; }
.section-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: .625rem; }
.section-title { font-size: 1.125rem; font-weight: 700; color: var(--ink); letter-spacing: -.02em; }
.link-more {
    display: inline-flex; align-items: center; gap: .25rem; min-height: 44px; padding: 0 .625rem;
    border-radius: 12px; font-size: .9375rem; font-weight: 600; color: var(--g-700); text-decoration: none;
}
.link-more:hover { background: var(--g-100); }
.link-more svg { width: 16px; height: 16px; }
.list { background: var(--card); border: 1px solid var(--line); border-radius: 20px; padding: .25rem 1.125rem; }
.row { display: flex; align-items: center; gap: .875rem; padding: .875rem 0; border-bottom: 1px solid var(--line); }
.row:last-child { border-bottom: 0; }
.row-aksi { display: flex; justify-content: flex-end; padding: 0 0 .75rem; }
.btn-batal {
    background: transparent; border: 1px solid var(--rose); color: var(--rose);
    border-radius: 999px; padding: .3rem .9rem; font: inherit; font-size: .8125rem;
    font-weight: 600; cursor: pointer;
}
.btn-batal:hover { background: var(--rose-bg); }
.row-ico {
    width: 46px; height: 46px; border-radius: 50%; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
}
.row-ico svg { width: 21px; height: 21px; }
.row-ico.success { background: var(--g-100); color: var(--g-700); }
.row-ico.warning { background: var(--amber-bg); color: var(--amber); }
.row-ico.info { background: var(--sky-bg); color: var(--sky); }
.row-ico.danger { background: var(--rose-bg); color: var(--rose); }
.row-ico.neutral { background: #eef1ec; color: var(--ink-3); }
.row-body { flex: 1; min-width: 0; }
.row-title { font-size: 1rem; font-weight: 600; color: var(--ink); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.row-sub { font-size: .875rem; color: var(--ink-3); }
.row-end { text-align: right; flex-shrink: 0; }
.row-amount { font-size: 1rem; font-weight: 700; font-variant-numeric: tabular-nums; color: var(--ink); }
.row-amount.plus { color: var(--g-700); }
.row-amount.minus { color: var(--amber); }
.row-bal { font-size: .8125rem; color: var(--ink-3); font-variant-numeric: tabular-nums; }
.badge {
    display: inline-block; margin-top: .25rem; padding: .1875rem .625rem; border-radius: 999px;
    font-size: .8125rem; font-weight: 600;
}
.badge-success { background: var(--g-100); color: var(--g-800); }
.badge-warning { background: var(--amber-bg); color: var(--amber); }
.badge-danger { background: var(--rose-bg); color: var(--rose); }
.badge-info { background: var(--sky-bg); color: var(--sky); }
.empty { text-align: center; padding: 2rem 1rem; }
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
    /* Laptop/desktop: lebih ringkas agar seluruh halaman terlihat tanpa banyak scroll */
    .wallet-amount { font-size: 2.25rem; }
    .wallet { padding: 1.25rem 1.75rem; }
    .wallet-split { margin-top: 1rem; }
    .actions { margin: 1.25rem 0 1.5rem; }
    .action-ico { width: 54px; height: 54px; }
    .row { padding: .75rem 0; }
}
@media (max-width: 1100px) {
    .cols { grid-template-columns: minmax(0, 1fr); }
}
@media (max-width: 720px) {
    .topbar { padding: 0 1rem; }
    .page { padding: .5rem 1rem 3rem; }
    .who { padding-left: 0; }
    .who-name { display: none; }
    .greet-name { font-size: 1.5rem; }
    .wallet { padding: 1.25rem 1.125rem; border-radius: 22px; }
    .wallet-amount { font-size: 2rem; }
    .wallet-split { gap: .625rem; }
    .split { padding: .75rem .875rem; }
    .split-value { font-size: 1.0625rem; }
    .actions { gap: .5rem; margin: 1.25rem 0 1.5rem; }
    .action { font-size: .875rem; }
    .action-ico { width: 56px; height: 56px; }
    .list { padding: .25rem 1rem; border-radius: 18px; }
    .row { gap: .75rem; }
}
@media (max-width: 380px) {
    .wallet-split { grid-template-columns: 1fr; }
    .topbar-brand { display: none; }
}
@media (prefers-reduced-motion: reduce) {
    * { transition-duration: .01ms !important; animation-duration: .01ms !important; }
}
</style>
<?php notifikasiStyle(); ?>
<script>
/* Atur kondisi awal sidebar & saldo sebelum tampil, supaya tidak berkedip */
(function () {
    try {
        var wide = window.innerWidth > 1100;
        var pref = localStorage.getItem('nav');
        window.__navOpen = wide && pref !== 'closed';
        window.__moneyHidden = localStorage.getItem('money') === 'hidden';
    } catch (e) {
        window.__navOpen = window.innerWidth > 1100;
        window.__moneyHidden = false;
    }
})();
</script>
</head>
<body>
<script>
if (window.__navOpen) { document.body.classList.add('nav-open'); }
if (window.__moneyHidden) { document.body.classList.add('money-hidden'); }
</script>

<div class="scrim" id="scrim"></div>


<?php $menuAktif = 'nasabah/dashboard'; include VIEW_PATH . '/nasabah/partials/sidebar.php'; ?>
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
        <?php if (isset($flash) && $flash && !empty($flash['message'])): ?>
            <div class="flash flash-<?= $flashClass ?>" role="status">
                <?= icon($flashClass === 'success' ? 'check-circle' : ($flashClass === 'error' ? 'x-circle' : ($flashClass === 'warning' ? 'alert' : 'info'))) ?>
                <span><?= htmlspecialchars($flash['message']) ?></span>
            </div>
        <?php endif; ?>

        <div class="greet">
            <p class="greet-hi"><?= $sapaan ?>,</p>
            <h1 class="greet-name"><?= htmlspecialchars($namaDepan !== '' ? $namaDepan : 'Nasabah') ?></h1>
        </div>

        <section class="wallet" aria-label="Saldo tabungan">
            <div class="wallet-top">
                <span class="wallet-label"><?= icon('wallet') ?> Total tabungan</span>
                <button type="button" class="eye" id="eye" aria-pressed="false" aria-label="Sembunyikan saldo">
                    <span class="i-on"><?= icon('eye') ?></span>
                    <span class="i-off"><?= icon('eye-off') ?></span>
                </button>
            </div>
            <div class="wallet-amount money" data-value="<?= htmlspecialchars(formatRupiah($saldo)) ?>"><?= formatRupiah($saldo) ?></div>
            <p class="wallet-note">Bank Sampah <?= htmlspecialchars($nasabah['bank_sampah_nama'] ?? 'Desa') ?></p>

            <div class="wallet-split">
                <div class="split">
                    <div class="split-label">Bisa dicairkan</div>
                    <div class="split-value money" data-value="<?= htmlspecialchars(formatRupiah($saldoTersedia)) ?>"><?= formatRupiah($saldoTersedia) ?></div>
                </div>
                <div class="split">
                    <div class="split-label">Sedang diajukan</div>
                    <div class="split-value money" data-value="<?= htmlspecialchars(formatRupiah($totalTerikat)) ?>"><?= formatRupiah($totalTerikat) ?></div>
                </div>
            </div>
        </section>

        <nav class="actions" aria-label="Aksi cepat">
            <a href="<?= base_url('nasabah/setor') ?>" class="action">
                <span class="action-ico"><?= icon('tag') ?></span> Harga Sampah
            </a>
            <a href="<?= base_url('nasabah/cairkan') ?>" class="action">
                <span class="action-ico"><?= icon('banknote') ?></span> Cairkan
            </a>
            <a href="<?= base_url('nasabah/riwayat') ?>" class="action">
                <span class="action-ico"><?= icon('history') ?></span> Riwayat
            </a>
        </nav>

        <div class="cols">
            <section aria-labelledby="h-riwayat">
                <div class="section-head">
                    <h2 class="section-title" id="h-riwayat">Aktivitas terakhir</h2>
                    <a href="<?= base_url('nasabah/riwayat') ?>" class="link-more">Lihat semua <?= icon('chevron') ?></a>
                </div>

                <div class="list">
                    <?php if (!empty($riwayatTerbaru)): ?>
                        <?php foreach ($riwayatTerbaru as $row): ?>
                            <?php
                            $tipe = strtoupper((string) $row['tipe']);
                            $jumlah = (float) $row['jumlah'];
                            $judul = !empty($row['keterangan']) ? $row['keterangan'] : ucfirst(strtolower($tipe));
                            ?>
                            <div class="row">
                                <span class="row-ico <?= kelasBadgeTipe($tipe) ?>" aria-hidden="true"><?= icon(ikonTipe($tipe)) ?></span>
                                <span class="row-body">
                                    <span class="row-title" style="display:block"><?= htmlspecialchars($judul) ?></span>
                                    <span class="row-sub"><?= htmlspecialchars(ucfirst(strtolower($tipe))) ?> &middot; <?= formatTanggalIndonesia($row['created_at']) ?></span>
                                </span>
                                <span class="row-end">
                                    <span class="row-amount money <?= $jumlah >= 0 ? 'plus' : 'minus' ?>" style="display:block" data-value="<?= $jumlah >= 0 ? '+' : '-' ?><?= htmlspecialchars(formatRupiah(abs($jumlah))) ?>"><?= $jumlah >= 0 ? '+' : '-' ?><?= formatRupiah(abs($jumlah)) ?></span>
                                    <span class="row-bal money" data-value="Saldo <?= htmlspecialchars(formatRupiah($row['saldo_setelah'])) ?>">Saldo <?= formatRupiah($row['saldo_setelah']) ?></span>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty">
                            <div class="empty-ico" aria-hidden="true"><?= icon('inbox') ?></div>
                            <h3>Belum ada aktivitas</h3>
                            <p>Riwayat akan muncul setelah pengelola mencatat setoran Anda.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <section aria-labelledby="h-pengajuan">
                <div class="section-head">
                    <h2 class="section-title" id="h-pengajuan">Pengajuan pencairan</h2>
                </div>

                <div class="list">
                    <?php if (!empty($pengajuanAktif)): ?>
                        <?php foreach ($pengajuanAktif as $p): ?>
                            <?php list($label, $kelas, $ikonStatus, $ketStatus) = statusPencairan($p['status']); ?>
<div class="row">
    <span class="row-ico <?= $kelas ?>" aria-hidden="true"><?= icon($ikonStatus) ?></span>
    <span class="row-body">
        <span class="row-title" style="display:block"><?= htmlspecialchars($p['no_pengajuan']) ?></span>
        <span class="row-sub"><?= htmlspecialchars($p['metode']) ?><?= $ketStatus !== '' ? ' &middot; ' . htmlspecialchars($ketStatus) : '' ?></span>
    </span>
                                <span class="row-end">
                                    <span class="row-amount money" style="display:block" data-value="<?= htmlspecialchars(formatRupiah($p['jumlah'])) ?>"><?= formatRupiah($p['jumlah']) ?></span>
                                    <span class="badge badge-<?= $kelas ?>"><?= $label ?></span>
                                </span>
                            </div>
                            <?php if (($p['status'] ?? '') === 'DIAJUKAN'): ?>
                                <form class="row-aksi" method="POST" action="<?= base_url('nasabah/pencairan/batal') ?>" onsubmit="return confirm('Batalkan pengajuan ini? Saldo yang tertahan akan dilepas kembali.')">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="id" value="<?= (int) ($p['id'] ?? 0) ?>">
                                    <button type="submit" class="btn-batal">Batalkan pengajuan</button>
                                </form>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty">
                            <div class="empty-ico" aria-hidden="true"><?= icon('banknote') ?></div>
                            <h3>Tidak ada pengajuan</h3>
                            <p>Pilih Cairkan jika Anda ingin menarik saldo.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>
</div>

<script>
(function () {
    var body = document.body;
    var burger = document.getElementById('burger');
    var scrim = document.getElementById('scrim');
    var eye = document.getElementById('eye');
    var mq = window.matchMedia('(min-width: 1101px)');

    function save(k, v) { try { localStorage.setItem(k, v); } catch (e) {} }

    /* ----- Sidebar ----- */
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

    /* ----- Sembunyikan / tampilkan saldo ----- */
    var money = document.querySelectorAll('.money');
    function applyMoney(hidden) {
        body.classList.toggle('money-hidden', hidden);
        for (var i = 0; i < money.length; i++) {
            var v = money[i].getAttribute('data-value') || '';
            money[i].textContent = hidden ? v.replace(/[0-9.]+/, 'Rp ••••••').replace('Rp Rp ', 'Rp ') : v;
        }
        eye.setAttribute('aria-pressed', hidden ? 'true' : 'false');
        eye.setAttribute('aria-label', hidden ? 'Tampilkan saldo' : 'Sembunyikan saldo');
    }
    eye.addEventListener('click', function () {
        var hidden = !body.classList.contains('money-hidden');
        applyMoney(hidden);
        save('money', hidden ? 'hidden' : 'shown');
    });
    if (body.classList.contains('money-hidden')) { applyMoney(true); }
})();
</script>
<?php notifikasiScript(); ?>
</body>
</html>