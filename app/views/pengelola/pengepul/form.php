<?php
// pengelola/pengepul/form.php
// Variabel dari controller (dipertahankan): $editPengepul, $errors, (opsional) $oldInput
// Field POST (dipertahankan): nama, no_hp, alamat, status (AKTIF | NONAKTIF)

$isEdit = isset($editPengepul) && $editPengepul;
$user = currentUser();

$errors = (isset($errors) && is_array($errors)) ? $errors : [];

// ASUMSI: jika controller mengirim $oldInput saat gagal validasi, itu yang dipakai;
// jika tidak, pakai $editPengepul (perilaku form lama).
$src = [];
if (isset($oldInput) && is_array($oldInput) && $oldInput) {
    $src = $oldInput;
} elseif (isset($editPengepul) && is_array($editPengepul)) {
    $src = $editPengepul;
}
$statusVal = strtoupper((string)($src['status'] ?? 'AKTIF'));
if (!in_array($statusVal, ['AKTIF', 'NONAKTIF'], true)) { $statusVal = 'AKTIF'; }

if (!function_exists('icon')) {
    function icon($name, $size = 20)
    {
        static $paths = [
            'menu'    => 'M4 6h16M4 12h16M4 18h16',
            'grid'    => 'M4 4h6v6H4zM14 4h6v6h-6zM14 14h6v6h-6zM4 14h6v6H4z',
            'cart'    => 'M3 4h2l2.4 11.2a1 1 0 0 0 1 .8h8.2a1 1 0 0 0 1-.8L19 8H6M9 20.5h.01M17 20.5h.01',
            'upload'  => 'M12 16V4M7 9l5-5 5 5M4 20h16',
            'box'     => 'M21 8l-9-5-9 5v8l9 5 9-5zM3 8l9 5 9-5M12 13v8',
            'tag'     => 'M20.6 13.4l-7.2 7.2a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8zM7.5 7.5h.01',
            'users'   => 'M16 20v-1.5a3.5 3.5 0 0 0-3.5-3.5h-5A3.5 3.5 0 0 0 4 18.5V20M10 12a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7zM20 20v-1.5a3.5 3.5 0 0 0-2.5-3.35M15.5 5.2a3.5 3.5 0 0 1 0 6.6',
            'truck'   => 'M2 6h11v10H2zM13 9h4l3 3v4h-7zM6.5 19a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3zM16.5 19a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z',
            'logout'  => 'M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9',
            'leaf'    => 'M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.5 19 2c1 2 2 4.2 2 8 0 5.5-4.8 10-10 10zM2 21c0-3 1.9-5.5 6-7',
            'plus'    => 'M12 5v14M5 12h14',
            'edit'    => 'M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z',
            'trash'   => 'M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2M19 6l-1 14a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1L5 6M10 11v6M14 11v6',
            'phone'   => 'M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z',
            'pin'     => 'M12 21s7-6.2 7-11a7 7 0 1 0-14 0c0 4.8 7 11 7 11zM12 12a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z',
            'back'    => 'M19 12H5M12 19l-7-7 7-7',
            'check'   => 'M5 12.5l4.5 4.5L19 7',
            'alert'   => 'M12 9v4M12 17h.01M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z',
            'info'    => 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20zM12 16v-4M12 8h.01',
            'save'    => 'M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2zM17 21v-8H7v8M7 3v5h8',
            'search'  => 'M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM21 21l-4.3-4.3',
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
$inisial = strtoupper(function_exists('mb_substr') ? mb_substr($namaUser, 0, 1) : substr($namaUser, 0, 1));


$menuAktif = 'pengelola/pengepul';
$judul = $isEdit ? 'Edit Pengepul' : 'Tambah Pengepul';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1b5e20">
    <title><?= $isEdit ? 'Edit' : 'Tambah' ?> Pengepul - <?= APP_NAME ?></title>
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
        .notice ul { margin:6px 0 0; padding-left:20px; }
        .notice strong { display:block; }

        /* Kartu & form */
        .card { background:#fff; border:1px solid var(--line); border-radius:24px; padding:24px; }
        .card-head { display:flex; align-items:center; gap:14px; margin-bottom:22px; padding-bottom:18px; border-bottom:1px solid var(--line); }
        .ico { width:46px; height:46px; border-radius:50%; background:var(--g-100); color:var(--g-700); display:grid; place-items:center; flex-shrink:0; }
        .card-head h2 { margin:0; font-size:1.1rem; line-height:1.3; }
        .card-head p { margin:0; font-size:.9rem; color:var(--ink-3); }
        .field { margin:0 0 20px; padding:0; border:0; min-width:0; }
        .field label, .field legend { display:block; padding:0; margin-bottom:8px; font-weight:700; color:var(--ink); }
        .req { color:var(--rose); }
        .input { width:100%; height:52px; padding:0 16px; border:1.5px solid var(--line); border-radius:14px; background:#fff; font:inherit; color:var(--ink); }
        .input::placeholder { color:#8a978c; }
        .input:focus { outline:none; border-color:var(--g-600); box-shadow:0 0 0 4px var(--g-100); }
        .hint { margin:6px 0 0; font-size:.875rem; color:var(--ink-3); }

        .choices { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        .choice { position:relative; display:block; margin:0; }
        .choice input { position:absolute; inset:0; width:100%; height:100%; margin:0; opacity:0; cursor:pointer; }
        .choice .box { display:flex; align-items:center; gap:12px; min-height:64px; padding:12px 16px; border:1.5px solid var(--line); border-radius:16px; background:#fff; }
        .choice .dot { width:24px; height:24px; border-radius:50%; border:1.5px solid var(--g-200); display:grid; place-items:center; color:#fff; flex-shrink:0; }
        .choice .dot svg { opacity:0; }
        .choice .txt strong { display:block; line-height:1.3; }
        .choice .txt small { color:var(--ink-3); font-size:.85rem; }
        .choice input:checked + .box { border-color:var(--g-600); background:var(--g-50); box-shadow:0 0 0 4px var(--g-100); }
        .choice input:checked + .box .dot { background:var(--g-600); border-color:var(--g-600); }
        .choice input:checked + .box .dot svg { opacity:1; }
        .choice input:focus-visible + .box { outline:3px solid var(--g-600); outline-offset:2px; }

        .form-actions { display:flex; gap:12px; flex-wrap:wrap; margin-top:8px; }

        @media (max-width:600px) {
            h1 { font-size:1.5rem; }
            .content { padding:18px 16px 56px; }
            .card { padding:20px 18px; border-radius:20px; }
            .choices { grid-template-columns:1fr; }
            .form-actions .btn { width:100%; }
            .form-actions .btn-primary { order:-1; }
        }
        @media (prefers-reduced-motion:reduce) { *, *::before, *::after { transition:none !important; animation:none !important; } }
    </style>
</head>
<body>
    <?php include VIEW_PATH . '/pengelola/partials/sidebar.php'; ?>
    <div class="scrim" id="scrim"></div>

    <div class="main">
        <header class="topbar">
            <button type="button" class="burger" id="burger" aria-controls="sidebar" aria-expanded="false" aria-label="Buka atau tutup menu"><?= icon('menu', 22) ?></button>
            <span class="top-title"><?= $e($judul) ?></span>
            <span class="avatar" title="<?= $e($namaUser) ?>" aria-hidden="true"><?= $e($inisial) ?></span>
        </header>

        <main class="content">
            <div class="wrap">
                <a class="back" href="<?= base_url('pengelola/pengepul') ?>"><?= icon('back', 18) ?>Pengepul</a>

                <h1><?= $e($judul) ?></h1>
                <p class="lead"><?= $isEdit ? 'Perbarui data pengepul, lalu simpan.' : 'Isi data pengepul baru. Kolom bertanda * wajib diisi.' ?></p>

                <?php if (!empty($errors)): ?>
                    <div class="notice notice-error" role="alert">
                        <?= icon('alert', 20) ?>
                        <div>
                            <strong>Data belum bisa disimpan</strong>
                            <ul><?php foreach ($errors as $err): ?><li><?= $e($err) ?></li><?php endforeach; ?></ul>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-head">
                        <span class="ico"><?= icon('truck', 22) ?></span>
                        <div>
                            <h2>Form Pengepul</h2>
                            <p>Data kontak dan status kerja sama</p>
                        </div>
                    </div>

                    <form method="POST" id="formPengepul">
                        <?= csrfField() ?>

                        <div class="field">
                            <label for="nama">Nama Pengepul <span class="req" aria-hidden="true">*</span></label>
                            <input type="text" id="nama" name="nama" class="input" value="<?= $e($src['nama'] ?? '') ?>" placeholder="Contoh: CV Daur Ulang Sejahtera" autocomplete="off" required>
                        </div>

                        <div class="field">
                            <label for="no_hp">No HP</label>
                            <input type="tel" id="no_hp" name="no_hp" class="input" value="<?= $e($src['no_hp'] ?? '') ?>" placeholder="Contoh: 081234567890" inputmode="tel" autocomplete="off">
                        </div>

                        <div class="field">
                            <label for="alamat">Alamat</label>
                            <input type="text" id="alamat" name="alamat" class="input" value="<?= $e($src['alamat'] ?? '') ?>" placeholder="Jalan, kelurahan, kota" autocomplete="off">
                        </div>

                        <fieldset class="field">
                            <legend>Status</legend>
                            <div class="choices">
                                <label class="choice">
                                    <input type="radio" name="status" value="AKTIF"<?= $statusVal === 'AKTIF' ? ' checked' : '' ?>>
                                    <span class="box"><span class="dot"><?= icon('check', 14) ?></span><span class="txt"><strong>Aktif</strong><small>Masih bekerja sama</small></span></span>
                                </label>
                                <label class="choice">
                                    <input type="radio" name="status" value="NONAKTIF"<?= $statusVal === 'NONAKTIF' ? ' checked' : '' ?>>
                                    <span class="box"><span class="dot"><?= icon('check', 14) ?></span><span class="txt"><strong>Nonaktif</strong><small>Tidak bekerja sama lagi</small></span></span>
                                </label>
                            </div>
                        </fieldset>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary" id="btnSimpan"><?= icon('save', 20) ?><span>Simpan</span></button>
                            <a href="<?= base_url('pengelola/pengepul') ?>" class="btn btn-ghost">Batal</a>
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

        // Cegah kirim ganda
        var form = document.getElementById('formPengepul');
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