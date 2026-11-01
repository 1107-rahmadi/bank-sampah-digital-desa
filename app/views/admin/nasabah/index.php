<?php
/**
 * admin/nasabah/index.php
 * Kelola Nasabah (Admin) - Daftar
 * Compatible dengan PHP 7.3+
 *
 * Admin dapat menambah, mengubah, menonaktifkan, dan mengaktifkan kembali nasabah.
 * Nasabah tidak pernah dihapus (FR-29).
 *
 * Variabel dari controller:
 *   $nasabahList    : baris nasabah (id, user_id, nama, no_anggota, no_hp, tipe, status, saldo, bank_sampah_nama)
 *   $totalNasabah, $totalPages, $page, $offset
 *   $pengajuanAktif : (opsional) peta id nasabah => ['jumlah' => float, 'jumlah_pengajuan' => int]
 *                     dari Nasabah::getPengajuanAktifPeta(), untuk peringatan saat menonaktifkan
 *   $csrfToken      : (opsional) bila tidak ada dipakai csrfToken()
 *   $flash          : (opsional)
 *
 * Tautan: admin/nasabah/add, admin/nasabah/edit?id=
 * Aksi POST: admin/nasabah/status dengan field csrf_token, id, aksi (nonaktifkan | aktifkan)
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
            'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/>',
            'arrow-left' => '<path d="M19 12H5M12 19l-7-7 7-7"/>',
            'chevron-right' => '<path d="M9 6l6 6-6 6"/>',
            'chevron-left' => '<path d="M15 6l-6 6 6 6"/>',
            'check' => '<path d="M5 12.5l4.5 4.5L19 7"/>',
            'alert' => '<path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/>',
            'building' => '<path d="M4 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16"/><path d="M16 9h2a2 2 0 0 1 2 2v10M2 21h20M8 7h4M8 11h4M8 15h4"/>',
            'pin' => '<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
            'file' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Z"/><path d="M14 2v6h6M9 14h6M9 18h4"/>',
            'settings' => '<path d="M4 6h8M16 6h4M4 12h4M12 12h8M4 18h10M18 18h2"/><circle cx="14" cy="6" r="2"/><circle cx="10" cy="12" r="2"/><circle cx="16" cy="18" r="2"/>',
            'plus' => '<path d="M12 5v14M5 12h14"/>',
            'user-plus' => '<circle cx="9" cy="8" r="4"/><path d="M2 21v-1a6 6 0 0 1 6-6h2a6 6 0 0 1 6 6v1M19 8v6M16 11h6"/>',
            'phone' => '<rect x="6" y="2" width="12" height="20" rx="2.5"/><path d="M11 18h2"/>',
            'hash' => '<path d="M4 9h16M4 15h16M10 3L8 21M16 3l-2 18"/>',
            'info' => '<circle cx="12" cy="12" r="9"/><line x1="12" y1="11" x2="12" y2="16"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
            'edit' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
            'trash' => '<path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6M10 11v6M14 11v6"/>',
            'key' => '<circle cx="8" cy="15" r="4"/><path d="M10.8 12.2L21 2M17 6l3 3M14 9l2 2"/>',
            'lock' => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
            'power' => '<path d="M12 2v10"/><path d="M18.4 6.6a9 9 0 1 1-12.8 0"/>',
            'refresh' => '<path d="M21 12a9 9 0 1 1-2.6-6.4"/><path d="M21 3v6h-6"/>',
            'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
            'eye-off' => '<path d="M17.9 17.9A10.1 10.1 0 0 1 12 19c-6.5 0-10-7-10-7a18 18 0 0 1 4.1-5.1M9.9 5.2A9.7 9.7 0 0 1 12 5c6.5 0 10 7 10 7a18 18 0 0 1-2.2 3.2"/><path d="M14.1 14.1a3 3 0 1 1-4.2-4.2"/><line x1="2" y1="2" x2="22" y2="22"/>',
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
$inisial = function ($s) {
    $s = trim((string) $s);
    if ($s === '') {
        return '?';
    }
    return function_exists('mb_substr')
        ? mb_strtoupper(mb_substr($s, 0, 1, 'UTF-8'), 'UTF-8')
        : strtoupper(substr($s, 0, 1));
};
$label = function ($v) {
    $v = (string) $v;
    return $v === '' ? '-' : ucfirst(strtolower($v));
};

$appName = defined('APP_NAME') ? APP_NAME : 'Bank Sampah';

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
$userInitial = $inisial($userName);

if (!isset($flash)) {
    $flash = function_exists('getFlashMessage') ? getFlashMessage() : null;
}
$flashType = 'info';
if (!empty($flash['type']) && in_array($flash['type'], ['success', 'error', 'warning', 'info'], true)) {
    $flashType = $flash['type'];
}
$flashIcon = ['success' => 'check', 'error' => 'alert', 'warning' => 'alert', 'info' => 'info'][$flashType];

$nasabahList = (isset($nasabahList) && is_array($nasabahList)) ? $nasabahList : [];
$jumlahHalaman = count($nasabahList);
$totalNasabah = isset($totalNasabah) ? (int) $totalNasabah : $jumlahHalaman;
$offset = isset($offset) ? (int) $offset : 0;
$page = isset($page) ? (int) $page : 1;
$totalPages = isset($totalPages) ? (int) $totalPages : 1;
if ($page < 1) {
    $page = 1;
}

// Token CSRF untuk form ubah status.
if (isset($csrfToken)) {
    $csrfToken = (string) $csrfToken;
} elseif (function_exists('csrfToken')) {
    $csrfToken = (string) csrfToken();
} else {
    $csrfToken = '';
}

// Pengajuan pencairan yang masih tertahan per nasabah, untuk peringatan.
$pengajuanAktif = (isset($pengajuanAktif) && is_array($pengajuanAktif)) ? $pengajuanAktif : [];

// Daftar nomor halaman (jendela +-2 di sekitar halaman aktif, dengan titik-titik).
$pageItems = [];
if ($totalPages > 1) {
    $last = 0;
    for ($i = 1; $i <= $totalPages; $i++) {
        if ($i === 1 || $i === $totalPages || abs($i - $page) <= 2) {
            if ($last && $i - $last > 1) {
                $pageItems[] = null;
            }
            $pageItems[] = $i;
            $last = $i;
        }
    }
}

$menu = [
    ['admin/dashboard',   'Dashboard',          'grid'],
    ['admin/users',       'Kelola Pengguna',    'users'],
    ['admin/nasabah',     'Kelola Nasabah',     'user'],
    ['admin/bank-sampah', 'Kelola Bank Sampah', 'building'],
    ['admin/desa',        'Kelola Desa',        'pin'],
    ['admin/audit-log',   'Audit Log',          'file'],
    ['admin/pengaturan',  'Pengaturan',         'settings'],
];
$menuAktif = 'admin/nasabah';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1b5e20">
    <title>Kelola Nasabah - <?= $e($appName) ?></title>
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
        .page-head { display:flex; align-items:flex-end; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:22px; }
        h1 { margin:0; font-size:1.75rem; line-height:1.25; font-weight:800; letter-spacing:-.01em; }
        .lead { margin:4px 0 0; color:var(--ink-3); }

        .btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; min-height:52px; padding:0 22px; border-radius:16px; border:1.5px solid transparent; font:inherit; font-weight:700; text-decoration:none; cursor:pointer; }
        .btn svg { width:20px; height:20px; }
        .btn-primary { color:#fff; background:linear-gradient(135deg,var(--g-600),var(--g-700)); box-shadow:0 8px 20px rgba(27,94,32,.22); }
        .btn-primary:hover { background:linear-gradient(135deg,var(--g-700),var(--g-800)); }
        .btn-ghost { color:var(--g-800); background:#fff; border-color:var(--g-200); }
        .btn-ghost:hover { background:var(--g-100); }
        .btn-danger-ghost { color:var(--rose); background:#fff; border-color:#f1b7b2; }
        .btn-danger-ghost:hover { background:var(--rose-bg); }
        .btn-sm { min-height:44px; padding:0 16px; border-radius:14px; font-size:.9375rem; }
        .btn-sm svg { width:18px; height:18px; }

        /* Notice */
        .notice { display:flex; align-items:flex-start; gap:12px; padding:14px 16px; border-radius:16px; margin-bottom:18px; font-weight:500; }
        .notice svg { flex-shrink:0; margin-top:2px; width:20px; height:20px; }
        .notice-success { background:var(--g-50); color:#14532d; border:1px solid var(--g-200); }
        .notice-error { background:var(--rose-bg); color:var(--rose); border:1px solid #f8c9c5; }
        .notice-warning { background:var(--amber-bg); color:var(--amber); border:1px solid #f6d9a0; }
        .notice-info { background:var(--sky-bg); color:var(--sky); border:1px solid #c4d8fa; }

        .badge { display:inline-flex; align-items:center; padding:3px 11px; border-radius:999px; font-size:.78rem; font-weight:700; white-space:nowrap; }
        .b-amber { background:var(--amber-bg); color:var(--amber); }
        .b-green { background:var(--g-100); color:var(--g-800); }
        .b-sky { background:var(--sky-bg); color:var(--sky); }
        .b-grey { background:#eef1ee; color:var(--ink-3); }

        .ico { width:46px; height:46px; border-radius:50%; flex:none; background:var(--g-100); color:var(--g-700); display:grid; place-items:center; font-weight:800; font-size:1.0625rem; }
        .ico svg { width:22px; height:22px; }

        .sec-head { display:flex; align-items:center; justify-content:space-between; gap:12px; margin:0 0 14px; }
        .sec-title { margin:0; font-size:1.125rem; font-weight:700; letter-spacing:-.01em; }

        /* Daftar */
        .list { list-style:none; margin:0; padding:0; background:#fff; border:1px solid var(--line); border-radius:20px; overflow:hidden; }
        .list li { display:grid; grid-template-columns:46px minmax(0,1fr) auto; grid-template-areas:"ico main end"; gap:8px 16px; align-items:center; padding:16px 18px; border-bottom:1px solid var(--line); }
        .list li:last-child { border-bottom:0; }
        .list .ico { grid-area:ico; }
        .row-main { grid-area:main; min-width:0; }
        .row-title { margin:0; font-weight:700; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .row-sub { margin:2px 0 0; display:flex; align-items:center; flex-wrap:wrap; gap:4px 14px; font-size:.9rem; color:var(--ink-3); }
        .row-sub span { display:inline-flex; align-items:center; gap:6px; }
        .row-sub svg { width:15px; height:15px; }
        .row-meta { display:flex; flex-wrap:wrap; gap:6px; margin-top:8px; }
        .row-end { grid-area:end; display:flex; flex-direction:column; align-items:flex-end; gap:10px; }
        .saldo-label { display:block; font-size:.8125rem; color:var(--ink-3); text-align:right; }
        .saldo { display:block; font-weight:800; color:var(--g-700); font-variant-numeric:tabular-nums; text-align:right; }
        .acts { display:flex; gap:8px; }
        .acts form { margin:0; }

        .range { margin:14px 0 0; text-align:center; font-size:.9rem; color:var(--ink-3); }
        .pager { display:flex; flex-wrap:wrap; justify-content:center; gap:6px; margin-top:12px; }
        .pager a, .pager span { min-width:44px; min-height:44px; padding:0 12px; display:inline-flex; align-items:center; justify-content:center; border-radius:14px; border:1px solid var(--line); background:#fff; color:var(--ink-2); font-weight:600; text-decoration:none; }
        .pager a:hover { background:var(--g-100); border-color:var(--g-200); }
        .pager svg { width:18px; height:18px; }
        .pager .cur { color:#fff; border-color:transparent; background:linear-gradient(135deg,var(--g-600),var(--g-700)); }
        .pager .gap { border:0; background:none; min-width:24px; padding:0; color:var(--ink-3); }
        .pager .off { opacity:.45; }

        .empty { text-align:center; padding:40px 24px; background:#fff; border:1px solid var(--line); border-radius:20px; }
        .empty .ico { margin:0 auto 14px; width:64px; height:64px; }
        .empty .ico svg { width:28px; height:28px; }
        .empty h3 { margin:0 0 4px; font-size:1.0625rem; }
        .empty p { margin:0 auto 18px; max-width:40ch; color:var(--ink-3); }

        dialog.dlg { width:min(860px, calc(100vw - 40px)); max-width:860px; padding:0; border:0; border-radius:28px; background:transparent; }
        dialog.dlg::backdrop { background:rgba(17, 30, 20, 0.32); }
        dialog.dlg form {
            background:#fff; border:1px solid #dfece0; border-radius:28px; padding:24px 28px 22px; box-shadow:0 18px 40px rgba(23, 42, 27, 0.12);
        }
        #dlgResetJudul { margin:0 0 10px; font-size:2.65rem; line-height:1.08; letter-spacing:-0.04em; color:#1a1f1b; }
        .dlg-info { margin:0 0 20px; font-size:1.08rem; color:#4a5a4d; }
        .label { display:block; margin:0 0 10px; font-size:1.05rem; font-weight:700; color:#1a1f1b; }
        .control { position:relative; margin:0 0 10px; }
        .f-input {
            width:100%; min-height:62px; padding:0 52px 0 16px; border:2px solid #6ea570; border-radius:18px; font:inherit; font-size:1.2rem; color:#1a1f1b; background:#fff; box-sizing:border-box;
        }
        .f-input:focus { outline:none; border-color:#4a8a52; box-shadow:0 0 0 4px rgba(74,138,82,.12); }
        .toggle {
            position:absolute; right:14px; top:50%; transform:translateY(-50%); width:36px; height:36px; border:0; background:transparent; color:#4a5a4d; display:grid; place-items:center; cursor:pointer;
        }
        .toggle svg { width:22px; height:22px; }
        .toggle .i-off { display:none; }
        .toggle[aria-pressed="true"] .i-on { display:none; }
        .toggle[aria-pressed="true"] .i-off { display:block; }
        .hint { margin:0 0 20px; font-size:1rem; line-height:1.5; color:#4c6d50; }
        .err { margin:0 0 10px; padding:10px 12px; border-radius:12px; background:#fde8e6; color:#9d1f17; font-size:.95rem; }
        .dlg-actions { display:flex; align-items:center; gap:14px; margin-top:8px; }
        .dlg-actions .btn { flex:1; min-height:60px; border-radius:16px; font-size:1.12rem; }
        #resetBatal, #resetAcak { background:#fff; border:1.5px solid #d5e6d7; color:#1a2a1d; }
        #resetAcak { display:inline-flex; gap:10px; }
        #resetSimpan { background:linear-gradient(135deg,#5ea55f,#2d7d3d); color:#fff; border:1.5px solid transparent; box-shadow:0 10px 20px rgba(45,125,61,.18); }
        #resetSimpan:hover { background:linear-gradient(135deg,#4f964f,#236d34); }

        @media (max-width:720px) {
            dialog.dlg { width:min(92vw, 460px); }
            dialog.dlg form { padding:20px 18px 18px; border-radius:22px; }
            #dlgResetJudul { font-size:2rem; }
            .dlg-actions { flex-wrap:wrap; }
            .dlg-actions .btn { width:100%; flex-basis:100%; }
            .control { margin-bottom:12px; }
        }
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
            <span class="top-title">Kelola Nasabah</span>
            <span class="avatar" title="<?= $e($userName) ?>" aria-hidden="true"><?= $e($userInitial) ?></span>
        </header>

        <main class="content">
            <div class="wrap">
                <a class="back" href="<?= base_url('admin/dashboard') ?>"><?= icon('arrow-left') ?>Dashboard</a>

                <?php if (!empty($flash) && !empty($flash['message'])): ?>
                    <div class="notice notice-<?= $flashType ?>" role="status"><?= icon($flashIcon) ?><span><?= $e($flash['message']) ?></span></div>
                <?php endif; ?>

                <div class="page-head">
                    <div>
                        <h1>Kelola Nasabah</h1>
                        <p class="lead">Atur data anggota bank sampah dan saldonya.</p>
                    </div>
                    <a href="<?= base_url('admin/nasabah/add') ?>" class="btn btn-primary"><?= icon('user-plus') ?>Tambah nasabah</a>
                </div>

                <section aria-labelledby="daftar-title">
                    <div class="sec-head">
                        <h2 class="sec-title" id="daftar-title">Daftar nasabah</h2>
                        <span class="badge b-green"><?= number_format($totalNasabah, 0, ',', '.') ?> total</span>
                    </div>

                    <?php if (empty($nasabahList)): ?>
                        <div class="empty">
                            <div class="ico"><?= icon('user') ?></div>
                            <h3>Belum ada nasabah</h3>
                            <p>Nasabah yang sudah ditambahkan akan muncul di sini.</p>
                            <a class="btn btn-primary" href="<?= base_url('admin/nasabah/add') ?>"><?= icon('user-plus') ?>Tambah nasabah</a>
                        </div>
                    <?php else: ?>
                        <ul class="list">
                            <?php foreach ($nasabahList as $n):
                                $nama = (string) (isset($n['nama']) ? $n['nama'] : '');
                                $noAng = trim((string) (isset($n['no_anggota']) ? $n['no_anggota'] : ''));
                                $hp = trim((string) (isset($n['no_hp']) ? $n['no_hp'] : ''));
                                $bankNama = trim((string) (isset($n['bank_sampah_nama']) ? $n['bank_sampah_nama'] : ''));
                                $tipe = (string) (isset($n['tipe']) ? $n['tipe'] : '');
                                $st = (string) (isset($n['status']) && $n['status'] !== '' ? $n['status'] : 'AKTIF');
                                $saldo = isset($n['saldo']) ? (float) $n['saldo'] : 0;
                                $id = isset($n['id']) ? $n['id'] : '';
                                $punyaAkun = !empty($n['user_id']);

                                // Teks konfirmasi dihitung di server dan hanya membantu pengguna.
                                // Penjagaan sebenarnya (CSRF, hak akses, status) ada di controller.
                                $tertahan = isset($pengajuanAktif[(int) $id]) ? $pengajuanAktif[(int) $id] : null;
                                $jumlahPengajuan = (is_array($tertahan) && isset($tertahan['jumlah_pengajuan']))
                                    ? (int) $tertahan['jumlah_pengajuan']
                                    : 0;

                                if ($st === 'AKTIF') {
                                    $pesanKonfirmasi = 'Nonaktifkan nasabah ' . $nama . '? Riwayatnya tetap tersimpan.';
                                    if ($punyaAkun) {
                                        $pesanKonfirmasi .= ' Akun login nasabah juga dinonaktifkan sehingga tidak bisa masuk.';
                                    }
                                    if ($saldo > 0) {
                                        $pesanKonfirmasi .= ' Saldo Rp ' . number_format($saldo, 0, ',', '.')
                                            . ' masih tersimpan.';
                                    }
                                    if ($jumlahPengajuan > 0) {
                                        $pesanKonfirmasi .= ' Ada ' . $jumlahPengajuan
                                            . ' pengajuan pencairan yang belum selesai.';
                                    }
                                    $pesanKonfirmasi .= ' Lanjutkan?';
                                } else {
                                    $pesanKonfirmasi = 'Aktifkan kembali nasabah ' . $nama . '?';
                                    if ($punyaAkun) {
                                        $pesanKonfirmasi .= ' Akun login nasabah juga diaktifkan kembali.';
                                    }
                                }
                            ?>
                                <li>
                                    <span class="ico" aria-hidden="true"><?= $e($inisial($nama)) ?></span>
                                    <div class="row-main">
                                        <p class="row-title"><?= $e($nama !== '' ? $nama : '-') ?></p>
                                        <div class="row-sub">
                                            <span><?= icon('hash') ?><?= $e($noAng !== '' ? $noAng : '-') ?></span>
                                            <?php if ($hp !== ''): ?><span><?= icon('phone') ?><?= $e($hp) ?></span><?php endif; ?>
                                            <?php if ($bankNama !== ''): ?><span><?= icon('building') ?><?= $e($bankNama) ?></span><?php endif; ?>
                                        </div>
                                        <div class="row-meta">
                                            <?php if ($tipe !== ''): ?><span class="badge <?= $tipe === 'INSTANSI' ? 'b-amber' : 'b-sky' ?>"><?= $e($label($tipe)) ?></span><?php endif; ?>
                                            <span class="badge <?= $st === 'AKTIF' ? 'b-green' : 'b-grey' ?>"><?= $e($label($st)) ?></span>
                                            <?php if (!$punyaAkun): ?><span class="badge b-grey">Tanpa akun</span><?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="row-end">
                                        <div>
                                            <span class="saldo-label">Saldo</span>
                                            <span class="saldo">Rp <?= number_format($saldo, 0, ',', '.') ?></span>
                                        </div>
                                        <div class="acts">
                                            <a href="<?= base_url('admin/nasabah/edit?id=' . $id) ?>" class="btn btn-ghost btn-sm" aria-label="Ubah nasabah <?= $e($nama) ?>"><?= icon('edit') ?>Ubah</a>
                                            <?php if ($punyaAkun): ?>
                                                <button type="button" class="btn btn-ghost btn-sm" data-reset-id="<?= $e($id) ?>" data-reset-nama="<?= $e($nama) ?>" aria-label="Reset kata sandi nasabah <?= $e($nama) ?>"><?= icon('key') ?>Reset kata sandi</button>
                                            <?php endif; ?>
                                            <form method="POST" action="<?= base_url('admin/nasabah/status') ?>" data-confirm="<?= $e($pesanKonfirmasi) ?>">
                                                <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
                                                <input type="hidden" name="id" value="<?= $e($id) ?>">
                                                <input type="hidden" name="aksi" value="<?= $st === 'AKTIF' ? 'nonaktifkan' : 'aktifkan' ?>">
                                                <button type="submit" class="btn <?= $st === 'AKTIF' ? 'btn-danger-ghost' : 'btn-ghost' ?> btn-sm"
                                                        aria-label="<?= $st === 'AKTIF' ? 'Nonaktifkan' : 'Aktifkan kembali' ?> nasabah <?= $e($nama) ?>">
                                                    <?= icon($st === 'AKTIF' ? 'power' : 'refresh') ?><?= $st === 'AKTIF' ? 'Nonaktifkan' : 'Aktifkan' ?>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <p class="range">Menampilkan <?= number_format($offset + 1, 0, ',', '.') ?>&ndash;<?= number_format($offset + $jumlahHalaman, 0, ',', '.') ?> dari <?= number_format($totalNasabah, 0, ',', '.') ?> nasabah</p>

                        <?php if ($totalPages > 1): ?>
                            <nav class="pager" aria-label="Halaman daftar nasabah">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?= $page - 1 ?>" aria-label="Halaman sebelumnya"><?= icon('chevron-left') ?></a>
                                <?php else: ?>
                                    <span class="off" aria-hidden="true"><?= icon('chevron-left') ?></span>
                                <?php endif; ?>

                                <?php foreach ($pageItems as $pi): ?>
                                    <?php if ($pi === null): ?>
                                        <span class="gap" aria-hidden="true">&hellip;</span>
                                    <?php elseif ($pi === $page): ?>
                                        <span class="cur" aria-current="page"><?= $pi ?></span>
                                    <?php else: ?>
                                        <a href="?page=<?= $pi ?>" aria-label="Halaman <?= $pi ?>"><?= $pi ?></a>
                                    <?php endif; ?>
                                <?php endforeach; ?>

                                <?php if ($page < $totalPages): ?>
                                    <a href="?page=<?= $page + 1 ?>" aria-label="Halaman berikutnya"><?= icon('chevron-right') ?></a>
                                <?php else: ?>
                                    <span class="off" aria-hidden="true"><?= icon('chevron-right') ?></span>
                                <?php endif; ?>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>

    <dialog class="dlg" id="dlgReset" aria-labelledby="dlgResetJudul">
        <form method="post" action="<?= $e(base_url('admin/nasabah/reset-password')) ?>" id="formReset" novalidate>
            <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
            <input type="hidden" name="id" id="resetId" value="">

            <h2 id="dlgResetJudul">Reset kata sandi</h2>
            <p class="dlg-info" id="dlgResetInfo">Ketik kata sandi sementara baru untuk nasabah ini.</p>

            <label for="resetPw" class="label">Kata sandi sementara baru</label>
            <div class="control">
                <input type="password" name="password" id="resetPw" class="f-input" maxlength="72" autocomplete="new-password" aria-describedby="resetHint resetErr">
                <button type="button" class="toggle" id="resetToggle" aria-pressed="false" aria-label="Tampilkan kata sandi" aria-controls="resetPw">
                    <span class="i-on"><?= icon('eye') ?></span><span class="i-off"><?= icon('eye-off') ?></span>
                </button>
            </div>
            <p class="hint" id="resetHint">Minimal 8 karakter, mengandung huruf dan angka. Nasabah wajib menggantinya saat login berikutnya.</p>
            <p class="err" id="resetErr" role="alert" hidden></p>

            <div class="dlg-actions">
                <button type="button" class="btn" id="resetBatal">Batal</button>
                <button type="button" class="btn" id="resetAcak"><?= icon('key') ?>Buat acak</button>
                <button type="submit" class="btn" id="resetSimpan">Reset kata sandi</button>
            </div>
        </form>
    </dialog>

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

        var forms = document.querySelectorAll('form[data-confirm]');
        Array.prototype.forEach.call(forms, function (f) {
            f.addEventListener('submit', function (ev) {
                if (!window.confirm(f.getAttribute('data-confirm'))) {
                    ev.preventDefault();
                }
            });
        });

        var dlg = document.getElementById('dlgReset');
        if (dlg) {
            var resetId = document.getElementById('resetId');
            var resetInfo = document.getElementById('dlgResetInfo');
            var resetPw = document.getElementById('resetPw');
            var resetErr = document.getElementById('resetErr');
            var resetToggle = document.getElementById('resetToggle');
            var formReset = document.getElementById('formReset');

            function tampilkanReset(tampil) {
                resetPw.type = tampil ? 'text' : 'password';
                resetToggle.setAttribute('aria-pressed', tampil ? 'true' : 'false');
                resetToggle.setAttribute('aria-label', tampil ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
            }
            function pesanReset(teks) {
                resetErr.textContent = teks;
                resetErr.hidden = (teks === '');
            }
            function bukaDialog() {
                if (typeof dlg.showModal === 'function') { dlg.showModal(); } else { dlg.setAttribute('open', 'open'); }
            }
            function tutupDialog() {
                if (typeof dlg.close === 'function') { dlg.close(); } else { dlg.removeAttribute('open'); }
            }

            var tombolReset = document.querySelectorAll('[data-reset-id]');
            Array.prototype.forEach.call(tombolReset, function (b) {
                b.addEventListener('click', function () {
                    resetId.value = b.getAttribute('data-reset-id');
                    resetInfo.textContent = 'Ketik kata sandi sementara baru untuk ' + b.getAttribute('data-reset-nama') + '.';
                    resetPw.value = '';
                    tampilkanReset(false);
                    pesanReset('');
                    bukaDialog();
                    resetPw.focus();
                });
            });

            document.getElementById('resetBatal').addEventListener('click', tutupDialog);
            resetToggle.addEventListener('click', function () { tampilkanReset(resetPw.type === 'password'); });

            document.getElementById('resetAcak').addEventListener('click', function () {
                var besar = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
                var kecil = 'abcdefghijkmnpqrstuvwxyz';
                var angka = '23456789';
                var semua = besar + kecil + angka;
                var len = 12;
                var buf = new Uint32Array(len);
                if (window.crypto && window.crypto.getRandomValues) {
                    window.crypto.getRandomValues(buf);
                } else {
                    for (var i = 0; i < len; i++) { buf[i] = Math.floor(Math.random() * 4294967295); }
                }
                var out = [besar.charAt(buf[0] % besar.length), kecil.charAt(buf[1] % kecil.length), angka.charAt(buf[2] % angka.length)];
                for (var i = 3; i < len; i++) { out.push(semua.charAt(buf[i] % semua.length)); }
                for (var j = out.length - 1; j > 0; j--) {
                    var k = buf[j] % (j + 1);
                    var t = out[j]; out[j] = out[k]; out[k] = t;
                }
                resetPw.value = out.join('');
                tampilkanReset(true);
                pesanReset('');
                resetPw.focus();
            });

            formReset.addEventListener('submit', function (ev) {
                var v = resetPw.value;
                var pesan = '';
                if (v === '') { pesan = 'Kata sandi wajib diisi.'; }
                else if (v.length < 8) { pesan = 'Kata sandi minimal 8 karakter.'; }
                else if (!/[A-Za-z]/.test(v) || !/[0-9]/.test(v)) { pesan = 'Kata sandi harus mengandung huruf dan angka.'; }
                if (pesan !== '') {
                    ev.preventDefault();
                    pesanReset(pesan);
                    resetPw.focus();
                    return;
                }
                pesanReset('');
            });
        }
    })();
    </script>
</body>
</html>