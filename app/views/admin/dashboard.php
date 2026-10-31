<?php
/**
 * Dashboard Admin
 * Compatible dengan PHP 7.3+
 *
 * Variabel dari controller: $stats, $recentUsers, $flash (opsional)
 * $stats: total_users, total_bank, bank_aktif, total_desa, (opsional) total_nasabah
 */

if (!function_exists('icon')) {
    /**
     * Ikon SVG garis yang konsisten (tanpa emoji).
     */
    function icon(string $name, string $class = ''): string
    {
        $paths = [
            'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
            'close' => '<path d="M6 6l12 12M18 6L6 18"/>',
            'leaf' => '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>',
            'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
            'cart' => '<circle cx="9" cy="20" r="1.25"/><circle cx="18" cy="20" r="1.25"/><path d="M2 3h3l2.6 12.4a1 1 0 0 0 .8.8h8.8a1 1 0 0 0 1-.76L20 8H6"/>',
            'upload' => '<path d="M12 16V4M7 9l5-5 5 5"/><path d="M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/>',
            'box' => '<path d="M21 8l-9-5-9 5v8l9 5 9-5V8Z"/><path d="M3.3 7.5L12 12l8.7-4.5M12 12v9"/>',
            'tag' => '<path d="M20.6 13.4l-7.2 7.2a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8Z"/><path d="M7.5 7.5h.01"/>',
            'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
            'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/>',
            'truck' => '<path d="M1 6h13v11H1zM14 10h4l3 3v4h-7"/><circle cx="6" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',
            'chart' => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
            'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/>',
            'arrow-left' => '<path d="M19 12H5M12 19l-7-7 7-7"/>',
            'chevron-right' => '<path d="M9 6l6 6-6 6"/>',
            'chevron-left' => '<path d="M15 6l-6 6 6 6"/>',
            'download' => '<path d="M12 4v12M7 11l5 5 5-5"/><path d="M4 20h16"/>',
            'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
            'wallet' => '<path d="M19 7V6a2 2 0 0 0-2-2H5a2 2 0 0 0 0 4h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6"/><path d="M16 12h.01"/>',
            'cash' => '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/>',
            'bank' => '<path d="M3 10l9-6 9 6H3Z"/><path d="M6 10v8M10 10v8M14 10v8M18 10v8M3 21h18"/>',
            'card' => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>',
            'phone' => '<rect x="6" y="2" width="12" height="20" rx="2.5"/><path d="M11 18h2"/>',
            'hash' => '<path d="M4 9h16M4 15h16M10 3L8 21M16 3l-2 18"/>',
            'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
            'send' => '<path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7Z"/>',
            'file' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Z"/><path d="M14 2v6h6M9 14h6M9 18h4"/>',
            'check' => '<path d="M5 12.5l4.5 4.5L19 7"/>',
            'alert' => '<path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/>',
            'building' => '<path d="M4 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16"/><path d="M16 9h2a2 2 0 0 1 2 2v10M2 21h20M8 7h4M8 11h4M8 15h4"/>',
            'pin' => '<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
            'settings' => '<path d="M4 6h8M16 6h4M4 12h4M12 12h8M4 18h10M18 18h2"/><circle cx="14" cy="6" r="2"/><circle cx="10" cy="12" r="2"/><circle cx="16" cy="18" r="2"/>',
            'plus' => '<path d="M12 5v14M5 12h14"/>',
            'user-plus' => '<circle cx="9" cy="8" r="4"/><path d="M2 21v-1a6 6 0 0 1 6-6h2a6 6 0 0 1 6 6v1M19 8v6M16 11h6"/>',
            'info' => '<circle cx="12" cy="12" r="9"/><line x1="12" y1="11" x2="12" y2="16"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
        ];

        $body = isset($paths[$name]) ? $paths[$name] : $paths['info'];
        $cls = $class !== '' ? ' class="' . htmlspecialchars($class) . '"' : '';

        return '<svg' . $cls . ' viewBox="0 0 24 24" fill="none" stroke="currentColor"'
            . ' stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"'
            . ' aria-hidden="true" focusable="false">' . $body . '</svg>';
    }
}


$e = function ($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};

$user = currentUser();
$userName = 'Administrator';
if (is_array($user)) {
    foreach (['nama', 'name', 'username'] as $k) {
        if (!empty($user[$k])) {
            $userName = (string) $user[$k];
            break;
        }
    }
}
$userInitial = function_exists('mb_substr')
    ? mb_strtoupper(mb_substr($userName, 0, 1, 'UTF-8'), 'UTF-8')
    : strtoupper(substr($userName, 0, 1));

// Jika controller tidak mengirim $flash, ambil dari helper global.
if (!isset($flash)) {
    $flash = function_exists('getFlashMessage') ? getFlashMessage() : null;
}
$flashType = 'info';
if (!empty($flash['type']) && in_array($flash['type'], ['success', 'error', 'warning', 'info'], true)) {
    $flashType = $flash['type'];
}
$flashIcon = ['success' => 'check', 'error' => 'alert', 'warning' => 'alert', 'info' => 'info'][$flashType];

$stats = (isset($stats) && is_array($stats)) ? $stats : [];
$recentUsers = (isset($recentUsers) && is_array($recentUsers)) ? $recentUsers : [];

$angka = function ($key) use ($stats) {
    return number_format((int) (isset($stats[$key]) ? $stats[$key] : 0), 0, ',', '.');
};
// ASUMSI: controller bisa mengirim $stats['total_nasabah']. Jika belum ada,
// dipakai total_users seperti file lama (angkanya akan sama dengan Total Pengguna).
$totalNasabah = number_format((int) (isset($stats['total_nasabah']) ? $stats['total_nasabah'] : (isset($stats['total_users']) ? $stats['total_users'] : 0)), 0, ',', '.');

$hari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
$bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$hariIni = $hari[(int) date('N') - 1] . ', ' . date('j') . ' ' . $bulan[(int) date('n') - 1] . ' ' . date('Y');

$roleMeta = [
    'ADMIN'     => ['Admin', 'b-rose'],
    'PENGELOLA' => ['Pengelola', 'b-amber'],
    'NASABAH'   => ['Nasabah', 'b-sky'],
];
$label = function ($v) {
    $v = (string) $v;
    return $v === '' ? '-' : ucfirst(strtolower($v));
};

$menu = [
    ['admin/dashboard',        'Dashboard',          'grid'],
    ['admin/users',            'Kelola Pengguna',    'users'],
    ['admin/nasabah',          'Kelola Nasabah',     'user'],
    ['admin/bank-sampah',      'Kelola Bank Sampah', 'building'],
    ['admin/desa',             'Kelola Desa',        'pin'],
    ['admin/pengaturan',       'Pengaturan',         'settings'],
];
$menuAktif = 'admin/dashboard';

$statCards = [
    ['admin/users',       'users',    $angka('total_users'), 'Total pengguna', ''],
    ['admin/nasabah',     'user',     $totalNasabah,         'Total nasabah',  ''],
    ['admin/bank-sampah', 'building', $angka('total_bank'),  'Bank sampah',    $angka('bank_aktif') . ' aktif'],
    ['admin/desa',        'pin',      $angka('total_desa'),  'Desa',           ''],
];
// Router memakai segmen URL (/add), bukan query string (?action=add).
$aksiCepat = [
    ['admin/users/add',        'user-plus', 'Tambah pengguna',     'Buat akun baru'],
    ['admin/nasabah/add',      'user',      'Tambah nasabah',      'Daftarkan anggota baru'],
    ['admin/bank-sampah/add',  'building',  'Tambah bank sampah',  'Buat unit baru'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1b5e20">
    <title>Dashboard - <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        (function () {
            var r = document.documentElement;
            r.classList.add('no-anim');
            var s = null;
            try { s = localStorage.getItem('nav'); } catch (e) {}
            if (window.innerWidth > 1100 && s !== 'closed') { r.classList.add('nav-open'); }
        })();
    </script>
    <style>
        :root {
            --g-900:#0f3d14; --g-800:#1b5e20; --g-700:#2e7d32; --g-600:#43a047; --g-500:#66bb6a;
            --g-200:#c8e6c9; --g-100:#e8f5e9; --g-50:#f4faf4;
            --paper:#f7faf6; --ink:#17231a; --ink-2:#3b4a3e; --ink-3:#5b6a5e; --line:#e6ece5;
            --amber:#a85a00; --amber-bg:#fff1d6; --rose:#b3261e; --rose-bg:#fde8e6; --sky:#1a56c4; --sky-bg:#e2edff;
            --nav-w:280px;
        }
        * { box-sizing:border-box; }
        html { -webkit-text-size-adjust:100%; }
        body { margin:0; font-family:'Plus Jakarta Sans','Segoe UI',system-ui,sans-serif; font-size:16px; line-height:1.5; color:var(--ink); background:var(--paper); }
        a { color:inherit; }
        svg { display:block; }
        :focus-visible { outline:3px solid var(--g-600); outline-offset:2px; }
        .no-anim *, .no-anim *::before, .no-anim *::after { transition:none !important; }

        /* Sidebar */
        .sidebar { position:fixed; top:0; left:0; bottom:0; width:var(--nav-w); max-width:86vw; z-index:60; display:flex; flex-direction:column; padding:20px 14px; color:#fff;
            background:linear-gradient(170deg,var(--g-700) 0%,var(--g-800) 60%,var(--g-900) 100%); transform:translateX(-100%); transition:transform .25s ease; overflow-y:auto; }
        .nav-open .sidebar { transform:none; }
        .brand { display:flex; align-items:center; gap:12px; padding:4px 8px 20px; }
        .brand-mark { width:42px; height:42px; border-radius:14px; background:rgba(255,255,255,.16); display:grid; place-items:center; flex-shrink:0; }
        .brand-mark svg { width:22px; height:22px; }
        .brand strong { display:block; font-weight:800; line-height:1.2; }
        .brand small { opacity:.75; font-size:.85rem; }
        .sidebar nav { display:flex; flex-direction:column; gap:4px; flex:1; }
        .sidebar nav a { display:flex; align-items:center; gap:12px; min-height:50px; padding:0 14px; border-radius:14px; text-decoration:none; color:rgba(255,255,255,.95); font-weight:500; transition:background .15s; }
        .sidebar nav a svg { width:20px; height:20px; flex:none; }
        .sidebar nav a:hover { background:rgba(255,255,255,.1); color:#fff; }
        .sidebar nav a.active { background:rgba(255,255,255,.2); color:#fff; font-weight:700; }
        .sidebar nav a.logout { color:#ffd9d6; margin-top:auto; border-top:1px solid rgba(255,255,255,.14); border-radius:0 0 14px 14px; }
        .sidebar nav a:focus-visible { outline-color:#fff; }
        .sidebar nav a.logout:hover { background:rgba(230,90,80,.22); color:#ffd9d6; }
        .scrim { position:fixed; top:0; right:0; bottom:0; left:0; background:rgba(15,61,20,.45); opacity:0; visibility:hidden; transition:opacity .25s; z-index:50; }

        /* Layout */
        .main { transition:margin-left .25s ease; min-height:100vh; }
        @media (min-width:1101px) { .nav-open .main { margin-left:var(--nav-w); } }
        @media (max-width:1100px) { .nav-open .scrim { opacity:1; visibility:visible; } .nav-open body { overflow:hidden; } }
        .topbar { position:sticky; top:0; z-index:40; height:68px; display:flex; align-items:center; gap:14px; padding:0 20px; background:rgba(247,250,246,.92); -webkit-backdrop-filter:blur(8px); backdrop-filter:blur(8px); border-bottom:1px solid var(--line); }
        .burger { width:46px; height:46px; border-radius:14px; border:1px solid var(--line); background:#fff; color:var(--g-800); display:grid; place-items:center; cursor:pointer; }
        .burger:hover { background:var(--g-50); }
        .burger svg { width:22px; height:22px; }
        .top-title { font-weight:700; color:var(--ink-2); }
        .avatar { margin-left:auto; width:42px; height:42px; border-radius:50%; display:grid; place-items:center; color:#fff; font-weight:800; background:linear-gradient(140deg,var(--g-500),var(--g-700)); }
        .content { padding:24px 20px 64px; }
        .wrap { max-width:880px; margin:0 auto; }

        .back { display:inline-flex; align-items:center; gap:8px; min-height:44px; margin-bottom:8px; color:var(--g-700); font-weight:600; text-decoration:none; }
        .back:hover { color:var(--g-800); }
        .back svg { width:18px; height:18px; }
        .page-head { margin-bottom:22px; }
        h1 { margin:0; font-size:1.75rem; line-height:1.25; font-weight:800; letter-spacing:-.01em; }
        .lead { margin:4px 0 0; color:var(--ink-3); }

        .btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; min-height:52px; padding:0 22px; border-radius:16px; border:1.5px solid transparent; font:inherit; font-weight:700; text-decoration:none; cursor:pointer; }
        .btn svg { width:20px; height:20px; }
        .btn-primary { color:#fff; background:linear-gradient(135deg,var(--g-600),var(--g-700)); box-shadow:0 8px 20px rgba(27,94,32,.22); }
        .btn-primary:hover { background:linear-gradient(135deg,var(--g-700),var(--g-800)); }
        .btn-ghost { color:var(--g-800); background:#fff; border-color:var(--g-200); }
        .btn-ghost:hover { background:var(--g-100); }
        .btn-danger { color:#fff; background:var(--rose); }
        .btn-danger:hover { background:#8f1d17; }
        .btn-danger-ghost { color:var(--rose); background:#fff; border-color:#f1b7b2; }
        .btn-danger-ghost:hover { background:var(--rose-bg); }
        .btn-block { width:100%; }

        /* Notice */
        .notice { display:flex; align-items:flex-start; gap:12px; padding:14px 16px; border-radius:16px; margin-bottom:18px; font-weight:500; }
        .notice svg { flex-shrink:0; margin-top:2px; width:20px; height:20px; }
        .notice-success { background:var(--g-50); color:#14532d; border:1px solid var(--g-200); }
        .notice-error { background:var(--rose-bg); color:var(--rose); border:1px solid #f8c9c5; }
        .notice-warning { background:var(--amber-bg); color:var(--amber); border:1px solid #f6d9a0; }
        .notice-info { background:var(--sky-bg); color:var(--sky); border:1px solid #c4d8fa; }

        /* Kartu ringkasan */
        .wallet { position:relative; overflow:hidden; border-radius:24px; padding:24px; margin-bottom:22px; color:#fff; background:linear-gradient(135deg,#43a047 0%,#2e7d32 45%,#14501a 100%); box-shadow:0 14px 32px rgba(27,94,32,.22); }
        .wallet::before, .wallet::after { content:""; position:absolute; border-radius:50%; background:rgba(255,255,255,.07); pointer-events:none; }
        .wallet::before { width:220px; height:220px; right:-70px; top:-90px; }
        .wallet::after { width:140px; height:140px; right:60px; bottom:-85px; }
        .wallet > * { position:relative; z-index:1; }
        .wallet-top { display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; margin-bottom:14px; }
        .wallet-label { margin:0; font-size:1rem; font-weight:600; opacity:.95; }
        .wallet-value { margin:2px 0 0; font-size:2.25rem; font-weight:800; line-height:1.15; letter-spacing:-.03em; overflow-wrap:anywhere; }
        .chips { display:flex; flex-wrap:wrap; gap:10px; margin-top:16px; }
        .chip { display:inline-flex; align-items:center; gap:8px; min-height:34px; padding:4px 14px; border-radius:999px; background:rgba(255,255,255,.14); border:1px solid rgba(255,255,255,.18); font-size:.9rem; font-weight:600; color:#fff; text-decoration:none; }
        .chip svg { width:16px; height:16px; }
        a.chip:hover { background:rgba(255,255,255,.24); }
        a.chip:focus-visible { outline-color:#fff; }

        /* Badge status */
        .badge { display:inline-flex; align-items:center; padding:3px 11px; border-radius:999px; font-size:.78rem; font-weight:700; white-space:nowrap; }
        .b-amber { background:var(--amber-bg); color:var(--amber); }
        .b-green { background:var(--g-100); color:var(--g-800); }
        .b-rose { background:var(--rose-bg); color:var(--rose); }
        .b-sky { background:var(--sky-bg); color:var(--sky); }
        .b-grey { background:#eef1ee; color:var(--ink-3); }

        .ico { width:46px; height:46px; border-radius:50%; flex:none; background:var(--g-100); color:var(--g-700); display:grid; place-items:center; }
        .ico svg { width:22px; height:22px; }
        .ico.warn { background:var(--amber-bg); color:var(--amber); }
        .ico.sky { background:var(--sky-bg); color:var(--sky); }

        .wallet h1 { margin:12px 0 0; color:#fff; font-size:1.75rem; line-height:1.25; letter-spacing:-.02em; overflow-wrap:anywhere; }
        .wallet .hero-lead { margin:6px 0 0; max-width:52ch; color:rgba(255,255,255,.92); }

        .sec-head { display:flex; align-items:center; justify-content:space-between; gap:12px; margin:0 0 14px; }
        .sec-title { margin:0; font-size:1.125rem; font-weight:700; letter-spacing:-.01em; }
        .sec-link { display:inline-flex; align-items:center; min-height:44px; padding:0 4px; color:var(--g-700); font-weight:700; text-decoration:none; }
        .sec-link:hover { color:var(--g-800); text-decoration:underline; text-underline-offset:4px; }
        .section { margin-bottom:28px; }

        .stats { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; }
        .stat { display:flex; flex-direction:column; gap:12px; padding:18px; border-radius:20px; background:#fff; border:1px solid var(--line); text-decoration:none; color:inherit; transition:border-color .15s, box-shadow .15s; }
        .stat:hover { border-color:var(--g-200); box-shadow:0 10px 22px rgba(27,94,32,.08); }
        .stat-num { display:block; font-size:1.75rem; font-weight:800; line-height:1.1; letter-spacing:-.02em; color:var(--ink); font-variant-numeric:tabular-nums; }
        .stat-label { display:block; margin-top:4px; font-size:.9375rem; font-weight:600; color:var(--ink-3); }
        .stat-sub { display:inline-flex; margin-top:8px; }

        .tiles { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; }
        .tile { display:flex; align-items:center; gap:14px; min-height:76px; padding:14px 16px; border-radius:20px; background:var(--g-100); border:1px solid var(--g-200); text-decoration:none; color:inherit; transition:border-color .15s, background .15s, box-shadow .15s; }
        .tile:hover { border-color:var(--g-500); background:var(--g-200); box-shadow:0 10px 22px rgba(27,94,32,.1); }
        .tile .ico { background:#fff; color:var(--g-700); transition:background .15s, color .15s; }
        .tile:hover .ico { background:var(--g-700); color:#fff; }
        .tile strong { display:block; font-weight:700; color:var(--ink); }
        .tile small { display:block; font-size:.875rem; color:var(--ink-3); }

        .list { list-style:none; margin:0; padding:0; background:#fff; border:1px solid var(--line); border-radius:20px; overflow:hidden; }
        .list li { display:grid; grid-template-columns:46px minmax(0,1fr) auto; grid-template-areas:"ico main bdg"; gap:4px 16px; align-items:center; padding:14px 18px; border-bottom:1px solid var(--line); }
        .list li:last-child { border-bottom:0; }
        .list .ico { grid-area:ico; font-weight:800; font-size:1.0625rem; }
        .row-main { grid-area:main; min-width:0; }
        .row-title { margin:0; font-weight:700; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .row-sub { margin:2px 0 0; display:flex; align-items:center; gap:6px; font-size:.9rem; color:var(--ink-3); }
        .row-sub svg { width:15px; height:15px; }
        .bdgs { grid-area:bdg; display:flex; flex-wrap:wrap; justify-content:flex-end; gap:6px; }

        .empty { text-align:center; padding:40px 24px; background:#fff; border:1px solid var(--line); border-radius:20px; }
        .empty .ico { margin:0 auto 14px; width:64px; height:64px; }
        .empty .ico svg { width:28px; height:28px; }
        .empty h3 { margin:0 0 4px; font-size:1.0625rem; }
        .empty p { margin:0 auto 18px; max-width:40ch; color:var(--ink-3); }

        @media (max-width:900px) {
            .stats { grid-template-columns:repeat(2,minmax(0,1fr)); }
            .tiles { grid-template-columns:minmax(0,1fr); }
        }
        @media (max-width:720px) {
            .content { padding:18px 16px 56px; }
            .topbar { padding:0 16px; }
            .wallet h1 { font-size:1.5rem; }
            .list li { grid-template-columns:46px minmax(0,1fr); grid-template-areas:"ico main" "ico bdg"; }
            .bdgs { justify-content:flex-start; margin-top:4px; }
        }
        @media (max-width:380px) { .stats { grid-template-columns:minmax(0,1fr); } }
        @media (prefers-reduced-motion:reduce) { *, *::before, *::after { transition:none !important; animation:none !important; } }
    </style>
</head>
<body>
    <aside class="sidebar" id="sidebar" aria-label="Menu utama">
        <div class="brand">
            <span class="brand-mark"><?= icon('leaf') ?></span>
            <div><strong><?= APP_NAME ?></strong><small>Administrator</small></div>
        </div>
        <nav>
            <?php foreach ($menu as $m): $isActive = ($m[0] === $menuAktif); ?>
                <a href="<?= base_url($m[0]) ?>"<?= $isActive ? ' class="active" aria-current="page"' : '' ?>><?= icon($m[2]) ?><span><?= $e($m[1]) ?></span></a>
            <?php endforeach; ?>
            <a href="<?= base_url('logout') ?>" class="logout"><?= icon('logout') ?><span>Keluar</span></a>
        </nav>
    </aside>
    <div class="scrim" id="scrim"></div>

    <div class="main">
        <header class="topbar">
            <button type="button" class="burger" id="burger" aria-controls="sidebar" aria-expanded="false" aria-label="Buka atau tutup menu"><?= icon('menu') ?></button>
            <span class="top-title">Dashboard</span>
            <span class="avatar" title="<?= $e($userName) ?>" aria-hidden="true"><?= $e($userInitial) ?></span>
        </header>

        <main class="content">
            <div class="wrap">
                <?php if (!empty($flash) && !empty($flash['message'])): ?>
                    <div class="notice notice-<?= $flashType ?>" role="status"><?= icon($flashIcon) ?><span><?= $e($flash['message']) ?></span></div>
                <?php endif; ?>

                <section class="wallet" aria-label="Selamat datang">
                    <span class="chip"><?= icon('calendar') ?><?= $e($hariIni) ?></span>
                    <h1>Selamat datang, <?= $e($userName) ?></h1>
                    <p class="hero-lead">Kelola sistem Bank Sampah Digital Desa dari panel ini.</p>
                </section>

                <section class="section" aria-labelledby="ringkasan-title">
                    <div class="sec-head"><h2 class="sec-title" id="ringkasan-title">Ringkasan sistem</h2></div>
                    <div class="stats">
                        <?php foreach ($statCards as $c): ?>
                            <a class="stat" href="<?= base_url($c[0]) ?>">
                                <span class="ico"><?= icon($c[1]) ?></span>
                                <span>
                                    <span class="stat-num"><?= $e($c[2]) ?></span>
                                    <span class="stat-label"><?= $e($c[3]) ?></span>
                                    <?php if ($c[4] !== ''): ?><span class="badge b-green stat-sub"><?= $e($c[4]) ?></span><?php endif; ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="section" aria-labelledby="aksi-title">
                    <div class="sec-head"><h2 class="sec-title" id="aksi-title">Tambah daftar</h2></div>
                    <div class="tiles">
                        <?php foreach ($aksiCepat as $a): ?>
                            <a class="tile" href="<?= base_url($a[0]) ?>">
                                <span class="ico"><?= icon($a[1]) ?></span>
                                <span><strong><?= $e($a[2]) ?></strong><small><?= $e($a[3]) ?></small></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="section" aria-labelledby="terbaru-title">
                    <div class="sec-head">
                        <h2 class="sec-title" id="terbaru-title">Pengguna terbaru</h2>
                        <a class="sec-link" href="<?= base_url('admin/users') ?>">Lihat semua</a>
                    </div>

                    <?php if (empty($recentUsers)): ?>
                        <div class="empty">
                            <div class="ico"><?= icon('users') ?></div>
                            <h3>Belum ada pengguna</h3>
                            <p>Pengguna yang baru dibuat akan muncul di sini.</p>
                            <a class="btn btn-primary" href="<?= base_url('admin/users/add') ?>"><?= icon('user-plus') ?>Tambah pengguna</a>
                        </div>
                    <?php else: ?>
                        <ul class="list">
                            <?php foreach ($recentUsers as $u):
                                $nama = (string) (isset($u['nama']) ? $u['nama'] : '');
                                $hp = trim((string) (isset($u['no_hp']) ? $u['no_hp'] : ''));
                                $role = (string) (isset($u['role']) ? $u['role'] : '');
                                $st = (string) (isset($u['status']) ? $u['status'] : '');
                                $rm = isset($roleMeta[$role]) ? $roleMeta[$role] : [$label($role), 'b-grey'];
                                $ini = $nama === '' ? '?' : (function_exists('mb_substr') ? mb_strtoupper(mb_substr($nama, 0, 1, 'UTF-8'), 'UTF-8') : strtoupper(substr($nama, 0, 1)));
                            ?>
                                <li>
                                    <span class="ico" aria-hidden="true"><?= $e($ini) ?></span>
                                    <div class="row-main">
                                        <p class="row-title"><?= $e($nama !== '' ? $nama : '-') ?></p>
                                        <?php if ($hp !== ''): ?><span class="row-sub"><?= icon('phone') ?><?= $e($hp) ?></span><?php endif; ?>
                                    </div>
                                    <div class="bdgs">
                                        <span class="badge <?= $e($rm[1]) ?>"><?= $e($rm[0]) ?></span>
                                        <span class="badge <?= $st === 'AKTIF' ? 'b-green' : 'b-grey' ?>"><?= $e($label($st)) ?></span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>

    <script>
    (function () {
        var root = document.documentElement;
        var burger = document.getElementById('burger');
        var scrim = document.getElementById('scrim');
        var mq = window.matchMedia('(min-width:1101px)');

        function setNav(open, save) {
            root.classList.toggle('nav-open', open);
            burger.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (save && mq.matches) {
                try { localStorage.setItem('nav', open ? 'open' : 'closed'); } catch (e) {}
            }
        }
        function initial() {
            var s = null;
            try { s = localStorage.getItem('nav'); } catch (e) {}
            setNav(mq.matches && s !== 'closed', false);
        }
        initial();
        window.requestAnimationFrame(function () { root.classList.remove('no-anim'); });

        burger.addEventListener('click', function () { setNav(!root.classList.contains('nav-open'), true); });
        scrim.addEventListener('click', function () { setNav(false, false); });
        document.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape' && !mq.matches && root.classList.contains('nav-open')) {
                setNav(false, false);
                burger.focus();
            }
        });
        if (mq.addEventListener) { mq.addEventListener('change', initial); } else if (mq.addListener) { mq.addListener(initial); }
    })();
    </script>
</body>
</html>