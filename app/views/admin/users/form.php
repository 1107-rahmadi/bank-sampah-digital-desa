<?php
/**
 * Form Tambah/Edit Pengguna
 * Compatible dengan PHP 7.3+
 *
 * Variabel dari controller:
 *   $editUser   : null saat tambah, array pengguna saat ubah
 *   $desas      : daftar desa (id, nama)
 *   $banks      : daftar bank sampah (id, nama, desa_id)
 *   $errors     : array asosiatif [nama_field => pesan]. Kunci yang dikenali:
 *                 nama, no_hp, email, password, role, status, desa_id, bank_sampah_id.
 *                 Kunci 'umum' (atau kunci angka) = pesan umum non-kolom.
 *   $oldInput   : (opsional) isian terakhir saat validasi gagal
 *   $csrfToken  : (opsional) token CSRF; bila tidak ada dipakai csrf_token() jika tersedia
 *   $roleOpsi   : (opsional) ['PENGELOLA' => 'Pengelola', 'KADES' => 'Kepala Desa'];
 *                 bila tidak dikirim, hanya Pengelola dan Kepala Desa
 *   $flash
 *
 * Field POST:
 *   Mode tambah : nama, no_hp, email, role, status, desa_id, bank_sampah_id, password
 *   Mode ubah   : nama, no_hp, email, role, status, desa_id, bank_sampah_id,
 *                 reset_password (=1, opsional, bukan akun sendiri) dan password
 *                 (hanya dikirim bila reset_password dicentang)
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
            'phone' => '<rect x="6" y="2" width="12" height="20" rx="2.5"/><path d="M11 18h2"/>',
            'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
            'lock' => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
            'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
            'eye-off' => '<path d="M17.9 17.9A10.1 10.1 0 0 1 12 19c-6.5 0-10-7-10-7a18 18 0 0 1 4.1-5.1M9.9 5.2A9.7 9.7 0 0 1 12 5c6.5 0 10 7 10 7a18 18 0 0 1-2.2 3.2"/><path d="M14.1 14.1a3 3 0 1 1-4.2-4.2"/><line x1="2" y1="2" x2="22" y2="22"/>',
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

$editUser = (isset($editUser) && is_array($editUser) && $editUser) ? $editUser : null;
$oldInput = (isset($oldInput) && is_array($oldInput)) ? $oldInput : [];
$isEdit = $editUser !== null;
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

$errors = (isset($errors) && is_array($errors)) ? $errors : [];
$desas = (isset($desas) && is_array($desas)) ? $desas : [];
$banks = (isset($banks) && is_array($banks)) ? $banks : [];
$appName = defined('APP_NAME') ? APP_NAME : 'Bank Sampah';
$judul = $isEdit ? 'Edit Pengguna' : 'Tambah Pengguna';
$idSaya = (string) currentUserId();

/* Token CSRF */
if (isset($csrfToken)) {
    $csrfToken = (string) $csrfToken;
} elseif (function_exists('csrf_token')) {
    $csrfToken = (string) csrf_token();
} else {
    $csrfToken = '';
}

/* Nilai awal field: isian terakhir saat gagal validasi ($oldInput / $_POST), lalu data $editUser */
$nilai = function ($key) use ($isEdit, $editUser, $oldInput) {
    if (isset($oldInput[$key]) && is_scalar($oldInput[$key])) {
        return (string) $oldInput[$key];
    }
    if (isset($_POST[$key]) && is_scalar($_POST[$key])) {
        return (string) $_POST[$key];
    }
    if ($isEdit && is_array($editUser) && isset($editUser[$key]) && is_scalar($editUser[$key])) {
        return (string) $editUser[$key];
    }
    return '';
};
$vNama = $nilai('nama');
$vHp = $nilai('no_hp');
$vEmail = $nilai('email');
$vRole = $nilai('role');
$vStatus = $nilai('status');
$vDesa = $nilai('desa_id');
$vBank = $nilai('bank_sampah_id');
if ($vStatus === '') {
    $vStatus = 'AKTIF';
}

/* Peran yang boleh dipilih: Pengelola dan Kepala Desa saja (Admin/Nasabah tidak muncul) */
if (!isset($roleOpsi) || !is_array($roleOpsi) || !$roleOpsi) {
    $roleOpsi = [
        'PENGELOLA' => 'Pengelola',
        'KADES' => 'Kepala Desa',
    ];
}
$statusOpsi = [
    'AKTIF' => 'Aktif',
    'NONAKTIF' => 'Nonaktif',
];

/* Penguncian saat ubah */
$editRole = $isEdit ? (string) (isset($editUser['role']) ? $editUser['role'] : '') : '';
$isSelf = $isEdit && isset($editUser['id']) && (string) $editUser['id'] === $idSaya;
$kunciRole = $isEdit && ($isSelf || $editRole === 'ADMIN');
$kunciStatus = $isSelf;
$roleTampil = $roleOpsi;
if ($kunciRole && $editRole === 'ADMIN') {
    $roleTampil = ['ADMIN' => 'Admin'] + $roleOpsi;
}
if ($kunciRole) {
    $vRole = $editRole;
}
if ($kunciStatus) {
    $vStatus = isset($editUser['status']) ? (string) $editUser['status'] : $vStatus;
}

/* Reset kata sandi: hanya mode ubah dan bukan akun sendiri */
$bisaReset = $isEdit && !$isSelf;
$namaTarget = $isEdit && isset($editUser['nama']) ? (string) $editUser['nama'] : '';
$resetAktif = $bisaReset && ($nilai('reset_password') === '1' || isset($errors['password']));

/* Kolom kata sandi tampil saat tambah, atau saat reset dicentang */
$tampilSandi = !$isEdit || $resetAktif;

/* Error per kolom */
$fieldKeys = ['nama', 'no_hp', 'email', 'password', 'role', 'status', 'desa_id', 'bank_sampah_id'];
$fieldLabel = [
    'nama' => 'Nama lengkap',
    'no_hp' => 'No HP',
    'email' => 'Email',
    'password' => 'Kata sandi sementara',
    'role' => 'Peran',
    'status' => 'Status',
    'desa_id' => 'Desa',
    'bank_sampah_id' => 'Bank sampah',
];
$errMsg = function ($k) use ($errors) {
    if (!isset($errors[$k])) {
        return '';
    }
    return is_array($errors[$k]) ? implode(' ', array_map('strval', $errors[$k])) : (string) $errors[$k];
};
$errUmum = [];
foreach ($errors as $k => $v) {
    if (!in_array($k, $fieldKeys, true)) {
        $errUmum[] = is_array($v) ? implode(' ', array_map('strval', $v)) : (string) $v;
    }
}
$adaError = !empty($errors);
$cls = function ($k) use ($errMsg) {
    return 'input' . ($errMsg($k) !== '' ? ' is-invalid' : '');
};
$aria = function ($k, $hintId = '') use ($errMsg) {
    $ids = [];
    if ($errMsg($k) !== '') {
        $ids[] = 'err-' . $k;
    }
    if ($hintId !== '') {
        $ids[] = $hintId;
    }
    return ($errMsg($k) !== '' ? ' aria-invalid="true"' : '') . ($ids ? ' aria-describedby="' . implode(' ', $ids) . '"' : '');
};
$errHtml = function ($k) use ($errMsg, $e) {
    $m = $errMsg($k);
    if ($m === '') {
        return '';
    }
    return '<p class="err" id="err-' . $k . '">' . icon('alert') . '<span>' . $e($m) . '</span></p>';
};

$menu = [
    ['admin/dashboard',   'Dashboard',          'grid'],
    ['admin/users',       'Kelola Pengguna',    'users'],
    ['admin/nasabah',     'Kelola Nasabah',     'user'],
    ['admin/bank-sampah', 'Kelola Bank Sampah', 'building'],
    ['admin/desa',        'Kelola Desa',        'pin'],
    ['admin/audit-log',   'Audit Log',          'file'],
   // ['admin/pengaturan',  'Pengaturan',         'settings'],
];
$menuAktif = 'admin/users';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1b5e20">
    <title><?= $e($judul) ?> - <?= $e($appName) ?></title>
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
        .wrap { max-width:760px; margin:0 auto; }

        .back { display:inline-flex; align-items:center; gap:8px; min-height:44px; margin-bottom:8px; color:var(--g-700); font-weight:600; text-decoration:none; }
        .back:hover { color:var(--g-800); }
        .back svg { width:18px; height:18px; }
        .page-head { margin-bottom:22px; }
        h1 { margin:0; font-size:1.75rem; line-height:1.25; font-weight:800; letter-spacing:-.01em; }
        .lead { margin:4px 0 0; color:var(--ink-3); }

        .btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; min-height:54px; padding:0 22px; border-radius:16px; border:1.5px solid transparent; font:inherit; font-size:1.0625rem; font-weight:700; text-decoration:none; cursor:pointer; }
        .btn svg { width:20px; height:20px; }
        .btn-primary { color:#fff; background:linear-gradient(135deg,var(--g-600),var(--g-700)); box-shadow:0 8px 20px rgba(27,94,32,.22); }
        .btn-primary:hover { background:linear-gradient(135deg,var(--g-700),var(--g-800)); }
        .btn-primary[disabled] { opacity:.7; cursor:wait; }
        .btn-ghost { color:var(--g-800); background:#fff; border-color:var(--g-200); }
        .btn-ghost:hover { background:var(--g-100); }

        .notice { display:flex; align-items:flex-start; gap:12px; padding:14px 16px; border-radius:16px; margin-bottom:18px; font-weight:500; }
        .notice svg { flex-shrink:0; margin-top:2px; width:20px; height:20px; }
        .notice ul { margin:4px 0 0 18px; padding:0; }
        .notice a { font-weight:700; text-decoration:underline; }
        .notice-success { background:var(--g-50); color:#14532d; border:1px solid var(--g-200); }
        .notice-error { background:var(--rose-bg); color:var(--rose); border:1px solid #f8c9c5; }
        .notice-warning { background:var(--amber-bg); color:var(--amber); border:1px solid #f6d9a0; }
        .notice-info { background:var(--sky-bg); color:var(--sky); border:1px solid #c4d8fa; }

        /* Form */
        .sec-title { margin:26px 0 10px; font-size:1.125rem; font-weight:700; letter-spacing:-.01em; }
        .sec-title:first-of-type { margin-top:0; }
        .panel { background:#fff; border:1px solid var(--line); border-radius:20px; padding:20px; }
        .grid2 { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; }
        .field { display:block; }
        .field.full { grid-column:1 / -1; }
        .label { display:block; margin-bottom:6px; font-size:.9375rem; font-weight:600; color:var(--ink); }
        .label .req { color:var(--rose); }
        .label .opt { font-weight:600; color:var(--ink-3); font-size:.875rem; }
        .hint { margin:6px 0 0; font-size:.9rem; color:var(--ink-3); }
        .err { display:flex; align-items:flex-start; gap:6px; margin:6px 0 0; font-size:.9rem; font-weight:700; color:var(--rose); }
        .err svg { width:16px; height:16px; flex:none; margin-top:3px; }
        .control { position:relative; }
        .control > .ic { position:absolute; left:14px; top:50%; transform:translateY(-50%); width:20px; height:20px; color:var(--ink-3); pointer-events:none; }
        .control > .ic svg { width:20px; height:20px; }
        .input { width:100%; height:52px; padding:0 14px; font-size:1rem; color:var(--ink); background:#fff; border:1.5px solid #b9c7ba; border-radius:14px; transition:border-color .15s, box-shadow .15s; }
        .control .input { padding-left:44px; }
        .input::placeholder { color:#5f6e62; opacity:1; }
        .input:hover { border-color:var(--g-600); }
        .input:focus { outline:none; border-color:var(--g-600); box-shadow:0 0 0 4px rgba(67,160,71,.16); }
        .input.is-invalid { border-color:var(--rose); background:#fffafa; }
        .input.is-invalid:focus { box-shadow:0 0 0 4px rgba(179,38,30,.15); }
        .input:disabled { background:#eef1ee; color:var(--ink-2); border-color:var(--line); cursor:not-allowed; -webkit-text-fill-color:var(--ink-2); opacity:1; }
        select.input { -webkit-appearance:none; appearance:none; padding-right:42px; cursor:pointer;
            background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%234a5a4d' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
            background-repeat:no-repeat; background-position:right 14px center; background-size:18px; }
        select.input:disabled { cursor:not-allowed; }
        .input.has-toggle { padding-right:56px; }
        .toggle { position:absolute; right:6px; top:50%; transform:translateY(-50%); width:44px; height:44px; border:0; border-radius:12px; cursor:pointer; display:grid; place-items:center; background:transparent; color:var(--ink-3); }
        .toggle:hover { background:var(--g-100); color:var(--g-800); }
        .toggle svg { width:20px; height:20px; }
        .toggle .i-off { display:none; }
        .toggle[aria-pressed="true"] .i-on { display:none; }
        .toggle[aria-pressed="true"] .i-off { display:block; }

        .info { display:flex; gap:14px; align-items:flex-start; }
        .info .ico-box { width:44px; height:44px; border-radius:14px; background:var(--g-100); color:var(--g-800); display:grid; place-items:center; flex:none; }
        .info .ico-box svg { width:22px; height:22px; }
        .info p { margin:0; color:var(--ink-2); }
        .info p + p { margin-top:6px; }
        .info strong { color:var(--ink); }
        .info a { color:var(--g-800); font-weight:700; }
        .akses-note { margin:0 0 4px; padding:12px 14px; border-radius:14px; background:var(--g-50); border:1px dashed var(--g-200); color:var(--ink-2); font-size:.9375rem; }

        /* Kotak centang reset kata sandi */
        .check { display:flex; align-items:flex-start; gap:14px; min-height:48px; padding:12px 14px; border:1.5px solid var(--g-200); border-radius:14px; background:var(--g-50); cursor:pointer; }
        .check:hover { background:var(--g-100); }
        .check input { width:24px; height:24px; margin:1px 0 0; flex:none; accent-color:var(--g-700); cursor:pointer; }
        .check input:focus-visible { outline:3px solid var(--g-600); outline-offset:2px; }
        .check .t { display:block; font-weight:700; color:var(--ink); }
        .check .d { display:block; margin-top:2px; font-size:.9rem; color:var(--ink-3); }
        .reset-fields { margin-top:16px; }

        .form-actions { display:flex; gap:12px; margin-top:24px; }
        .form-actions .btn-primary { flex:1; }

        @media (max-width:720px) {
            .content { padding:18px 16px 56px; }
            .topbar { padding:0 16px; }
            h1 { font-size:1.5rem; }
            .panel { padding:16px; border-radius:18px; }
            .grid2 { grid-template-columns:minmax(0,1fr); }
            .form-actions { flex-direction:column-reverse; }
            .form-actions .btn { width:100%; }
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
            <span class="top-title"><?= $e($judul) ?></span>
            <span class="avatar" title="<?= $e($userName) ?>" aria-hidden="true"><?= $e($userInitial) ?></span>
        </header>

        <main class="content">
            <div class="wrap">
                <a class="back" href="<?= base_url('admin/users') ?>"><?= icon('arrow-left') ?>Kelola Pengguna</a>

                <div class="page-head">
                    <h1><?= $e($judul) ?></h1>
                    <p class="lead"><?= $isEdit ? 'Perbarui data akun pengguna' : 'Buat akun Pengelola atau Kepala Desa' ?></p>
                </div>

                <?php if (!empty($flash) && !empty($flash['message'])): ?>
                    <div class="notice notice-<?= $flashType ?>" role="status"><?= icon($flashIcon) ?><span><?= $e($flash['message']) ?></span></div>
                <?php endif; ?>

                <?php if ($adaError): ?>
                    <div class="notice notice-error" role="alert" tabindex="-1" id="ringkasan-error">
                        <?= icon('alert') ?>
                        <div>
                            <strong>Data belum bisa disimpan. Periksa isian berikut:</strong>
                            <ul>
                                <?php foreach ($fieldKeys as $k): if ($errMsg($k) === '') { continue; } ?>
                                    <li><a href="#<?= $e($k) ?>"><?= $e($fieldLabel[$k]) ?></a>: <?= $e($errMsg($k)) ?></li>
                                <?php endforeach; ?>
                                <?php foreach ($errUmum as $m): ?>
                                    <li><?= $e($m) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="formUser" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">

                    <h2 class="sec-title">Identitas</h2>
                    <div class="panel">
                        <div class="grid2">
                            <div class="field full">
                                <label for="nama" class="label">Nama lengkap <span class="req" aria-hidden="true">*</span></label>
                                <div class="control">
                                    <span class="ic"><?= icon('user') ?></span>
                                    <input type="text" name="nama" id="nama" class="<?= $cls('nama') ?>" value="<?= $e($vNama) ?>" maxlength="100" autocomplete="name" required aria-required="true"<?= $aria('nama') ?>>
                                </div>
                                <?= $errHtml('nama') ?>
                            </div>
                            <div class="field">
                                <label for="no_hp" class="label">No HP <span class="req" aria-hidden="true">*</span></label>
                                <div class="control">
                                    <span class="ic"><?= icon('phone') ?></span>
                                    <input type="text" name="no_hp" id="no_hp" class="<?= $cls('no_hp') ?>" value="<?= $e($vHp) ?>" maxlength="16" inputmode="numeric" autocomplete="tel" placeholder="08xxxxxxxxxx" required aria-required="true"<?= $aria('no_hp', 'hint-hp') ?>>
                                </div>
                                <p class="hint" id="hint-hp">Hanya angka, diawali 08 atau 62.</p>
                                <?= $errHtml('no_hp') ?>
                            </div>
                            <div class="field">
                                <label for="email" class="label">Email <span class="opt">(opsional)</span></label>
                                <div class="control">
                                    <span class="ic"><?= icon('mail') ?></span>
                                    <input type="email" name="email" id="email" class="<?= $cls('email') ?>" value="<?= $e($vEmail) ?>" maxlength="150" autocomplete="email" placeholder="nama@email.com"<?= $aria('email') ?>>
                                </div>
                                <?= $errHtml('email') ?>
                            </div>
                        </div>
                    </div>

                    <h2 class="sec-title">Akses</h2>
                    <div class="panel">
                        <div class="grid2">
                            <div class="field">
                                <label for="role" class="label">Peran <span class="req" aria-hidden="true">*</span></label>
                                <select name="role" id="role" class="<?= $cls('role') ?>" required aria-required="true"<?= $kunciRole ? ' disabled' : '' ?><?= $aria('role', $kunciRole ? 'hint-role' : '') ?>>
                                    <option value="">-- Pilih peran --</option>
                                    <?php foreach ($roleTampil as $val => $lbl): ?>
                                        <option value="<?= $e($val) ?>"<?= $vRole === $val ? ' selected' : '' ?>><?= $e($lbl) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($kunciRole): ?>
                                    <p class="hint" id="hint-role"><?= $isSelf ? 'Anda tidak dapat mengubah peran akun sendiri.' : 'Peran Admin tidak dapat diubah dari sini.' ?></p>
                                <?php endif; ?>
                                <?= $errHtml('role') ?>
                            </div>
                            <div class="field">
                                <label for="status" class="label">Status</label>
                                <select name="status" id="status" class="<?= $cls('status') ?>"<?= $kunciStatus ? ' disabled' : '' ?><?= $aria('status', $kunciStatus ? 'hint-status' : '') ?>>
                                    <?php foreach ($statusOpsi as $val => $lbl): ?>
                                        <option value="<?= $e($val) ?>"<?= $vStatus === $val ? ' selected' : '' ?>><?= $e($lbl) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($kunciStatus): ?>
                                    <p class="hint" id="hint-status">Anda tidak dapat menonaktifkan akun sendiri.</p>
                                <?php endif; ?>
                                <?= $errHtml('status') ?>
                            </div>

                            <p class="akses-note field full" id="hintPeran" hidden>Pilih peran untuk menampilkan isian desa dan bank sampah yang sesuai.</p>

                            <div class="field" id="fldDesa">
                                <label for="desa_id" class="label">Desa <span class="req" id="reqDesa" aria-hidden="true" hidden>*</span></label>
                                <select name="desa_id" id="desa_id" class="<?= $cls('desa_id') ?>"<?= $aria('desa_id', 'hint-desa') ?>>
                                    <option value="">-- Pilih desa --</option>
                                    <?php foreach ($desas as $d): ?>
                                        <option value="<?= $e($d['id']) ?>"<?= ($vDesa !== '' && $vDesa === (string) $d['id']) ? ' selected' : '' ?>><?= $e($d['nama']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="hint" id="hint-desa">Wajib dipilih untuk Kepala Desa.</p>
                                <?= $errHtml('desa_id') ?>
                            </div>
                            <div class="field" id="fldBank">
                                <label for="bank_sampah_id" class="label">Bank sampah <span class="req" id="reqBank" aria-hidden="true" hidden>*</span></label>
                                <select name="bank_sampah_id" id="bank_sampah_id" class="<?= $cls('bank_sampah_id') ?>"<?= $aria('bank_sampah_id', 'hint-bank') ?>>
                                    <option value="">-- Pilih bank sampah --</option>
                                    <?php foreach ($banks as $b): ?>
                                        <option value="<?= $e($b['id']) ?>" data-desa="<?= $e(isset($b['desa_id']) ? $b['desa_id'] : '') ?>"<?= ($vBank !== '' && $vBank === (string) $b['id']) ? ' selected' : '' ?>><?= $e($b['nama']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="hint" id="hint-bank">Wajib dipilih untuk Pengelola. Daftar mengikuti desa yang dipilih.</p>
                                <?= $errHtml('bank_sampah_id') ?>
                            </div>
                        </div>
                    </div>

                    <h2 class="sec-title">Keamanan</h2>
                    <div class="panel">
                        <?php if ($isEdit && $isSelf): ?>
                            <div class="info">
                                <span class="ico-box"><?= icon('lock') ?></span>
                                <div>
                                    <p><strong>Ini akun Anda sendiri.</strong></p>
                                    <p>Untuk mengganti kata sandi Anda, gunakan halaman <a href="<?= base_url('ganti-password') ?>">Ganti kata sandi</a>. Reset kata sandi hanya untuk akun pengguna lain.</p>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php if ($bisaReset): ?>
                                <label class="check" for="resetCek">
                                    <input type="checkbox" name="reset_password" id="resetCek" value="1"<?= $resetAktif ? ' checked' : '' ?> aria-controls="fldSandi">
                                    <span>
                                        <span class="t">Reset kata sandi</span>
                                        <span class="d">Centang untuk membuat kata sandi sementara baru bagi <?= $e($namaTarget !== '' ? $namaTarget : 'pengguna ini') ?>. Pengguna wajib menggantinya saat login berikutnya.</span>
                                    </span>
                                </label>
                            <?php endif; ?>

                            <div class="field<?= $bisaReset ? ' reset-fields' : '' ?>" id="fldSandi"<?= $tampilSandi ? '' : ' hidden' ?>>
                                <label for="password" class="label">Kata sandi sementara<?= $bisaReset ? ' baru' : '' ?> <span class="req" aria-hidden="true">*</span></label>
                                <div class="control">
                                    <span class="ic"><?= icon('lock') ?></span>
                                    <input type="password" name="password" id="password" class="<?= $cls('password') ?> has-toggle" maxlength="72" autocomplete="new-password"<?= $tampilSandi ? ' required aria-required="true"' : ' disabled' ?><?= $aria('password', 'hint-password') ?>>
                                    <button type="button" class="toggle" id="togglePw" aria-pressed="false" aria-label="Tampilkan kata sandi" aria-controls="password">
                                        <span class="i-on"><?= icon('eye') ?></span><span class="i-off"><?= icon('eye-off') ?></span>
                                    </button>
                                </div>
                                <p class="hint" id="hint-password">Minimal 8 karakter, mengandung huruf dan angka. Pengguna wajib menggantinya saat <?= $bisaReset ? 'login berikutnya' : 'login pertama' ?>.</p>
                                <?= $errHtml('password') ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" id="btnSimpan">Simpan</button>
                        <a href="<?= base_url('admin/users') ?>" class="btn btn-ghost">Batal</a>
                    </div>
                </form>
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

        /* Fokus ke ringkasan error saat validasi server gagal */
        var ringkasan = document.getElementById('ringkasan-error');
        if (ringkasan) { ringkasan.focus(); }

        /* Aturan Peran, Desa, dan Bank sampah (tampilan; server tetap memvalidasi ulang) */
        var roleSel = document.getElementById('role');
        var desaSel = document.getElementById('desa_id');
        var bankSel = document.getElementById('bank_sampah_id');
        var fldDesa = document.getElementById('fldDesa');
        var fldBank = document.getElementById('fldBank');
        var hintPeran = document.getElementById('hintPeran');
        var hintDesa = document.getElementById('hint-desa');
        var reqDesa = document.getElementById('reqDesa');
        var reqBank = document.getElementById('reqBank');

        /* Simpan semua opsi bank sampah agar bisa disaring per desa */
        var semuaBank = [];
        var bankPlaceholder = '';
        Array.prototype.forEach.call(bankSel.options, function (o) {
            if (o.value === '') { bankPlaceholder = o.text; return; }
            semuaBank.push({ value: o.value, text: o.text, desa: o.getAttribute('data-desa') || '' });
        });

        function buatUlangBank() {
            var desa = desaSel.value;
            var pilih = bankSel.value;
            while (bankSel.options.length) { bankSel.remove(0); }
            var ph = document.createElement('option');
            ph.value = '';
            ph.text = bankPlaceholder;
            bankSel.add(ph);
            semuaBank.forEach(function (b) {
                if (desa === '' || b.desa === desa) {
                    var o = document.createElement('option');
                    o.value = b.value;
                    o.text = b.text;
                    o.setAttribute('data-desa', b.desa);
                    if (b.value === pilih) { o.selected = true; }
                    bankSel.add(o);
                }
            });
            if (bankSel.value !== pilih) { bankSel.value = ''; }
        }

        function terapkanPeran() {
            var r = roleSel.value;
            var pengelola = (r === 'PENGELOLA');
            var kades = (r === 'KADES');

            fldDesa.hidden = !(pengelola || kades);
            fldBank.hidden = !pengelola;
            hintPeran.hidden = (r !== '');

            desaSel.disabled = !(pengelola || kades);
            bankSel.disabled = !pengelola;
            reqDesa.hidden = !kades;
            reqBank.hidden = !pengelola;

            if (pengelola) {
                hintDesa.textContent = 'Pilih desa untuk menyaring bank sampah. Desa Pengelola otomatis mengikuti desa bank sampahnya.';
            } else if (kades) {
                hintDesa.textContent = 'Wajib dipilih untuk Kepala Desa.';
            }
            if (!pengelola) { bankSel.value = ''; }
            if (!pengelola && !kades) { desaSel.value = ''; }
            buatUlangBank();
        }

        desaSel.addEventListener('change', buatUlangBank);
        bankSel.addEventListener('change', function () {
            if (roleSel.value !== 'PENGELOLA' || bankSel.value === '') { return; }
            for (var i = 0; i < semuaBank.length; i++) {
                if (semuaBank[i].value === bankSel.value) {
                    if (semuaBank[i].desa !== '') { desaSel.value = semuaBank[i].desa; }
                    break;
                }
            }
        });
        roleSel.addEventListener('change', terapkanPeran);
        terapkanPeran();

        /* Kata sandi sementara: tampil/sembunyi, dan kotak centang reset (mode ubah) */
        var pw = document.getElementById('password');
        var tg = document.getElementById('togglePw');
        var fldSandi = document.getElementById('fldSandi');
        var resetCek = document.getElementById('resetCek');

        function tampilkanSandi(tampil) {
            if (!pw || !tg) { return; }
            pw.type = tampil ? 'text' : 'password';
            tg.setAttribute('aria-pressed', tampil ? 'true' : 'false');
            tg.setAttribute('aria-label', tampil ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
        }
        if (tg) { tg.addEventListener('click', function () { tampilkanSandi(pw.type === 'password'); }); }

        function terapkanReset() {
            if (!resetCek || !pw || !fldSandi) { return; }
            var aktif = resetCek.checked;
            fldSandi.hidden = !aktif;
            pw.disabled = !aktif;
            if (aktif) {
                pw.setAttribute('required', 'required');
                pw.setAttribute('aria-required', 'true');
            } else {
                pw.removeAttribute('required');
                pw.removeAttribute('aria-required');
                pw.value = '';
                tampilkanSandi(false);
            }
        }
        if (resetCek) {
            resetCek.addEventListener('change', function () {
                terapkanReset();
                if (resetCek.checked && pw) { pw.focus(); }
            });
            terapkanReset();
        }

        /* Validasi kata sandi di browser (server tetap memvalidasi ulang) */
        var form = document.getElementById('formUser');
        var btn = document.getElementById('btnSimpan');
        form.addEventListener('submit', function (ev) {
            if (pw && !pw.disabled && (!resetCek || resetCek.checked)) {
                var v = pw.value;
                var pesan = '';
                if (v === '') { pesan = 'Kata sandi wajib diisi.'; }
                else if (v.length < 8) { pesan = 'Kata sandi minimal 8 karakter.'; }
                else if (!/[A-Za-z]/.test(v) || !/[0-9]/.test(v)) { pesan = 'Kata sandi harus mengandung huruf dan angka.'; }

                var lama = document.getElementById('err-password-js');
                if (lama) { lama.parentNode.removeChild(lama); }
                if (pesan !== '') {
                    ev.preventDefault();
                    var p = document.createElement('p');
                    p.className = 'err';
                    p.id = 'err-password-js';
                    p.setAttribute('role', 'alert');
                    p.textContent = pesan;
                    fldSandi.appendChild(p);
                    pw.focus();
                    return;
                }
            }
            /* Cegah klik Simpan ganda */
            btn.setAttribute('disabled', 'disabled');
            window.setTimeout(function () { btn.removeAttribute('disabled'); }, 8000);
        });
    })();
    </script>
</body>
</html>