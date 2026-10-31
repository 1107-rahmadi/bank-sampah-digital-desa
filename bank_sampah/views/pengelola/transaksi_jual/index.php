<?php
/**
 * pengelola/transaksi_jual/index.php
 * Daftar Transaksi Jual ke pengepul (Pengelola).
 *
 * Compatible dengan PHP 7.3+
 *
 * @var array|null $flash
 * @var array      $stats        total_transaksi, total_berat, total_nilai,
 *                                total_lunas, total_belum_bayar
 * @var array      $transaksis   tiap item: id, no_transaksi, tanggal, pengepul_nama,
 *                                total_berat, total_nilai, status_bayar (BELUM|LUNAS),
 *                                status (SAH|BATAL)
 */

require_once VIEW_PATH . '/pengelola/partials/ui.php';

$menuAktif = 'pengelola/transaksi-jual';
$judulHalaman = 'Transaksi Jual';

$stats = isset($stats) && is_array($stats) ? $stats : [];
$transaksis = isset($transaksis) && is_array($transaksis) ? $transaksis : [];

$totalTransaksi = (float) ($stats['total_transaksi'] ?? 0);
$totalBerat = (float) ($stats['total_berat'] ?? 0);
$totalNilai = (float) ($stats['total_nilai'] ?? 0);
$piutang = (float) ($stats['total_belum_bayar'] ?? 0);

/*
 * Kelompokkan per tanggal. Data dari server sudah urut terbaru lebih dulu.
 */
$grup = [];
foreach ($transaksis as $t) {
    $ts = isset($t['tanggal']) ? strtotime((string) $t['tanggal']) : false;
    if ($ts) {
        $kunci = date('Y-m-d', $ts);
        $label = tanggalSingkatIndonesia($kunci);
        $jam = date('H:i', $ts);
    } else {
        $kunci = 'tanpa-tanggal';
        $label = 'Tanggal tidak diketahui';
        $jam = '-';
    }
    if (!isset($grup[$kunci])) {
        $grup[$kunci] = ['label' => $label, 'items' => []];
    }
    $t['_jam'] = $jam;
    $grup[$kunci]['items'][] = $t;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?= pengelolaHead($judulHalaman) ?>
    <style>
        /* Gaya khusus halaman daftar transaksi jual. */
        .wallet-split { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; margin-top: 20px; }
        .split { padding: 12px 16px; border-radius: 16px; background: rgba(255, 255, 255, .14); border: 1px solid rgba(255, 255, 255, .18); }
        .split-label { font-size: .875rem; color: rgba(255, 255, 255, .92); }
        .split-value { margin-top: 2px; font-size: 1.1875rem; font-weight: 700; letter-spacing: -.02em; font-variant-numeric: tabular-nums; }

        @media (max-width: 560px) {
            .wallet-split { grid-template-columns: minmax(0, 1fr); gap: 10px; }
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
                <a class="back" href="<?= base_url('pengelola/dashboard') ?>"><?= icon('arrow-left') ?>Dashboard</a>

                <?php if (!empty($flash) && !empty($flash['message'])): ?>
                    <div class="notice notice-<?= htmlspecialchars($flashClass, ENT_QUOTES, 'UTF-8') ?>" role="status">
                        <?= icon($flashIcon) ?><span><?= htmlspecialchars((string) $flash['message'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endif; ?>

                <div class="page-head">
                    <div>
                        <h1>Transaksi Jual</h1>
                        <p class="lead">Penjualan sampah ke pengepul. Stok berkurang setelah pengepul membayar.</p>
                    </div>
                    <a class="btn btn-primary" href="<?= base_url('pengelola/transaksi-jual/add') ?>"><?= icon('plus') ?>Transaksi Baru</a>
                </div>

                <section class="wallet" aria-label="Ringkasan transaksi jual">
                    <span class="wallet-label"><?= icon('wallet') ?>Total nilai penjualan</span>
                    <p class="wallet-value"><?= htmlspecialchars(formatRupiah($totalNilai), ENT_QUOTES, 'UTF-8') ?></p>

                    <div class="wallet-split">
                        <div class="split">
                            <div class="split-label">Total transaksi</div>
                            <div class="split-value"><?= htmlspecialchars(number_format($totalTransaksi, 0, ',', '.'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="split">
                            <div class="split-label">Total berat</div>
                            <div class="split-value"><?= htmlspecialchars(formatBeratKg($totalBerat), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="split">
                            <div class="split-label">Piutang</div>
                            <div class="split-value"><?= htmlspecialchars(formatRupiah($piutang), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </div>
                </section>

                <?php if (!empty($grup)): ?>
                    <?php foreach ($grup as $g): ?>
                        <h2 class="group-label"><?= htmlspecialchars($g['label'], ENT_QUOTES, 'UTF-8') ?></h2>
                        <ul class="list">
                            <?php foreach ($g['items'] as $t):
                                $lunas = strtoupper((string) ($t['status_bayar'] ?? '')) === 'LUNAS';
                                $batal = strtoupper((string) ($t['status'] ?? 'SAH')) === 'BATAL';
                            ?>
                                <li class="row">
                                    <span class="ico ico--info" aria-hidden="true"><?= icon('truck') ?></span>
                                    <span class="row-main">
                                        <span class="row-title">
                                            <a href="<?= base_url('pengelola/transaksi-jual/detail?id=' . (int) ($t['id'] ?? 0)) ?>"><?= htmlspecialchars((string) ($t['pengepul_nama'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></a>
                                        </span>
                                        <span class="row-sub">
                                            <span><?= htmlspecialchars((string) ($t['no_transaksi'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                                            <span><?= htmlspecialchars((string) ($t['_jam'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                                            <span><?= htmlspecialchars(formatBeratKg($t['total_berat'] ?? 0), ENT_QUOTES, 'UTF-8') ?></span>
                                        </span>
                                    </span>
                                    <span class="row-end">
                                        <span class="row-amount"><?= htmlspecialchars(formatRupiah($t['total_nilai'] ?? 0), ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php if ($batal): ?>
                                            <span class="badge badge--danger">Dibatalkan</span>
                                        <?php else: ?>
                                            <span class="badge badge--<?= $lunas ? 'success' : 'warning' ?>"><?= $lunas ? 'Lunas' : 'Belum lunas' ?></span>
                                        <?php endif; ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty">
                        <span class="ico" aria-hidden="true"><?= icon('inbox') ?></span>
                        <h2>Belum ada transaksi</h2>
                        <p>Penjualan ke pengepul yang dicatat akan muncul di sini.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <?= pengelolaScripts() ?>
</body>
</html>
