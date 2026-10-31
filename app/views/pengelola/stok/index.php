<?php
/**
 * pengelola/stok/index.php
 * Halaman stok sampah (FR-14).
 *
 * Menampilkan stok fisik per kategori, barang yang sudah dipesan ke pengepul
 * tetapi belum dibayar (reservasi), dan stok yang benar-benar bisa dijual.
 *
 * Compatible dengan PHP 7.3+
 *
 * @var array  $baris  tiap item: kategori_nama, jenis, satuan, berat_kg,
 *                     stok_cents, reserved_cents, tersedia_cents
 * @var array|null $flash
 */

require_once VIEW_PATH . '/pengelola/partials/ui.php';

$menuAktif = 'pengelola/stok';
$judulHalaman = 'Stok Sampah';

$baris = isset($baris) && is_array($baris) ? $baris : [];

$totalStok = 0;
$totalReservasi = 0;
$totalTersedia = 0;
foreach ($baris as $b) {
    $totalStok += (int) ($b['stok_cents'] ?? 0);
    $totalReservasi += (int) ($b['reserved_cents'] ?? 0);
    $totalTersedia += (int) ($b['tersedia_cents'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?= pengelolaHead($judulHalaman) ?>
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
                        <h1>Stok Sampah</h1>
                        <p class="lead">Stok bertambah saat pembelian, berkurang saat penjualan dibayar pengepul.</p>
                    </div>
                    <a class="btn btn-primary" href="<?= base_url('pengelola/transaksi-jual/add') ?>"><?= icon('upload') ?>Catat Penjualan</a>
                </div>

                <section class="card" aria-labelledby="ringkas-title">
                    <h2 class="card-title" id="ringkas-title">Ringkasan Gudang</h2>
                    <p class="card-hint">Semua angka di halaman ini memakai kilogram, bukan tonase.</p>
                    <dl class="dl">
                        <dt>Total stok di gudang</dt>
                        <dd><?= htmlspecialchars(formatBerat($totalStok), ENT_QUOTES, 'UTF-8') ?></dd>

                        <dt>Sudah dipesan (piutang)</dt>
                        <dd><?= htmlspecialchars(formatBerat($totalReservasi), ENT_QUOTES, 'UTF-8') ?></dd>

                        <dt>Bisa dijual sekarang</dt>
                        <dd><?= htmlspecialchars(formatBerat($totalTersedia), ENT_QUOTES, 'UTF-8') ?></dd>
                    </dl>
                </section>

                <section aria-labelledby="daftar-title">
                    <h2 class="section-title" id="daftar-title">Stok per Kategori</h2>
                    <?php if (empty($baris)): ?>
                        <div class="empty">
                            <span class="ico" aria-hidden="true"><?= icon('inbox') ?></span>
                            <h2>Belum ada stok</h2>
                            <p>Stok akan terisi setelah pembelian sampah pertama dicatat.</p>
                            <a class="btn btn-primary" href="<?= base_url('pengelola/transaksi-beli/add') ?>"><?= icon('plus') ?>Catat Pembelian</a>
                        </div>
                    <?php else: ?>
                        <div class="table-wrap">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th scope="col">Kategori</th>
                                        <th scope="col" class="num">Stok</th>
                                        <th scope="col" class="num">Dipesan</th>
                                        <th scope="col" class="num">Bisa dijual</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($baris as $b): ?>
                                    <?php
                                    $tersedia = (int) ($b['tersedia_cents'] ?? 0);
                                    $reservasi = (int) ($b['reserved_cents'] ?? 0);
                                    ?>
                                    <tr>
                                        <td>
                                            <?= htmlspecialchars((string) ($b['kategori_nama'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                                            <?php if (!empty($b['jenis'])): ?>
                                                <span class="badge badge--neutral"><?= htmlspecialchars((string) $b['jenis'], ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="num"><?= htmlspecialchars(formatBerat((int) ($b['stok_cents'] ?? 0)), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="num">
                                            <?php if ($reservasi > 0): ?>
                                                <?= htmlspecialchars(formatBerat($reservasi), ENT_QUOTES, 'UTF-8') ?>
                                            <?php else: ?>
                                                &mdash;
                                            <?php endif; ?>
                                        </td>
                                        <td class="num"><strong><?= htmlspecialchars(formatBerat($tersedia), ENT_QUOTES, 'UTF-8') ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <p class="hint" style="margin-top:.75rem;">
                            &ldquo;Dipesan&rdquo; berarti sampah sudah dijual ke pengepul tetapi belum dibayar.
                            Stok ini masih berada di gudang, tetapi tidak boleh dijual lagi sampai transaksinya selesai.
                        </p>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>

    <?= pengelolaScripts() ?>
</body>
</html>
