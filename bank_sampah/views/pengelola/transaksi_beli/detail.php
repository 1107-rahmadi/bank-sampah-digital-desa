<?php
/**
 * pengelola/transaksi_beli/detail.php
 * Bukti transaksi beli (UC07, UC08, FR-12) dan pembatalan transaksi (FR-18, BR-08).
 *
 * Tombol Cetak Bukti menyembunyikan sidebar, topbar, dan form pembatalan, sehingga
 * halaman ini bisa dicetak atau disimpan sebagai PDF untuk diberikan ke penjual.
 *
 * Compatible dengan PHP 7.3+
 *
 * @var array      $transaksi  baris transaksi_beli beserta 'items' (detail_beli).
 *                             Kolom opsional: alasan_batal. Jika tidak ada, alasan
 *                             pembatalan dibaca dari catatan.
 * @var array|null $flash
 */

require_once VIEW_PATH . '/pengelola/partials/ui.php';

$menuAktif = 'pengelola/transaksi-beli';
$judulHalaman = 'Bukti Transaksi Beli';

$e = function ($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};

$trx = (isset($transaksi) && is_array($transaksi)) ? $transaksi : [];
$items = isset($trx['items']) && is_array($trx['items']) ? $trx['items'] : [];
$status = (string) ($trx['status'] ?? 'SAH');
$isBatal = $status === 'BATAL';

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

$tipe = (string) ($trx['tipe_penjual'] ?? 'NASABAH');
$metode = (string) ($trx['metode_bayar'] ?? '');

// Penjual: nama nasabah untuk pembelian dari nasabah, nama bebas untuk non-nasabah.
if ($tipe === 'NASABAH') {
    $penjual = trim((string) ($trx['nasabah_nama'] ?? ''));
    if ($penjual === '') {
        $penjual = '-';
    }
} else {
    $penjual = trim((string) ($trx['nama_penjual'] ?? ''));
    if ($penjual === '') {
        $penjual = 'Tanpa nama';
    }
}

// BR-02: metode pembayaran.
$metodeLabel = $metode === 'TABUNG' ? 'Masuk tabungan' : ($metode === 'TUNAI' ? 'Dibayar tunai' : '-');

/*
 * Alasan pembatalan: pakai kolom alasan_batal bila ada, kalau tidak pakai catatan
 * (sesuai kamus data). Bila catatan dipakai sebagai alasan, baris Catatan tidak
 * ditampilkan lagi agar tidak ganda.
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

/*
 * Pembatalan pembelian membalik efeknya: stok dikurangi, dan saldo nasabah dikurangi
 * hanya bila pembayarannya masuk tabungan. Controller harus menolak pembatalan bila
 * stok atau SALDO TERSEDIA (saldo dikurangi pengajuan pencairan berstatus DIAJUKAN
 * atau DISETUJUI, BR-04) sudah tidak cukup, agar saldo dan stok tidak negatif (BR-03).
 */
if ($tipe === 'NASABAH' && $metode === 'TABUNG') {
    $teksBatal = 'Pembatalan membalik efek transaksi: stok sampah dikurangi sesuai berat yang tercatat '
        . 'dan saldo nasabah dikurangi sebesar nilai transaksi. Pembatalan ditolak bila stok atau saldo tersedia nasabah '
        . '(saldo setelah dikurangi pengajuan pencairan yang masih berjalan) sudah tidak cukup.';
    $konfirmBatal = 'Batalkan transaksi ini? Stok dan saldo nasabah akan dikurangi.';
} else {
    $teksBatal = 'Pembatalan membalik efek transaksi: stok sampah dikurangi sesuai berat yang tercatat. '
        . 'Saldo nasabah tidak berubah karena transaksi ini dibayar tunai. Pembatalan ditolak bila stok sudah tidak cukup.';
    $konfirmBatal = 'Batalkan transaksi ini? Stok akan dikurangi.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?= pengelolaHead($judulHalaman) ?>
    <style>
        /* Gaya khusus halaman bukti transaksi beli. */
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
                <a class="back" href="<?= base_url('pengelola/transaksi-beli') ?>"><?= icon('arrow-left') ?>Transaksi Beli</a>

                <?php if (!empty($flash) && !empty($flash['message'])): ?>
                    <div class="notice notice-<?= $e($noticeClass) ?>" role="status">
                        <?= icon($noticeIcon) ?><span><?= $e($flash['message']) ?></span>
                    </div>
                <?php endif; ?>

                <div class="print-only">
                    <h2><?= $e($trx['bank_nama'] ?? 'Bank Sampah') ?></h2>
                    <p>Bukti Pembelian Sampah</p>
                </div>

                <div class="page-head">
                    <div>
                        <h1><?= $e($trx['no_transaksi'] ?? '-') ?></h1>
                        <p class="lead">Bukti pembelian sampah &middot; <?= $e(tanggalPanjangIndonesia($trx['tanggal'] ?? '')) ?></p>
                    </div>
                    <div class="head-actions">
                        <?php if ($isBatal): ?>
                            <span class="badge badge--danger"><?= icon('x-circle') ?> Dibatalkan</span>
                        <?php else: ?>
                            <span class="badge badge--success"><?= icon('check-circle') ?> Sah</span>
                            <button type="button" class="btn-cetak no-print" data-print><?= icon('receipt') ?>Cetak Bukti</button>
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
                    <h2 class="card-title" id="info-title"><span class="ico"><?= icon('info') ?></span>Informasi Transaksi</h2>
                    <dl class="dl">
                        <dt>Penjual</dt>
                        <dd><?= $e($penjual) ?></dd>

                        <dt>Tipe penjual</dt>
                        <dd><?= $tipe === 'NASABAH' ? 'Nasabah bank sampah' : 'Non-nasabah' ?></dd>

                        <dt>Pembayaran</dt>
                        <dd><?= $e($metodeLabel) ?></dd>

                        <dt>Total berat</dt>
                        <dd><?= $e(formatBeratKg($totalBerat)) ?></dd>

                        <dt>Total nilai</dt>
                        <dd><?= $e(formatRupiah($totalNilai)) ?></dd>

                        <dt>Petugas</dt>
                        <dd><?= $e($trx['petugas_nama'] ?? '-') ?></dd>

                        <dt>Bank sampah</dt>
                        <dd><?= $e($trx['bank_nama'] ?? '-') ?></dd>

                        <?php if ($tampilCatatan): ?>
                            <dt>Catatan</dt>
                            <dd><?= $e($catatan) ?></dd>
                        <?php endif; ?>

                        <?php if ($isBatal): ?>
                            <dt>Alasan pembatalan</dt>
                            <dd><?= $e($alasanBatal !== '' ? $alasanBatal : '-') ?></dd>
                        <?php endif; ?>
                    </dl>
                </section>

                <?php if (!$isBatal): ?>
                    <section class="card no-print" aria-labelledby="batal-title">
                        <h2 class="card-title" id="batal-title"><span class="ico ico--warning"><?= icon('alert') ?></span>Batalkan Transaksi</h2>
                        <p class="note">
                            Transaksi tidak pernah dihapus. Kesalahan dikoreksi lewat pembatalan yang disertai alasan.
                            <?= $e($teksBatal) ?>
                        </p>
                        <form method="POST" action="<?= base_url('pengelola/transaksi-beli/batal') ?>" data-once
                              data-confirm="<?= $e($konfirmBatal) ?>">
                            <?= csrfField() ?>
                            <input type="hidden" name="id" value="<?= (int) ($trx['id'] ?? 0) ?>">
                            <div class="field">
                                <label class="label" for="alasan">Alasan pembatalan <span class="req" aria-hidden="true">*</span></label>
                                <textarea class="input" id="alasan" name="alasan" rows="3" required maxlength="200"
                                          placeholder="Contoh: salah timbangan, jumlah kilogram keliru."></textarea>
                                <p class="hint">Alasan ini tersimpan pada audit log dan tidak dapat diubah lagi. Maksimal 200 karakter.</p>
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