<?php
/**
 * pengelola/transaksi_jual/detail.php
 * Nota penjualan (UC10, FR-15), tandai lunas, dan pembatalan (FR-18, BR-08).
 *
 * Halaman ini juga berfungsi sebagai nota yang diterima pengepul:
 * tombol Cetak Nota menyembunyikan sidebar, topbar, dan form aksi.
 *
 * Compatible dengan PHP 7.3+
 *
 * @var array      $transaksi  baris transaksi_jual beserta 'items' (detail_jual).
 *                             Kolom opsional: dibayar_at, alasan_batal. Jika alasan_batal
 *                             tidak ada, alasan pembatalan dibaca dari catatan.
 * @var array|null $flash
 */

require_once VIEW_PATH . '/pengelola/partials/ui.php';

$menuAktif = 'pengelola/transaksi-jual';
$judulHalaman = 'Nota Penjualan';

$e = function ($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};

$trx = (isset($transaksi) && is_array($transaksi)) ? $transaksi : [];
$items = isset($trx['items']) && is_array($trx['items']) ? $trx['items'] : [];
$status = (string) ($trx['status'] ?? 'SAH');
$statusBayar = (string) ($trx['status_bayar'] ?? 'BELUM');
$isBatal = $status === 'BATAL';
$isLunas = $statusBayar === 'LUNAS';

/*
 * Satu sumber total: jumlah dari rincian item. Header transaksi hanya dipakai
 * bila rincian item tidak tersedia, sehingga tabel dan kartu informasi selalu sama.
 */
$totalBerat = 0.0;
$totalNilai = 0.0;
foreach ($items as $it) {
    $totalBerat += (float) ($it['berat_kg'] ?? 0);
    $totalNilai += (float) ($it['subtotal'] ?? 0);
}
if (empty($items)) {
    $totalBerat = (float) ($trx['total_berat'] ?? 0);
    $totalNilai = (float) ($trx['total_nilai'] ?? 0);
}

/*
 * Alasan pembatalan: pakai kolom alasan_batal bila ada, kalau tidak pakai catatan
 * (sesuai kamus data: pembatalan menyimpan alasan di catatan). Bila catatan dipakai
 * sebagai alasan, baris Catatan tidak ditampilkan lagi agar tidak ganda.
 */
$catatan = trim((string) ($trx['catatan'] ?? ''));
$alasanBatal = '';
if ($isBatal) {
    $alasanBatal = trim((string) ($trx['alasan_batal'] ?? ''));
    if ($alasanBatal === '') {
        $alasanBatal = $catatan;
    }
}
$tampilCatatan = $catatan !== '' && !($isBatal && $catatan === $alasanBatal);

/* Kelas dan ikon notice: pakai bawaan ui.php bila ada, kalau tidak pakai nilai aman. */
$noticeClass = isset($flashClass) && $flashClass !== '' ? $flashClass : 'info';
$noticeIcon = isset($flashIcon) && $flashIcon !== '' ? $flashIcon : 'info';

/* Teks pembayaran dan pembatalan menyesuaikan kondisi transaksi. */
if ($isBatal) {
    $teksBayar = $isLunas ? 'Sudah dibayar pengepul sebelum dibatalkan' : 'Tidak dibayar (transaksi dibatalkan)';
} else {
    $teksBayar = $isLunas ? 'Sudah dibayar pengepul' : 'Belum dibayar pengepul';
}
$teksBatal = $isLunas
    ? 'Stok yang sudah keluar dikembalikan.'
    : 'Stok belum keluar, jadi pembatalan hanya melepas stok yang tertahan untuk transaksi ini.';
$konfirmBatal = $isLunas
    ? 'Batalkan transaksi ini? Stok akan dikembalikan.'
    : 'Batalkan transaksi ini? Stok yang tertahan akan dilepas.';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?= pengelolaHead($judulHalaman) ?>
    <style>
        /* Gaya khusus halaman nota penjualan. */
        .btn-cetak {
            display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
            min-height: 46px; padding: 0 1.125rem; border-radius: 14px; cursor: pointer;
            font-size: .9375rem; font-weight: 700; color: var(--g-800);
            background: transparent; border: 1.5px solid var(--g-200);
        }
        .btn-cetak:hover { background: var(--g-100); }
        .btn-cetak svg { width: 18px; height: 18px; }
        .head-actions { display: flex; align-items: center; gap: .625rem; flex-wrap: wrap; }
        .print-only { display: none; }

        @media print {
            .sidebar, .topbar, .scrim, .burger, .back, .notice, .no-print { display: none !important; }
            .main { margin-left: 0 !important; }
            body { background: #fff !important; }
            .content, .wrap { padding: 0 !important; max-width: none !important; }
            .card { box-shadow: none !important; break-inside: avoid; }
            .print-only { display: block; margin-bottom: 1rem; }
            .print-only h2 { font-size: 1.125rem; margin: 0; }
            .print-only p { margin: .125rem 0 0; font-size: .875rem; }
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
                <a class="back" href="<?= base_url('pengelola/transaksi-jual') ?>"><?= icon('arrow-left') ?>Transaksi Jual</a>

                <?php if (!empty($flash) && !empty($flash['message'])): ?>
                    <div class="notice notice-<?= $e($noticeClass) ?>" role="status">
                        <?= icon($noticeIcon) ?><span><?= $e($flash['message']) ?></span>
                    </div>
                <?php endif; ?>

                <div class="print-only">
                    <h2><?= $e($trx['bank_nama'] ?? 'Bank Sampah') ?></h2>
                    <p>Nota Penjualan Sampah</p>
                </div>

                <div class="page-head">
                    <div>
                        <h1><?= $e($trx['no_transaksi'] ?? '-') ?></h1>
                        <p class="lead">Nota penjualan sampah ke pengepul &middot; <?= $e(tanggalPanjangIndonesia($trx['tanggal'] ?? '')) ?></p>
                    </div>
                    <div class="head-actions">
                        <?php if ($isBatal): ?>
                            <span class="badge badge--danger"><?= icon('x-circle') ?> Dibatalkan</span>
                        <?php elseif ($isLunas): ?>
                            <span class="badge badge--success"><?= icon('check-circle') ?> Lunas</span>
                        <?php else: ?>
                            <span class="badge badge--warning"><?= icon('clock') ?> Belum dibayar</span>
                        <?php endif; ?>
                        <?php if (!$isBatal): ?>
                            <button type="button" class="btn-cetak no-print" data-print><?= icon('receipt') ?>Cetak Nota</button>
                        <?php endif; ?>
                    </div>
                </div>

                <section class="card" aria-labelledby="jumlah-title">
                    <h2 class="card-title" id="jumlah-title"><span class="ico"><?= icon('receipt') ?></span>Rincian Sampah</h2>
                    <p class="card-hint">
                        Harga per kilogram disalin saat transaksi ini dicatat, jadi harga yang berlaku
                        saat ini tidak mengubah transaksi lama.
                    </p>
                    <?php if (empty($items)): ?>
                        <div class="empty">
                            <span class="ico" aria-hidden="true"><?= icon('inbox') ?></span>
                            <h2>Rincian item tidak tersedia</h2>
                            <p>Hubungi petugas lain yang mencatat transaksi ini.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-wrap">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th scope="col">Kategori</th>
                                        <th scope="col" class="num">Berat</th>
                                        <th scope="col" class="num">Harga/kg</th>
                                        <th scope="col" class="num">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($items as $it): ?>
                                    <tr>
                                        <td><?= $e($it['kategori_nama'] ?? '-') ?></td>
                                        <td class="num"><?= $e(formatBeratKg($it['berat_kg'] ?? 0)) ?></td>
                                        <td class="num"><?= $e(formatRupiah($it['harga_per_kg'] ?? 0)) ?></td>
                                        <td class="num"><?= $e(formatRupiah($it['subtotal'] ?? 0)) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td>Total</td>
                                        <td class="num"><?= $e(formatBeratKg($totalBerat)) ?></td>
                                        <td></td>
                                        <td class="num"><?= $e(formatRupiah($totalNilai)) ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="card" aria-labelledby="info-title">
                    <h2 class="card-title" id="info-title"><span class="ico"><?= icon('info') ?></span>Informasi Penjualan</h2>
                    <dl class="dl">
                        <dt>Pengepul</dt>
                        <dd><?= $e($trx['pengepul_nama'] ?? '-') ?></dd>

                        <dt>Status pembayaran</dt>
                        <dd><?= $e($teksBayar) ?></dd>

                        <dt>Total berat</dt>
                        <dd><?= $e(formatBeratKg($totalBerat)) ?></dd>

                        <dt>Total nilai</dt>
                        <dd><?= $e(formatRupiah($totalNilai)) ?></dd>

                        <dt>Petugas</dt>
                        <dd><?= $e($trx['petugas_nama'] ?? '-') ?></dd>

                        <dt>Bank sampah</dt>
                        <dd><?= $e($trx['bank_nama'] ?? '-') ?></dd>

                        <?php if (!empty($trx['dibayar_at'])): ?>
                            <dt>Dibayar pada</dt>
                            <dd><?= $e(tanggalPanjangIndonesia($trx['dibayar_at'])) ?></dd>
                        <?php endif; ?>

                        <?php if ($tampilCatatan): ?>
                            <dt>Catatan</dt>
                            <dd><?= $e($catatan) ?></dd>
                        <?php endif; ?>

                        <?php if ($isBatal): ?>
                            <dt>Alasan pembatalan</dt>
                            <dd><?= $e($alasanBatal !== '' ? $alasanBatal : '-') ?></dd>
                        <?php endif; ?>
                    </dl>
                    <?php if (!$isLunas && !$isBatal): ?>
                        <p class="hint no-print" style="margin-top:.75rem;">
                            Stok baru keluar dari gudang setelah pengepul membayar. Kalau pengepul sudah bayar,
                            tandai lunas supaya stok berkurang.
                        </p>
                    <?php endif; ?>
                </section>

                <?php if (!$isBatal): ?>
                    <?php if (!$isLunas): ?>
                        <section class="card no-print" aria-labelledby="lunas-title">
                            <h2 class="card-title" id="lunas-title"><span class="ico"><?= icon('check-circle') ?></span>Tandai Sudah Dibayar</h2>
                            <p class="note">
                                Stok keluar dari gudang saat transaksi ditandai lunas. Stok akan diperiksa ulang
                                dan transaksi ditolak bila stok sudah tidak cukup.
                            </p>
                            <form method="POST" action="<?= base_url('pengelola/transaksi-jual/lunas') ?>" data-once
                                  data-confirm="Tandai transaksi ini sudah dibayar pengepul?">
                                <?= csrfField() ?>
                                <input type="hidden" name="id" value="<?= (int) ($trx['id'] ?? 0) ?>">
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary btn-block">
                                        <?= icon('check') ?>Tandai Sudah Lunas
                                    </button>
                                </div>
                            </form>
                        </section>
                    <?php endif; ?>

                    <section class="card no-print" aria-labelledby="batal-title">
                        <h2 class="card-title" id="batal-title"><span class="ico ico--warning"><?= icon('alert') ?></span>Batalkan Transaksi</h2>
                        <p class="note">
                            Transaksi tidak pernah dihapus. Kesalahan dikoreksi lewat pembatalan yang disertai alasan.
                            <?= $e($teksBatal) ?>
                        </p>
                        <form method="POST" action="<?= base_url('pengelola/transaksi-jual/batal') ?>" data-once
                              data-confirm="<?= $e($konfirmBatal) ?>">
                            <?= csrfField() ?>
                            <input type="hidden" name="id" value="<?= (int) ($trx['id'] ?? 0) ?>">
                            <div class="field">
                                <label class="label" for="alasan">Alasan pembatalan <span class="req" aria-hidden="true">*</span></label>
                                <textarea class="input" id="alasan" name="alasan" rows="3" required
                                          placeholder="Contoh: salah timbangan, pengepul tidak jadi mengambil."></textarea>
                                <p class="hint">Alasan ini tersimpan pada audit log dan tidak dapat diubah lagi.</p>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-danger btn-block">
                                    <?= icon('close') ?>Batalkan Transaksi
                                </button>
                            </div>
                        </form>
                    </section>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <?= pengelolaScripts() ?>
    <script>
    (function () {
        var btn = document.querySelector('[data-print]');
        if (btn) { btn.addEventListener('click', function () { window.print(); }); }
    })();
    </script>
</body>
</html>