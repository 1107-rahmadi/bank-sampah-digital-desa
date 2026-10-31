<?php
// pengelola/pengepul/index.php
// Variabel dari controller (dipertahankan): $pengepulList, $flash (opsional)

$user = currentUser();

// ASUMSI: jika controller tidak mengirim $flash, ambil dari helper global.
if (!isset($flash)) {
    $flash = function_exists('getFlashMessage') ? getFlashMessage() : null;
}
$pengepulList = (isset($pengepulList) && is_array($pengepulList)) ? $pengepulList : [];

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

$total = count($pengepulList);
$aktif = 0;
foreach ($pengepulList as $p) {
    if (($p['status'] ?? '') === 'AKTIF') { $aktif++; }
}
$nonaktif = $total - $aktif;

$flashType = 'info';
if (!empty($flash['type']) && in_array($flash['type'], ['success', 'error', 'warning', 'info'], true)) {
    $flashType = $flash['type'];
}
$flashIcon = ['success' => 'check', 'error' => 'alert', 'warning' => 'alert', 'info' => 'info'][$flashType];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1b5e20">
    <title>Pengepul - <?= APP_NAME ?></title>
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
        .sr-only { position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; }

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
        .sidebar nav a.logout { color:#ffd9d6; margin-top:auto; border-top:1px solid rgba(255,255,255,.14); border-radius:0 0 14px 14px; padding-top:0; }
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
        .wrap { max-width:880px; margin:0 auto; }

        .back { display:inline-flex; align-items:center; gap:8px; min-height:44px; margin-bottom:8px; color:var(--g-700); font-weight:600; text-decoration:none; }
        .back:hover { color:var(--g-800); }
        .page-head { display:flex; align-items:flex-end; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:22px; }
        h1 { margin:0; font-size:1.75rem; line-height:1.25; font-weight:800; letter-spacing:-.01em; }
        .lead { margin:4px 0 0; color:var(--ink-3); }

        /* Tombol */
        .btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; min-height:52px; padding:0 22px; border-radius:16px; border:0; font:inherit; font-weight:700; text-decoration:none; cursor:pointer; }
        .btn-primary { color:#fff; background:linear-gradient(135deg,var(--g-600),var(--g-700)); box-shadow:0 8px 20px rgba(27,94,32,.22); }
        .btn-primary:hover { background:linear-gradient(135deg,var(--g-700),var(--g-800)); }
        .btn-ghost { color:var(--g-800); background:#fff; border:1.5px solid var(--g-200); }
        .btn-ghost:hover { background:var(--g-50); }

        /* Notice */
        .notice { display:flex; align-items:flex-start; gap:12px; padding:14px 16px; border-radius:16px; margin-bottom:18px; font-weight:500; }
        .notice svg { flex-shrink:0; margin-top:2px; }
        .notice-success { background:var(--g-50); color:#14532d; border:1px solid var(--g-200); }
        .notice-error { background:var(--rose-bg); color:var(--rose); border:1px solid #f8c9c5; }
        .notice-warning { background:var(--amber-bg); color:var(--amber); border:1px solid #f6d9a0; }
        .notice-info { background:var(--sky-bg); color:var(--sky); border:1px solid #c4d8fa; }

        /* Kartu ringkasan */
        .wallet { position:relative; overflow:hidden; border-radius:24px; padding:24px; margin-bottom:22px; color:#fff; background:linear-gradient(135deg,#43a047 0%,#2e7d32 45%,#14501a 100%); box-shadow:0 14px 32px rgba(27,94,32,.22); }
        .wallet::before, .wallet::after { content:""; position:absolute; border-radius:50%; background:rgba(255,255,255,.07); }
        .wallet::before { width:220px; height:220px; right:-70px; top:-90px; }
        .wallet::after { width:140px; height:140px; right:60px; bottom:-85px; background:rgba(255,255,255,.07); }
        .wallet > * { position:relative; z-index:1; }
        .wallet-label { margin:0; font-size:.9rem; opacity:.88; }
        .wallet-value { margin:2px 0 0; font-size:2.25rem; font-weight:800; line-height:1.15; }
        .wallet-stats { display:flex; flex-wrap:wrap; gap:10px; margin-top:16px; }
        .chip { padding:6px 14px; border-radius:999px; background:rgba(255,255,255,.14); border:1px solid rgba(255,255,255,.18); font-size:.9rem; font-weight:600; }

        /* Pencarian */
        .search { position:relative; margin-bottom:14px; }
        .search svg { position:absolute; left:16px; top:50%; transform:translateY(-50%); color:var(--ink-3); pointer-events:none; }
        .search input { width:100%; height:52px; padding:0 16px 0 48px; border:1.5px solid var(--line); border-radius:16px; background:#fff; font:inherit; color:var(--ink); }
        .search input:focus { outline:none; border-color:var(--g-600); box-shadow:0 0 0 4px var(--g-100); }

        /* Daftar */
        .list { list-style:none; margin:0; padding:0; background:#fff; border:1px solid var(--line); border-radius:20px; overflow:hidden; }
        .row { display:grid; grid-template-columns:46px minmax(0,1fr) auto; grid-template-areas:"ico main side"; gap:4px 14px; align-items:center; padding:16px 18px; border-bottom:1px solid var(--line); }
        .row:last-child { border-bottom:0; }
        .row[hidden] { display:none; }
        .ico { grid-area:ico; width:46px; height:46px; border-radius:50%; background:var(--g-100); color:var(--g-700); display:grid; place-items:center; }
        .row.off .ico { background:#eef1ee; color:var(--ink-3); }
        .row-main { grid-area:main; min-width:0; }
        .row-title { margin:0; font-weight:700; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .row-sub { margin:2px 0 0; display:flex; align-items:center; gap:6px; font-size:.9rem; color:var(--ink-3); min-width:0; }
        .row-sub svg { flex-shrink:0; }
        .row-sub span { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .row-side { grid-area:side; display:flex; align-items:center; gap:10px; }
        .badge { display:inline-flex; align-items:center; padding:4px 12px; border-radius:999px; font-size:.8rem; font-weight:700; }
        .badge-on { background:var(--g-100); color:var(--g-800); }
        .badge-off { background:#eef1ee; color:var(--ink-3); }
        .acts { display:flex; gap:8px; }
        .icon-btn { width:44px; height:44px; border-radius:12px; border:1px solid var(--line); background:#fff; color:var(--ink-2); display:grid; place-items:center; text-decoration:none; cursor:pointer; }
        .icon-btn:hover { background:var(--g-50); color:var(--g-800); }
        .inline-form { margin:0; }
        button.icon-btn { padding:0; font:inherit; }
        .icon-btn.danger { color:var(--rose); }
        .icon-btn.danger:hover { background:var(--rose-bg); }

        .empty { text-align:center; padding:48px 24px; background:#fff; border:1px solid var(--line); border-radius:20px; }
        .empty .ico { margin:0 auto 14px; width:64px; height:64px; }
        .empty h2 { margin:0 0 4px; font-size:1.15rem; }
        .empty p { margin:0 0 20px; color:var(--ink-3); }
        .no-result { display:none; text-align:center; padding:28px 16px; color:var(--ink-3); }

        @media (max-width:600px) {
            h1 { font-size:1.5rem; }
            .content { padding:18px 16px 56px; }
            .page-head .btn { width:100%; }
            .wallet-value { font-size:2rem; }
            .row { grid-template-columns:46px minmax(0,1fr); grid-template-areas:"ico main" "ico side"; }
            .row-side { justify-content:space-between; margin-top:8px; }
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
            <span class="top-title">Pengepul</span>
            <span class="avatar" title="<?= $e($namaUser) ?>" aria-hidden="true"><?= $e($inisial) ?></span>
        </header>

        <main class="content">
            <div class="wrap">
                <a class="back" href="<?= base_url('pengelola/dashboard') ?>"><?= icon('back', 18) ?>Dashboard</a>

                <?php if (!empty($flash) && !empty($flash['message'])): ?>
                    <div class="notice notice-<?= $flashType ?>" role="status"><?= icon($flashIcon, 20) ?><span><?= $e($flash['message']) ?></span></div>
                <?php endif; ?>

                <div class="page-head">
                    <div>
                        <h1>Data Pengepul</h1>
                        <p class="lead">Kelola mitra pengepul yang membeli sampah dari bank sampah.</p>
                    </div>
                    <a href="<?= base_url('pengelola/pengepul/add') ?>" class="btn btn-primary"><?= icon('plus', 20) ?>Tambah Pengepul</a>
                </div>

                <section class="wallet" aria-label="Ringkasan pengepul">
                    <p class="wallet-label">Total pengepul</p>
                    <p class="wallet-value"><?= (int)$total ?></p>
                    <div class="wallet-stats">
                        <span class="chip"><?= (int)$aktif ?> aktif</span>
                        <span class="chip"><?= (int)$nonaktif ?> nonaktif</span>
                    </div>
                </section>

                <?php if ($total === 0): ?>
                    <div class="empty">
                        <div class="ico"><?= icon('truck', 28) ?></div>
                        <h2>Belum ada pengepul</h2>
                        <p>Tambahkan pengepul pertama untuk mulai mencatat penjualan sampah.</p>
                        <a href="<?= base_url('pengelola/pengepul/add') ?>" class="btn btn-primary"><?= icon('plus', 20) ?>Tambah Pengepul</a>
                    </div>
                <?php else: ?>
                    <div class="search">
                        <?= icon('search', 20) ?>
                        <input type="search" id="cari" placeholder="Cari nama, no HP, atau alamat" aria-label="Cari pengepul" autocomplete="off">
                    </div>
                    <p class="sr-only" id="hasil" aria-live="polite"></p>

                    <ul class="list" id="daftar">
                        <?php foreach ($pengepulList as $p):
                            $nama   = $p['nama'] ?? '';
                            $hp     = ($p['no_hp'] ?? '') !== '' ? $p['no_hp'] : '-';
                            $alamat = ($p['alamat'] ?? '') !== '' ? $p['alamat'] : '-';
                            $isOn   = (($p['status'] ?? '') === 'AKTIF');
                            $cariText = function_exists('mb_strtolower') ? mb_strtolower($nama . ' ' . $hp . ' ' . $alamat, 'UTF-8') : strtolower($nama . ' ' . $hp . ' ' . $alamat);
                        ?>
                            <li class="row<?= $isOn ? '' : ' off' ?>" data-cari="<?= $e($cariText) ?>">
                                <span class="ico"><?= icon('truck', 22) ?></span>
                                <div class="row-main">
                                    <p class="row-title"><?= $e($nama) ?></p>
                                    <p class="row-sub"><?= icon('phone', 15) ?><span><?= $e($hp) ?></span></p>
                                    <p class="row-sub"><?= icon('pin', 15) ?><span><?= $e($alamat) ?></span></p>
                                </div>
                                <div class="row-side">
                                    <span class="badge <?= $isOn ? 'badge-on' : 'badge-off' ?>"><?= $e($p['status'] ?? '-') ?></span>
                                    <div class="acts">
                                        <a href="<?= base_url('pengelola/pengepul/edit?id=' . $p['id']) ?>" class="icon-btn" aria-label="Edit <?= $e($nama) ?>" title="Edit"><?= icon('edit', 20) ?></a>
                                        <?php if (($p['status'] ?? 'AKTIF') === 'AKTIF') { ?>
                                        <form method="post" action="<?= base_url('pengelola/pengepul/nonaktifkan') ?>" class="inline-form">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                            <button type="submit" class="icon-btn danger" data-konfirm="Nonaktifkan pengepul <?= $e($nama) ?>? Riwayat transaksinya tetap tersimpan dan pengepul bisa diaktifkan kembali." aria-label="Nonaktifkan <?= $e($nama) ?>" title="Nonaktifkan"><?= icon('trash', 20) ?></button>
                                        </form>
                                        <?php } ?>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="no-result" id="kosong">Tidak ada pengepul yang cocok dengan pencarian.</div>
                <?php endif; ?>
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

        // Konfirmasi hapus
        document.addEventListener('click', function (ev) {
            var a = ev.target.closest ? ev.target.closest('[data-konfirm]') : null;
            if (a && !window.confirm(a.getAttribute('data-konfirm'))) { ev.preventDefault(); }
        });

        // Pencarian
        var input = document.getElementById('cari');
        if (input) {
            var rows = document.querySelectorAll('#daftar .row');
            var kosong = document.getElementById('kosong');
            var hasil = document.getElementById('hasil');
            input.addEventListener('input', function () {
                var q = input.value.trim().toLowerCase();
                var n = 0;
                for (var i = 0; i < rows.length; i++) {
                    var cocok = q === '' || rows[i].getAttribute('data-cari').indexOf(q) !== -1;
                    rows[i].hidden = !cocok;
                    if (cocok) { n++; }
                }
                kosong.style.display = n === 0 ? 'block' : 'none';
                hasil.textContent = q === '' ? '' : n + ' pengepul ditemukan';
            });
        }
    })();
    </script>
</body>
</html>