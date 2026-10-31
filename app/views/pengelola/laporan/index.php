<?php
/**
 * pengelola/laporan/index.php
 * Laporan Operasional (Pengelola) — FR-17.
 *
 * Laporan mencakup empat hal sesuai PRODUCT.md: pembelian, penjualan,
 * stok, dan pencairan. Pembelian, penjualan, dan pencairan mengikuti
 * periode yang dipilih; stok adalah posisi gudang saat ini karena stok
 * bersifat kumulatif.
 *
 * Compatible dengan PHP 7.3+
 *
 * @var string|null $tanggalFrom  Format Y-m-d
 * @var string|null $tanggalTo    Format Y-m-d
 * @var array      $statsBeli
 * @var array      $statsJual
 * @var array      $barisStok
 * @var array      $statsPencairan
 * @var array|null $flash
 */

require_once VIEW_PATH . '/pengelola/partials/ui.php';

$menuAktif = 'pengelola/laporan';
$judulHalaman = 'Laporan';

$tanggalFrom = isset($tanggalFrom) && is_string($tanggalFrom) ? $tanggalFrom : '';
$tanggalTo = isset($tanggalTo) && is_string($tanggalTo) ? $tanggalTo : '';
$statsBeli = isset($statsBeli) && is_array($statsBeli) ? $statsBeli : [];
$statsJual = isset($statsJual) && is_array($statsJual) ? $statsJual : [];
$barisStok = isset($barisStok) && is_array($barisStok) ? $barisStok : [];
$statsPencairan = isset($statsPencairan) && is_array($statsPencairan) ? $statsPencairan : [];

/**
 * Ubah "2026-10-24" menjadi "24 Okt 2026". Nilai tidak valid dikembalikan apa adanya.
 */
$formatTanggalId = function ($ymd): string {
    $ymd = trim((string) $ymd);
    if ($ymd === '') {
        return '';
    }
    $t = DateTime::createFromFormat('Y-m-d', $ymd);
    if (!$t || $t->format('Y-m-d') !== $ymd) {
        return $ymd;
    }
    $bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    return $t->format('j') . ' ' . $bulan[(int) $t->format('n') - 1] . ' ' . $t->format('Y');
};

$fromLabel = $formatTanggalId($tanggalFrom);
$toLabel = $formatTanggalId($tanggalTo);
if ($fromLabel !== '' && $toLabel !== '') {
    $periode = $fromLabel . ' - ' . $toLabel;
} elseif ($fromLabel !== '') {
    $periode = 'Mulai ' . $fromLabel;
} elseif ($toLabel !== '') {
    $periode = 'Sampai ' . $toLabel;
} else {
    $periode = 'Semua periode';
}

$exportUrl = base_url('pengelola/laporan/export-pdf')
    . '?tanggal_from=' . rawurlencode($tanggalFrom)
    . '&tanggal_to=' . rawurlencode($tanggalTo);

$angka = function ($v) {
    return number_format((float) $v, 0, ',', '.');
};
$beratKg = function ($v) {
    return number_format((float) $v, 2, ',', '.') . ' kg';
};

$beliKosong = ((int) ($statsBeli['total_transaksi'] ?? 0)) === 0;
$jualKosong = ((int) ($statsJual['total_transaksi'] ?? 0)) === 0;
$pencairanKosong = ((int) ($statsPencairan['TOTAL']['jumlah'] ?? 0)) === 0;

// Ringkasan stok di gudang (sen-kg dari controller).
$stokTotal = 0;
$stokReservasi = 0;
$stokTersedia = 0;
foreach ($barisStok as $b) {
    $stokTotal += (int) ($b['stok_cents'] ?? 0);
    $stokReservasi += (int) ($b['reserved_cents'] ?? 0);
    $stokTersedia += (int) ($b['tersedia_cents'] ?? 0);
}

// Status pencairan yang tampil di laporan.
$statusPencairan = [
    'DIAJUKAN' => 'Diajukan',
    'DISETUJUI' => 'Disetujui',
    'DITOLAK' => 'Ditolak',
    'DICAIRKAN' => 'Dicairkan',
    'DIBATALKAN' => 'Dibatalkan',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?= pengelolaHead($judulHalaman) ?>
    <style>
        /* Gaya khusus halaman laporan. */
        .wallet-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; margin-top: 16px; }
        .filter-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .filter-actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 20px; }
        .row-label { flex: 1; min-width: 0; font-weight: 600; color: var(--ink-2); }
        .row-val { font-weight: 800; color: var(--ink); font-variant-numeric: tabular-nums; text-align: right; white-space: nowrap; }
        .row-val.warn { color: var(--amber); }
        .ico--warning { background: var(--amber-bg); color: var(--amber); }
        .badge--info { background: var(--sky-bg); color: var(--sky); }

        @media (max-width: 560px) {
            .wallet-grid, .filter-grid { grid-template-columns: minmax(0, 1fr); }
            .filter-actions .btn { width: 100%; }
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
                        <h1>Laporan Operasional</h1>
                        <p class="lead">Ringkasan pembelian, penjualan, stok, dan pencairan bank sampah.</p>
                    </div>
                </div>

                <section class="wallet" aria-label="Ringkasan periode">
                    <span class="wallet-label"><?= icon('calendar') ?>Periode: <?= htmlspecialchars($periode, ENT_QUOTES, 'UTF-8') ?></span>
                    <div class="wallet-grid">
                        <div>
                            <p class="wallet-label">Total nilai beli</p>
                            <p class="wallet-value"><?= htmlspecialchars(formatRupiah($statsBeli['total_nilai'] ?? 0), ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <div>
                            <p class="wallet-label">Total penjualan</p>
                            <p class="wallet-value"><?= htmlspecialchars(formatRupiah($statsJual['total_nilai'] ?? 0), ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </div>
                </section>

                <section class="card" aria-labelledby="filter-title">
                    <h2 class="card-title" id="filter-title">Pilih Periode</h2>
                    <p class="card-hint">Kosongkan salah satu atau kedua tanggal untuk melihat semua periode.</p>
                    <form method="GET" id="filterForm">
                        <div class="filter-grid">
                            <div class="field">
                                <label class="label" for="tanggal_from">Dari tanggal</label>
                                <input class="input" type="date" id="tanggal_from" name="tanggal_from" value="<?= htmlspecialchars($tanggalFrom, ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="field">
                                <label class="label" for="tanggal_to">Sampai tanggal</label>
                                <input class="input" type="date" id="tanggal_to" name="tanggal_to" value="<?= htmlspecialchars($tanggalTo, ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        </div>
                    </form>
                    <div class="filter-actions">
                        <button type="submit" form="filterForm" class="btn btn-primary"><?= icon('search') ?>Tampilkan</button>
                        <a href="<?= htmlspecialchars($exportUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-ghost"><?= icon('download') ?>Unduh PDF</a>
                    </div>
                </section>

                <section class="card" aria-labelledby="beli-title">
                    <h2 class="card-title" id="beli-title"><span class="ico"><?= icon('cart') ?></span>Transaksi Beli</h2>
                    <?php if ($beliKosong): ?>
                        <div class="empty">
                            <span class="ico" aria-hidden="true"><?= icon('file') ?></span>
                            <h2>Belum ada transaksi beli</h2>
                            <p>Tidak ada pembelian pada periode ini. Coba ubah rentang tanggal.</p>
                        </div>
                    <?php else: ?>
                        <ul class="list">
                            <li class="row"><span class="ico"><?= icon('receipt') ?></span><span class="row-label">Total transaksi</span><span class="row-val"><?= htmlspecialchars($angka($statsBeli['total_transaksi'] ?? 0), ENT_QUOTES, 'UTF-8') ?></span></li>
                            <li class="row"><span class="ico"><?= icon('scale') ?></span><span class="row-label">Total berat</span><span class="row-val"><?= htmlspecialchars($beratKg($statsBeli['total_berat'] ?? 0), ENT_QUOTES, 'UTF-8') ?></span></li>
                            <li class="row"><span class="ico"><?= icon('coins') ?></span><span class="row-label">Total nilai beli</span><span class="row-val"><?= htmlspecialchars(formatRupiah($statsBeli['total_nilai'] ?? 0), ENT_QUOTES, 'UTF-8') ?></span></li>
                            <li class="row"><span class="ico"><?= icon('wallet') ?></span><span class="row-label">Masuk tabungan nasabah</span><span class="row-val"><?= htmlspecialchars(formatRupiah($statsBeli['total_tabungan'] ?? 0), ENT_QUOTES, 'UTF-8') ?></span></li>
                            <li class="row"><span class="ico"><?= icon('cash') ?></span><span class="row-label">Dibayar tunai</span><span class="row-val"><?= htmlspecialchars(formatRupiah($statsBeli['total_tunai'] ?? 0), ENT_QUOTES, 'UTF-8') ?></span></li>
                        </ul>
                    <?php endif; ?>
                </section>

                <section class="card" aria-labelledby="jual-title">
                    <h2 class="card-title" id="jual-title"><span class="ico"><?= icon('upload') ?></span>Transaksi Jual</h2>
                    <?php if ($jualKosong): ?>
                        <div class="empty">
                            <span class="ico" aria-hidden="true"><?= icon('file') ?></span>
                            <h2>Belum ada transaksi jual</h2>
                            <p>Tidak ada penjualan pada periode ini. Coba ubah rentang tanggal.</p>
                        </div>
                    <?php else: ?>
                        <ul class="list">
                            <li class="row"><span class="ico"><?= icon('receipt') ?></span><span class="row-label">Total transaksi</span><span class="row-val"><?= htmlspecialchars($angka($statsJual['total_transaksi'] ?? 0), ENT_QUOTES, 'UTF-8') ?></span></li>
                            <li class="row"><span class="ico"><?= icon('scale') ?></span><span class="row-label">Total berat</span><span class="row-val"><?= htmlspecialchars($beratKg($statsJual['total_berat'] ?? 0), ENT_QUOTES, 'UTF-8') ?></span></li>
                            <li class="row"><span class="ico"><?= icon('coins') ?></span><span class="row-label">Total penjualan</span><span class="row-val"><?= htmlspecialchars(formatRupiah($statsJual['total_nilai'] ?? 0), ENT_QUOTES, 'UTF-8') ?></span></li>
                            <li class="row"><span class="ico"><?= icon('banknote') ?></span><span class="row-label">Sudah dibayar pengepul</span><span class="row-val"><?= htmlspecialchars(formatRupiah($statsJual['total_lunas'] ?? 0), ENT_QUOTES, 'UTF-8') ?></span></li>
                            <li class="row"><span class="ico ico--warning"><?= icon('clock') ?></span><span class="row-label">Piutang (belum dibayar)</span><span class="row-val warn"><?= htmlspecialchars(formatRupiah($statsJual['total_belum_bayar'] ?? 0), ENT_QUOTES, 'UTF-8') ?></span></li>
                        </ul>
                    <?php endif; ?>
                </section>

                <section class="card" aria-labelledby="stok-title">
                    <h2 class="card-title" id="stok-title"><span class="ico"><?= icon('box') ?></span>Stok Sampah</h2>
                    <p class="card-hint">Posisi gudang saat ini. Stok bertambah saat pembelian dan berkurang saat penjualan dibayar.</p>
                    <?php if (empty($barisStok)): ?>
                        <div class="empty">
                            <span class="ico" aria-hidden="true"><?= icon('inbox') ?></span>
                            <h2>Belum ada stok</h2>
                            <p>Stok akan terisi setelah pembelian sampah pertama dicatat.</p>
                        </div>
                    <?php else: ?>
                        <ul class="list">
                            <li class="row"><span class="ico"><?= icon('scale') ?></span><span class="row-label">Total stok di gudang</span><span class="row-val"><?= htmlspecialchars(formatBerat($stokTotal), ENT_QUOTES, 'UTF-8') ?></span></li>
                            <li class="row"><span class="ico ico--warning"><?= icon('clock') ?></span><span class="row-label">Sudah dipesan, belum dibayar</span><span class="row-val warn"><?= htmlspecialchars(formatBerat($stokReservasi), ENT_QUOTES, 'UTF-8') ?></span></li>
                            <li class="row"><span class="ico"><?= icon('box') ?></span><span class="row-label">Bisa dijual sekarang</span><span class="row-val"><?= htmlspecialchars(formatBerat($stokTersedia), ENT_QUOTES, 'UTF-8') ?></span></li>
                        </ul>
                        <div class="table-wrap" style="margin-top:16px;">
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
                                <?php foreach ($barisStok as $b): ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string) ($b['kategori_nama'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="num"><?= htmlspecialchars(formatBerat((int) ($b['stok_cents'] ?? 0)), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="num"><?= htmlspecialchars(formatBerat((int) ($b['reserved_cents'] ?? 0)), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="num"><strong><?= htmlspecialchars(formatBerat((int) ($b['tersedia_cents'] ?? 0)), ENT_QUOTES, 'UTF-8') ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="card" aria-labelledby="pencairan-title">
                    <h2 class="card-title" id="pencairan-title"><span class="ico"><?= icon('banknote') ?></span>Pengajuan Pencairan</h2>
                    <p class="card-hint">Pengajuan yang masuk pada periode yang dipilih.</p>
                    <?php if ($pencairanKosong): ?>
                        <div class="empty">
                            <span class="ico" aria-hidden="true"><?= icon('file') ?></span>
                            <h2>Belum ada pengajuan pencairan</h2>
                            <p>Tidak ada pengajuan pada periode ini. Coba ubah rentang tanggal.</p>
                        </div>
                    <?php else: ?>
                        <ul class="list">
                            <?php foreach ($statusPencairan as $status => $label): ?>
                                <?php $jumlah = (int) ($statsPencairan[$status]['jumlah'] ?? 0); ?>
                                <?php if ($jumlah === 0) { continue; } ?>
                                <li class="row">
                                    <span class="ico<?= ($status === 'DIAJUKAN' || $status === 'DISETUJUI') ? ' ico--warning' : '' ?>"><?= icon('banknote') ?></span>
                                    <span class="row-label"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="row-val"><?= htmlspecialchars($angka($jumlah), ENT_QUOTES, 'UTF-8') ?> pengajuan</span>
                                </li>
                            <?php endforeach; ?>
                            <li class="row">
                                <span class="ico"><?= icon('coins') ?></span>
                                <span class="row-label">Total nilai pengajuan</span>
                                <span class="row-val"><?= htmlspecialchars(formatRupiah($statsPencairan['TOTAL']['total_nilai'] ?? 0), ENT_QUOTES, 'UTF-8') ?></span>
                            </li>
                        </ul>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>

    <?= pengelolaScripts() ?>
</body>
</html>
