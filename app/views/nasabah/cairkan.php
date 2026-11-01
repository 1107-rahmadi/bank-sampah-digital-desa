<?php
/**nasabah/cairkan.php
 * Form Pengajuan Pencairan (versi e-wallet)
 *
 * Compatible dengan PHP 7.3+
 * Desain sama dengan dashboard, riwayat, dan profil.
 *
 * @var array|null $flash
 * @var float      $saldo
 * @var float      $saldoTersedia
 * @var float      $totalTerikat
 * @var array      $errors
 * @var array      $oldInput
 * @var array      $pengajuanAktif
 * @var array      $nasabah            (disarankan) data nasabah; dipakai untuk mengisi rekening dari profil
 */

if (!function_exists('formatRupiah')) {
    /**
     * @param float|int $number
     */
    function formatRupiah($number): string
    {
        return 'Rp ' . number_format((float) $number, 0, ',', '.');
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

if (!function_exists('icon')) {
    function icon(string $name, string $class = ''): string
    {
        $paths = [
            'leaf' => '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>',
            'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/>',
            'trash' => '<path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2m2 0v14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V6"/><path d="M10 11v6M14 11v6"/>',
            'history' => '<path d="M3 12a9 9 0 1 0 2.6-6.4"/><polyline points="3 4 3 9 8 9"/><path d="M12 7v5l3 2"/>',
            'banknote' => '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/>',
            'landmark' => '<line x1="3" y1="22" x2="21" y2="22"/><line x1="6" y1="18" x2="6" y2="11"/><line x1="10" y1="18" x2="10" y2="11"/><line x1="14" y1="18" x2="14" y2="11"/><line x1="18" y1="18" x2="18" y2="11"/><polygon points="12 2 20 7 4 7"/>',
            'user' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
            'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
            'wallet' => '<path d="M19 7V6a2 2 0 0 0-2-2H5a2 2 0 0 0 0 4h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6"/><path d="M16 12h.01"/>',
            'check-circle' => '<path d="M22 11.1V12a10 10 0 1 1-5.9-9.1"/><polyline points="22 4 12 14.01 9 11.01"/>',
            'clock' => '<circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/>',
            'x-circle' => '<circle cx="12" cy="12" r="9"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>',
            'alert' => '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
            'info' => '<circle cx="12" cy="12" r="9"/><line x1="12" y1="11" x2="12" y2="16"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
            'inbox' => '<polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.5 5.1 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.5-6.9A2 2 0 0 0 16.8 4H7.2a2 2 0 0 0-1.7 1.1z"/>',
            'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/>',
            'chevron-left' => '<polyline points="15 6 9 12 15 18"/>',
        ];

        $body = isset($paths[$name]) ? $paths[$name] : $paths['info'];
        $cls = $class !== '' ? ' class="' . htmlspecialchars($class) . '"' : '';

        return '<svg' . $cls . ' viewBox="0 0 24 24" fill="none" stroke="currentColor"'
            . ' stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"'
            . ' aria-hidden="true" focusable="false">' . $body . '</svg>';
    }
}

// Minimal pencairan
$MINIMAL_PENCAIRAN = PengajuanPencairan::MINIMAL_PENCAIRAN;

$user = currentUser();
$errors = isset($errors) ? $errors : [];
$oldInput = isset($oldInput) ? $oldInput : [];
$pengajuanAktif = isset($pengajuanAktif) ? $pengajuanAktif : [];

// Rekening dari profil (jika controller mengirim $nasabah)
$punyaDataNasabah = isset($nasabah) && is_array($nasabah) && !empty($nasabah);
$namaBankProfil = $punyaDataNasabah ? trim((string) ($nasabah['nama_bank'] ?? '')) : '';
$noRekProfil = $punyaDataNasabah ? trim((string) ($nasabah['no_rekening'] ?? '')) : '';
$rekeningProfil = $noRekProfil !== '' ? trim($namaBankProfil . ' ' . $noRekProfil) : '';
$tujuanLama = isset($oldInput['tujuan_transfer']) ? (string) $oldInput['tujuan_transfer'] : '';

$namaUser = (string) ($user['nama'] ?? 'Nasabah');
$inisial = function_exists('mb_substr') ? mb_strtoupper(mb_substr($namaUser, 0, 1)) : strtoupper(substr($namaUser, 0, 1));
$appName = defined('APP_NAME') ? APP_NAME : 'Bank Sampah';

$flashTipe = isset($flash) && $flash && isset($flash['type']) ? strtolower((string) $flash['type']) : '';
$flashClass = in_array($flashTipe, ['success', 'error', 'warning', 'info'], true) ? $flashTipe : 'info';
$metodeLama = isset($oldInput['metode']) ? (string) $oldInput['metode'] : '';

// Notifikasi dalam aplikasi (lonceng saja; tanpa pop-up otomatis)
require_once VIEW_PATH . '/nasabah/partials/notifikasi.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#1b5e20">
<title>Cairkan Saldo - <?= htmlspecialchars($appName) ?></title>
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
[hidden] { display: none !important; }
.vh { position: absolute; width: 1px; height: 1px; margin: -1px; padding: 0; overflow: hidden; clip: rect(0,0,0,0); border: 0; }

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
.who { display: flex; align-items: center; gap: .625rem; text-decoration: none; padding: .25rem .25rem .25rem .75rem; border-radius: 999px; }
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

/* ---------- Kartu saldo ---------- */
.wallet {
    position: relative; overflow: hidden; color: #fff;
    border-radius: 24px; padding: 1.5rem 1.75rem;
    background: linear-gradient(135deg, #43a047 0%, #2e7d32 45%, #14501a 100%);
    box-shadow: 0 14px 32px rgba(27,94,32,.22);
}
.wallet::before {
    content: ""; position: absolute; width: 260px; height: 260px; right: -90px; top: -120px;
    border-radius: 50%; background: rgba(255,255,255,.07); pointer-events: none;
}
.wallet > * { position: relative; }
.wallet-label { display: flex; align-items: center; gap: .5rem; font-size: 1rem; font-weight: 600; color: rgba(255,255,255,.95); }
.wallet-label svg { width: 20px; height: 20px; }
.wallet-amount { margin-top: .5rem; font-size: 2.5rem; font-weight: 800; letter-spacing: -.04em; line-height: 1.1; font-variant-numeric: tabular-nums; }
.wallet-split { display: grid; grid-template-columns: 1fr 1fr; gap: .875rem; margin-top: 1.25rem; }
.split { padding: .875rem 1rem; border-radius: 16px; background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.18); }
.split-label { font-size: .875rem; color: rgba(255,255,255,.92); }
.split-value { font-size: 1.125rem; font-weight: 700; letter-spacing: -.02em; font-variant-numeric: tabular-nums; margin-top: .125rem; }

/* ---------- Formulir ---------- */
.group { background: var(--card); border: 1px solid var(--line); border-radius: 20px; padding: 1.5rem 1.5rem 1.5rem; margin-top: 1.25rem; }
.group-title { font-size: 1.0625rem; font-weight: 700; color: var(--ink); letter-spacing: -.01em; }
.group-hint { font-size: .875rem; color: var(--ink-3); margin: .125rem 0 1.25rem; }
.field { margin-bottom: 1.25rem; }
.label { display: block; margin-bottom: .375rem; font-size: .9375rem; font-weight: 600; color: var(--ink); }
.req { color: var(--rose); }
.input {
    width: 100%; height: 52px; padding: 0 1rem;
    font: inherit; font-size: 1rem; color: var(--ink);
    background: #fbfcfa; border: 1.5px solid #d9e1d8; border-radius: 14px;
    transition: border-color .18s var(--ease), box-shadow .18s var(--ease), background .18s var(--ease);
}
.input::placeholder { color: #8a968c; }
.input:hover { border-color: #bccabb; }
.input:focus { outline: none; background: #fff; border-color: var(--g-700); box-shadow: 0 0 0 4px rgba(67,160,71,.18); }
.input.error { border-color: var(--rose); background: #fffafa; }
.input.error:focus { box-shadow: 0 0 0 4px rgba(179,38,30,.15); }
.amount { position: relative; }
.amount .prefix { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); font-weight: 700; color: var(--ink-3); pointer-events: none; }
.amount .input { padding-left: 3rem; font-size: 1.25rem; font-weight: 700; letter-spacing: -.01em; font-variant-numeric: tabular-nums; }
.hint-row { display: flex; align-items: center; justify-content: space-between; gap: .75rem; margin-top: .5rem; flex-wrap: wrap; }
.hint { font-size: .875rem; color: var(--ink-3); }
.hint a { color: var(--g-700); font-weight: 700; text-decoration: underline; text-underline-offset: 3px; }
.chip {
    min-height: 40px; padding: 0 1rem; border-radius: 999px; cursor: pointer;
    background: var(--g-100); color: var(--g-800); border: 0;
    font-size: .875rem; font-weight: 700;
    transition: background .18s var(--ease);
}
.chip:hover { background: var(--g-200); }
.err { font-size: .875rem; font-weight: 500; color: var(--rose); margin-top: .375rem; }
.err:empty { display: none; }

.methods { display: grid; grid-template-columns: 1fr 1fr; gap: .875rem; }
.method input:focus-visible + label { outline: 3px solid var(--g-600); outline-offset: 2px; }
.method label {
    display: flex; flex-direction: column; align-items: center; gap: .5rem;
    padding: 1.125rem .75rem; text-align: center; cursor: pointer;
    border: 1.5px solid #d9e1d8; border-radius: 16px; background: #fbfcfa;
    font-size: 1rem; font-weight: 700; color: var(--ink-2);
    transition: border-color .18s var(--ease), background .18s var(--ease), color .18s var(--ease);
}
.method label:hover { border-color: #bccabb; }
.method .m-ico {
    width: 48px; height: 48px; border-radius: 50%;
    background: var(--g-100); color: var(--g-700);
    display: flex; align-items: center; justify-content: center;
    transition: background .18s var(--ease), color .18s var(--ease);
}
.method .m-ico svg { width: 22px; height: 22px; }
.method input:checked + label { border-color: var(--g-700); background: var(--g-50); color: var(--g-800); box-shadow: 0 0 0 3px rgba(67,160,71,.15); }
.method input:checked + label .m-ico { background: var(--g-700); color: #fff; }

.btn {
    display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
    min-height: 54px; padding: 0 1.75rem; border-radius: 14px; border: 0; width: 100%;
    font-size: 1.0625rem; font-weight: 700; text-decoration: none; cursor: pointer;
    transition: background .18s var(--ease), transform .18s var(--ease);
}
.btn svg { width: 20px; height: 20px; }
.btn-primary { background: linear-gradient(135deg, var(--g-600), var(--g-800)); color: #fff; box-shadow: 0 8px 18px rgba(27,94,32,.22); }
.btn-primary:hover { background: linear-gradient(135deg, var(--g-700), var(--g-900)); transform: translateY(-1px); }

/* ---------- Daftar & info ---------- */
.section-title { font-size: 1.125rem; font-weight: 700; color: var(--ink); letter-spacing: -.02em; margin: 2rem 0 .75rem; }
.list { background: var(--card); border: 1px solid var(--line); border-radius: 20px; padding: .25rem 1.25rem; }
.row { display: flex; align-items: center; gap: .875rem; padding: 1rem 0; border-bottom: 1px solid var(--line); }
.row:last-child { border-bottom: 0; }
.row-aksi { display: flex; justify-content: flex-end; padding: 0 0 .875rem; }
.btn-batal {
    background: transparent; border: 1px solid var(--rose); color: var(--rose);
    border-radius: 999px; padding: .3rem .9rem; font: inherit; font-size: .8125rem;
    font-weight: 600; cursor: pointer;
}
.btn-batal:hover { background: var(--rose-bg); }
.row-ico { width: 46px; height: 46px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
.row-ico svg { width: 21px; height: 21px; }
.row-ico.success { background: var(--g-100); color: var(--g-700); }
.row-ico.warning { background: var(--amber-bg); color: var(--amber); }
.row-ico.info { background: var(--sky-bg); color: var(--sky); }
.row-ico.danger { background: var(--rose-bg); color: var(--rose); }
.row-ico.neutral { background: #eef1ec; color: var(--ink-3); }
.row-body { flex: 1; min-width: 0; }
.row-title { display: block; font-size: 1rem; font-weight: 600; color: var(--ink); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.row-sub { font-size: .875rem; color: var(--ink-3); }
.row-end { text-align: right; flex-shrink: 0; }
.row-amount { display: block; font-size: 1rem; font-weight: 700; color: var(--ink); font-variant-numeric: tabular-nums; }
.badge { display: inline-block; margin-top: .125rem; padding: .125rem .625rem; border-radius: 999px; font-size: .8125rem; font-weight: 600; }
.badge-success { background: var(--g-100); color: var(--g-800); }
.badge-warning { background: var(--amber-bg); color: var(--amber); }
.badge-danger { background: var(--rose-bg); color: var(--rose); }
.badge-info { background: var(--sky-bg); color: var(--sky); }
.badge-neutral { background: #eef1ec; color: var(--ink-3); }
.empty { text-align: center; padding: 2.25rem 1rem; }
.empty-ico { width: 60px; height: 60px; margin: 0 auto .875rem; border-radius: 50%; background: var(--g-100); color: var(--g-700); display: flex; align-items: center; justify-content: center; }
.empty-ico svg { width: 26px; height: 26px; }
.empty h3 { font-size: 1.0625rem; font-weight: 700; color: var(--ink); }
.empty p { font-size: .9375rem; color: var(--ink-3); margin-top: .25rem; }

.rules { background: var(--g-50); border: 1px solid var(--g-200); border-radius: 20px; padding: 1.25rem 1.5rem; }
.rules-head { display: flex; align-items: center; gap: .625rem; font-size: 1rem; font-weight: 700; color: var(--g-800); margin-bottom: .75rem; }
.rules-head svg { width: 20px; height: 20px; }
.rules ul { margin-left: 1.25rem; }
.rules li { font-size: .9375rem; color: var(--ink-2); margin-bottom: .5rem; }
.rules li:last-child { margin-bottom: 0; }

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
    .wallet { padding: 1.25rem; border-radius: 20px; }
    .wallet-amount { font-size: 2rem; }
    .group { padding: 1.25rem 1.125rem; border-radius: 18px; }
    .list { padding: .125rem 1rem; border-radius: 18px; }
    .row { gap: .75rem; padding: .875rem 0; }
    .row-ico { width: 42px; height: 42px; }
}
@media (max-width: 380px) {
    .topbar-brand { display: none; }
    .wallet-split { grid-template-columns: 1fr; }
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

<?php $menuAktif = 'nasabah/cairkan'; include VIEW_PATH . '/nasabah/partials/sidebar.php'; ?>

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

        <?php if (isset($flash) && $flash && !empty($flash['message'])): ?>
            <div class="flash flash-<?= $flashClass ?>" role="status">
                <?= icon($flashClass === 'success' ? 'check-circle' : ($flashClass === 'error' ? 'x-circle' : ($flashClass === 'warning' ? 'alert' : 'info'))) ?>
                <span><?= htmlspecialchars($flash['message']) ?></span>
            </div>
        <?php endif; ?>

        <div class="head">
            <h1 class="title">Cairkan saldo</h1>
            <p class="lead">Ajukan penarikan dari tabungan sampah Anda</p>
        </div>

        <section class="wallet" aria-label="Ringkasan saldo">
            <div class="wallet-label"><?= icon('wallet') ?> Bisa dicairkan</div>
            <div class="wallet-amount"><?= formatRupiah($saldoTersedia) ?></div>
            <div class="wallet-split">
                <div class="split">
                    <div class="split-label">Saldo tabungan</div>
                    <div class="split-value"><?= formatRupiah($saldo) ?></div>
                </div>
                <div class="split">
                    <div class="split-label">Sedang diajukan</div>
                    <div class="split-value">-<?= formatRupiah($totalTerikat) ?></div>
                </div>
            </div>
        </section>

        <section class="group" aria-labelledby="form-title">
            <h2 class="group-title" id="form-title">Form pengajuan</h2>
            <p class="group-hint">Tanda <span class="req">*</span> wajib diisi</p>

            <?php if ((float) $saldoTersedia < (float) $MINIMAL_PENCAIRAN): ?>
                <div class="notice notice-warning" style="margin-bottom:0">
                    <?= icon('alert') ?>
                    <p>
                        <strong>Saldo belum cukup untuk dicairkan</strong>
                        Saldo yang bisa dicairkan saat ini <?= formatRupiah($saldoTersedia) ?>, sedangkan minimal pencairan <?= formatRupiah($MINIMAL_PENCAIRAN) ?>.<?php if ((float) $totalTerikat > 0): ?> Sebagian saldo Anda masih terikat pada pengajuan yang sedang berjalan.<?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                <?php if (!empty($errors)): ?>
                    <div class="notice notice-error" role="alert">
                        <?= icon('x-circle') ?>
                        <div>
                            <strong>Pengajuan belum bisa diproses</strong>
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="formCairkan" onsubmit="return validateForm();" novalidate>
                    <div class="field">
                        <label for="jumlah" class="label">Jumlah pencairan <span class="req">*</span></label>
                        <div class="amount">
                            <span class="prefix" aria-hidden="true">Rp</span>
                            <input
                                type="text"
                                inputmode="numeric"
                                id="jumlah"
                                name="jumlah"
                                class="input"
                                placeholder="0"
                                autocomplete="off"
                                value="<?= htmlspecialchars($oldInput['jumlah'] ?? '') ?>"
                                oninput="formatNumberInput(this)"
                                onblur="validateJumlah()"
                            >
                        </div>
                        <div class="hint-row">
                            <span class="hint">Minimal <?= formatRupiah($MINIMAL_PENCAIRAN) ?></span>
                            <button type="button" class="chip" onclick="isiSemuaSaldo()">Ajukan semua</button>
                        </div>
                        <div class="err" id="jumlahError" role="alert"></div>
                    </div>

                    <div class="field" role="radiogroup" aria-labelledby="metode-label">
                        <span class="label" id="metode-label">Metode pencairan <span class="req">*</span></span>
                        <div class="methods">
                            <div class="method">
                                <input class="vh" type="radio" name="metode" id="metodeTunai" value="TUNAI" <?= $metodeLama === 'TUNAI' ? 'checked' : '' ?> onclick="toggleTransfer()">
                                <label for="metodeTunai">
                                    <span class="m-ico" aria-hidden="true"><?= icon('banknote') ?></span>
                                    Tunai
                                </label>
                            </div>
                            <div class="method">
                                <input class="vh" type="radio" name="metode" id="metodeTransfer" value="TRANSFER" <?= $metodeLama === 'TRANSFER' ? 'checked' : '' ?> onclick="toggleTransfer()">
                                <label for="metodeTransfer">
                                    <span class="m-ico" aria-hidden="true"><?= icon('landmark') ?></span>
                                    Transfer
                                </label>
                            </div>
                        </div>
                        <div class="err" id="metodeError" role="alert"></div>
                    </div>

                    <div class="field" id="transferGroup" style="display: none;">
                        <label for="tujuan_transfer" class="label">Tujuan transfer <span class="req">*</span></label>
                        <input
                            type="text"
                            inputmode="numeric"
                            id="tujuan_transfer"
                            name="tujuan_transfer"
                            class="input"
                            placeholder="Nomor rekening atau e-wallet"
                            autocomplete="off"
                            data-default="<?= htmlspecialchars($rekeningProfil) ?>"
                            value="<?= htmlspecialchars($tujuanLama) ?>"
                        >
                        <div class="hint-row">
                            <?php if ($punyaDataNasabah && $rekeningProfil !== ''): ?>
                                <span class="hint">Isian awal diambil dari rekening di profil Anda. Ubah jika ingin ke tujuan lain.</span>
                            <?php elseif ($punyaDataNasabah): ?>
                                <span class="hint">Belum ada rekening tersimpan. <a href="<?= base_url('nasabah/profil') ?>">Simpan di Profil</a> agar terisi otomatis.</span>
                            <?php else: ?>
                                <span class="hint">Contoh: 1234567890 (BCA) atau 081234567890 (GoPay/OVO)</span>
                            <?php endif; ?>
                        </div>
                        <div class="err" id="transferError" role="alert"></div>
                    </div>

                    <button type="submit" name="submit" class="btn btn-primary"><?= icon('banknote') ?> Ajukan pencairan</button>
                </form>
            <?php endif; ?>
        </section>

        <h2 class="section-title">Pengajuan aktif</h2>
        <div class="list">
            <?php if (!empty($pengajuanAktif)): ?>
                <?php foreach ($pengajuanAktif as $p): ?>
                    <?php list($label, $kelas, $ikonStatus, $ketStatus) = statusPencairan($p['status'] ?? ''); ?>
                    <div class="row">
                        <span class="row-ico <?= $kelas ?>" aria-hidden="true"><?= icon($ikonStatus) ?></span>
                        <span class="row-body">
                            <span class="row-title"><?= htmlspecialchars($p['no_pengajuan'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="row-sub"><?= htmlspecialchars($p['metode'], ENT_QUOTES, 'UTF-8') ?><?= $ketStatus !== '' ? ' &middot; ' . htmlspecialchars($ketStatus, ENT_QUOTES, 'UTF-8') : '' ?></span>
                        </span>
                        <span class="row-end">
                            <span class="row-amount"><?= formatRupiah($p['jumlah']) ?></span>
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
                    <div class="empty-ico" aria-hidden="true"><?= icon('inbox') ?></div>
                    <h3>Belum ada pengajuan</h3>
                    <p>Pengajuan pencairan yang Anda buat akan tampil di sini.</p>
                </div>
            <?php endif; ?>
        </div>

        <h2 class="section-title">Informasi pencairan</h2>
        <section class="rules" aria-label="Ketentuan pencairan">
            <div class="rules-head"><?= icon('shield') ?> Ketentuan sebelum mengajukan</div>
            <ul>
                <li>Minimal pencairan sebesar <strong><?= formatRupiah($MINIMAL_PENCAIRAN) ?></strong>.</li>
                <li>Saldo yang bisa dicairkan sudah dikurangi pengajuan yang sedang berjalan.</li>
                <li>Pengajuan diproses dalam 1–2 hari kerja.</li>
                <li>Pencairan lewat transfer dapat dikenakan biaya admin sesuai bank tujuan.</li>
            </ul>
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

/* ---------- Logika formulir ---------- */
var MINIMAL_PENCAIRAN = <?= (float) $MINIMAL_PENCAIRAN ?>;
var SALDO_TERSEDIA = <?= (float) $saldoTersedia ?>;

function formatNumberInput(input) {
    var value = input.value.replace(/[^\d]/g, '');
    if (value) { value = parseInt(value, 10).toLocaleString('id-ID'); }
    input.value = value;
    input.classList.remove('error');
    var errorEl = document.getElementById('jumlahError');
    if (errorEl) { errorEl.textContent = ''; }
}

function isiSemuaSaldo() {
    var input = document.getElementById('jumlah');
    if (!input) { return; }
    input.value = Math.floor(SALDO_TERSEDIA).toLocaleString('id-ID');
    input.classList.remove('error');
    document.getElementById('jumlahError').textContent = '';
    input.focus();
}

function toggleTransfer() {
    var metodeTransfer = document.getElementById('metodeTransfer');
    var transferGroup = document.getElementById('transferGroup');
    var tujuan = document.getElementById('tujuan_transfer');
    if (!metodeTransfer || !transferGroup || !tujuan) { return; }

    var metodeError = document.getElementById('metodeError');
    if (metodeError) { metodeError.textContent = ''; }

    if (metodeTransfer.checked) {
        transferGroup.style.display = 'block';
        tujuan.required = true;
        /* Isi otomatis dari rekening di profil bila kolom masih kosong */
        if (!tujuan.value) { tujuan.value = tujuan.getAttribute('data-default') || ''; }
    } else {
        transferGroup.style.display = 'none';
        tujuan.required = false;
        tujuan.value = '';
        tujuan.classList.remove('error');
        document.getElementById('transferError').textContent = '';
    }
}

function validateJumlah() {
    var input = document.getElementById('jumlah');
    var errorEl = document.getElementById('jumlahError');
    var value = parseInt(input.value.replace(/[^\d]/g, ''), 10);

    if (isNaN(value) || value <= 0) {
        errorEl.textContent = 'Jumlah harus diisi dengan angka.';
        input.classList.add('error');
        return false;
    }
    if (value < MINIMAL_PENCAIRAN) {
        errorEl.textContent = 'Minimal pencairan adalah Rp ' + MINIMAL_PENCAIRAN.toLocaleString('id-ID') + '.';
        input.classList.add('error');
        return false;
    }
    if (value > SALDO_TERSEDIA) {
        errorEl.textContent = 'Jumlah tidak boleh melebihi saldo yang tersedia (Rp ' + SALDO_TERSEDIA.toLocaleString('id-ID') + ').';
        input.classList.add('error');
        return false;
    }
    errorEl.textContent = '';
    input.classList.remove('error');
    return true;
}

function validateTransfer() {
    var metodeTransfer = document.getElementById('metodeTransfer').checked;
    if (metodeTransfer) {
        var input = document.getElementById('tujuan_transfer');
        var errorEl = document.getElementById('transferError');
        var value = input.value.trim();
        if (value.length < 8) {
            errorEl.textContent = 'Nomor tujuan transfer minimal 8 karakter.';
            input.classList.add('error');
            return false;
        }
        errorEl.textContent = '';
        input.classList.remove('error');
    }
    return true;
}

function validateForm() {
    var isValid = true;

    if (!validateJumlah()) { isValid = false; }

    var metodeTunai = document.getElementById('metodeTunai').checked;
    var metodeTransfer = document.getElementById('metodeTransfer').checked;
    var metodeError = document.getElementById('metodeError');
    if (!metodeTunai && !metodeTransfer) {
        metodeError.textContent = 'Silakan pilih metode pencairan.';
        isValid = false;
    } else {
        metodeError.textContent = '';
    }

    if (!validateTransfer()) { isValid = false; }

    if (isValid) {
        var jumlah = document.getElementById('jumlah').value;
        return confirm('Anda yakin ingin mengajukan pencairan sebesar Rp ' + jumlah + '?');
    }

    var firstError = document.querySelector('.input.error, .err:not(:empty)');
    if (firstError && firstError.scrollIntoView) {
        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    return false;
}

document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('metodeTransfer')) { toggleTransfer(); }
});
</script>
<?php notifikasiScript(); ?>
</body>
</html>