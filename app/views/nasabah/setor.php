<?php
/**nasabah/setor.php
 * Daftar Harga Sampah (read-only)
 *
 * Compatible dengan PHP 7.3+
 * Desain sama dengan dashboard, riwayat, profil, dan cairkan.
 *
 * PERUBAHAN: sesuai proses bisnis, nasabah tidak mencatat setoran sendiri.
 * Penimbangan dan pencatatan dilakukan PENGELOLA. Halaman ini sekarang hanya
 * menampilkan harga beli yang berlaku (FR-09).
 *
 * Form setor lama TIDAK dihapus, hanya disembunyikan. Untuk menyalakan
 * kembali: define('FITUR_SETOR_NASABAH', true); (controller juga harus
 * mengizinkan POST, lihat catatan di controller).
 *
 * @var float      $saldo         (tidak dipakai saat form disembunyikan)
 * @var array      $nasabah
 * @var array      $hargaSampah   Baris: kategori_id, kategori_nama, harga_beli
 *                                (opsional: jenis, berlaku_mulai)
 * @var array      $errors
 * @var array      $oldInput
 * @var array|null $flash
 */

if (!function_exists('formatRupiah')) {
    function formatRupiah($number): string
    {
        return 'Rp ' . number_format((float) $number, 0, ',', '.');
    }
}

if (!function_exists('formatTanggalPendek')) {
    function formatTanggalPendek($date): string
    {
        $bulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $ts = is_string($date) ? strtotime($date) : $date;
        if (!$ts) {
            return '-';
        }

        return (int) date('j', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);
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
            'wallet' => '<path d="M19 7V6a2 2 0 0 0-2-2H5a2 2 0 0 0 0 4h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6"/><path d="M16 12h.01"/>',
            'check-circle' => '<path d="M22 11.1V12a10 10 0 1 1-5.9-9.1"/><polyline points="22 4 12 14.01 9 11.01"/>',
            'x-circle' => '<circle cx="12" cy="12" r="9"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>',
            'alert' => '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
            'info' => '<circle cx="12" cy="12" r="9"/><line x1="12" y1="11" x2="12" y2="16"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
            'inbox' => '<polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.5 5.1 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.5-6.9A2 2 0 0 0 16.8 4H7.2a2 2 0 0 0-1.7 1.1z"/>',
            'chevron-left' => '<polyline points="15 6 9 12 15 18"/>',
            'tag' => '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L2 12V2h10l8.6 8.6a2 2 0 0 1 0 2.8Z"/><circle cx="7" cy="7" r="1.5"/>',
            'scale' => '<path d="M12 3v18M5 21h14"/><path d="M5 7h14"/><path d="M5 7l-3 7a3 3 0 0 0 6 0L5 7zM19 7l-3 7a3 3 0 0 0 6 0l-3-7z"/>',
        ];

        $body = isset($paths[$name]) ? $paths[$name] : $paths['info'];
        $cls = $class !== '' ? ' class="' . htmlspecialchars($class) . '"' : '';

        return '<svg' . $cls . ' viewBox="0 0 24 24" fill="none" stroke="currentColor"'
            . ' stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"'
            . ' aria-hidden="true" focusable="false">' . $body . '</svg>';
    }
}

$saldo = $saldo ?? 0;
$nasabah = $nasabah ?? [];
$hargaSampah = $hargaSampah ?? [];
$errors = $errors ?? [];
$oldInput = $oldInput ?? [];

// Fitur setor oleh nasabah: MATI secara bawaan (penimbangan dilakukan pengelola)
$fiturSetorAktif = defined('FITUR_SETOR_NASABAH') && FITUR_SETOR_NASABAH === true;

$user = function_exists('currentUser') ? currentUser() : [];
$namaUser = (string) ($user['nama'] ?? $nasabah['nama'] ?? 'Nasabah');
$inisial = function_exists('mb_substr') ? mb_strtoupper(mb_substr($namaUser, 0, 1)) : strtoupper(substr($namaUser, 0, 1));
$appName = defined('APP_NAME') ? APP_NAME : 'Bank Sampah';

$flashTipe = isset($flash) && $flash && isset($flash['type']) ? strtolower((string) $flash['type']) : '';
$flashClass = in_array($flashTipe, ['success', 'error', 'warning', 'info'], true) ? $flashTipe : 'info';

// Tanggal berlaku harga terbaru (bila controller menyertakan kolom berlaku_mulai)
$berlakuTerbaru = '';
foreach ($hargaSampah as $h) {
    if (!empty($h['berlaku_mulai']) && (string) $h['berlaku_mulai'] > $berlakuTerbaru) {
        $berlakuTerbaru = (string) $h['berlaku_mulai'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#1b5e20">
<title>Harga Sampah - <?= htmlspecialchars($appName) ?></title>
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
.page { max-width: 760px; width: 100%; margin: 0 auto; padding: .75rem 2rem 3.5rem; }
.back {
    display: inline-flex; align-items: center; gap: .25rem; min-height: 44px;
    margin-left: -.5rem; padding: 0 .75rem 0 .5rem; border-radius: 12px;
    font-size: .9375rem; font-weight: 600; color: var(--g-700); text-decoration: none;
}
.back:hover { background: var(--g-100); }
.back svg { width: 18px; height: 18px; }
.head { margin: .25rem 0 1.5rem; }
.title { font-size: 1.75rem; font-weight: 800; color: var(--ink); letter-spacing: -.03em; line-height: 1.2; }
.lead { font-size: .9375rem; color: var(--ink-3); margin-top: .25rem; }

.flash, .notice {
    display: flex; gap: .75rem; align-items: flex-start;
    padding: .875rem 1.125rem; border-radius: 14px; margin-bottom: 1.25rem;
    font-size: .9375rem; font-weight: 500; border: 1px solid transparent;
}
.flash svg, .notice svg { width: 20px; height: 20px; flex-shrink: 0; margin-top: 2px; }
.flash-success { background: var(--g-50); color: #14532d; border-color: var(--g-200); }
.flash-error, .notice-error { background: var(--rose-bg); color: var(--rose); border-color: #f8c9c5; }
.flash-warning, .notice-warning { background: var(--amber-bg); color: var(--amber); border-color: #f6d9a0; }
.flash-info { background: var(--sky-bg); color: var(--sky); border-color: #c4d8fa; }
.notice ul { margin: .25rem 0 0 1.125rem; font-weight: 500; }
.notice strong { display: block; font-weight: 700; }

/* ---------- Daftar harga ---------- */
.section-head { display: flex; align-items: baseline; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin: 0 0 .75rem; }
.section-title { font-size: 1.125rem; font-weight: 700; color: var(--ink); letter-spacing: -.02em; }
.section-note { font-size: .875rem; color: var(--ink-3); }
.list { background: var(--card); border: 1px solid var(--line); border-radius: 20px; padding: .25rem 1.25rem; }
.row { display: flex; align-items: center; gap: .875rem; padding: .9375rem 0; border-bottom: 1px solid var(--line); }
.row:last-child { border-bottom: 0; }
.row-ico { width: 44px; height: 44px; border-radius: 50%; flex-shrink: 0; background: var(--g-100); color: var(--g-700); display: flex; align-items: center; justify-content: center; }
.row-ico svg { width: 20px; height: 20px; }
.row-body { flex: 1; min-width: 0; }
.row-title { display: block; font-size: 1rem; font-weight: 600; color: var(--ink); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.row-sub { font-size: .875rem; color: var(--ink-3); }
.row-price { font-size: 1.0625rem; font-weight: 800; color: var(--g-700); font-variant-numeric: tabular-nums; white-space: nowrap; letter-spacing: -.01em; }
.row-price small { font-size: .8125rem; font-weight: 500; color: var(--ink-3); }

.empty { text-align: center; padding: 2.25rem 1rem; }
.empty-ico { width: 60px; height: 60px; margin: 0 auto .875rem; border-radius: 50%; background: var(--g-100); color: var(--g-700); display: flex; align-items: center; justify-content: center; }
.empty-ico svg { width: 26px; height: 26px; }
.empty h3 { font-size: 1.0625rem; font-weight: 700; color: var(--ink); }
.empty p { font-size: .9375rem; color: var(--ink-3); margin: .25rem auto 0; max-width: 360px; }

/* ---------- Cara menyetor ---------- */
.steps-wrap { margin-top: 2rem; }
.steps { background: var(--g-50); border: 1px solid var(--g-200); border-radius: 20px; padding: 1.25rem 1.5rem; list-style: none; counter-reset: langkah; }
.steps-head { display: flex; align-items: center; gap: .625rem; font-size: 1rem; font-weight: 700; color: var(--g-800); margin-bottom: .875rem; }
.steps-head svg { width: 20px; height: 20px; }
.steps li { position: relative; padding-left: 2.5rem; margin-bottom: .875rem; font-size: .9375rem; color: var(--ink-2); counter-increment: langkah; }
.steps li:last-child { margin-bottom: 0; }
.steps li::before {
    content: counter(langkah); position: absolute; left: 0; top: 0;
    width: 28px; height: 28px; border-radius: 50%;
    background: var(--g-700); color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: .875rem; font-weight: 700;
}
.steps strong { color: var(--ink); }

/* ---------- Form setor (disembunyikan, hanya tampil bila fitur dinyalakan) ---------- */
.group { background: var(--card); border: 1px solid var(--line); border-radius: 20px; padding: 1.5rem; margin-bottom: 1.5rem; }
.group-title { font-size: 1.0625rem; font-weight: 700; color: var(--ink); letter-spacing: -.01em; }
.group-hint { font-size: .875rem; color: var(--ink-3); margin: .125rem 0 1.25rem; }
.req { color: var(--rose); }
.field { margin-bottom: 1.25rem; }
.label { display: block; margin-bottom: .375rem; font-size: .9375rem; font-weight: 600; color: var(--ink); }
.input {
    width: 100%; height: 52px; padding: 0 1rem;
    font: inherit; font-size: 1rem; color: var(--ink);
    background: #fbfcfa; border: 1.5px solid #d9e1d8; border-radius: 14px;
    transition: border-color .18s var(--ease), box-shadow .18s var(--ease), background .18s var(--ease);
}
select.input {
    appearance: none; -webkit-appearance: none; padding-right: 2.75rem; cursor: pointer;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%232e7d32' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 1rem center; background-size: 20px;
}
.input:focus { outline: none; background-color: #fff; border-color: var(--g-700); box-shadow: 0 0 0 4px rgba(67,160,71,.18); }
.suffix-wrap { position: relative; }
.suffix-wrap .input { padding-right: 3.5rem; font-size: 1.25rem; font-weight: 700; font-variant-numeric: tabular-nums; }
.suffix { position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); font-weight: 700; color: var(--ink-3); pointer-events: none; }
.hint { font-size: .875rem; color: var(--ink-3); margin-top: .5rem; }
.preview {
    display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;
    padding: 1.125rem 1.25rem; border-radius: 16px;
    background: var(--g-50); border: 1px solid var(--g-200); margin-bottom: 1.5rem;
}
.preview-label { font-size: .9375rem; font-weight: 600; color: var(--g-800); }
.preview-rate { font-size: .875rem; color: var(--ink-3); }
.preview-total { font-size: 1.75rem; font-weight: 800; letter-spacing: -.03em; color: var(--g-800); font-variant-numeric: tabular-nums; }
.actions { display: flex; gap: .75rem; }
.btn {
    display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
    min-height: 54px; padding: 0 1.75rem; border-radius: 14px; border: 0;
    font-size: 1.0625rem; font-weight: 700; text-decoration: none; cursor: pointer;
}
.btn svg { width: 20px; height: 20px; }
.btn-primary { flex: 1; background: linear-gradient(135deg, var(--g-600), var(--g-800)); color: #fff; }
.btn-ghost { background: var(--card); color: var(--ink-2); border: 1.5px solid #d9e1d8; }

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
    .steps { padding: 1.125rem 1.125rem; border-radius: 18px; }
    .group { padding: 1.25rem 1.125rem; border-radius: 18px; }
    .actions { flex-direction: column-reverse; }
    .btn { width: 100%; }
}
@media (max-width: 380px) {
    .topbar-brand { display: none; }
}
@media (prefers-reduced-motion: reduce) {
    * { transition-duration: .01ms !important; animation-duration: .01ms !important; }
}
</style>
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

<?php $menuAktif = 'nasabah/dashboard'; include VIEW_PATH . '/nasabah/partials/sidebar.php'; ?>

<div class="main">
    <header class="topbar">
        <button type="button" class="burger" id="burger" aria-label="Buka menu" aria-expanded="false" aria-controls="sidebar">
            <span class="burger-bar"></span><span class="burger-bar"></span><span class="burger-bar"></span>
        </button>
        <div class="topbar-brand"><?= htmlspecialchars($appName) ?></div>
        <a href="<?= base_url('nasabah/profil') ?>" class="who" aria-label="Profil saya">
            <span class="who-name"><?= htmlspecialchars($namaUser) ?></span>
            <span class="who-avatar" aria-hidden="true"><?= htmlspecialchars($inisial) ?></span>
        </a>
    </header>

    <main class="page">
        <a href="<?= base_url('nasabah/dashboard') ?>" class="back"><?= icon('chevron-left') ?> Beranda</a>

        <?php if (isset($flash) && $flash && !empty($flash['message'])): ?>
            <div class="flash flash-<?= $flashClass ?>" role="status">
                <?= icon($flashClass === 'success' ? 'check-circle' : ($flashClass === 'error' ? 'x-circle' : ($flashClass === 'warning' ? 'alert' : 'info'))) ?>
                <span><?= htmlspecialchars($flash['message']) ?></span>
            </div>
        <?php endif; ?>

        <div class="head">
            <h1 class="title">Harga sampah</h1>
            <p class="lead">Harga beli yang berlaku di bank sampah</p>
        </div>

        <?php if ($fiturSetorAktif && !empty($hargaSampah)): ?>
        <!-- ===== FORM SETOR (disembunyikan; aktif hanya jika FITUR_SETOR_NASABAH = true) ===== -->
        <section class="group" aria-labelledby="form-title">
            <h2 class="group-title" id="form-title">Form penyetoran</h2>
            <p class="group-hint">Tanda <span class="req">*</span> wajib diisi</p>

            <?php if (!empty($errors)): ?>
                <div class="notice notice-error" role="alert">
                    <?= icon('x-circle') ?>
                    <div>
                        <strong>Setoran belum bisa diproses</strong>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= base_url('nasabah/setor') ?>">
                <div class="field">
                    <label for="kategori_id" class="label">Jenis sampah <span class="req">*</span></label>
                    <select id="kategori_id" name="kategori_id" class="input" required onchange="updateTotal()">
                        <option value="">Pilih jenis sampah</option>
                        <?php foreach ($hargaSampah as $h): ?>
                            <option value="<?= htmlspecialchars($h['kategori_id']) ?>" <?= (($oldInput['kategori_id'] ?? '') == $h['kategori_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($h['kategori_nama']) ?> - <?= formatRupiah($h['harga_beli']) ?>/kg
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="berat" class="label">Berat sampah <span class="req">*</span></label>
                    <div class="suffix-wrap">
                        <input type="number" inputmode="decimal" id="berat" name="berat" class="input" step="0.01" min="0.01" placeholder="0" autocomplete="off" value="<?= htmlspecialchars($oldInput['berat'] ?? '') ?>" required oninput="updateTotal()">
                        <span class="suffix" aria-hidden="true">kg</span>
                    </div>
                    <p class="hint">Boleh pakai desimal, misalnya 2,5 kg</p>
                </div>

                <div class="preview" aria-live="polite">
                    <div>
                        <div class="preview-label">Nilai setoran</div>
                        <div class="preview-rate" id="rate-info">Pilih jenis sampah dulu</div>
                    </div>
                    <div class="preview-total" id="total-nilai">Rp 0</div>
                </div>

                <div class="actions">
                    <a href="<?= base_url('nasabah/dashboard') ?>" class="btn btn-ghost">Batal</a>
                    <button type="submit" name="submit" class="btn btn-primary"><?= icon('trash') ?> Setor sekarang</button>
                </div>
            </form>
        </section>
        <?php endif; ?>

        <!-- ===== DAFTAR HARGA (read-only) ===== -->
        <section aria-labelledby="h-harga">
            <div class="section-head">
                <h2 class="section-title" id="h-harga">Harga beli per kilogram</h2>
                <?php if ($berlakuTerbaru !== ''): ?>
                    <span class="section-note">Berlaku sejak <?= htmlspecialchars(formatTanggalPendek($berlakuTerbaru)) ?></span>
                <?php endif; ?>
            </div>

            <div class="list">
                <?php if (!empty($hargaSampah)): ?>
                    <?php foreach ($hargaSampah as $h): ?>
                        <?php
                        $jenis = isset($h['jenis']) ? strtolower((string) $h['jenis']) : '';
                        $jenisLabel = $jenis === 'organik' ? 'Organik' : ($jenis === 'anorganik' ? 'Anorganik' : '');
                        ?>
                        <div class="row">
                            <span class="row-ico" aria-hidden="true"><?= icon('tag') ?></span>
                            <span class="row-body">
                                <span class="row-title"><?= htmlspecialchars($h['kategori_nama']) ?></span>
                                <?php if ($jenisLabel !== ''): ?>
                                    <span class="row-sub"><?= $jenisLabel ?></span>
                                <?php endif; ?>
                            </span>
                            <span class="row-price"><?= formatRupiah($h['harga_beli']) ?> <small>/kg</small></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty">
                        <div class="empty-ico" aria-hidden="true"><?= icon('inbox') ?></div>
                        <h3>Harga belum tersedia</h3>
                        <p>Daftar harga akan tampil di sini setelah admin menetapkannya.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- ===== CARA MENYETOR ===== -->
        <section class="steps-wrap" aria-labelledby="h-cara">
            <h2 class="section-title" id="h-cara" style="margin-bottom:.75rem">Cara menyetor sampah</h2>
            <ol class="steps">
                <li><strong>Pilah dan bawa sampah</strong> ke bank sampah sesuai jenis pada daftar di atas.</li>
                <li><strong>Pengelola menimbang</strong> dan mencatat setoran Anda, memakai harga yang berlaku hari itu.</li>
                <li><strong>Nilai setoran masuk ke tabungan</strong> atau dibayar tunai, sesuai pilihan Anda. Saldo dan riwayatnya bisa dilihat di aplikasi.</li>
            </ol>
        </section>
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

<?php if ($fiturSetorAktif && !empty($hargaSampah)): ?>
<script>
/* ---------- Hitung nilai setoran (hanya saat fitur setor dinyalakan) ---------- */
var hargaSampahData = <?= json_encode(array_values($hargaSampah), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

function updateTotal() {
    var kategori = document.getElementById('kategori_id');
    var beratEl = document.getElementById('berat');
    if (!kategori || !beratEl) { return; }

    var berat = parseFloat(beratEl.value) || 0;
    var harga = 0;
    for (var i = 0; i < hargaSampahData.length; i++) {
        if (String(hargaSampahData[i].kategori_id) === String(kategori.value)) {
            harga = parseFloat(hargaSampahData[i].harga_beli) || 0;
            break;
        }
    }

    document.getElementById('rate-info').textContent = harga > 0
        ? 'Rp ' + Math.round(harga).toLocaleString('id-ID') + ' per kg'
        : 'Pilih jenis sampah dulu';
    document.getElementById('total-nilai').textContent = 'Rp ' + Math.round(berat * harga).toLocaleString('id-ID');
}

document.addEventListener('DOMContentLoaded', updateTotal);
</script>
<?php endif; ?>
</body>
</html>