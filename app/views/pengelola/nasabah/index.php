<?php
/**
 * Daftar Nasabah (Pengelola)
 * Compatible dengan PHP 7.3+
 *
 * Variabel dari controller:
 *   $nasabahList    baris yang tampil pada halaman ini. Tiap baris: id, no_anggota,
 *                   nama, no_hp, nik, tipe, saldo, status, saldo_tersedia
 *   $totalNasabah   jumlah nasabah yang cocok dengan filter (bukan hanya halaman ini)
 *   $totalAktif     jumlah nasabah aktif yang cocok dengan filter
 *   $totalSaldo     total saldo nasabah yang cocok dengan filter
 *   $page           halaman sekarang
 *   $totalPages     jumlah halaman
 *   $cari           kata kunci pencarian
 *   $status         filter status: AKTIF, NONAKTIF, atau kosong
 *   $flash          (opsional)
 */

if (!isset($flash) && function_exists('getFlashMessage')) {
    $flash = getFlashMessage();
}
include VIEW_PATH . '/pengelola/partials/ui.php';

$e = function ($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};
$hurufAwal = function ($v) {
    $v = trim((string) $v);
    if ($v === '') {
        return '?';
    }
    return function_exists('mb_substr') ? mb_strtoupper(mb_substr($v, 0, 1, 'UTF-8'), 'UTF-8') : strtoupper(substr($v, 0, 1));
};

$nasabahList = (isset($nasabahList) && is_array($nasabahList)) ? $nasabahList : [];
$cari = isset($cari) ? (string) $cari : '';
$statusFilter = isset($status) ? strtoupper((string) $status) : '';
if ($statusFilter !== 'AKTIF' && $statusFilter !== 'NONAKTIF') {
    $statusFilter = '';
}

$total = isset($totalNasabah) ? (int) $totalNasabah : count($nasabahList);
$aktif = isset($totalAktif) ? (int) $totalAktif : 0;
$nonaktif = isset($totalNonaktif) ? (int) $totalNonaktif : max(0, $total - $aktif);
$saldoSemua = isset($totalSaldo) ? (float) $totalSaldo : 0.0;

$page = isset($page) ? max(1, (int) $page) : 1;
$totalPages = isset($totalPages) ? max(1, (int) $totalPages) : 1;

/* Susun query string untuk tautan filter dan paginasi. */
$tautan = function (array $ubah) use ($cari, $statusFilter): string {
    $params = [];
    if ($cari !== '') {
        $params['q'] = $cari;
    }
    if ($statusFilter !== '') {
        $params['status'] = $statusFilter;
    }
    foreach ($ubah as $k => $v) {
        if ($v === '' || $v === null) {
            unset($params[$k]);
        } else {
            $params[$k] = $v;
        }
    }
    return $params ? ('?' . http_build_query($params)) : '';

};

$menuAktif = 'pengelola/nasabah';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1b5e20">
    <title>Data Nasabah - <?= $e($appName) ?></title>
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
    <?php include VIEW_PATH . '/pengelola/partials/style.php'; ?>
    <style>
        /* Kartu ringkasan */
        .wallet { position: relative; overflow: hidden; color: #fff; border-radius: 24px; padding: 1.5rem 1.75rem; margin-bottom: 1.25rem;
            background: linear-gradient(135deg, #43a047 0%, #2e7d32 45%, #14501a 100%); box-shadow: 0 14px 32px rgba(27,94,32,.22); }
        .wallet::before, .wallet::after { content: ""; position: absolute; border-radius: 50%; background: rgba(255,255,255,.07); pointer-events: none; }
        .wallet::before { width: 260px; height: 260px; right: -90px; top: -120px; }
        .wallet::after { width: 180px; height: 180px; right: 80px; bottom: -120px; }
        .wallet > * { position: relative; z-index: 1; }
        .wallet-label { font-size: 1rem; font-weight: 600; color: rgba(255,255,255,.95); }
        .wallet-value { margin-top: .5rem; font-size: 2.25rem; font-weight: 800; letter-spacing: -.04em; line-height: 1.1; font-variant-numeric: tabular-nums; overflow-wrap: anywhere; }
        .chips { display: flex; flex-wrap: wrap; gap: .625rem; margin-top: 1rem; }
        .chip { padding: .375rem .875rem; border-radius: 999px; background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.18); font-size: .9rem; font-weight: 600; }

        /* Pencarian & filter */
        .search { position: relative; margin-bottom: .75rem; display: flex; gap: .5rem; }
        .search .grow { position: relative; flex: 1; min-width: 0; }
        .search .grow svg { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); width: 20px; height: 20px; color: var(--ink-3); pointer-events: none; }
        .search .grow input { padding-left: 3rem; padding-right: 3rem; }
        .search .clear { position: absolute; right: .375rem; top: 50%; transform: translateY(-50%); width: 40px; height: 40px; border: 0; border-radius: 12px; cursor: pointer; display: grid; place-items: center; background: transparent; color: var(--ink-3); text-decoration: none; }
        .search .clear:hover { background: var(--g-100); color: var(--g-800); }
        .filters { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: .875rem; }
        .fbtn { min-height: 44px; padding: 0 1.125rem; border-radius: 999px; border: 1.5px solid var(--line); background: var(--card); font: inherit; font-weight: 600; color: var(--ink-2); cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; }
        .fbtn:hover { background: var(--g-50); }
        .fbtn[aria-pressed="true"] { background: var(--g-100); border-color: var(--g-600); color: var(--g-800); }

        /* Daftar */
        .nlist { list-style: none; background: var(--card); border: 1px solid var(--line); border-radius: 20px; overflow: hidden; margin: 0; padding: 0; }
        .nrow { display: grid; grid-template-columns: 46px minmax(0, 1fr) auto auto; grid-template-areas: "av main end act"; gap: .25rem 1rem; align-items: center; padding: 1rem 1.125rem; border-bottom: 1px solid var(--line); }
        .nrow:last-child { border-bottom: 0; }
        .av { grid-area: av; width: 46px; height: 46px; border-radius: 50%; background: var(--g-100); color: var(--g-700); display: grid; place-items: center; font-weight: 800; font-size: 1.0625rem; }
        .nrow.off .av { background: #eef1ee; color: var(--ink-3); }
        .n-main { grid-area: main; min-width: 0; }
        .n-name { font-weight: 700; color: var(--ink); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .n-sub { display: flex; align-items: center; gap: .375rem; font-size: .9rem; color: var(--ink-3); min-width: 0; }
        .n-sub svg { width: 15px; height: 15px; flex-shrink: 0; }
        .n-sub span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .n-end { grid-area: end; display: flex; flex-direction: column; align-items: flex-end; gap: .375rem; }
        .n-saldo { font-weight: 800; color: var(--g-700); font-variant-numeric: tabular-nums; white-space: nowrap; text-align: right; }
        .n-saldo small { display: block; font-size: .78rem; font-weight: 600; color: var(--ink-3); }
        .n-avail { font-size: .8125rem; color: var(--ink-3); font-variant-numeric: tabular-nums; }
        .tags { display: flex; gap: .375rem; flex-wrap: wrap; justify-content: flex-end; }
        .n-act { grid-area: act; display: flex; gap: .375rem; }
        .n-act form { margin: 0; }
        .icon-btn { width: 44px; height: 44px; border-radius: 12px; display: grid; place-items: center; border: 1.5px solid var(--g-200); color: var(--g-800); background: var(--card); text-decoration: none; cursor: pointer; transition: background .18s var(--ease); }
        .icon-btn:hover { background: var(--g-100); }
        .icon-btn svg { width: 20px; height: 20px; }
        .icon-btn.danger { border-color: #f3c9c5; color: var(--rose); }
        .icon-btn.danger:hover { background: var(--rose-bg); }

        .no-result { display: none; text-align: center; padding: 1.75rem 1rem; color: var(--ink-3); }
        .empty-card { text-align: center; padding: 3rem 1.5rem; background: var(--card); border: 1px solid var(--line); border-radius: 20px; }
        .empty-card .ico { width: 64px; height: 64px; margin: 0 auto .875rem; border-radius: 50%; background: var(--g-100); color: var(--g-700); display: grid; place-items: center; }
        .empty-card .ico svg { width: 28px; height: 28px; }
        .empty-card h2 { font-size: 1.125rem; margin-bottom: .25rem; color: var(--ink); }
        .empty-card p { color: var(--ink-3); margin-bottom: 1.25rem; }

        .pager { display: flex; flex-wrap: wrap; justify-content: center; align-items: center; gap: .375rem; margin-top: 1.25rem; }
        .pager a, .pager span.cur { min-width: 44px; height: 44px; padding: 0 .75rem; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; text-decoration: none; }
        .pager a { color: var(--g-800); background: var(--card); border: 1.5px solid var(--line); }
        .pager a:hover { background: var(--g-100); }
        .pager a svg { width: 18px; height: 18px; }
        .pager span.cur { color: #fff; background: linear-gradient(135deg, var(--g-600), var(--g-700)); }
        .hint-page { text-align: center; font-size: .875rem; color: var(--ink-3); margin-top: .75rem; }

        @media (max-width: 720px) {
            .wallet { padding: 1.25rem 1.125rem; border-radius: 22px; }
            .wallet-value { font-size: 2rem; }
            .page-head .btn-primary { width: 100%; }
            .search { flex-wrap: wrap; }
            .search .grow { flex: 1 1 100%; }
            .search .btn-primary { width: 100%; }
            .nrow { grid-template-columns: 46px minmax(0, 1fr) auto; grid-template-areas: "av main act" "av end end"; }
            .n-end { flex-direction: row-reverse; justify-content: space-between; align-items: center; margin-top: .5rem; }
            .n-saldo small { display: inline; margin-right: .375rem; }
            .tags { justify-content: flex-start; }
            .n-act { grid-area: act; align-self: start; }
        }
    </style>
</head>
<body>
    <div class="scrim" id="scrim"></div>
    <?php include VIEW_PATH . '/pengelola/partials/sidebar.php'; ?>

    <div class="main">
        <header class="topbar">
            <button type="button" class="burger" id="burger" aria-controls="sidebar" aria-expanded="false" aria-label="Buka atau tutup menu"><?= icon('menu') ?></button>
            <span class="top-title">Nasabah</span>
            <span class="avatar" title="<?= $e($namaUser) ?>" aria-hidden="true"><?= $e($inisial) ?></span>
        </header>

        <main class="page">
            <a class="back" href="<?= base_url('pengelola/dashboard') ?>"><?= icon('arrow-left') ?>Dashboard</a>

            <?php if ($flash && !empty($flash['message'])): ?>
                <div class="flash flash-<?= $flashClass ?>" role="status"><?= icon($flashIcon) ?><span><?= $e($flash['message']) ?></span></div>
            <?php endif; ?>

            <div class="page-head">
                <div>
                    <h1 class="page-title">Data Nasabah</h1>
                    <p class="page-sub">Daftarkan nasabah, buatkan akun aplikasinya, dan pantau saldonya.</p>
                </div>
                <a href="<?= base_url('pengelola/nasabah/add') ?>" class="btn btn-primary"><?= icon('user-plus') ?>Tambah Nasabah</a>
            </div>

            <section class="wallet" aria-label="Ringkasan nasabah">
                <p class="wallet-label">Total saldo seluruh nasabah</p>
                <p class="wallet-value"><?= $e(formatRupiah($saldoSemua)) ?></p>
                <div class="chips">
                    <span class="chip"><?= (int) $total ?> nasabah</span>
                    <span class="chip"><?= (int) $aktif ?> aktif</span>
                    <span class="chip"><?= (int) $nonaktif ?> nonaktif</span>
                </div>
            </section>

            <form method="GET" action="<?= base_url('pengelola/nasabah') ?>" class="search" role="search">
                <?php if ($statusFilter !== ''): ?>
                    <input type="hidden" name="status" value="<?= $e($statusFilter) ?>">
                <?php endif; ?>
                <div class="grow">
                    <?= icon('search') ?>
                    <input type="search" name="q" id="cari" class="input" value="<?= $e($cari) ?>" placeholder="Cari nama, nomor anggota, nomor HP, atau NIK" aria-label="Cari nasabah" autocomplete="off">
                    <?php if ($cari !== ''): ?>
                        <a class="clear" href="<?= base_url('pengelola/nasabah' . $tautan(['q' => '', 'page' => ''])) ?>" aria-label="Hapus pencarian">&times;</a>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-primary">Cari</button>
            </form>

            <div class="filters" role="group" aria-label="Filter status">
                <a class="fbtn" href="<?= base_url('pengelola/nasabah' . $tautan(['status' => '', 'page' => ''])) ?>" aria-pressed="<?= $statusFilter === '' ? 'true' : 'false' ?>">Semua</a>
                <a class="fbtn" href="<?= base_url('pengelola/nasabah' . $tautan(['status' => 'AKTIF', 'page' => ''])) ?>" aria-pressed="<?= $statusFilter === 'AKTIF' ? 'true' : 'false' ?>">Aktif</a>
                <a class="fbtn" href="<?= base_url('pengelola/nasabah' . $tautan(['status' => 'NONAKTIF', 'page' => ''])) ?>" aria-pressed="<?= $statusFilter === 'NONAKTIF' ? 'true' : 'false' ?>">Nonaktif</a>
            </div>

            <?php if (empty($nasabahList)): ?>
                <div class="empty-card">
                    <div class="ico"><?= icon('users') ?></div>
                    <?php if ($cari !== '' || $statusFilter !== ''): ?>
                        <h2>Tidak ada nasabah yang cocok</h2>
                        <p>Ubah kata kunci atau pilih filter "Semua" untuk melihat seluruh nasabah.</p>
                        <a href="<?= base_url('pengelola/nasabah') ?>" class="btn btn-ghost">Tampilkan semua nasabah</a>
                    <?php else: ?>
                        <h2>Belum ada nasabah</h2>
                        <p>Daftarkan nasabah pertama agar setoran mereka bisa dicatat ke tabungan.</p>
                        <a href="<?= base_url('pengelola/nasabah/add') ?>" class="btn btn-primary"><?= icon('user-plus') ?>Tambah Nasabah</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <ul class="nlist" id="daftar">
                    <?php foreach ($nasabahList as $n):
                        $nama = (string) ($n['nama'] ?? '');
                        $noAnggota = ($n['no_anggota'] ?? '') !== '' ? (string) $n['no_anggota'] : '-';
                        $hp = ($n['no_hp'] ?? '') !== '' ? (string) $n['no_hp'] : '-';
                        $status = strtoupper((string) ($n['status'] ?? 'AKTIF'));
                        $isOn = ($status === 'AKTIF');
                        $tipe = strtoupper((string) ($n['tipe'] ?? ''));
                        $tipeLabel = $tipe === 'INSTANSI' ? 'Instansi' : ($tipe === 'PERORANGAN' ? 'Perorangan' : '');
                        $nid = isset($n['id']) ? (string) $n['id'] : '';
                        $tersedia = isset($n['saldo_tersedia']) ? (float) $n['saldo_tersedia'] : (float) ($n['saldo'] ?? 0);
                        $adaTersedia = $tersedia < (float) ($n['saldo'] ?? 0) - 0.005;
                    ?>
                        <li class="nrow<?= $isOn ? '' : ' off' ?>">
                            <span class="av" aria-hidden="true"><?= $e($hurufAwal($nama)) ?></span>
                            <div class="n-main">
                                <p class="n-name"><?= $e($nama !== '' ? $nama : '-') ?></p>
                                <p class="n-sub"><?= icon('id') ?><span><?= $e($noAnggota) ?></span></p>
                                <p class="n-sub"><?= icon('phone') ?><span><?= $e($hp) ?></span></p>
                            </div>
                            <div class="n-end">
                                <span class="n-saldo"><small>Saldo</small><?= $e(formatRupiah($n['saldo'] ?? 0)) ?></span>
                                <?php if ($adaTersedia): ?><span class="n-avail">Bisa dicairkan <?= $e(formatRupiah($tersedia)) ?></span><?php endif; ?>
                                <span class="tags">
                                    <span class="badge <?= $isOn ? 'badge-on' : 'badge-grey' ?>"><?= $isOn ? 'Aktif' : 'Nonaktif' ?></span>
                                    <?php if ($tipeLabel !== ''): ?><span class="badge badge-info"><?= $e($tipeLabel) ?></span><?php endif; ?>
                                </span>
                            </div>
                            <div class="n-act">
                                <?php if ($nid !== ''): ?>
                                    <a href="<?= base_url('pengelola/nasabah/edit?id=' . rawurlencode($nid)) ?>" class="icon-btn" aria-label="Ubah <?= $e($nama) ?>" title="Ubah data"><?= icon('edit') ?></a>
                                    <form method="POST" action="<?= base_url('pengelola/nasabah/' . ($isOn ? 'nonaktifkan' : 'aktifkan')) ?>"
                                          data-confirm="<?= $e($isOn ? 'Nonaktifkan nasabah ' . $nama . '? Riwayatnya tetap tersimpan.' : 'Aktifkan kembali nasabah ' . $nama . '?') ?>">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="id" value="<?= $e($nid) ?>">
                                        <button type="submit" class="icon-btn <?= $isOn ? 'danger' : '' ?>"
                                                aria-label="<?= $isOn ? 'Nonaktifkan' : 'Aktifkan kembali' ?> <?= $e($nama) ?>"
                                                title="<?= $isOn ? 'Nonaktifkan' : 'Aktifkan kembali' ?>">
                                            <?= icon($isOn ? 'ban' : 'refresh') ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <?php if ($totalPages > 1): ?>
                    <nav class="pager" aria-label="Halaman">
                        <?php if ($page > 1): ?>
                            <a href="<?= base_url('pengelola/nasabah' . $tautan(['page' => $page - 1])) ?>" aria-label="Halaman sebelumnya"><?= icon('chevron-left') ?></a>
                        <?php endif; ?>
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <?php if ($i === $page): ?>
                                <span class="cur" aria-current="page"><?= $i ?></span>
                            <?php else: ?>
                                <a href="<?= base_url('pengelola/nasabah' . $tautan(['page' => $i])) ?>"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?>
                            <a href="<?= base_url('pengelola/nasabah' . $tautan(['page' => $page + 1])) ?>" aria-label="Halaman berikutnya"><?= icon('chevron-right') ?></a>
                        <?php endif; ?>
                    </nav>
                    <p class="hint-page">Halaman <?= (int) $page ?> dari <?= (int) $totalPages ?>.<?= $cari !== '' ? ' Pencarian: "' . $e($cari) . '".' : '' ?></p>
                <?php endif; ?>
            <?php endif; ?>
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

        /* Konfirmasi sebelum menonaktifkan atau mengaktifkan kembali nasabah. */
        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (ev) {
                if (!window.confirm(form.getAttribute('data-confirm'))) {
                    ev.preventDefault();
                }
            });
        });
    })();
    </script>
</body>
</html>
