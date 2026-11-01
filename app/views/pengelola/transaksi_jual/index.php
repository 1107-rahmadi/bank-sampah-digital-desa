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
 *                                (ikut rentang tanggal filter)
 * @var string     $tanggalFrom
 * @var string     $tanggalTo
 * @var array      $transaksis   tiap item: id, no_transaksi, tanggal, pengepul_nama,
 *                                total_berat, total_nilai, status_bayar (BELUM|LUNAS),
 *                                status (SAH|BATAL)
 * @var int|null   $halaman       (opsional) halaman saat ini, mulai dari 1
 * @var int|null   $totalHalaman  (opsional) jumlah halaman; pager tampil bila lebih dari 1
 */

require_once VIEW_PATH . '/pengelola/partials/ui.php';

$menuAktif = 'pengelola/transaksi-jual';
$judulHalaman = 'Transaksi Jual';

$e = function ($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};

$stats = isset($stats) && is_array($stats) ? $stats : [];
$transaksis = isset($transaksis) && is_array($transaksis) ? $transaksis : [];
$tanggalFrom = isset($tanggalFrom) ? (string) $tanggalFrom : '';
$tanggalTo = isset($tanggalTo) ? (string) $tanggalTo : '';
$halaman = isset($halaman) ? max(1, (int) $halaman) : 1;
$totalHalaman = isset($totalHalaman) ? max(1, (int) $totalHalaman) : 1;
$hariIni = date('Y-m-d');

$totalTransaksi = (float) ($stats['total_transaksi'] ?? 0);
$totalBerat = (float) ($stats['total_berat'] ?? 0);
$totalNilai = (float) ($stats['total_nilai'] ?? 0);
$piutang = (float) ($stats['total_belum_bayar'] ?? 0);

$adaFilter = ($tanggalFrom !== '' || $tanggalTo !== '');

// Format tanggal hanya dipanggil bila ada isinya.
$dari = $tanggalFrom !== '' ? (string) tanggalSingkatIndonesia($tanggalFrom) : '';
$sampai = $tanggalTo !== '' ? (string) tanggalSingkatIndonesia($tanggalTo) : '';
if ($dari !== '' && $sampai !== '') {
    $subJudul = $dari . ' sampai ' . $sampai;
} elseif ($dari !== '') {
    $subJudul = 'Sejak ' . $dari;
} elseif ($sampai !== '') {
    $subJudul = 'Sampai ' . $sampai;
} else {
    $subJudul = 'Penjualan sampah ke pengepul. Stok berkurang setelah pengepul membayar.';
}

/* Alamat halaman tertentu dengan filter tanggal tetap terbawa. */
$urlHalaman = function ($p) use ($tanggalFrom, $tanggalTo) {
    $q = [];
    if ($tanggalFrom !== '') {
        $q['tanggal_from'] = $tanggalFrom;
    }
    if ($tanggalTo !== '') {
        $q['tanggal_to'] = $tanggalTo;
    }
    $q['page'] = (int) $p;
    return base_url('pengelola/transaksi-jual') . '?' . http_build_query($q);
};

/* Kelas dan ikon notice: pakai bawaan ui.php bila ada, kalau tidak pakai nilai aman. */
$noticeClass = isset($flashClass) && $flashClass !== '' ? $flashClass : 'info';
$noticeIcon = isset($flashIcon) && $flashIcon !== '' ? $flashIcon : 'info';

/*
 * Kelompokkan per tanggal. Data dari server sudah urut terbaru lebih dulu.
 */
$grup = [];
foreach ($transaksis as $t) {
    $ts = isset($t['tanggal']) ? strtotime((string) $t['tanggal']) : false;
    if ($ts) {
        $kunci = date('Y-m-d', $ts);
        $label = (string) tanggalSingkatIndonesia($kunci);
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
        .filter { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) auto auto; gap: 14px; align-items: end; margin-bottom: 20px; }
        .wallet-split { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; margin-top: 20px; }
        .split { padding: 12px 16px; border-radius: 16px; background: rgba(255, 255, 255, .14); border: 1px solid rgba(255, 255, 255, .18); }
        .split-label { font-size: .875rem; color: rgba(255, 255, 255, .92); }
        .split-value { margin-top: 2px; font-size: 1.1875rem; font-weight: 700; letter-spacing: -.02em; font-variant-numeric: tabular-nums; }

        .pager { display: flex; align-items: center; justify-content: center; gap: 14px; flex-wrap: wrap; margin-top: 24px; }
        .pager-info { font-size: .9375rem; font-weight: 600; color: var(--ink-3); }

        @media (max-width: 1100px) {
            .filter { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .filter .btn { grid-column: 1 / -1; }
        }
        @media (max-width: 560px) {
            .filter { grid-template-columns: minmax(0, 1fr); }
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
                    <div class="notice notice-<?= $e($noticeClass) ?>" role="status">
                        <?= icon($noticeIcon) ?><span><?= $e($flash['message']) ?></span>
                    </div>
                <?php endif; ?>

                <div class="page-head">
                    <div>
                        <h1>Transaksi Jual</h1>
                        <p class="lead"><?= $e($subJudul) ?></p>
                    </div>
                    <a class="btn btn-primary" href="<?= base_url('pengelola/transaksi-jual/add') ?>"><?= icon('plus') ?>Transaksi Baru</a>
                </div>

                <section class="wallet" aria-label="Ringkasan transaksi jual">
                    <span class="wallet-label"><?= icon('wallet') ?>Total nilai penjualan</span>
                    <p class="wallet-value"><?= $e(formatRupiah($totalNilai)) ?></p>

                    <div class="wallet-split">
                        <div class="split">
                            <div class="split-label">Total transaksi</div>
                            <div class="split-value"><?= $e(number_format($totalTransaksi, 0, ',', '.')) ?></div>
                        </div>
                        <div class="split">
                            <div class="split-label">Total berat</div>
                            <div class="split-value"><?= $e(formatBeratKg($totalBerat)) ?></div>
                        </div>
                        <div class="split">
                            <div class="split-label">Piutang</div>
                            <div class="split-value"><?= $e(formatRupiah($piutang)) ?></div>
                        </div>
                    </div>
                </section>

                <form method="GET" class="filter" aria-label="Filter tanggal">
                    <div>
                        <label class="label" for="tanggal_from">Dari tanggal</label>
                        <input class="input" type="date" name="tanggal_from" id="tanggal_from" value="<?= $e($tanggalFrom) ?>" max="<?= $e($tanggalTo !== '' ? $tanggalTo : $hariIni) ?>">
                    </div>
                    <div>
                        <label class="label" for="tanggal_to">Sampai tanggal</label>
                        <input class="input" type="date" name="tanggal_to" id="tanggal_to" value="<?= $e($tanggalTo) ?>"<?= $tanggalFrom !== '' ? ' min="' . $e($tanggalFrom) . '"' : '' ?> max="<?= $e($hariIni) ?>">
                    </div>
                    <button type="submit" class="btn btn-primary"><?= icon('search') ?>Tampilkan</button>
                    <?php if ($adaFilter): ?>
                        <a href="<?= base_url('pengelola/transaksi-jual') ?>" class="btn btn-ghost">Hapus filter</a>
                    <?php endif; ?>
                </form>

                <?php if (!empty($grup)): ?>
                    <?php foreach ($grup as $g): ?>
                        <h2 class="group-label"><?= $e($g['label']) ?></h2>
                        <ul class="list">
                            <?php foreach ($g['items'] as $t):
                                $lunas = strtoupper((string) ($t['status_bayar'] ?? '')) === 'LUNAS';
                                $batal = strtoupper((string) ($t['status'] ?? 'SAH')) === 'BATAL';
                            ?>
                                <li class="row">
                                    <span class="ico ico--info" aria-hidden="true"><?= icon('truck') ?></span>
                                    <span class="row-main">
                                        <span class="row-title">
                                            <a href="<?= base_url('pengelola/transaksi-jual/detail?id=' . (int) ($t['id'] ?? 0)) ?>"><?= $e($t['pengepul_nama'] ?? '-') ?></a>
                                        </span>
                                        <span class="row-sub">
                                            <span><?= $e($t['no_transaksi'] ?? '-') ?></span>
                                            <span><?= $e($t['_jam'] ?? '-') ?></span>
                                            <span><?= $e(formatBeratKg($t['total_berat'] ?? 0)) ?></span>
                                        </span>
                                    </span>
                                    <span class="row-end">
                                        <span class="row-amount"><?= $e(formatRupiah($t['total_nilai'] ?? 0)) ?></span>
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

                    <?php if ($totalHalaman > 1): ?>
                        <nav class="pager" aria-label="Halaman daftar transaksi">
                            <?php if ($halaman > 1): ?>
                                <a class="btn btn-ghost" href="<?= $e($urlHalaman($halaman - 1)) ?>">Sebelumnya</a>
                            <?php endif; ?>
                            <span class="pager-info">Halaman <?= (int) $halaman ?> dari <?= (int) $totalHalaman ?></span>
                            <?php if ($halaman < $totalHalaman): ?>
                                <a class="btn btn-ghost" href="<?= $e($urlHalaman($halaman + 1)) ?>">Selanjutnya</a>
                            <?php endif; ?>
                        </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty">
                        <span class="ico" aria-hidden="true"><?= icon('inbox') ?></span>
                        <h2>Belum ada transaksi</h2>
                        <p><?= $adaFilter
                            ? 'Tidak ada transaksi pada rentang tanggal ini.'
                            : 'Penjualan ke pengepul yang dicatat akan muncul di sini.' ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <?= pengelolaScripts() ?>
    <script>
    /* Rentang tanggal filter: batas bawah dan atas saling menyesuaikan. */
    (function () {
        var from = document.getElementById('tanggal_from');
        var to = document.getElementById('tanggal_to');
        var hariIni = <?= json_encode($hariIni) ?>;
        if (!from || !to) { return; }
        function sinkron() {
            to.min = from.value || '';
            from.max = to.value || hariIni;
        }
        from.addEventListener('change', sinkron);
        to.addEventListener('change', sinkron);
        sinkron();
    })();
    </script>
</body>
</html>