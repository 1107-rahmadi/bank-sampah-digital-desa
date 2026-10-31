<?php
/**
 * Audit Log - Admin View
 * Compatible dengan PHP 7.3+
 *
 * Variabel dari controller: $logs (opsional $flash)
 * $logs: tiap item berisi created_at, user_nama, aksi, tabel, record_id, sebelum, sesudah
 * Filter dibaca dari $_GET: tabel, aksi, tanggal_from, tanggal_to
 */

if (!function_exists('icon')) {
    /**
     * Ikon SVG garis yang konsisten (tanpa emoji).
     */
    function icon(string $name, string $class = ''): string
    {
        $paths = [
            'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
            'leaf' => '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>',
            'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
            'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
            'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/>',
            'building' => '<path d="M4 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16"/><path d="M16 9h2a2 2 0 0 1 2 2v10M2 21h20M8 7h4M8 11h4M8 15h4"/>',
            'pin' => '<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
            'file' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Z"/><path d="M14 2v6h6M9 14h6M9 18h4"/>',
            'settings' => '<path d="M4 6h8M16 6h4M4 12h4M12 12h8M4 18h10M18 18h2"/><circle cx="14" cy="6" r="2"/><circle cx="10" cy="12" r="2"/><circle cx="16" cy="18" r="2"/>',
            'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/>',
            'arrow-left' => '<path d="M19 12H5M12 19l-7-7 7-7"/>',
            'plus' => '<path d="M12 5v14M5 12h14"/>',
            'edit' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
            'trash' => '<path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2m2 0v14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V6"/><path d="M10 11v6M14 11v6"/>',
            'search' => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/>',
            'check' => '<path d="M5 12.5l4.5 4.5L19 7"/>',
            'alert' => '<path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/>',
            'info' => '<circle cx="12" cy="12" r="9"/><line x1="12" y1="11" x2="12" y2="16"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
            'inbox' => '<path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.5 5.1L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.5-6.9A2 2 0 0 0 16.8 4H7.2a2 2 0 0 0-1.7 1.1Z"/>',
            'chevron-right' => '<path d="M9 6l6 6-6 6"/>',
        ];

        $body = isset($paths[$name]) ? $paths[$name] : $paths['info'];
        $cls = $class !== '' ? ' class="' . htmlspecialchars($class) . '"' : '';

        return '<svg' . $cls . ' viewBox="0 0 24 24" fill="none" stroke="currentColor"'
            . ' stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"'
            . ' aria-hidden="true" focusable="false">' . $body . '</svg>';
    }
}

if (!function_exists('auditFormatJson')) {
    /**
     * Rapikan JSON agar mudah dibaca. Jika bukan JSON valid, tampilkan apa adanya.
     */
    function auditFormatJson($raw): string
    {
        $raw = (string) $raw;
        $data = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            $out = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($out !== false) {
                return $out;
            }
        }
        return $raw;
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

if (!isset($flash)) {
    $flash = function_exists('getFlashMessage') ? getFlashMessage() : null;
}
$flashType = 'info';
if (!empty($flash['type']) && in_array($flash['type'], ['success', 'error', 'warning', 'info'], true)) {
    $flashType = $flash['type'];
}
$flashIcon = ['success' => 'check', 'error' => 'alert', 'warning' => 'alert', 'info' => 'info'][$flashType];

$logs = (isset($logs) && is_array($logs)) ? $logs : [];
$appName = defined('APP_NAME') ? APP_NAME : 'Bank Sampah';

$menu = [
    ['admin/dashboard',   'Dashboard',          'grid'],
    ['admin/users',       'Kelola Pengguna',    'users'],
    ['admin/nasabah',     'Kelola Nasabah',     'user'],
    ['admin/bank-sampah', 'Kelola Bank Sampah', 'building'],
    ['admin/desa',        'Kelola Desa',        'pin'],
    ['admin/audit-log',   'Audit Log',          'file'],
    ['admin/pengaturan',  'Pengaturan',         'settings'],
];
$menuAktif = 'admin/audit-log';

/* Filter dari URL (nilai di-escape saat ditampilkan) */
$get = function ($key) {
    return (isset($_GET[$key]) && is_scalar($_GET[$key])) ? (string) $_GET[$key] : '';
};
$fTabel = $get('tabel');
$fAksi = $get('aksi');
$fFrom = $get('tanggal_from');
$fTo = $get('tanggal_to');
$adaFilter = ($fTabel !== '' || $fAksi !== '' || $fFrom !== '' || $fTo !== '');

$tabelOpsi = [
    'users' => 'Users',
    'nasabah' => 'Nasabah',
    'transaksi_beli' => 'Transaksi Beli',
    'transaksi_jual' => 'Transaksi Jual',
];
$aksiOpsi = [
    'INSERT' => 'Tambah (INSERT)',
    'UPDATE' => 'Ubah (UPDATE)',
    'DELETE' => 'Hapus (DELETE)',
];
/* label, kelas badge, ikon, kelas ikon bulat */
$aksiMeta = [
    'INSERT' => ['Tambah', 'b-green', 'plus', ''],
    'UPDATE' => ['Ubah', 'b-amber', 'edit', 'warn'],
    'DELETE' => ['Hapus', 'b-rose', 'trash', 'rose'],
];

$bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

/* Kelompokkan per tanggal (data diasumsikan terurut terbaru lebih dulu) */
$grup = [];
foreach ($logs as $log) {
    $ts = isset($log['created_at']) ? strtotime((string) $log['created_at']) : false;
    if ($ts) {
        $kunci = date('Y-m-d', $ts);
        $labelTgl = (int) date('j', $ts) . ' ' . $bulan[(int) date('n', $ts) - 1] . ' ' . date('Y', $ts);
        $log['_jam'] = date('H:i', $ts);
    } else {
        $kunci = 'tanpa-tanggal';
        $labelTgl = 'Tanggal tidak diketahui';
        $log['_jam'] = '-';
    }
    if (!isset($grup[$kunci])) {
        $grup[$kunci] = ['label' => $labelTgl, 'items' => []];
    }
    $grup[$kunci]['items'][] = $log;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1b5e20">
    <title>Audit Log - <?= $e($appName) ?></title>
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
        button, input, select { font-family:inherit; }
        [hidden] { display:none !important; }
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

        .notice { display:flex; align-items:flex-start; gap:12px; padding:14px 16px; border-radius:16px; margin-bottom:18px; font-weight:500; }
        .notice svg { flex-shrink:0; margin-top:2px; width:20px; height:20px; }
        .notice-success { background:var(--g-50); color:#14532d; border:1px solid var(--g-200); }
        .notice-error { background:var(--rose-bg); color:var(--rose); border:1px solid #f8c9c5; }
        .notice-warning { background:var(--amber-bg); color:var(--amber); border:1px solid #f6d9a0; }
        .notice-info { background:var(--sky-bg); color:var(--sky); border:1px solid #c4d8fa; }

        .badge { display:inline-flex; align-items:center; padding:3px 11px; border-radius:999px; font-size:.78rem; font-weight:700; white-space:nowrap; }
        .b-amber { background:var(--amber-bg); color:var(--amber); }
        .b-green { background:var(--g-100); color:var(--g-800); }
        .b-rose { background:var(--rose-bg); color:var(--rose); }
        .b-sky { background:var(--sky-bg); color:var(--sky); }

        .ico { width:46px; height:46px; border-radius:50%; flex:none; background:var(--g-100); color:var(--g-700); display:grid; place-items:center; }
        .ico svg { width:22px; height:22px; }
        .ico.warn { background:var(--amber-bg); color:var(--amber); }
        .ico.rose { background:var(--rose-bg); color:var(--rose); }
        .ico.sky { background:var(--sky-bg); color:var(--sky); }

        /* Filter */
        .panel { background:#fff; border:1px solid var(--line); border-radius:20px; padding:18px; margin-bottom:22px; }
        .filter { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; align-items:end; }
        .label { display:block; margin-bottom:6px; font-size:.9rem; font-weight:600; color:var(--ink); }
        .input { width:100%; height:52px; padding:0 14px; font-size:1rem; color:var(--ink); background:#fff; border:1.5px solid var(--line); border-radius:14px; }
        .input:hover { border-color:var(--g-200); }
        .input:focus { outline:none; border-color:var(--g-600); box-shadow:0 0 0 4px rgba(67,160,71,.16); }
        select.input { -webkit-appearance:none; appearance:none; padding-right:42px; cursor:pointer;
            background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%235b6a5e' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
            background-repeat:no-repeat; background-position:right 14px center; background-size:18px; }
        .filter-actions { grid-column:1 / -1; display:flex; gap:12px; flex-wrap:wrap; }

        /* Daftar log */
        .sec-head { display:flex; align-items:baseline; justify-content:space-between; gap:12px; margin:0 0 10px; }
        .sec-title { margin:0; font-size:1.125rem; font-weight:700; letter-spacing:-.01em; }
        .sec-count { font-size:.9rem; color:var(--ink-3); }
        .group-label { margin:22px 0 8px; font-size:.875rem; font-weight:600; color:var(--ink-3); }
        .list { list-style:none; margin:0; padding:0; background:#fff; border:1px solid var(--line); border-radius:20px; overflow:hidden; }
        .log { display:grid; grid-template-columns:46px minmax(0,1fr) auto; gap:4px 14px; align-items:center; padding:14px 18px; border-bottom:1px solid var(--line); }
        .log:last-child { border-bottom:0; }
        .row-title { margin:0; font-weight:700; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .row-sub { margin:2px 0 0; font-size:.9rem; color:var(--ink-3); overflow-wrap:anywhere; }
        .log-detail { grid-column:2 / -1; display:flex; flex-direction:column; gap:4px; }
        .dt summary { display:inline-flex; align-items:center; gap:6px; min-height:44px; cursor:pointer; font-weight:700; color:var(--g-700); list-style:none; }
        .dt summary::-webkit-details-marker { display:none; }
        .dt summary svg { width:16px; height:16px; transition:transform .15s; }
        .dt[open] summary svg { transform:rotate(90deg); }
        .dt.sebelum summary { color:var(--sky); }
        .json-view { margin:0 0 8px; padding:12px 14px; max-height:280px; overflow:auto; background:var(--g-50); border:1px solid var(--line); border-radius:12px; font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace; font-size:.8125rem; line-height:1.55; white-space:pre; color:var(--ink-2); }

        .empty { text-align:center; padding:40px 24px; background:#fff; border:1px solid var(--line); border-radius:20px; }
        .empty .ico { margin:0 auto 14px; width:64px; height:64px; }
        .empty .ico svg { width:28px; height:28px; }
        .empty h3 { margin:0 0 4px; font-size:1.0625rem; }
        .empty p { margin:0 auto 18px; max-width:40ch; color:var(--ink-3); }

        @media (max-width:900px) { .filter { grid-template-columns:repeat(2,minmax(0,1fr)); } }
        @media (max-width:720px) {
            .content { padding:18px 16px 56px; }
            .topbar { padding:0 16px; }
            h1 { font-size:1.5rem; }
            .panel { padding:16px; }
            .filter { grid-template-columns:minmax(0,1fr); }
            .filter-actions .btn { width:100%; }
            .log { grid-template-columns:46px minmax(0,1fr); grid-template-areas:"ico main" "ico bdg" "det det"; padding:14px; }
            .log > .ico { grid-area:ico; align-self:start; }
            .log > .log-main { grid-area:main; }
            .log > .badge { grid-area:bdg; justify-self:start; }
            .log-detail { grid-area:det; grid-column:auto; }
        }
        @media (prefers-reduced-motion:reduce) { *, *::before, *::after { transition:none !important; animation:none !important; } }
    </style>
</head>
<body>
    <aside class="sidebar" id="sidebar" aria-label="Menu utama">
        <div class="brand">
            <span class="brand-mark"><?= icon('leaf') ?></span>
            <div><strong><?= $e($appName) ?></strong><small>Administrator</small></div>
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
            <span class="top-title">Audit Log</span>
            <span class="avatar" title="<?= $e($userName) ?>" aria-hidden="true"><?= $e($userInitial) ?></span>
        </header>

        <main class="content">
            <div class="wrap">
                <a class="back" href="<?= base_url('admin/dashboard') ?>"><?= icon('arrow-left') ?>Dashboard</a>

                <?php if (!empty($flash) && !empty($flash['message'])): ?>
                    <div class="notice notice-<?= $flashType ?>" role="status"><?= icon($flashIcon) ?><span><?= $e($flash['message']) ?></span></div>
                <?php endif; ?>

                <div class="page-head">
                    <h1>Audit Log</h1>
                    <p class="lead">Riwayat perubahan data di sistem</p>
                </div>

                <form method="GET" class="panel" aria-label="Filter audit log">
                    <div class="filter">
                        <div>
                            <label for="tabel" class="label">Tabel</label>
                            <select name="tabel" id="tabel" class="input">
                                <option value="">Semua Tabel</option>
                                <?php foreach ($tabelOpsi as $val => $lbl): ?>
                                    <option value="<?= $e($val) ?>"<?= $fTabel === $val ? ' selected' : '' ?>><?= $e($lbl) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="aksi" class="label">Aksi</label>
                            <select name="aksi" id="aksi" class="input">
                                <option value="">Semua Aksi</option>
                                <?php foreach ($aksiOpsi as $val => $lbl): ?>
                                    <option value="<?= $e($val) ?>"<?= $fAksi === $val ? ' selected' : '' ?>><?= $e($lbl) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="tanggal_from" class="label">Dari tanggal</label>
                            <input type="date" name="tanggal_from" id="tanggal_from" class="input" value="<?= $e($fFrom) ?>">
                        </div>
                        <div>
                            <label for="tanggal_to" class="label">Sampai tanggal</label>
                            <input type="date" name="tanggal_to" id="tanggal_to" class="input" value="<?= $e($fTo) ?>">
                        </div>
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary"><?= icon('search') ?>Tampilkan</button>
                            <?php if ($adaFilter): ?>
                                <a class="btn btn-ghost" href="<?= base_url('admin/audit-log') ?>">Hapus filter</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>

                <section aria-labelledby="log-title">
                    <div class="sec-head">
                        <h2 class="sec-title" id="log-title">Catatan perubahan</h2>
                        <?php if (!empty($logs)): ?><span class="sec-count"><?= number_format(count($logs), 0, ',', '.') ?> catatan</span><?php endif; ?>
                    </div>

                    <?php if (empty($logs)): ?>
                        <div class="empty">
                            <div class="ico"><?= icon('inbox') ?></div>
                            <h3>Tidak ada data log</h3>
                            <p><?= $adaFilter ? 'Tidak ada catatan yang cocok dengan filter ini.' : 'Perubahan data akan tercatat di sini.' ?></p>
                            <?php if ($adaFilter): ?><a class="btn btn-ghost" href="<?= base_url('admin/audit-log') ?>">Hapus filter</a><?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php foreach ($grup as $g): ?>
                            <h3 class="group-label"><?= $e($g['label']) ?></h3>
                            <ul class="list">
                                <?php foreach ($g['items'] as $log):
                                    $aksi = strtoupper((string) (isset($log['aksi']) ? $log['aksi'] : ''));
                                    $am = isset($aksiMeta[$aksi]) ? $aksiMeta[$aksi] : [($aksi !== '' ? $aksi : '-'), 'b-sky', 'info', 'sky'];
                                    $pelaku = (isset($log['user_nama']) && $log['user_nama'] !== '' && $log['user_nama'] !== null) ? $log['user_nama'] : 'System';
                                    $recId = (isset($log['record_id']) && $log['record_id'] !== '' && $log['record_id'] !== null) ? $log['record_id'] : '-';
                                    $sebelum = isset($log['sebelum']) ? $log['sebelum'] : '';
                                    $sesudah = isset($log['sesudah']) ? $log['sesudah'] : '';
                                ?>
                                    <li class="log">
                                        <span class="ico <?= $e($am[3]) ?>" aria-hidden="true"><?= icon($am[2]) ?></span>
                                        <div class="log-main">
                                            <p class="row-title"><?= $e($pelaku) ?></p>
                                            <p class="row-sub">Tabel <?= $e(isset($log['tabel']) ? $log['tabel'] : '-') ?> &middot; ID <?= $e($recId) ?> &middot; <?= $e($log['_jam']) ?></p>
                                        </div>
                                        <span class="badge <?= $e($am[1]) ?>"><?= $e($am[0]) ?></span>

                                        <?php if ($sebelum || $sesudah): ?>
                                            <div class="log-detail">
                                                <?php if ($sebelum): ?>
                                                    <details class="dt sebelum">
                                                        <summary><?= icon('chevron-right') ?>Data sebelum</summary>
                                                        <pre class="json-view"><?= $e(auditFormatJson($sebelum)) ?></pre>
                                                    </details>
                                                <?php endif; ?>
                                                <?php if ($sesudah): ?>
                                                    <details class="dt">
                                                        <summary><?= icon('chevron-right') ?>Data sesudah</summary>
                                                        <pre class="json-view"><?= $e(auditFormatJson($sesudah)) ?></pre>
                                                    </details>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endforeach; ?>
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