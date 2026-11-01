<?php
/**
 * desa/index.php
 * Kelola Desa - Daftar
 * Compatible dengan PHP 7.3+
 *
 * Variabel dari controller: $desas (array), $flash (opsional)
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
            'check' => '<path d="M5 12.5l4.5 4.5L19 7"/>',
            'alert' => '<path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/>',
            'building' => '<path d="M4 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16"/><path d="M16 9h2a2 2 0 0 1 2 2v10M2 21h20M8 7h4M8 11h4M8 15h4"/>',
            'pin' => '<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
            'settings' => '<path d="M4 6h8M16 6h4M4 12h4M12 12h8M4 18h10M18 18h2"/><circle cx="14" cy="6" r="2"/><circle cx="10" cy="12" r="2"/><circle cx="16" cy="18" r="2"/>',
            'plus' => '<path d="M12 5v14M5 12h14"/>',
            'info' => '<circle cx="12" cy="12" r="9"/><line x1="12" y1="11" x2="12" y2="16"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
            'edit' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
            'trash' => '<path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6M10 11v6M14 11v6"/>',
            'search' => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>',
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

$desas = (isset($desas) && is_array($desas)) ? $desas : [];
$jumlah = count($desas);

$menu = [
    ['admin/dashboard',   'Dashboard',          'grid'],
    ['admin/users',       'Kelola Pengguna',    'users'],
    ['admin/nasabah',     'Kelola Nasabah',     'user'],
    ['admin/bank-sampah', 'Kelola Bank Sampah', 'building'],
    ['admin/desa',        'Kelola Desa',        'pin'],
  //  ['admin/pengaturan',  'Pengaturan',         'settings'],
];
$menuAktif = 'admin/desa';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1b5e20">
    <title>Kelola Desa - <?= APP_NAME ?></title>
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
        .b-green { background:var(--g-100); color:var(--g-800); }
        .b-grey { background:#eef1ee; color:var(--ink-3); }

        .ico { width:46px; height:46px; border-radius:50%; flex:none; background:var(--g-100); color:var(--g-700); display:grid; place-items:center; }
        .ico svg { width:22px; height:22px; }

        .sec-head { display:flex; align-items:center; justify-content:space-between; gap:12px; margin:0 0 14px; }
        .sec-title { margin:0; font-size:1.125rem; font-weight:700; letter-spacing:-.01em; }

        /* Pencarian */
        .search { position:relative; margin-bottom:14px; }
        .search svg { position:absolute; left:16px; top:50%; width:20px; height:20px; margin-top:-10px; color:var(--ink-3); pointer-events:none; }
        .search input { width:100%; height:52px; padding:0 16px 0 46px; border:1.5px solid var(--line); border-radius:16px; background:#fff; font:inherit; color:var(--ink); }
        .search input::placeholder { color:var(--ink-3); opacity:.8; }
        .search input:focus { outline:none; border-color:var(--g-600); box-shadow:0 0 0 4px rgba(67,160,71,.15); }

        /* Daftar */
        .list { list-style:none; margin:0; padding:0; background:#fff; border:1px solid var(--line); border-radius:20px; overflow:hidden; }
        .list li { display:grid; grid-template-columns:46px minmax(0,1fr) auto; grid-template-areas:"ico main act"; gap:6px 16px; align-items:center; padding:14px 18px; border-bottom:1px solid var(--line); }
        .list li:last-child { border-bottom:0; }
        .list li[hidden] { display:none; }
        .list .ico { grid-area:ico; }
        .row-main { grid-area:main; min-width:0; }
        .row-title { margin:0; font-weight:700; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .row-sub { margin:2px 0 0; font-size:.9rem; color:var(--ink-3); overflow-wrap:anywhere; }
        .row-meta { margin-top:6px; }
        .acts { grid-area:act; display:flex; gap:8px; }
        .no-result { display:none; text-align:center; padding:28px 16px; color:var(--ink-3); }
        .no-result.show { display:block; }

        .empty { text-align:center; padding:40px 24px; background:#fff; border:1px solid var(--line); border-radius:20px; }
        .empty .ico { margin:0 auto 14px; width:64px; height:64px; }
        .empty .ico svg { width:28px; height:28px; }
        .empty h3 { margin:0 0 4px; font-size:1.0625rem; }
        .empty p { margin:0 auto 18px; max-width:40ch; color:var(--ink-3); }

        .sr-only { position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; border:0; }

        @media (max-width:720px) {
            .content { padding:18px 16px 56px; }
            .topbar { padding:0 16px; }
            h1 { font-size:1.5rem; }
            .page-head .btn { width:100%; }
            .list li { grid-template-columns:46px minmax(0,1fr); grid-template-areas:"ico main" "ico act"; }
            .acts { margin-top:6px; }
        }
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
            <span class="top-title">Kelola Desa</span>
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
                        <h1>Kelola Desa</h1>
                        <p class="lead">Atur data desa yang terdaftar di sistem.</p>
                    </div>
                    <a href="<?= base_url('admin/desa/add') ?>" class="btn btn-primary"><?= icon('plus') ?>Tambah desa</a>
                </div>

                <section aria-labelledby="daftar-title">
                    <div class="sec-head">
                        <h2 class="sec-title" id="daftar-title">Daftar desa</h2>
                        <span class="badge b-green"><?= number_format($jumlah, 0, ',', '.') ?> desa</span>
                    </div>

                    <?php if (empty($desas)): ?>
                        <div class="empty">
                            <div class="ico"><?= icon('pin') ?></div>
                            <h3>Belum ada data desa</h3>
                            <p>Desa yang sudah ditambahkan akan muncul di sini.</p>
                            <a class="btn btn-primary" href="<?= base_url('admin/desa/add') ?>"><?= icon('plus') ?>Tambah desa</a>
                        </div>
                    <?php else: ?>
                        <div class="search">
                            <?= icon('search') ?>
                            <label for="cari" class="sr-only">Cari desa</label>
                            <input type="search" id="cari" placeholder="Cari nama desa, kecamatan, atau kabupaten" autocomplete="off">
                        </div>

                        <ul class="list" id="daftar">
                            <?php foreach ($desas as $d):
                                $nama = (string) (isset($d['nama']) ? $d['nama'] : '');
                                $kode = trim((string) (isset($d['kode_wilayah']) ? $d['kode_wilayah'] : ''));
                                $kec = (string) (isset($d['kecamatan']) ? $d['kecamatan'] : '');
                                $kab = (string) (isset($d['kabupaten']) ? $d['kabupaten'] : '');
                                $id = isset($d['id']) ? $d['id'] : '';
                                $cari = function_exists('mb_strtolower')
                                    ? mb_strtolower($nama . ' ' . $kec . ' ' . $kab . ' ' . $kode, 'UTF-8')
                                    : strtolower($nama . ' ' . $kec . ' ' . $kab . ' ' . $kode);
                            ?>
                                <li data-cari="<?= $e($cari) ?>">
                                    <span class="ico" aria-hidden="true"><?= icon('pin') ?></span>
                                    <div class="row-main">
                                        <p class="row-title"><?= $e($nama !== '' ? $nama : '-') ?></p>
                                        <p class="row-sub">Kec. <?= $e($kec !== '' ? $kec : '-') ?>, <?= $e($kab !== '' ? $kab : '-') ?></p>
                                        <div class="row-meta">
                                            <span class="badge <?= $kode !== '' ? 'b-green' : 'b-grey' ?>">Kode: <?= $e($kode !== '' ? $kode : '-') ?></span>
                                        </div>
                                    </div>
                                    <div class="acts">
                                        <a href="<?= base_url('admin/desa/edit?id=' . $id) ?>" class="btn btn-ghost btn-sm" aria-label="Ubah desa <?= $e($nama) ?>"><?= icon('edit') ?>Ubah</a>
                                        <a href="<?= base_url('admin/desa/delete?id=' . $id) ?>" class="btn btn-danger-ghost btn-sm" data-nama="<?= $e($nama) ?>" aria-label="Hapus desa <?= $e($nama) ?>"><?= icon('trash') ?>Hapus</a>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="no-result" id="noResult" role="status" aria-live="polite">Tidak ada desa yang cocok dengan pencarian.</div>
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

        // Konfirmasi hapus
        var links = document.querySelectorAll('a[data-nama]');
        Array.prototype.forEach.call(links, function (a) {
            a.addEventListener('click', function (ev) {
                if (!window.confirm('Yakin hapus desa "' + a.getAttribute('data-nama') + '"?')) {
                    ev.preventDefault();
                }
            });
        });

        // Pencarian di halaman
        var input = document.getElementById('cari');
        var items = document.querySelectorAll('#daftar li');
        var noRes = document.getElementById('noResult');
        if (input) {
            input.addEventListener('input', function () {
                var q = input.value.toLowerCase().replace(/^\s+|\s+$/g, '');
                var shown = 0;
                Array.prototype.forEach.call(items, function (li) {
                    var cocok = q === '' || (li.getAttribute('data-cari') || '').indexOf(q) !== -1;
                    li.hidden = !cocok;
                    if (cocok) { shown++; }
                });
                noRes.classList.toggle('show', shown === 0);
            });
        }
    })();
    </script>
</body>
</html>