<?php
/**
 * Laporan Keuangan Kepala Desa
 *
 * Compatible dengan PHP 7.3+
 *
 * Hanya angka agregat, tanpa data pribadi warga (BR-09).
 *
 * @var array      $stats        total_transaksi_beli, total_berat_beli, total_nilai_beli,
 *                               total_tabungan, total_tunai, total_transaksi_jual,
 *                               total_berat_jual, total_nilai_jual, total_piutang, keuntungan
 * @var array      $banks        daftar bank sampah desa (id, nama)
 * @var string     $tanggalFrom  Y-m-d
 * @var string     $tanggalTo    Y-m-d
 * @var int|null   $bankId       filter bank (null = semua)
 * @var array|null $pencairan    OPSIONAL: jumlah_dicairkan, nilai_dicairkan, menunggu, ditolak
 * @var array|null $stok         OPSIONAL: tiap item nama, berat_kg
 * @var array|null $komposisi    OPSIONAL: tiap item nama, berat_kg, nilai
 * @var array      $user
 * @var array|null $flash
 */

if (!function_exists('formatRupiah')) {
    function formatRupiah($number): string
    {
        return 'Rp ' . number_format((float) $number, 0, ',', '.');
    }
}

if (!function_exists('formatBerat')) {
    /** Berat dalam kg, koma desimal, tanpa nol di belakang. */
    function formatBerat($kg): string
    {
        $s = number_format((float) $kg, 2, ',', '.');
        $s = rtrim(rtrim($s, '0'), ',');
        return ($s === '' ? '0' : $s) . ' kg';
    }
}

if (!function_exists('tanggalIndo')) {
    function tanggalIndo(string $ymd): string
    {
        $bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $t = strtotime($ymd);
        if ($t === false) {
            return $ymd;
        }
        return (int) date('j', $t) . ' ' . $bulan[(int) date('n', $t) - 1] . ' ' . date('Y', $t);
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
            'check-circle' => '<path d="M22 11.1V12a10 10 0 1 1-5.9-9.1"/><polyline points="22 4 12 14.01 9 11.01"/>',
            'x-circle' => '<circle cx="12" cy="12" r="9"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>',
            'alert' => '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
            'info' => '<circle cx="12" cy="12" r="9"/><line x1="12" y1="11" x2="12" y2="16"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
            'inbox' => '<polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.5 5.1 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.5-6.9A2 2 0 0 0 16.8 4H7.2a2 2 0 0 0-1.7 1.1z"/>',
            'chevron' => '<polyline points="9 6 15 12 9 18"/>',
            'printer' => '<polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>',
            'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
            'package' => '<path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="m3 8 9 5 9-5M12 13v8"/>',
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
$banks = (isset($banks) && is_array($banks)) ? $banks : [];
$tanggalFrom = isset($tanggalFrom) ? (string) $tanggalFrom : date('Y-m-01');
$tanggalTo = isset($tanggalTo) ? (string) $tanggalTo : date('Y-m-d');
$bankId = isset($bankId) && $bankId ? (int) $bankId : null;

$pencairan = (isset($pencairan) && is_array($pencairan)) ? $pencairan : null;
$stok = (isset($stok) && is_array($stok)) ? $stok : null;
$komposisi = (isset($komposisi) && is_array($komposisi)) ? $komposisi : null;

$trBeli = (float) ($stats['total_transaksi_beli'] ?? 0);
$brBeli = (float) ($stats['total_berat_beli'] ?? 0);
$nlBeli = (float) ($stats['total_nilai_beli'] ?? 0);
$tabungan = (float) ($stats['total_tabungan'] ?? 0);
$tunai = (float) ($stats['total_tunai'] ?? 0);
$trJual = (float) ($stats['total_transaksi_jual'] ?? 0);
$brJual = (float) ($stats['total_berat_jual'] ?? 0);
$nlJual = (float) ($stats['total_nilai_jual'] ?? 0);
$piutang = (float) ($stats['total_piutang'] ?? 0);
$selisih = (float) ($stats['keuntungan'] ?? ($nlJual - $nlBeli));
$sudahDibayar = max(0, $nlJual - $piutang);

$pctTabungan = $nlBeli > 0 ? min(100, max(0, $tabungan / $nlBeli * 100)) : 0;
$pctTunai = $nlBeli > 0 ? min(100 - $pctTabungan, max(0, $tunai / $nlBeli * 100)) : 0;

// Laporan dianggap berisi bila ada transaksi, aktivitas pencairan, atau stok
// (pencairan dan stok bisa ada walau periode ini tanpa pembelian/penjualan).
$adaPencairan = $pencairan !== null && (
    (float) ($pencairan['jumlah_dicairkan'] ?? 0)
    + (float) ($pencairan['menunggu'] ?? 0)
    + (float) ($pencairan['ditolak'] ?? 0)
) > 0;
$adaTransaksi = ($trBeli + $trJual) > 0 || $adaPencairan || !empty($stok);

$namaBank = 'Semua bank sampah';
if ($bankId) {
    foreach ($banks as $b) {
        if ((int) ($b['id'] ?? 0) === $bankId) {
            $namaBank = (string) ($b['nama'] ?? $namaBank);
            break;
        }
    }
}

/* Pintasan periode */
$qBank = $bankId ? ['bank_id' => $bankId] : [];
$presets = [
    'Bulan ini' => [date('Y-m-01'), date('Y-m-d')],
    '30 hari terakhir' => [date('Y-m-d', strtotime('-29 days')), date('Y-m-d')],
    'Bulan lalu' => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('first day of last month'))],
];

$namaUser = (string) ($user['nama'] ?? 'Kepala Desa');
$inisial = function_exists('mb_substr') ? mb_strtoupper(mb_substr($namaUser, 0, 1)) : strtoupper(substr($namaUser, 0, 1));

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
<title>Laporan Keuangan - <?= htmlspecialchars($appName) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
    --g-900: #0f3d14; --g-800: #1b5e20; --g-700: #2e7d32; --g-600: #43a047; --g-500: #66bb6a;
    --g-200: #c8e6c9; --g-100: #e8f5e9; --g-50: #f4faf4;
    --paper: #f7faf6; --card: #ffffff;
    --ink: #17231a; --ink-2: #3b4a3e; --ink-3: #5b6a5e; --line: #e6ece5;
    --amber: #a85a00; --amber-bg: #fff1d6;
    --rose: #b3261e; --rose-bg: #fde8e6;
    --sky: #1a56c4; --sky-bg: #e2edff;
    --sidebar-w: 268px; --topbar-h: 68px;
    --ease: cubic-bezier(0.4, 0, 0.2, 1);
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { -webkit-text-size-adjust: 100%; }
body {
    font-family: 'Plus Jakarta Sans', 'Segoe UI', system-ui, -apple-system, sans-serif;
    font-size: 16px; line-height: 1.5; color: var(--ink-2); background: var(--paper);
    -webkit-font-smoothing: antialiased;
}
svg { display: block; }
a { color: inherit; }
button, input, select { font-family: inherit; font-size: inherit; }
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
    color: #fff; transform: translateX(-100%); transition: transform .3s var(--ease);
}
.side-head { padding: 1.25rem 1.25rem 1rem; }
.side-brand { display: flex; align-items: center; gap: .75rem; text-decoration: none; }
.side-mark {
    width: 42px; height: 42px; border-radius: 12px; flex-shrink: 0; background: rgba(255,255,255,.16);
    display: flex; align-items: center; justify-content: center;
}
.side-mark svg { width: 22px; height: 22px; }
.side-name { display: block; font-size: 1rem; font-weight: 700; line-height: 1.25; }
.side-sub { display: block; font-size: .75rem; color: rgba(255,255,255,.85); }
.side-nav { flex: 1; overflow-y: auto; padding: .5rem .875rem; }
.side-group + .side-group { margin-top: 1.25rem; }
.side-group-label { font-size: .8125rem; font-weight: 600; color: rgba(255,255,255,.8); padding: 0 .75rem; margin-bottom: .375rem; }
.side-link {
    display: flex; align-items: center; gap: .75rem; min-height: 50px; padding: .625rem .875rem;
    border-radius: 12px; text-decoration: none; font-size: 1rem; font-weight: 500;
    color: rgba(255,255,255,.95); transition: background .18s var(--ease);
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
    backdrop-filter: saturate(180%) blur(12px); -webkit-backdrop-filter: saturate(180%) blur(12px);
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
    display: flex; align-items: center; justify-content: center; font-size: 1rem; font-weight: 700;
}

/* ---------- Halaman ---------- */
.page { max-width: 1040px; width: 100%; margin: 0 auto; padding: .5rem 1.75rem 3rem; }
.crumb { display: inline-flex; align-items: center; gap: .375rem; font-size: .9375rem; font-weight: 600; color: var(--g-700); text-decoration: none; margin-bottom: .5rem; }
.crumb svg { width: 16px; height: 16px; transform: rotate(180deg); }
.head-row { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem; }
.page-title { font-size: 1.75rem; font-weight: 800; color: var(--ink); letter-spacing: -.03em; line-height: 1.2; }
.page-sub { margin-top: .25rem; font-size: 1rem; color: var(--ink-3); }
.btn {
    display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
    min-height: 46px; padding: .5rem 1.125rem; border-radius: 14px; cursor: pointer;
    font-size: .9375rem; font-weight: 700; text-decoration: none;
    border: 1px solid var(--line); background: var(--card); color: var(--ink);
    transition: background .18s var(--ease), border-color .18s var(--ease);
}
.btn:hover { background: var(--g-100); border-color: var(--g-200); }
.btn svg { width: 18px; height: 18px; }
.btn-primary { background: var(--g-700); border-color: var(--g-700); color: #fff; }
.btn-primary:hover { background: var(--g-800); border-color: var(--g-800); }

.flash {
    display: flex; gap: .625rem; align-items: flex-start; padding: .875rem 1.125rem;
    border-radius: 14px; margin-bottom: 1.25rem; font-size: .9375rem; font-weight: 500; border: 1px solid transparent;
}
.flash svg { width: 20px; height: 20px; flex-shrink: 0; margin-top: 2px; }
.flash-success { background: var(--g-50); color: #14532d; border-color: var(--g-200); }
.flash-error { background: var(--rose-bg); color: var(--rose); border-color: #f8c9c5; }
.flash-warning { background: var(--amber-bg); color: var(--amber); border-color: #f6d9a0; }
.flash-info { background: var(--sky-bg); color: var(--sky); border-color: #c4d8fa; }

/* ---------- Filter ---------- */
.filter { background: var(--card); border: 1px solid var(--line); border-radius: 20px; padding: 1rem 1.25rem 1.125rem; margin-bottom: 1.25rem; }
.filter-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)) auto; gap: .75rem; align-items: end; }
.field label { display: block; font-size: .8125rem; font-weight: 600; color: var(--ink-3); margin-bottom: .25rem; }
.field input, .field select {
    width: 100%; min-height: 46px; padding: .5rem .75rem; border-radius: 12px;
    border: 1px solid #d3ddd2; background: #fff; color: var(--ink);
}
.presets { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .875rem; }
.preset {
    padding: .3125rem .875rem; border-radius: 999px; font-size: .875rem; font-weight: 600;
    text-decoration: none; background: #eef1ec; color: var(--ink-2); border: 1px solid transparent;
}
.preset:hover { background: var(--g-100); }
.preset.on { background: var(--g-100); color: var(--g-800); border-color: var(--g-200); }

/* ---------- Kartu hero ---------- */
.wallet {
    position: relative; overflow: hidden; color: #fff; border-radius: 24px; padding: 1.5rem 1.75rem;
    background: linear-gradient(135deg, #43a047 0%, #2e7d32 45%, #14501a 100%);
    box-shadow: 0 14px 32px rgba(27,94,32,.22);
}
.wallet::before, .wallet::after { content: ""; position: absolute; border-radius: 50%; pointer-events: none; background: rgba(255,255,255,.07); }
.wallet::before { width: 260px; height: 260px; right: -90px; top: -120px; }
.wallet::after { width: 180px; height: 180px; right: 80px; bottom: -120px; }
.wallet > * { position: relative; z-index: 1; }
.wallet-label { display: flex; align-items: center; gap: .5rem; font-size: 1rem; font-weight: 600; color: rgba(255,255,255,.95); }
.wallet-label svg { width: 20px; height: 20px; }
.wallet-amount { margin-top: .5rem; font-size: 2.25rem; font-weight: 800; letter-spacing: -.04em; line-height: 1.1; font-variant-numeric: tabular-nums; }
.wallet-note { margin-top: .375rem; font-size: .9375rem; color: rgba(255,255,255,.92); }
.wallet-split { display: grid; grid-template-columns: repeat(2, 1fr); gap: .875rem; margin-top: 1.25rem; }
.split { padding: .75rem 1rem; border-radius: 16px; background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.18); }
.split-label { font-size: .875rem; color: rgba(255,255,255,.92); }
.split-value { font-size: 1.1875rem; font-weight: 700; letter-spacing: -.02em; font-variant-numeric: tabular-nums; margin-top: .125rem; }

/* ---------- Panel ---------- */
.cols { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; margin: 1.25rem 0; }
.panel { background: var(--card); border: 1px solid var(--line); border-radius: 20px; overflow: hidden; }
.panel-head { display: flex; align-items: center; gap: .75rem; padding: 1rem 1.25rem; border-bottom: 1px solid var(--line); }
.panel-ico { width: 40px; height: 40px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
.panel-ico svg { width: 19px; height: 19px; }
.panel-ico.beli { background: var(--g-100); color: var(--g-700); }
.panel-ico.jual { background: var(--sky-bg); color: var(--sky); }
.panel-ico.neutral { background: #eef1ec; color: var(--ink-2); }
.panel-title { font-size: 1.0625rem; font-weight: 700; color: var(--ink); letter-spacing: -.01em; }
.kv { list-style: none; }
.kv li { display: flex; justify-content: space-between; gap: 1rem; padding: .75rem 1.25rem; border-bottom: 1px solid var(--line); font-size: .9375rem; }
.kv li:last-child { border-bottom: 0; }
.kv .k { color: var(--ink-3); }
.kv .v { font-weight: 700; color: var(--ink); font-variant-numeric: tabular-nums; text-align: right; }
.kv .v.beli { color: var(--g-700); }
.kv .v.jual { color: var(--sky); }
.kv .v.warn { color: var(--amber); }
.bar { display: flex; height: 10px; border-radius: 999px; overflow: hidden; background: #eef1ec; margin: .25rem 1.25rem 1rem; }
.bar i { display: block; height: 100%; }
.bar .a { background: var(--g-700); }
.bar .b { background: var(--g-200); }
.bar-legend { display: flex; flex-wrap: wrap; gap: .25rem 1rem; padding: 0 1.25rem 1rem; font-size: .8125rem; color: var(--ink-3); }
.bar-legend span { display: inline-flex; align-items: center; gap: .375rem; }
.bar-legend i { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }

.section-title { font-size: 1.125rem; font-weight: 700; color: var(--ink); letter-spacing: -.02em; margin: 1.5rem 0 .625rem; }
.table-wrap { overflow-x: auto; }
.tbl { width: 100%; border-collapse: collapse; font-variant-numeric: tabular-nums; }
.tbl th { padding: .75rem 1.125rem; text-align: right; font-size: .8125rem; font-weight: 600; color: var(--ink-3); background: var(--g-50); border-bottom: 1px solid var(--line); white-space: nowrap; }
.tbl td { padding: .75rem 1.125rem; text-align: right; font-size: .9375rem; color: var(--ink); border-bottom: 1px solid var(--line); }
.tbl th:first-child, .tbl td:first-child { text-align: left; font-weight: 600; }
.tbl tbody tr:last-child td { border-bottom: 0; }

.note { display: flex; gap: .625rem; align-items: flex-start; margin-top: 1.25rem; padding: .875rem 1.125rem; border-radius: 14px; background: var(--sky-bg); color: #163f8f; font-size: .875rem; }
.note svg { width: 18px; height: 18px; flex-shrink: 0; margin-top: 2px; }

.empty { text-align: center; padding: 2.5rem 1rem; }
.empty-ico { width: 56px; height: 56px; margin: 0 auto .75rem; border-radius: 50%; background: var(--g-100); color: var(--g-700); display: flex; align-items: center; justify-content: center; }
.empty-ico svg { width: 26px; height: 26px; }
.empty h3 { font-size: 1rem; font-weight: 700; color: var(--ink); }
.empty p { font-size: .9375rem; color: var(--ink-3); margin-top: .25rem; }

.print-head { display: none; }

/* ---------- Responsif ---------- */
@media (min-width: 1101px) {
    body.nav-open { overflow: auto; }
    body.nav-open .scrim { opacity: 0; visibility: hidden; }
    body.nav-open .main { margin-left: var(--sidebar-w); }
}
@media (max-width: 900px) {
    .filter-grid { grid-template-columns: 1fr 1fr; }
    .filter-grid .field-bank { grid-column: 1 / -1; }
    .filter-grid .btn { grid-column: 1 / -1; }
}
@media (max-width: 720px) {
    .topbar { padding: 0 1rem; }
    .page { padding: .5rem 1rem 3rem; }
    .who { padding-left: 0; }
    .who-name { display: none; }
    .page-title { font-size: 1.5rem; }
    .wallet { padding: 1.25rem 1.125rem; border-radius: 22px; }
    .wallet-amount { font-size: 2rem; }
    .cols { grid-template-columns: minmax(0, 1fr); }
    .head-row .btn { width: 100%; }
}
@media (max-width: 380px) {
    .wallet-split { grid-template-columns: 1fr; }
    .topbar-brand { display: none; }
}
@media (prefers-reduced-motion: reduce) {
    * { transition-duration: .01ms !important; animation-duration: .01ms !important; }
}

/* ---------- Cetak / simpan PDF ---------- */
@media print {
    body { background: #fff; font-size: 12px; }
    .sidebar, .scrim, .topbar, .filter, .btn, .crumb, .flash { display: none !important; }
    .main { margin-left: 0 !important; }
    .page { max-width: none; padding: 0; }
    .wallet { box-shadow: none; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .panel, .wallet { break-inside: avoid; }
    .print-head { display: block; margin-bottom: 1rem; }
    .print-head h2 { font-size: 1.125rem; color: #000; }
    .print-head p { color: #333; }
    .bar, .bar i, .panel-ico { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>
<script>
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
            <a href="<?= base_url('kades/monitoring') ?>" class="side-link"><?= icon('trending') ?> <span>Monitoring</span></a>
        </div>
        <div class="side-group">
            <div class="side-group-label">Keuangan</div>
            <a href="<?= base_url('kades/laporan') ?>" class="side-link active" aria-current="page"><?= icon('receipt') ?> <span>Laporan Keuangan</span></a>
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

        <a href="<?= base_url('kades/dashboard') ?>" class="crumb"><?= icon('chevron') ?> Dashboard</a>

        <div class="head-row">
            <div>
                <h1 class="page-title">Laporan Keuangan</h1>
                <p class="page-sub"><?= htmlspecialchars($namaBank) ?> &middot; <?= htmlspecialchars(tanggalIndo($tanggalFrom)) ?> sampai <?= htmlspecialchars(tanggalIndo($tanggalTo)) ?></p>
            </div>
            <button type="button" class="btn" onclick="window.print()"><?= icon('printer') ?> Cetak / simpan PDF</button>
        </div>

        <div class="print-head">
            <h2><?= htmlspecialchars($appName) ?> &ndash; Laporan Keuangan Desa</h2>
            <p><?= htmlspecialchars($namaBank) ?>, periode <?= htmlspecialchars(tanggalIndo($tanggalFrom)) ?> sampai <?= htmlspecialchars(tanggalIndo($tanggalTo)) ?></p>
        </div>

        <!-- Filter periode -->
        <form class="filter" method="get" action="<?= base_url('kades/laporan') ?>" aria-label="Filter laporan">
            <div class="filter-grid">
                <div class="field">
                    <label for="tanggal_from">Dari tanggal</label>
                    <input type="date" id="tanggal_from" name="tanggal_from" value="<?= htmlspecialchars($tanggalFrom) ?>" max="<?= date('Y-m-d') ?>">
                </div>
                <div class="field">
                    <label for="tanggal_to">Sampai tanggal</label>
                    <input type="date" id="tanggal_to" name="tanggal_to" value="<?= htmlspecialchars($tanggalTo) ?>" max="<?= date('Y-m-d') ?>">
                </div>
                <div class="field field-bank">
                    <label for="bank_id">Bank sampah</label>
                    <select id="bank_id" name="bank_id">
                        <option value="">Semua bank sampah</option>
                        <?php foreach ($banks as $b): ?>
                            <option value="<?= (int) $b['id'] ?>"<?= $bankId === (int) $b['id'] ? ' selected' : '' ?>><?= htmlspecialchars((string) ($b['nama'] ?? '-')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Terapkan</button>
            </div>
            <div class="presets" aria-label="Pintasan periode">
                <?php foreach ($presets as $label => $range):
                    $on = ($range[0] === $tanggalFrom && $range[1] === $tanggalTo);
                    $href = base_url('kades/laporan') . '?' . http_build_query(array_merge(['tanggal_from' => $range[0], 'tanggal_to' => $range[1]], $qBank));
                ?>
                    <a class="preset<?= $on ? ' on' : '' ?>" href="<?= htmlspecialchars($href) ?>"<?= $on ? ' aria-current="true"' : '' ?>><?= htmlspecialchars($label) ?></a>
                <?php endforeach; ?>
            </div>
        </form>

        <?php if (!$adaTransaksi): ?>
            <div class="panel">
                <div class="empty">
                    <div class="empty-ico" aria-hidden="true"><?= icon('inbox') ?></div>
                    <h3>Belum ada transaksi</h3>
                    <p>Tidak ada pembelian atau penjualan pada periode ini. Coba ubah rentang tanggal.</p>
                </div>
            </div>
        <?php else: ?>

            <!-- Ringkasan -->
            <section class="wallet" aria-label="Ringkasan periode">
                <span class="wallet-label"><?= icon('wallet') ?> Selisih penjualan dikurangi pembelian</span>
                <div class="wallet-amount"><?= ($selisih < 0 ? '-' : '') . formatRupiah(abs($selisih)) ?></div>
                <p class="wallet-note">Bukan laba bersih. Sampah yang sudah dibeli tetapi belum terjual masih tercatat sebagai stok.</p>
                <div class="wallet-split">
                    <div class="split">
                        <div class="split-label">Total pembelian</div>
                        <div class="split-value"><?= formatRupiah($nlBeli) ?></div>
                    </div>
                    <div class="split">
                        <div class="split-label">Total penjualan</div>
                        <div class="split-value"><?= formatRupiah($nlJual) ?></div>
                    </div>
                </div>
            </section>

            <div class="cols">
                <!-- Pembelian -->
                <section class="panel" aria-labelledby="h-beli">
                    <div class="panel-head">
                        <span class="panel-ico beli"><?= icon('receipt') ?></span>
                        <h2 class="panel-title" id="h-beli">Pembelian dari warga</h2>
                    </div>
                    <ul class="kv">
                        <li><span class="k">Jumlah transaksi</span><span class="v"><?= number_format($trBeli) ?></span></li>
                        <li><span class="k">Total berat</span><span class="v"><?= htmlspecialchars(formatBerat($brBeli)) ?></span></li>
                        <li><span class="k">Total nilai</span><span class="v beli"><?= formatRupiah($nlBeli) ?></span></li>
                        <li><span class="k">Masuk tabungan nasabah</span><span class="v"><?= formatRupiah($tabungan) ?></span></li>
                        <li><span class="k">Dibayar tunai</span><span class="v"><?= formatRupiah($tunai) ?></span></li>
                    </ul>
                    <?php if ($nlBeli > 0): ?>
                        <div class="bar" role="img" aria-label="Tabungan <?= round($pctTabungan) ?> persen, tunai <?= round($pctTunai) ?> persen">
                            <i class="a" style="width:<?= number_format($pctTabungan, 2, '.', '') ?>%"></i>
                            <i class="b" style="width:<?= number_format($pctTunai, 2, '.', '') ?>%"></i>
                        </div>
                        <div class="bar-legend" aria-hidden="true">
                            <span><i style="background:var(--g-700)"></i>Tabungan <?= round($pctTabungan) ?>%</span>
                            <span><i style="background:var(--g-200)"></i>Tunai <?= round($pctTunai) ?>%</span>
                        </div>
                    <?php endif; ?>
                </section>

                <!-- Penjualan -->
                <section class="panel" aria-labelledby="h-jual">
                    <div class="panel-head">
                        <span class="panel-ico jual"><?= icon('trending') ?></span>
                        <h2 class="panel-title" id="h-jual">Penjualan ke pengepul</h2>
                    </div>
                    <ul class="kv">
                        <li><span class="k">Jumlah transaksi</span><span class="v"><?= number_format($trJual) ?></span></li>
                        <li><span class="k">Total berat</span><span class="v"><?= htmlspecialchars(formatBerat($brJual)) ?></span></li>
                        <li><span class="k">Total nilai</span><span class="v jual"><?= formatRupiah($nlJual) ?></span></li>
                        <li><span class="k">Sudah dibayar pengepul</span><span class="v"><?= formatRupiah($sudahDibayar) ?></span></li>
                        <li><span class="k">Belum dibayar (piutang)</span><span class="v<?= $piutang > 0 ? ' warn' : '' ?>"><?= formatRupiah($piutang) ?></span></li>
                    </ul>
                </section>
            </div>

            <?php if ($pencairan !== null): ?>
                <section class="panel" aria-labelledby="h-cair" style="margin-bottom:1.25rem">
                    <div class="panel-head">
                        <span class="panel-ico neutral"><?= icon('wallet') ?></span>
                        <h2 class="panel-title" id="h-cair">Pencairan tabungan</h2>
                    </div>
                    <ul class="kv">
                        <li><span class="k">Sudah dicairkan</span><span class="v"><?= formatRupiah($pencairan['nilai_dicairkan'] ?? 0) ?> (<?= number_format((float) ($pencairan['jumlah_dicairkan'] ?? 0)) ?> pengajuan)</span></li>
                        <li><span class="k">Menunggu diproses</span><span class="v<?= ((float) ($pencairan['menunggu'] ?? 0)) > 0 ? ' warn' : '' ?>"><?= number_format((float) ($pencairan['menunggu'] ?? 0)) ?> pengajuan</span></li>
                        <li><span class="k">Ditolak</span><span class="v"><?= number_format((float) ($pencairan['ditolak'] ?? 0)) ?> pengajuan</span></li>
                    </ul>
                </section>
            <?php endif; ?>

            <?php if (!empty($komposisi)): ?>
                <h2 class="section-title">Komposisi per jenis sampah</h2>
                <div class="panel">
                    <div class="table-wrap">
                        <table class="tbl">
                            <thead><tr><th scope="col">Jenis sampah</th><th scope="col">Berat</th><th scope="col">Nilai beli</th></tr></thead>
                            <tbody>
                                <?php foreach ($komposisi as $k): ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string) ($k['nama'] ?? '-')) ?></td>
                                        <td><?= htmlspecialchars(formatBerat($k['berat_kg'] ?? 0)) ?></td>
                                        <td><?= formatRupiah($k['nilai'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($stok)): ?>
                <h2 class="section-title">Stok sampah saat ini</h2>
                <div class="panel">
                    <div class="table-wrap">
                        <table class="tbl">
                            <thead><tr><th scope="col">Jenis sampah</th><th scope="col">Stok</th></tr></thead>
                            <tbody>
                                <?php foreach ($stok as $s): ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string) ($s['nama'] ?? '-')) ?></td>
                                        <td><?= htmlspecialchars(formatBerat($s['berat_kg'] ?? 0)) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <p class="note">
                <?= icon('info') ?>
                <span>Laporan ini hanya memuat angka gabungan. Nama, NIK, dan nomor HP warga tidak ditampilkan.</span>
            </p>
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