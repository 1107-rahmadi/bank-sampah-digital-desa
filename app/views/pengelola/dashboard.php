<?php
/**
 * pengelola/dashboard.php
 * Beranda Pengelola.
 *
 * Compatible dengan PHP 7.3+
 *
 * Halaman ini memakai kerangka satu halaman yang sama dengan seluruh
 * halaman pengelola:
 *   partials/ui.php      -> helper + variabel header
 *   partials/sidebar.php -> menu samping
 *   partials/topbar.php  -> judul + tombol menu
 *   MANAGER.css / MANAGER.js -> gaya dan perilaku bersama
 *
 * @var array|null $bankInfo        baris bank_sampah (nama)
 * @var array      $statsBeli       total_transaksi, total_berat, total_nilai,
 *                                  total_tabungan, total_tunai
 * @var array      $statsJual       total_transaksi, total_berat, total_nilai,
 *                                  total_lunas, total_belum_bayar
 * @var array      $recentBeli      5 transaksi beli terakhir
 * @var array      $recentJual      5 transaksi jual terakhir
 * @var array      $stokList        stok per kategori (berat_kg)
 * @var array|null $statsPencairan  jumlah & nilai per status pencairan
 * @var array|null $flash
 */

require_once VIEW_PATH . '/pengelola/partials/ui.php';

$menuAktif = 'pengelola/dashboard';
$judulHalaman = 'Dashboard';

$bankInfo = isset($bankInfo) && is_array($bankInfo) ? $bankInfo : null;
$statsBeli = isset($statsBeli) && is_array($statsBeli) ? $statsBeli : [];
$statsJual = isset($statsJual) && is_array($statsJual) ? $statsJual : [];
$recentBeli = isset($recentBeli) && is_array($recentBeli) ? $recentBeli : [];
$recentJual = isset($recentJual) && is_array($recentJual) ? $recentJual : [];
$stokList = isset($stokList) && is_array($stokList) ? $stokList : [];
$statsPencairan = isset($statsPencairan) && is_array($statsPencairan) ? $statsPencairan : [];

$namaBank = (string) ($bankInfo['nama'] ?? 'bank sampah');

// Sapaan selon jam.
$jam = (int) date('G');
if ($jam < 11) {
    $sapaan = 'Selamat pagi';
} elseif ($jam < 15) {
    $sapaan = 'Selamat siang';
} elseif ($jam < 18) {
    $sapaan = 'Selamat sore';
} else {
    $sapaan = 'Selamat malam';
}

// Ringkasan angka.
$jumlahBeli = (int) ($statsBeli['total_transaksi'] ?? 0);
$nilaiBeli = (float) ($statsBeli['total_nilai'] ?? 0);
$jumlahJual = (int) ($statsJual['total_transaksi'] ?? 0);
$nilaiJual = (float) ($statsJual['total_nilai'] ?? 0);
$piutang = (float) ($statsJual['total_belum_bayar'] ?? 0);

// Total stok di gudang, dalam kilogram (bukan sen-kg).
$totalStokKg = 0.0;
foreach ($stokList as $s) {
    $totalStokKg += (float) ($s['berat_kg'] ?? 0);
}

// Pengajuan pencairan yang masih menunggu tindakan pengelola.
$jumlahTertahan = (int) ($statsPencairan['DIAJUKAN']['jumlah'] ?? 0)
    + (int) ($statsPencairan['DISETUJUI']['jumlah'] ?? 0);
$nilaiTertahan = (float) ($statsPencairan['DIAJUKAN']['total_nilai'] ?? 0)
    + (float) ($statsPencairan['DISETUJUI']['total_nilai'] ?? 0);

// Stok: tampil 5 kategori pertama.
$stokTampil = array_slice($stokList, 0, 5);
$stokSisa = count($stokList) - count($stokTampil);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?= pengelolaHead($judulHalaman) ?>
    <style>
        /* Gaya khusus halaman beranda. */
        .greet { margin-bottom: 20px; }
        .greet-hi { margin: 0; font-size: .9375rem; color: var(--ink-3); }
        .greet-name { margin: 2px 0 0; font-size: 1.75rem; font-weight: 800; line-height: 1.2; letter-spacing: -.03em; color: var(--ink); overflow-wrap: anywhere; }
        .greet-sub { margin: 4px 0 0; color: var(--ink-3); }

        .wallet-label { display: flex; align-items: center; gap: 8px; }
        .wallet-split { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; margin-top: 20px; }
        .split { padding: 12px 16px; border-radius: 16px; background: rgba(255, 255, 255, .14); border: 1px solid rgba(255, 255, 255, .18); }
        .split-label { font-size: .875rem; color: rgba(255, 255, 255, .92); }
        .split-value { margin-top: 2px; font-size: 1.1875rem; font-weight: 700; letter-spacing: -.02em; font-variant-numeric: tabular-nums; }

        /* Kartu penanda pengajuan pencairan yang perlu ditangani. */
        .notice-card { display: flex; align-items: center; gap: 14px; min-height: 64px; margin-bottom: 20px; padding: 14px 18px; border-radius: 20px; background: var(--card); border: 1px solid var(--line); text-decoration: none; color: inherit; }
        .notice-card:hover { border-color: var(--g-200); box-shadow: 0 10px 24px rgba(27, 94, 32, .1); }
        .notice-card.pending { background: var(--amber-bg); border-color: #f6d9a0; }
        .notice-card.pending .ico { background: #fff; color: var(--amber); }
        .notice-text { flex: 1; min-width: 0; }
        .notice-title { display: block; font-weight: 700; color: var(--ink); }
        .notice-card.pending .notice-title { color: var(--amber); }
        .notice-sub { display: block; font-size: .9rem; color: var(--ink-3); }
        .notice-card.pending .notice-sub { color: #7a4200; }
        .chev { width: 18px; height: 18px; flex: none; color: var(--ink-3); }

        /* Aksi cepat */
        .actions { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; margin-bottom: 22px; }
        .action { display: flex; align-items: center; gap: 14px; min-height: 64px; padding: 14px 18px; border-radius: 20px; background: var(--card); border: 1px solid var(--line); text-decoration: none; color: var(--ink); font-weight: 600; }
        .action:hover { border-color: var(--g-200); box-shadow: 0 10px 24px rgba(27, 94, 32, .1); }
        .action-text { flex: 1; min-width: 0; }
        .action-sub { display: block; font-size: .9rem; font-weight: 500; color: var(--ink-3); }

        .cols { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 360px), 1fr)); gap: 24px; align-items: start; }
        .sec-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin: 0 0 10px; }
        .sec-title { margin: 0; font-size: 1.125rem; font-weight: 700; letter-spacing: -.01em; }
        .link-more { display: inline-flex; align-items: center; gap: 4px; min-height: 44px; padding: 0 10px; border-radius: 12px; color: var(--g-700); font-weight: 600; text-decoration: none; }
        .link-more:hover { background: var(--g-100); }
        .link-more svg { width: 16px; height: 16px; }
        .list-more { margin: 0; padding: 12px 0 14px; text-align: center; font-size: .9rem; color: var(--ink-3); border-top: 1px solid var(--line); }

        @media (max-width: 560px) {
            .actions { grid-template-columns: minmax(0, 1fr); }
            .wallet-split { grid-template-columns: minmax(0, 1fr); gap: 10px; }
            .greet-name { font-size: 1.5rem; }
        }
    </style>
</head>
<body>
    <?php include VIEW_PATH . '/pengelola/partials/sidebar.php'; ?>
    <div class="scrim" id="scrim"></div>

    <div class="main">
        <?php include VIEW_PATH . '/pengelola/partials/topbar.php'; ?>

        <main class="content">
            <div class="wrap">
                <?php if (!empty($flash) && !empty($flash['message'])): ?>
                    <div class="notice notice-<?= htmlspecialchars($flashClass, ENT_QUOTES, 'UTF-8') ?>" role="status">
                        <?= icon($flashIcon) ?><span><?= htmlspecialchars((string) $flash['message'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endif; ?>

                <div class="greet">
                    <p class="greet-hi"><?= htmlspecialchars($sapaan, ENT_QUOTES, 'UTF-8') ?>,</p>
                    <h1 class="greet-name"><?= htmlspecialchars($namaUser, ENT_QUOTES, 'UTF-8') ?></h1>
                    <p class="greet-sub">Kelola operasional harian <?= htmlspecialchars($namaBank, ENT_QUOTES, 'UTF-8') ?></p>
                </div>

                <section class="wallet" aria-label="Ringkasan operasional">
                    <span class="wallet-label"><?= icon('wallet') ?>Total nilai pembelian</span>
                    <p class="wallet-value"><?= htmlspecialchars(formatRupiah($nilaiBeli), ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="wallet-note">Seluruh sampah yang sudah dibeli, masuk tabungan maupun dibayar tunai.</p>

                    <div class="wallet-split">
                        <div class="split">
                            <div class="split-label">Transaksi beli</div>
                            <div class="split-value"><?= htmlspecialchars(number_format($jumlahBeli, 0, ',', '.'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="split">
                            <div class="split-label">Transaksi jual</div>
                            <div class="split-value"><?= htmlspecialchars(number_format($jumlahJual, 0, ',', '.'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="split">
                            <div class="split-label">Stok di gudang</div>
                            <div class="split-value"><?= htmlspecialchars(formatBeratKg($totalStokKg), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </div>
                </section>

                <a href="<?= base_url('pengelola/pencairan') ?>" class="notice-card<?= $jumlahTertahan > 0 ? ' pending' : '' ?>">
                    <span class="ico" aria-hidden="true"><?= icon($jumlahTertahan > 0 ? 'clock' : 'check-circle') ?></span>
                    <span class="notice-text">
                        <?php if ($jumlahTertahan > 0): ?>
                            <span class="notice-title"><?= htmlspecialchars(number_format($jumlahTertahan, 0, ',', '.'), ENT_QUOTES, 'UTF-8') ?> pengajuan pencairan perlu ditangani</span>
                            <span class="notice-sub">Total <?= htmlspecialchars(formatRupiah($nilaiTertahan), ENT_QUOTES, 'UTF-8') ?>. Status-diajukan atau disetujui masih menunggu uang diserahkan.</span>
                        <?php else: ?>
                            <span class="notice-title">Tidak ada pengajuan pencairan yang menunggu</span>
                            <span class="notice-sub">Semua pengajuan sudah disetujui, ditolak, atau dicairkan.</span>
                        <?php endif; ?>
                    </span>
                    <?= icon('chevron-right', 'chev') ?>
                </a>

                <nav class="actions" aria-label="Aksi cepat">
                    <a href="<?= base_url('pengelola/transaksi-beli/add') ?>" class="action">
                        <span class="ico" aria-hidden="true"><?= icon('plus') ?></span>
                        <span class="action-text">Catat Pembelian<span class="action-sub">Timbang dan catat sampah dari warga</span></span>
                        <?= icon('chevron-right', 'chev') ?>
                    </a>
                    <a href="<?= base_url('pengelola/transaksi-jual/add') ?>" class="action">
                        <span class="ico" aria-hidden="true"><?= icon('upload') ?></span>
                        <span class="action-text">Catat Penjualan<span class="action-sub">Jual sampah ke pengepul</span></span>
                        <?= icon('chevron-right', 'chev') ?>
                    </a>
                    <a href="<?= base_url('pengelola/pencairan') ?>" class="action">
                        <span class="ico" aria-hidden="true"><?= icon('banknote') ?></span>
                        <span class="action-text">Proses Pencairan<span class="action-sub">Setujui, tolak, atau cairkan</span></span>
                        <?= icon('chevron-right', 'chev') ?>
                    </a>
                    <a href="<?= base_url('pengelola/stok') ?>" class="action">
                        <span class="ico" aria-hidden="true"><?= icon('box') ?></span>
                        <span class="action-text">Cek Stok<span class="action-sub">Sisa sampah di gudang</span></span>
                        <?= icon('chevron-right', 'chev') ?>
                    </a>
                </nav>

                <div class="cols">
                    <section aria-labelledby="h-beli">
                        <div class="sec-head">
                            <h2 class="sec-title" id="h-beli">Transaksi beli terakhir</h2>
                            <a href="<?= base_url('pengelola/transaksi-beli') ?>" class="link-more">Lihat semua<?= icon('chevron-right') ?></a>
                        </div>
                        <div class="list">
                            <?php if (!empty($recentBeli)): ?>
                                <?php foreach ($recentBeli as $t):
                                    $nm = trim((string) ($t['nasabah_nama'] ?? ''));
                                    if ($nm === '') {
                                        $nm = trim((string) ($t['nama_penjual'] ?? ''));
                                    }
                                    if ($nm === '') {
                                        $nm = 'Tanpa nama';
                                    }
                                    $tanggalBeli = tanggalSingkatIndonesia($t['tanggal'] ?? '');
                                ?>
                                    <a href="<?= base_url('pengelola/transaksi-beli/detail?id=' . (int) ($t['id'] ?? 0)) ?>" class="row">
                                        <span class="ico" aria-hidden="true"><?= icon('cart') ?></span>
                                        <span class="row-main">
                                            <span class="row-title"><?= htmlspecialchars($nm, ENT_QUOTES, 'UTF-8') ?></span>
                                            <span class="row-sub"><?= htmlspecialchars($tanggalBeli, ENT_QUOTES, 'UTF-8') ?></span>
                                        </span>
                                        <span class="row-end">
                                            <span class="row-amount plus"><?= htmlspecialchars(formatRupiah($t['total_nilai'] ?? 0), ENT_QUOTES, 'UTF-8') ?></span>
                                        </span>
                                    </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty">
                                    <span class="ico" aria-hidden="true"><?= icon('inbox') ?></span>
                                    <h2>Belum ada transaksi</h2>
                                    <p>Transaksi beli terbaru akan muncul di sini.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <section aria-labelledby="h-jual">
                        <div class="sec-head">
                            <h2 class="sec-title" id="h-jual">Transaksi jual terakhir</h2>
                            <a href="<?= base_url('pengelola/transaksi-jual') ?>" class="link-more">Lihat semua<?= icon('chevron-right') ?></a>
                        </div>
                        <div class="list">
                            <?php if (!empty($recentJual)): ?>
                                <?php foreach ($recentJual as $t): ?>
                                    <a href="<?= base_url('pengelola/transaksi-jual/detail?id=' . (int) ($t['id'] ?? 0)) ?>" class="row">
                                        <span class="ico" aria-hidden="true"><?= icon('upload') ?></span>
                                        <span class="row-main">
                                            <span class="row-title"><?= htmlspecialchars((string) ($t['pengepul_nama'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                                            <span class="row-sub">
                                                <?= htmlspecialchars(tanggalSingkatIndonesia($t['tanggal'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                                &middot; <?= ($t['status_bayar'] ?? 'BELUM') === 'LUNAS' ? 'Lunas' : 'Belum dibayar' ?>
                                            </span>
                                        </span>
                                        <span class="row-end">
                                            <span class="row-amount"><?= htmlspecialchars(formatRupiah($t['total_nilai'] ?? 0), ENT_QUOTES, 'UTF-8') ?></span>
                                        </span>
                                    </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty">
                                    <span class="ico" aria-hidden="true"><?= icon('inbox') ?></span>
                                    <h2>Belum ada penjualan</h2>
                                    <p>Penjualan ke pengepul akan muncul di sini.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <section aria-labelledby="h-stok">
                        <div class="sec-head">
                            <h2 class="sec-title" id="h-stok">Stok sampah</h2>
                            <a href="<?= base_url('pengelola/stok') ?>" class="link-more">Rincian<?= icon('chevron-right') ?></a>
                        </div>
                        <div class="list">
                            <?php if (!empty($stokTampil)): ?>
                                <?php foreach ($stokTampil as $s): ?>
                                    <div class="row">
                                        <span class="ico" aria-hidden="true"><?= icon('box') ?></span>
                                        <span class="row-main">
                                            <span class="row-title"><?= htmlspecialchars((string) ($s['kategori_nama'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                                        </span>
                                        <span class="row-end">
                                            <span class="row-amount"><?= htmlspecialchars(formatBeratKg($s['berat_kg'] ?? 0), ENT_QUOTES, 'UTF-8') ?></span>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                                <?php if ($stokSisa > 0): ?>
                                    <p class="list-more">dan <?= htmlspecialchars(number_format($stokSisa, 0, ',', '.'), ENT_QUOTES, 'UTF-8') ?> kategori lainnya</p>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="empty">
                                    <span class="ico" aria-hidden="true"><?= icon('box') ?></span>
                                    <h2>Stok kosong</h2>
                                    <p>Stok bertambah setelah ada pembelian dicatat.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </div>

    <?= pengelolaScripts() ?>
</body>
</html>
