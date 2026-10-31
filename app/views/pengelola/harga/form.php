<?php
// pengelola/harga/form.php
// Variabel dari controller (dipertahankan): $editHarga, $errors, $kategoriList, (opsional) $oldInput
// Field POST (dipertahankan): kategori_id, harga_beli, harga_jual, berlaku_mulai

$isEdit = isset($editHarga) && $editHarga;
$user = currentUser();

$errors = (isset($errors) && is_array($errors)) ? $errors : [];
$kategoriList = (isset($kategoriList) && is_array($kategoriList)) ? $kategoriList : [];

// ASUMSI: jika controller mengirim $oldInput saat gagal validasi, itu yang dipakai;
// jika tidak, pakai $editHarga (perilaku form lama).
$src = [];
if (isset($oldInput) && is_array($oldInput) && $oldInput) {
    $src = $oldInput;
} elseif (isset($editHarga) && is_array($editHarga)) {
    $src = $editHarga;
}

$kategoriVal = (string)($src['kategori_id'] ?? '');

// Tanggal harus berformat Y-m-d agar terbaca input type="date"
$tglVal = date('Y-m-d');
if (!empty($src['berlaku_mulai'])) {
    $t = strtotime((string)$src['berlaku_mulai']);
    if ($t) { $tglVal = date('Y-m-d', $t); }
}

// Status aktif harga (checkbox hanya tampil di mode edit).
// Controller menyimpan aktif = isset($_POST['aktif']) ? 1 : 0, jadi checkbox harus dikirim.
if (isset($oldInput) && is_array($oldInput) && $oldInput) {
    $aktifVal = isset($oldInput['aktif']);
} else {
    $aktifVal = !isset($src['aktif']) || (int)$src['aktif'] === 1;
}

// Nama kategori terpilih (untuk mode edit, yang tidak bisa mengganti kategori)
$kategoriLabel = '-';
foreach ($kategoriList as $k) {
    if ((string)($k['id'] ?? '') === $kategoriVal) {
        $kategoriLabel = (string)($k['nama'] ?? '-') . (isset($k['jenis']) && $k['jenis'] !== '' ? ' (' . $k['jenis'] . ')' : '');
        break;
    }
}

if (!function_exists('icon')) {
    function icon($name, $size = 20)
    {
        static $paths = [
            'menu'     => 'M4 6h16M4 12h16M4 18h16',
            'grid'     => 'M4 4h6v6H4zM14 4h6v6h-6zM14 14h6v6h-6zM4 14h6v6H4z',
            'cart'     => 'M3 4h2l2.4 11.2a1 1 0 0 0 1 .8h8.2a1 1 0 0 0 1-.8L19 8H6M9 20.5h.01M17 20.5h.01',
            'upload'   => 'M12 16V4M7 9l5-5 5 5M4 20h16',
            'box'      => 'M21 8l-9-5-9 5v8l9 5 9-5zM3 8l9 5 9-5M12 13v8',
            'tag'      => 'M20.6 13.4l-7.2 7.2a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8zM7.5 7.5h.01',
            'users'    => 'M16 20v-1.5a3.5 3.5 0 0 0-3.5-3.5h-5A3.5 3.5 0 0 0 4 18.5V20M10 12a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7zM20 20v-1.5a3.5 3.5 0 0 0-2.5-3.35M15.5 5.2a3.5 3.5 0 0 1 0 6.6',
            'truck'    => 'M2 6h11v10H2zM13 9h4l3 3v4h-7zM6.5 19a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3zM16.5 19a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z',
            'logout'   => 'M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9',
            'leaf'     => 'M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.5 19 2c1 2 2 4.2 2 8 0 5.5-4.8 10-10 10zM2 21c0-3 1.9-5.5 6-7',
            'plus'     => 'M12 5v14M5 12h14',
            'edit'     => 'M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z',
            'calendar' => 'M7 3v3M17 3v3M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1zM4 9h16',
            'back'     => 'M19 12H5M12 19l-7-7 7-7',
            'check'    => 'M5 12.5l4.5 4.5L19 7',
            'alert'    => 'M12 9v4M12 17h.01M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z',
            'info'     => 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20zM12 16v-4M12 8h.01',
            'save'     => 'M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2zM17 21v-8H7v8M7 3v5h8',
            'search'   => 'M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM21 21l-4.3-4.3',
        ];
        $d = isset($paths[$name]) ? $paths[$name] : $paths['info'];
        return '<svg width="' . (int)$size . '" height="' . (int)$size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="' . $d . '"/></svg>';
    }
}

$e = function ($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
};

// ASUMSI: kunci nama pada currentUser() bisa 'nama', 'username', atau 'name'.
$namaUser = 'Pengelola';
if (is_array($user)) {
    foreach (['nama', 'username', 'name'] as $k) {
        if (!empty($user[$k])) { $namaUser = (string)$user[$k]; break; }
    }
}
$inisial = strtoupper(function_exists('mb_substr') ? mb_substr($namaUser, 0, 1, 'UTF-8') : substr($namaUser, 0, 1));

$menu = [
    ['pengelola/dashboard',      'Dashboard',      'grid'],
    ['pengelola/transaksi-beli', 'Transaksi Beli', 'cart'],
    ['pengelola/transaksi-jual', 'Transaksi Jual', 'upload'],
    ['pengelola/harga',          'Harga',          'tag'],
    ['pengelola/nasabah',        'Nasabah',        'users'],
    ['pengelola/pengepul',       'Pengepul',       'truck'],
];
$menuAktif = 'pengelola/harga';
$judul = $isEdit ? 'Edit Harga Sampah' : 'Atur Harga Sampah';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1b5e20">
    <title><?= $isEdit ? 'Edit' : 'Atur' ?> Harga - <?= APP_NAME ?></title>
    <script>
        (function () {
            var r = document.documentElement;
            r.classList.add('no-anim');
            var s = null;
            try { s = localStorage.getItem('nav'); } catch (e) {}
            if (window.innerWidth > 1100 && s !== 'closed') { r.classList.add('nav-open'); }
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
        :focus-visible { outline:3px solid var(--g-600); outline-offset:2px; }
        .no-anim *, .no-anim *::before, .no-anim *::after { transition:none !important; }

        /* Sidebar */
        .sidebar { position:fixed; inset:0 auto 0 0; width:var(--nav-w); max-width:86vw; z-index:60; display:flex; flex-direction:column; padding:20px 14px; color:#fff;
            background:linear-gradient(170deg,var(--g-700) 0%,var(--g-800) 60%,var(--g-900) 100%); transform:translateX(-100%); transition:transform .25s ease; overflow-y:auto; }
        .nav-open .sidebar { transform:none; }
        .brand { display:flex; align-items:center; gap:12px; padding:4px 8px 20px; }
        .brand-mark { width:42px; height:42px; border-radius:14px; background:rgba(255,255,255,.16); display:grid; place-items:center; flex-shrink:0; }
        .brand strong { display:block; font-weight:800; line-height:1.2; }
        .brand small { opacity:.75; font-size:.85rem; }
        .sidebar nav { display:flex; flex-direction:column; gap:4px; flex:1; }
        .sidebar nav a { display:flex; align-items:center; gap:12px; min-height:50px; padding:0 14px; border-radius:14px; text-decoration:none; color:rgba(255,255,255,.95); font-weight:500; transition:background .15s; }
        .sidebar nav a:hover { background:rgba(255,255,255,.1); color:#fff; }
        .sidebar nav a.active { background:rgba(255,255,255,.2); color:#fff; font-weight:700; }
        .sidebar nav a.logout { color:#ffd9d6; margin-top:auto; border-top:1px solid rgba(255,255,255,.14); border-radius:0 0 14px 14px; }
        .sidebar nav a:focus-visible { outline-color:#fff; }
        .sidebar nav a.logout:hover { background:rgba(230,90,80,.22); color:#ffd9d6; }
        .scrim { position:fixed; inset:0; background:rgba(15,61,20,.45); opacity:0; visibility:hidden; transition:opacity .25s; z-index:50; }

        /* Layout */
        .main { transition:margin-left .25s ease; min-height:100vh; }
        @media (min-width:1101px) { .nav-open .main { margin-left:var(--nav-w); } }
        @media (max-width:1100px) { .nav-open .scrim { opacity:1; visibility:visible; } }
        .topbar { position:sticky; top:0; z-index:40; height:68px; display:flex; align-items:center; gap:14px; padding:0 20px; background:rgba(247,250,246,.92); backdrop-filter:blur(8px); border-bottom:1px solid var(--line); }
        .burger { width:46px; height:46px; border-radius:14px; border:1px solid var(--line); background:#fff; color:var(--g-800); display:grid; place-items:center; cursor:pointer; }
        .burger:hover { background:var(--g-50); }
        .top-title { font-weight:700; color:var(--ink-2); }
        .avatar { margin-left:auto; width:42px; height:42px; border-radius:50%; display:grid; place-items:center; color:#fff; font-weight:800; background:linear-gradient(140deg,var(--g-500),var(--g-700)); }
        .content { padding:24px 20px 64px; }
        .wrap { max-width:760px; margin:0 auto; }

        .back { display:inline-flex; align-items:center; gap:8px; min-height:44px; margin-bottom:8px; color:var(--g-700); font-weight:600; text-decoration:none; }
        .back:hover { color:var(--g-800); }
        h1 { margin:0 0 4px; font-size:1.75rem; line-height:1.25; font-weight:800; letter-spacing:-.01em; }
        .lead { margin:0 0 22px; color:var(--ink-3); }

        /* Tombol */
        .btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; min-height:54px; padding:0 26px; border-radius:16px; border:0; font:inherit; font-weight:700; text-decoration:none; cursor:pointer; }
        .btn-primary { color:#fff; background:linear-gradient(135deg,var(--g-600),var(--g-700)); box-shadow:0 8px 20px rgba(27,94,32,.22); }
        .btn-primary:hover { background:linear-gradient(135deg,var(--g-700),var(--g-800)); }
        .btn-primary:disabled { opacity:.7; cursor:progress; }
        .btn-ghost { color:var(--g-800); background:#fff; border:1.5px solid var(--g-200); }
        .btn-ghost:hover { background:var(--g-50); }

        /* Notice */
        .notice { display:flex; align-items:flex-start; gap:12px; padding:14px 16px; border-radius:16px; margin-bottom:18px; font-weight:500; }
        .notice svg { flex-shrink:0; margin-top:2px; }
        .notice-error { background:var(--rose-bg); color:var(--rose); border:1px solid #f8c9c5; }
        .notice-info { background:var(--sky-bg); color:var(--sky); border:1px solid #c4d8fa; }
        .notice ul { margin:6px 0 0; padding-left:20px; }
        .notice strong { display:block; }

        /* Kartu & form */
        .card { background:#fff; border:1px solid var(--line); border-radius:24px; padding:24px; }
        .card-head { display:flex; align-items:center; gap:14px; margin-bottom:22px; padding-bottom:18px; border-bottom:1px solid var(--line); }
        .ico { width:46px; height:46px; border-radius:50%; background:var(--g-100); color:var(--g-700); display:grid; place-items:center; flex-shrink:0; }
        .card-head h2 { margin:0; font-size:1.1rem; line-height:1.3; }
        .card-head p { margin:0; font-size:.9rem; color:var(--ink-3); }
        .field { margin:0 0 20px; }
        .field label { display:block; margin-bottom:8px; font-weight:700; color:var(--ink); }
        .req { color:var(--rose); }
        .input { width:100%; height:52px; padding:0 16px; border:1.5px solid var(--line); border-radius:14px; background:#fff; font:inherit; color:var(--ink); }
        .input::placeholder { color:#8a978c; }
        .input:focus { outline:none; border-color:var(--g-600); box-shadow:0 0 0 4px var(--g-100); }
        .input[readonly] { background:var(--g-50); color:var(--ink-2); }
        select.input { appearance:none; -webkit-appearance:none; padding-right:44px; cursor:pointer;
            background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%235b6a5e' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E") no-repeat right 14px center; }
        .hint { margin:6px 0 0; font-size:.875rem; color:var(--ink-3); }
        .money { position:relative; }
        .money .pre, .money .suf { position:absolute; top:50%; transform:translateY(-50%); font-weight:700; color:var(--ink-3); pointer-events:none; }
        .money .pre { left:16px; }
        .money .suf { right:16px; font-weight:600; font-size:.9rem; }
        .money .input { padding-left:46px; padding-right:52px; -moz-appearance:textfield; font-variant-numeric:tabular-nums; }
        .money .input::-webkit-outer-spin-button, .money .input::-webkit-inner-spin-button { -webkit-appearance:none; margin:0; }

        .preview { display:flex; gap:12px; align-items:flex-start; padding:14px 16px; border-radius:16px; margin:0 0 22px; font-weight:600; background:var(--g-50); border:1px solid var(--g-200); color:var(--g-800); }
        .preview svg { flex-shrink:0; margin-top:2px; }
        .preview.idle { background:#fff; border-color:var(--line); color:var(--ink-3); font-weight:500; }
        .preview.warn { background:var(--amber-bg); border-color:#f6d9a0; color:var(--amber); }

        .check { position:relative; display:block; margin:0; }
        .check input { position:absolute; inset:0; width:100%; height:100%; margin:0; opacity:0; cursor:pointer; }
        .check .box { display:flex; align-items:center; gap:12px; min-height:64px; padding:12px 16px; border:1.5px solid var(--line); border-radius:16px; background:#fff; }
        .check .tick { width:24px; height:24px; border-radius:7px; border:1.5px solid var(--g-200); display:grid; place-items:center; color:#fff; flex-shrink:0; }
        .check .tick svg { opacity:0; }
        .check .txt strong { display:block; line-height:1.3; }
        .check .txt small { color:var(--ink-3); font-size:.85rem; }
        .check input:checked + .box { border-color:var(--g-600); background:var(--g-50); box-shadow:0 0 0 4px var(--g-100); }
        .check input:checked + .box .tick { background:var(--g-600); border-color:var(--g-600); }
        .check input:checked + .box .tick svg { opacity:1; }
        .check input:focus-visible + .box { outline:3px solid var(--g-600); outline-offset:2px; }

        .form-actions { display:flex; gap:12px; flex-wrap:wrap; margin-top:8px; }

        @media (max-width:600px) {
            h1 { font-size:1.5rem; }
            .content { padding:18px 16px 56px; }
            .card { padding:20px 18px; border-radius:20px; }
            .form-actions .btn { width:100%; }
            .form-actions .btn-primary { order:-1; }
        }
        @media (prefers-reduced-motion:reduce) { *, *::before, *::after { transition:none !important; animation:none !important; } }
    </style>
</head>
<body>
    <aside class="sidebar" id="sidebar" aria-label="Menu utama">
        <div class="brand">
            <span class="brand-mark"><?= icon('leaf', 22) ?></span>
            <div><strong><?= APP_NAME ?></strong><small>Pengelola</small></div>
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
            <button type="button" class="burger" id="burger" aria-controls="sidebar" aria-expanded="false" aria-label="Buka atau tutup menu"><?= icon('menu', 22) ?></button>
            <span class="top-title"><?= $e($judul) ?></span>
            <span class="avatar" title="<?= $e($namaUser) ?>" aria-hidden="true"><?= $e($inisial) ?></span>
        </header>

        <main class="content">
            <div class="wrap">
                <a class="back" href="<?= base_url('pengelola/harga') ?>"><?= icon('back', 18) ?>Harga</a>

                <h1><?= $e($judul) ?></h1>
                <p class="lead"><?= $isEdit ? 'Perbarui harga beli dan harga jual, lalu simpan.' : 'Tentukan harga beli dan harga jual per kilogram. Kolom bertanda * wajib diisi.' ?></p>

                <?php if (!empty($errors)): ?>
                    <div class="notice notice-error" role="alert">
                        <?= icon('alert', 20) ?>
                        <div>
                            <strong>Data belum bisa disimpan</strong>
                            <ul><?php foreach ($errors as $err): ?><li><?= $e($err) ?></li><?php endforeach; ?></ul>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!$isEdit): ?>
                    <div class="notice notice-info"><?= icon('info', 20) ?><span>Mengatur harga baru akan otomatis menonaktifkan harga lama untuk kategori yang sama.</span></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-head">
                        <span class="ico"><?= icon('tag', 22) ?></span>
                        <div>
                            <h2>Form Harga</h2>
                            <p>Harga per kilogram dan tanggal mulai berlaku</p>
                        </div>
                    </div>

                    <form method="POST" id="formHarga">
                        <div class="field">
                            <?php if ($isEdit): ?>
                                <label for="kategori_tampil">Kategori</label>
                                <input type="hidden" name="kategori_id" value="<?= $e($kategoriVal) ?>">
                                <input type="text" id="kategori_tampil" class="input" value="<?= $e($kategoriLabel) ?>" readonly aria-describedby="kategori-hint">
                                <p class="hint" id="kategori-hint">Kategori tidak dapat diubah. Untuk kategori lain, atur harga baru.</p>
                            <?php else: ?>
                                <label for="kategori_id">Kategori <span class="req" aria-hidden="true">*</span></label>
                                <select name="kategori_id" id="kategori_id" class="input" required>
                                    <option value="">Pilih kategori</option>
                                    <?php foreach ($kategoriList as $k): ?>
                                        <option value="<?= $e($k['id']) ?>"<?= (string)$k['id'] === $kategoriVal ? ' selected' : '' ?>><?= $e($k['nama'] ?? '') ?><?= isset($k['jenis']) && $k['jenis'] !== '' ? ' (' . $e($k['jenis']) . ')' : '' ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>

                        <div class="field">
                            <label for="harga_beli">Harga Beli (dari Nasabah) <span class="req" aria-hidden="true">*</span></label>
                            <div class="money">
                                <span class="pre" aria-hidden="true">Rp</span>
                                <input type="number" id="harga_beli" name="harga_beli" class="input" min="1" inputmode="numeric" value="<?= $e($src['harga_beli'] ?? '') ?>" placeholder="5000" aria-describedby="beli-hint" required>
                                <span class="suf" aria-hidden="true">/kg</span>
                            </div>
                            <p class="hint" id="beli-hint">Harga yang dibayar ke warga per kg</p>
                        </div>

                        <div class="field">
                            <label for="harga_jual">Harga Jual (ke Pengepul) <span class="req" aria-hidden="true">*</span></label>
                            <div class="money">
                                <span class="pre" aria-hidden="true">Rp</span>
                                <input type="number" id="harga_jual" name="harga_jual" class="input" min="1" inputmode="numeric" value="<?= $e($src['harga_jual'] ?? '') ?>" placeholder="7000" aria-describedby="jual-hint" required>
                                <span class="suf" aria-hidden="true">/kg</span>
                            </div>
                            <p class="hint" id="jual-hint">Harga jual ke pengepul per kg</p>
                        </div>

                        <div class="preview idle" id="selisih" aria-live="polite"><?= icon('info', 20) ?><span id="selisihTeks">Isi harga beli dan harga jual untuk melihat selisih per kg.</span></div>

                        <div class="field">
                            <label for="berlaku_mulai">Berlaku Mulai</label>
                            <input type="date" id="berlaku_mulai" name="berlaku_mulai" class="input" value="<?= $e($tglVal) ?>">
                        </div>

                        <?php if ($isEdit): ?>
                            <div class="field">
                                <label class="check" for="aktif">
                                    <input type="checkbox" id="aktif" name="aktif" value="1"<?= $aktifVal ? ' checked' : '' ?>>
                                    <span class="box"><span class="tick"><?= icon('check', 14) ?></span><span class="txt"><strong>Harga aktif</strong><small>Hilangkan centang untuk menonaktifkan harga ini</small></span></span>
                                </label>
                            </div>
                        <?php endif; ?>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary" id="btnSimpan"><?= icon('save', 20) ?><span>Simpan</span></button>
                            <a href="<?= base_url('pengelola/harga') ?>" class="btn btn-ghost">Batal</a>
                        </div>
                    </form>
                </div>
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

        // Pratinjau selisih harga
        var beli = document.getElementById('harga_beli');
        var jual = document.getElementById('harga_jual');
        var box = document.getElementById('selisih');
        var teks = document.getElementById('selisihTeks');

        function rp(n) { return 'Rp ' + Math.round(Math.abs(n)).toLocaleString('id-ID'); }
        function hitung() {
            var b = parseFloat(beli.value), j = parseFloat(jual.value);
            box.className = 'preview';
            if (isNaN(b) || isNaN(j)) {
                box.className = 'preview idle';
                teks.textContent = 'Isi harga beli dan harga jual untuk melihat selisih per kg.';
                return;
            }
            var d = j - b;
            if (d < 0) {
                box.className = 'preview warn';
                teks.textContent = 'Harga jual lebih rendah dari harga beli. Selisih rugi ' + rp(d) + ' per kg.';
            } else if (d === 0) {
                teks.textContent = 'Harga jual sama dengan harga beli. Tidak ada selisih per kg.';
            } else {
                var pct = b > 0 ? ' (' + (d / b * 100).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + '% dari harga beli)' : '';
                teks.textContent = 'Selisih ' + rp(d) + ' per kg' + pct + '.';
            }
        }
        beli.addEventListener('input', hitung);
        jual.addEventListener('input', hitung);
        hitung();

        // Cegah kirim ganda
        var form = document.getElementById('formHarga');
        var btn = document.getElementById('btnSimpan');
        form.addEventListener('submit', function () {
            if (!form.checkValidity()) { return; }
            btn.disabled = true;
            btn.setAttribute('aria-busy', 'true');
            btn.querySelector('span').textContent = 'Menyimpan...';
        });
        window.addEventListener('pageshow', function (ev) {
            if (ev.persisted) {
                btn.disabled = false;
                btn.removeAttribute('aria-busy');
                btn.querySelector('span').textContent = 'Simpan';
            }
        });
    })();
    </script>
</body>
</html>