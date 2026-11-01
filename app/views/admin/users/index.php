<?php
/**
 * Kelola Pengguna - Daftar
 * Compatible dengan PHP 7.3+
 *
 * Variabel dari controller:
 *   $flash, $users, $totalUsers, $totalPages, $page, $offset
 *   $filters      : ['q' => '', 'role' => '', 'status' => '']
 *   $csrfToken    : token CSRF (opsional; bila tidak ada dipakai csrf_token() jika fungsinya ada)
 *
 * $users: tiap item berisi id, nama, email, no_hp, role, status,
 *         bank_sampah_nama, last_login_at
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
            'user-plus' => '<circle cx="9" cy="8" r="4"/><path d="M2 21v-1a6 6 0 0 1 6-6h2a6 6 0 0 1 6 6v1M19 8v6M16 11h6"/>',
            'user-check' => '<circle cx="9" cy="8" r="4"/><path d="M2 21v-1a6 6 0 0 1 6-6h2a6 6 0 0 1 6 6v1M16 11l2 2 4-4"/>',
            'building' => '<path d="M4 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16"/><path d="M16 9h2a2 2 0 0 1 2 2v10M2 21h20M8 7h4M8 11h4M8 15h4"/>',
            'pin' => '<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
            'file' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Z"/><path d="M14 2v6h6M9 14h6M9 18h4"/>',
            'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/>',
            'arrow-left' => '<path d="M19 12H5M12 19l-7-7 7-7"/>',
            'phone' => '<rect x="6" y="2" width="12" height="20" rx="2.5"/><path d="M11 18h2"/>',
            'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
            'edit' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
            'search' => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>',
            'key' => '<circle cx="8" cy="15" r="4"/><path d="M10.8 12.2L21 2M17 6l3 3M14 9l2 2"/>',
            'power' => '<path d="M12 2v10"/><path d="M18.4 6.6a9 9 0 1 1-12.8 0"/>',
            'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
            'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
            'eye-off' => '<path d="M17.9 17.9A10.1 10.1 0 0 1 12 19c-6.5 0-10-7-10-7a18 18 0 0 1 4.1-5.1M9.9 5.2A9.7 9.7 0 0 1 12 5c6.5 0 10 7 10 7a18 18 0 0 1-2.2 3.2"/><path d="M14.1 14.1a3 3 0 1 1-4.2-4.2"/><line x1="2" y1="2" x2="22" y2="22"/>',
            'copy' => '<rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
            'chevron-left' => '<path d="M15 6l-6 6 6 6"/>',
            'chevron-right' => '<path d="M9 6l6 6-6 6"/>',
            'check' => '<path d="M5 12.5l4.5 4.5L19 7"/>',
            'alert' => '<path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/>',
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

if (!isset($flash)) {
    $flash = function_exists('getFlashMessage') ? getFlashMessage() : null;
}
$flashType = 'info';
if (!empty($flash['type']) && in_array($flash['type'], ['success', 'error', 'warning', 'info'], true)) {
    $flashType = $flash['type'];
}
$flashIcon = ['success' => 'check', 'error' => 'alert', 'warning' => 'alert', 'info' => 'info'][$flashType];

$users = (isset($users) && is_array($users)) ? $users : [];
$totalUsers = isset($totalUsers) ? (int) $totalUsers : count($users);
$totalPages = isset($totalPages) ? (int) $totalPages : 1;
$page = isset($page) ? (int) $page : 1;
$offset = isset($offset) ? (int) $offset : 0;
$appName = defined('APP_NAME') ? APP_NAME : 'Bank Sampah';
$idSaya = (string) currentUserId();

/* Filter aktif */
$filters = (isset($filters) && is_array($filters)) ? $filters : [];
$fQ = isset($filters['q']) ? trim((string) $filters['q']) : '';
$fRole = isset($filters['role']) ? (string) $filters['role'] : '';
$fStatus = isset($filters['status']) ? (string) $filters['status'] : '';
$adaFilter = ($fQ !== '' || $fRole !== '' || $fStatus !== '');

/* Token CSRF */
if (isset($csrfToken)) {
    $csrfToken = (string) $csrfToken;
} elseif (function_exists('csrf_token')) {
    $csrfToken = (string) csrf_token();
} else {
    $csrfToken = '';
}

/* Query string yang mempertahankan filter */
$bangunQuery = function ($p) use ($fQ, $fRole, $fStatus) {
    $q = [];
    if ($fQ !== '') { $q['q'] = $fQ; }
    if ($fRole !== '') { $q['role'] = $fRole; }
    if ($fStatus !== '') { $q['status'] = $fStatus; }
    if ($p > 1) { $q['page'] = $p; }
    return $q ? '?' . http_build_query($q) : '?';
};
$queryKembali = ltrim($bangunQuery($page), '?');

$menu = [
    ['admin/dashboard',   'Dashboard',          'grid'],
    ['admin/users',       'Kelola Pengguna',    'users'],
    ['admin/nasabah',     'Kelola Nasabah',     'user'],
    ['admin/bank-sampah', 'Kelola Bank Sampah', 'building'],
    ['admin/desa',        'Kelola Desa',        'pin'],
    ['admin/audit-log',   'Audit Log',          'file'],
];
$menuAktif = 'admin/users';

/* Nasabah sengaja tidak ada: dikelola lewat menu Kelola Nasabah */
$roleMeta = [
    'ADMIN'     => ['Admin', 'b-rose'],
    'PENGELOLA' => ['Pengelola', 'b-amber'],
    'KADES'     => ['Kepala Desa', 'b-green'],
];
$roleFilterOpsi = [
    'ADMIN'     => 'Admin',
    'PENGELOLA' => 'Pengelola',
    'KADES'     => 'Kepala Desa',
];
$statusFilterOpsi = [
    'AKTIF'    => 'Aktif',
    'NONAKTIF' => 'Nonaktif',
];

/* Format "Login terakhir" */
$formatLogin = function ($v) {
    $v = trim((string) $v);
    if ($v === '' || strpos($v, '0000-00-00') === 0) {
        return 'Belum pernah login';
    }
    $t = strtotime($v);
    return $t ? date('d/m/Y H:i', $t) : 'Belum pernah login';
};

/* Daftar nomor halaman: awal, akhir, dan 2 di sekitar halaman aktif */
$nomorHalaman = [];
for ($i = 1; $i <= $totalPages; $i++) {
    if ($i === 1 || $i === $totalPages || abs($i - $page) <= 2) {
        $nomorHalaman[] = $i;
    }
}
$dari = $totalUsers > 0 ? $offset + 1 : 0;
$sampai = $offset + count($users);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1b5e20">
    <title>Kelola Pengguna - <?= $e($appName) ?></title>
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
            --paper:#f7faf6; --ink:#17231a; --ink-2:#3b4a3e; --ink-3:#4a5a4d; --line:#dde5dc;
            --amber:#7a4100; --amber-bg:#ffefcc; --rose:#9b1c15; --rose-bg:#fde8e6; --sky:#12408f; --sky-bg:#e2edff;
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
        .sr-only { position:absolute; width:1px; height:1px; margin:-1px; padding:0; overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; border:0; }

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
        .wrap { max-width:960px; margin:0 auto; }

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

        .notice { display:flex; align-items:flex-start; gap:12px; padding:14px 16px; border-radius:16px; margin-bottom:18px; font-weight:500; }
        .notice svg { flex-shrink:0; margin-top:2px; width:20px; height:20px; }
        .notice-success { background:var(--g-50); color:#14532d; border:1px solid var(--g-200); }
        .notice-error { background:var(--rose-bg); color:var(--rose); border:1px solid #f8c9c5; }
        .notice-warning { background:var(--amber-bg); color:var(--amber); border:1px solid #f6d9a0; }
        .notice-info { background:var(--sky-bg); color:var(--sky); border:1px solid #c4d8fa; }

        /* Dialog reset kata sandi */
        .dlg { border:0; border-radius:22px; padding:0; width:calc(100% - 32px); max-width:460px; color:var(--ink); box-shadow:0 24px 60px rgba(15,61,20,.35); }
        .dlg::backdrop { background:rgba(15,61,20,.5); }
        .dlg form { padding:22px; }
        .dlg h2 { margin:0 0 4px; font-size:1.125rem; font-weight:800; }
        .dlg .dlg-info { margin:0 0 16px; color:var(--ink-2); }
        .dlg .label { display:block; margin-bottom:6px; font-size:.9375rem; font-weight:600; }
        .dlg .control { position:relative; }
        .dlg .f-input { padding-left:14px; padding-right:56px; height:52px; }
        .dlg .toggle { position:absolute; right:6px; top:50%; transform:translateY(-50%); width:44px; height:44px; border:0; border-radius:12px; cursor:pointer; display:grid; place-items:center; background:transparent; color:var(--ink-3); }
        .dlg .toggle:hover { background:var(--g-100); color:var(--g-800); }
        .dlg .toggle svg { width:20px; height:20px; }
        .dlg .toggle .i-off { display:none; }
        .dlg .toggle[aria-pressed="true"] .i-on { display:none; }
        .dlg .toggle[aria-pressed="true"] .i-off { display:block; }
        .dlg .hint { margin:8px 0 0; font-size:.9rem; color:var(--ink-3); }
        .dlg .err { margin:8px 0 0; font-size:.9rem; font-weight:700; color:var(--rose); }
        .dlg-actions { display:flex; flex-wrap:wrap; gap:10px; justify-content:flex-end; margin-top:18px; }
        .dlg-actions .btn { min-height:48px; padding:0 18px; border-radius:14px; }

        /* Filter */
        .filters { display:grid; grid-template-columns:minmax(0,2fr) minmax(0,1fr) minmax(0,1fr) auto; gap:12px; align-items:center; margin-bottom:18px; padding:14px; background:#fff; border:1px solid var(--line); border-radius:20px; }
        .f-search { position:relative; }
        .f-search .ic { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--ink-3); pointer-events:none; }
        .f-search .ic svg { width:20px; height:20px; }
        .f-input { width:100%; height:48px; padding:0 14px; font-size:1rem; color:var(--ink); background:#fff; border:1.5px solid #b9c7ba; border-radius:14px; }
        .f-search .f-input { padding-left:44px; }
        .f-input::placeholder { color:#5f6e62; opacity:1; }
        .f-input:hover { border-color:var(--g-600); }
        .f-input:focus { outline:none; border-color:var(--g-600); box-shadow:0 0 0 4px rgba(67,160,71,.16); }
        select.f-input { -webkit-appearance:none; appearance:none; padding-right:40px; cursor:pointer;
            background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%234a5a4d' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
            background-repeat:no-repeat; background-position:right 12px center; background-size:18px; }
        .f-actions { display:flex; align-items:center; gap:8px; }
        .f-actions .btn { min-height:48px; padding:0 18px; border-radius:14px; }
        .f-reset { display:inline-flex; align-items:center; min-height:44px; padding:0 8px; font-weight:600; color:var(--g-800); }

        .badge { display:inline-flex; align-items:center; gap:6px; padding:4px 12px; border-radius:999px; font-size:.875rem; font-weight:700; white-space:nowrap; line-height:1.3; }
        .b-amber { background:var(--amber-bg); color:var(--amber); }
        .b-green { background:var(--g-100); color:#14532d; }
        .b-rose { background:var(--rose-bg); color:var(--rose); }
        .b-sky { background:var(--sky-bg); color:var(--sky); }
        .b-grey { background:#e4e9e4; color:#2c3a2f; }
        .b-me { background:var(--ink); color:#fff; margin-left:8px; padding:2px 10px; font-size:.8125rem; vertical-align:middle; }
        .badge .dot { width:8px; height:8px; border-radius:50%; background:currentColor; flex:none; }

        .ico { width:46px; height:46px; border-radius:50%; flex:none; background:var(--g-100); color:var(--g-800); display:grid; place-items:center; font-weight:800; font-size:1.0625rem; }
        .ico svg { width:22px; height:22px; }
        .empty .ico { margin:0 auto 14px; width:64px; height:64px; }
        .empty .ico svg { width:28px; height:28px; }

        /* Daftar pengguna */
        .sec-head { display:flex; align-items:baseline; justify-content:space-between; gap:12px; margin:0 0 10px; }
        .sec-title { margin:0; font-size:1.125rem; font-weight:700; letter-spacing:-.01em; }
        .sec-count { font-size:.9375rem; color:var(--ink-3); }
        .list { list-style:none; margin:0; padding:0; background:#fff; border:1px solid var(--line); border-radius:20px; overflow:hidden; }
        .urow { display:grid; grid-template-columns:46px minmax(0,1fr) auto; grid-template-areas:"ico main bdg" "ico acts acts"; gap:6px 16px; align-items:start; padding:16px 18px; border-bottom:1px solid var(--line); }
        .urow:last-child { border-bottom:0; }
        .urow.is-off { background:#f6f8f5; }
        .urow.is-off .ico { background:#e4e9e4; color:#3b4a3e; }
        .urow > .ico { grid-area:ico; }
        .u-main { grid-area:main; min-width:0; }
        .row-title { margin:0; font-weight:700; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .row-sub { margin:3px 0 0; display:flex; align-items:center; gap:6px; font-size:.9375rem; color:var(--ink-3); min-width:0; }
        .row-sub svg { width:16px; height:16px; flex:none; }
        .row-sub span { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .bdgs { grid-area:bdg; display:flex; flex-wrap:wrap; justify-content:flex-end; gap:6px; }
        .acts { grid-area:acts; display:flex; flex-wrap:wrap; gap:8px; margin-top:6px; }
        .acts form { margin:0; }
        .act-btn { display:inline-flex; align-items:center; gap:8px; min-height:44px; padding:0 14px; border-radius:12px; border:1.5px solid var(--g-200); background:#fff; color:var(--g-800); font-size:.9375rem; font-weight:700; text-decoration:none; cursor:pointer; transition:background .15s; }
        .act-btn:hover { background:var(--g-100); }
        .act-btn svg { width:18px; height:18px; }
        .act-btn.warn { color:var(--amber); border-color:#e8c27a; }
        .act-btn.warn:hover { background:var(--amber-bg); }
        .act-btn.danger { color:var(--rose); border-color:#e59a94; }
        .act-btn.danger:hover { background:var(--rose-bg); }
        .act-btn[disabled] { opacity:.6; cursor:wait; }

        /* Halaman */
        .pager { display:flex; flex-wrap:wrap; justify-content:center; align-items:center; gap:6px; margin-top:18px; }
        .pager a, .pager span.cur, .pager span.gap { min-width:44px; height:44px; padding:0 12px; border-radius:12px; display:inline-flex; align-items:center; justify-content:center; font-weight:700; text-decoration:none; }
        .pager a { color:var(--g-800); background:#fff; border:1.5px solid var(--line); }
        .pager a:hover { background:var(--g-100); border-color:var(--g-200); }
        .pager a svg { width:18px; height:18px; }
        .pager span.cur { color:#fff; background:linear-gradient(135deg,var(--g-600),var(--g-700)); }
        .pager span.gap { color:var(--ink-3); min-width:24px; padding:0; }

        .empty { text-align:center; padding:40px 24px; background:#fff; border:1px solid var(--line); border-radius:20px; }
        .empty h3 { margin:0 0 4px; font-size:1.0625rem; }
        .empty p { margin:0 auto 18px; max-width:40ch; color:var(--ink-3); }

        @media (max-width:860px) {
            .filters { grid-template-columns:minmax(0,1fr) minmax(0,1fr); }
            .f-search { grid-column:1 / -1; }
            .f-actions { grid-column:1 / -1; }
            .f-actions .btn-primary { flex:1; }
        }
        @media (max-width:720px) {
            .content { padding:18px 16px 56px; }
            .topbar { padding:0 16px; }
            h1 { font-size:1.5rem; }
            .page-head .btn { width:100%; }
            .urow { grid-template-columns:46px minmax(0,1fr); grid-template-areas:"ico main" "ico bdg" "acts acts"; padding:14px; }
            .bdgs { justify-content:flex-start; margin-top:2px; }
            .act-btn { flex:1 1 auto; justify-content:center; }
            .acts form { flex:1 1 auto; display:flex; }
            .acts form .act-btn { flex:1; }
        }
        @media (max-width:520px) {
            .filters { grid-template-columns:minmax(0,1fr); }
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
            <span class="top-title">Kelola Pengguna</span>
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
                        <h1>Kelola Pengguna</h1>
                        <p class="lead"><?= number_format($totalUsers, 0, ',', '.') ?> pengguna <?= $adaFilter ? 'sesuai filter' : 'terdaftar' ?> (Admin, Pengelola, Kepala Desa)</p>
                    </div>
                    <a href="<?= base_url('admin/users/add') ?>" class="btn btn-primary"><?= icon('user-plus') ?>Tambah Pengguna</a>
                </div>

                <form class="filters" method="get" action="<?= $e(base_url('admin/users')) ?>" role="search" aria-label="Cari dan saring pengguna">
                    <div class="f-search">
                        <label for="f-q" class="sr-only">Cari nama atau no HP</label>
                        <span class="ic"><?= icon('search') ?></span>
                        <input type="search" id="f-q" name="q" class="f-input" value="<?= $e($fQ) ?>" placeholder="Cari nama atau no HP" autocomplete="off">
                    </div>
                    <div>
                        <label for="f-role" class="sr-only">Peran</label>
                        <select id="f-role" name="role" class="f-input">
                            <option value="">Semua peran</option>
                            <?php foreach ($roleFilterOpsi as $val => $lbl): ?>
                                <option value="<?= $e($val) ?>"<?= $fRole === $val ? ' selected' : '' ?>><?= $e($lbl) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="f-status" class="sr-only">Status</label>
                        <select id="f-status" name="status" class="f-input">
                            <option value="">Semua status</option>
                            <?php foreach ($statusFilterOpsi as $val => $lbl): ?>
                                <option value="<?= $e($val) ?>"<?= $fStatus === $val ? ' selected' : '' ?>><?= $e($lbl) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="f-actions">
                        <button type="submit" class="btn btn-primary"><?= icon('search') ?>Cari</button>
                        <?php if ($adaFilter): ?><a class="f-reset" href="<?= $e(base_url('admin/users')) ?>">Reset filter</a><?php endif; ?>
                    </div>
                </form>

                <section aria-labelledby="daftar-title">
                    <div class="sec-head">
                        <h2 class="sec-title" id="daftar-title">Daftar pengguna</h2>
                        <?php if (!empty($users)): ?><span class="sec-count">Menampilkan <?= number_format($dari, 0, ',', '.') ?>&ndash;<?= number_format($sampai, 0, ',', '.') ?> dari <?= number_format($totalUsers, 0, ',', '.') ?></span><?php endif; ?>
                    </div>

                    <?php if (empty($users)): ?>
                        <div class="empty">
                            <div class="ico"><?= icon($adaFilter ? 'search' : 'users') ?></div>
                            <?php if ($adaFilter): ?>
                                <h3>Tidak ada pengguna yang cocok</h3>
                                <p>Coba ubah kata kunci atau hapus filter yang dipakai.</p>
                                <a class="btn btn-ghost" href="<?= $e(base_url('admin/users')) ?>">Reset filter</a>
                            <?php else: ?>
                                <h3>Belum ada data</h3>
                                <p>Pengguna yang ditambahkan akan muncul di sini.</p>
                                <a class="btn btn-primary" href="<?= base_url('admin/users/add') ?>"><?= icon('user-plus') ?>Tambah Pengguna</a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <ul class="list">
                            <?php foreach ($users as $u):
                                $nama = (string) (isset($u['nama']) ? $u['nama'] : '');
                                $email = trim((string) (isset($u['email']) ? $u['email'] : ''));
                                $hp = trim((string) (isset($u['no_hp']) ? $u['no_hp'] : ''));
                                $role = (string) (isset($u['role']) ? $u['role'] : '');
                                $st = (string) (isset($u['status']) ? $u['status'] : '');
                                $bank = trim((string) (isset($u['bank_sampah_nama']) ? $u['bank_sampah_nama'] : ''));
                                $rm = isset($roleMeta[$role]) ? $roleMeta[$role] : [$role !== '' ? ucfirst(strtolower($role)) : '-', 'b-grey'];
                                $ini = $nama === '' ? '?' : (function_exists('mb_substr') ? mb_strtoupper(mb_substr($nama, 0, 1, 'UTF-8'), 'UTF-8') : strtoupper(substr($nama, 0, 1)));
                                $uid = isset($u['id']) ? (string) $u['id'] : '';
                                $aktif = ($st === 'AKTIF');
                                $diriSendiri = ($uid !== '' && $uid === $idSaya);
                            ?>
                                <li class="urow<?= $aktif ? '' : ' is-off' ?>">
                                    <span class="ico" aria-hidden="true"><?= $e($ini) ?></span>
                                    <div class="u-main">
                                        <p class="row-title"><?= $e($nama !== '' ? $nama : '-') ?><?php if ($diriSendiri): ?><span class="badge b-me">Akun Anda</span><?php endif; ?></p>
                                        <?php if ($hp !== ''): ?><p class="row-sub"><?= icon('phone') ?><span><?= $e($hp) ?></span></p><?php endif; ?>
                                        <p class="row-sub"><?= icon('mail') ?><span><?= $e($email !== '' ? $email : '-') ?></span></p>
                                        <?php if ($role === 'PENGELOLA'): ?>
                                            <p class="row-sub"><?= icon('building') ?><span><?= $e($bank !== '' ? $bank : 'Belum ada bank sampah') ?></span></p>
                                        <?php endif; ?>
                                        <p class="row-sub"><?= icon('clock') ?><span>Login terakhir: <?= $e($formatLogin(isset($u['last_login_at']) ? $u['last_login_at'] : '')) ?></span></p>
                                    </div>
                                    <div class="bdgs">
                                        <span class="badge <?= $e($rm[1]) ?>"><?= $e($rm[0]) ?></span>
                                        <span class="badge <?= $aktif ? 'b-green' : 'b-grey' ?>"><span class="dot" aria-hidden="true"></span><?= $aktif ? 'Aktif' : ($st === '' ? '-' : 'Nonaktif') ?></span>
                                    </div>
                                    <div class="acts">
                                        <a href="<?= base_url('admin/users/edit?id=' . $uid) ?>" class="act-btn" aria-label="Ubah <?= $e($nama) ?>"><?= icon('edit') ?>Ubah</a>

                                        <?php if (!$diriSendiri): ?>
                                            <button type="button" class="act-btn warn" data-reset-id="<?= $e($uid) ?>" data-reset-nama="<?= $e($nama) ?>" aria-label="Reset kata sandi <?= $e($nama) ?>"><?= icon('key') ?>Reset kata sandi</button>

                                            <?php if ($aktif): ?>
                                                <form method="post" action="<?= $e(base_url('admin/users/status')) ?>" data-confirm="<?= $e('Nonaktifkan akun ' . $nama . '? Pengguna ini tidak akan bisa login. Riwayat data tetap tersimpan.') ?>">
                                                    <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
                                                    <input type="hidden" name="id" value="<?= $e($uid) ?>">
                                                    <input type="hidden" name="aksi" value="nonaktifkan">
                                                    <input type="hidden" name="kembali" value="<?= $e($queryKembali) ?>">
                                                    <button type="submit" class="act-btn danger" aria-label="Nonaktifkan <?= $e($nama) ?>"><?= icon('power') ?>Nonaktifkan</button>
                                                </form>
                                            <?php else: ?>
                                                <form method="post" action="<?= $e(base_url('admin/users/status')) ?>" data-confirm="<?= $e('Aktifkan kembali akun ' . $nama . '? Pengguna ini bisa login lagi.') ?>">
                                                    <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
                                                    <input type="hidden" name="id" value="<?= $e($uid) ?>">
                                                    <input type="hidden" name="aksi" value="aktifkan">
                                                    <input type="hidden" name="kembali" value="<?= $e($queryKembali) ?>">
                                                    <button type="submit" class="act-btn" aria-label="Aktifkan kembali <?= $e($nama) ?>"><?= icon('user-check') ?>Aktifkan</button>
                                                </form>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <?php if ($totalPages > 1): ?>
                            <nav class="pager" aria-label="Halaman">
                                <?php if ($page > 1): ?><a href="<?= $e($bangunQuery($page - 1)) ?>" aria-label="Halaman sebelumnya"><?= icon('chevron-left') ?></a><?php endif; ?>
                                <?php $prev = 0; foreach ($nomorHalaman as $i):
                                    if ($prev && $i - $prev > 1): ?><span class="gap" aria-hidden="true">&hellip;</span><?php endif;
                                    if ($i === $page): ?><span class="cur" aria-current="page"><?= $i ?></span>
                                    <?php else: ?><a href="<?= $e($bangunQuery($i)) ?>"><?= $i ?></a><?php endif;
                                    $prev = $i;
                                endforeach; ?>
                                <?php if ($page < $totalPages): ?><a href="<?= $e($bangunQuery($page + 1)) ?>" aria-label="Halaman berikutnya"><?= icon('chevron-right') ?></a><?php endif; ?>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </section>

                <dialog class="dlg" id="dlgReset" aria-labelledby="dlgResetJudul">
                    <form method="post" action="<?= $e(base_url('admin/users/reset-password')) ?>" id="formReset" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
                        <input type="hidden" name="id" id="resetId" value="">
                        <input type="hidden" name="kembali" value="<?= $e($queryKembali) ?>">

                        <h2 id="dlgResetJudul">Reset kata sandi</h2>
                        <p class="dlg-info" id="dlgResetInfo">Ketik kata sandi sementara yang baru.</p>

                        <label for="resetPw" class="label">Kata sandi sementara baru</label>
                        <div class="control">
                            <input type="password" name="password" id="resetPw" class="f-input" maxlength="72" autocomplete="new-password" aria-describedby="resetHint resetErr">
                            <button type="button" class="toggle" id="resetToggle" aria-pressed="false" aria-label="Tampilkan kata sandi" aria-controls="resetPw">
                                <span class="i-on"><?= icon('eye') ?></span><span class="i-off"><?= icon('eye-off') ?></span>
                            </button>
                        </div>
                        <p class="hint" id="resetHint">Minimal 8 karakter, mengandung huruf dan angka. Pengguna wajib menggantinya saat login berikutnya.</p>
                        <p class="err" id="resetErr" role="alert" hidden></p>

                        <div class="dlg-actions">
                            <button type="button" class="btn btn-ghost" id="resetBatal">Batal</button>
                            <button type="button" class="btn btn-ghost" id="resetAcak"><?= icon('key') ?>Buat acak</button>
                            <button type="submit" class="btn btn-primary" id="resetSimpan">Reset kata sandi</button>
                        </div>
                    </form>
                </dialog>
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

        /* Konfirmasi sebelum Nonaktifkan / Aktifkan / Reset kata sandi */
        var forms = document.querySelectorAll('form[data-confirm]');
        Array.prototype.forEach.call(forms, function (f) {
            f.addEventListener('submit', function (ev) {
                if (!window.confirm(f.getAttribute('data-confirm'))) {
                    ev.preventDefault();
                    return;
                }
                var b = f.querySelector('button[type="submit"]');
                if (b) { b.setAttribute('disabled', 'disabled'); }
            });
        });

        /* Dialog reset kata sandi */
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
                var besar = 'ABCDEFGHJKLMNPQRSTUVWXYZ', kecil = 'abcdefghijkmnpqrstuvwxyz', angka = '23456789';
                var semua = besar + kecil + angka;
                var buf = new Uint32Array(12);
                (window.crypto || window.msCrypto).getRandomValues(buf);
                var out = [besar.charAt(buf[0] % besar.length), kecil.charAt(buf[1] % kecil.length), angka.charAt(buf[2] % angka.length)];
                for (var i = 3; i < 12; i++) { out.push(semua.charAt(buf[i] % semua.length)); }
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
                document.getElementById('resetSimpan').setAttribute('disabled', 'disabled');
            });
        }
    })();
    </script>
</body>
</html>