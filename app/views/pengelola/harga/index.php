<?php
// pengelola/harga/index.php
// Halaman HANYA LIHAT. Pengelolaan harga dan kategori adalah tugas Admin (FR-19).
// Variabel dari controller (dipertahankan): $hargaList, $flash (opsional)
// Variabel dari controller (sudah dikirim PengelolaController::harga()): $hargaAktif
// Field tiap harga: id, kategori_nama, jenis, harga_beli, harga_jual, berlaku_mulai

include_once VIEW_PATH . '/pengelola/partials/ui.php';

$menuAktif = 'pengelola/harga';
$judulHalaman = 'Harga Sampah';

$lower = function ($v) {
    return function_exists('mb_strtolower') ? mb_strtolower((string) $v, 'UTF-8') : strtolower((string) $v);
};

$hargaList = (isset($hargaList) && is_array($hargaList)) ? $hargaList : [];

// Tampilkan hanya harga yang berlaku hari ini (satu per kategori), yaitu harga yang
// sama dengan yang dipakai saat mencatat transaksi (BR-01).
// ASUMSI: $hargaAktif = HargaSampah::getPetaHarga() berisi baris dengan kunci 'id',
// dan 'id' itu sama dengan 'id' pada tiap baris $hargaList (kolom harga_sampah.id).
// Bila $hargaAktif tidak dikirim, atau tidak ada id yang cocok (asumsi meleset),
// daftar ditampilkan apa adanya seperti sebelumnya.
if (isset($hargaAktif) && is_array($hargaAktif)) {
    $idAktif = [];
    foreach ($hargaAktif as $a) {
        if (is_array($a) && isset($a['id'])) {
            $idAktif[(int) $a['id']] = true;
        }
    }

    $hanyaAktif = array_values(array_filter($hargaList, function ($h) use ($idAktif) {
        return isset($idAktif[(int) ($h['id'] ?? 0)]);
    }));

    if (empty($hargaAktif)) {
        // Tidak ada harga yang berlaku hari ini
        $hargaList = [];
    } elseif (!empty($hanyaAktif)) {
        $hargaList = $hanyaAktif;
    }
}

$total = count($hargaList);
$hariIni = date('Y-m-d');

// Pembaruan terakhir = tanggal berlaku terbaru yang sudah tiba; jenis unik untuk filter
$terbaru = 0;
$jenisList = [];
foreach ($hargaList as $h) {
    $mulai = (string) ($h['berlaku_mulai'] ?? '');
    $t = strtotime($mulai);
    if ($t && substr($mulai, 0, 10) <= $hariIni && $t > $terbaru) {
        $terbaru = $t;
    }
    $j = trim((string) ($h['jenis'] ?? ''));
    if ($j !== '') {
        $key = $lower($j);
        if (!isset($jenisList[$key])) {
            $jenisList[$key] = ['label' => $j, 'n' => 0];
        }
        $jenisList[$key]['n']++;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?= pengelolaHead($judulHalaman) ?>
    <style>
        /* Gaya khusus halaman harga: baris dengan dua kolom harga */
        .row--harga { display: grid; grid-template-columns: 46px minmax(0, 1fr) auto; grid-template-areas: "ico main prices"; gap: 4px 18px; }
        .row--harga .ico { grid-area: ico; }
        .row--harga .row-main { grid-area: main; }
        .row--harga .prices { grid-area: prices; }
        .prices { display: flex; gap: 24px; }
        .price small { display: block; font-size: .78rem; font-weight: 600; color: var(--ink-3); }
        .price strong { font-weight: 800; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .price em { font-style: normal; font-weight: 600; font-size: .82rem; color: var(--ink-3); }
        .price.beli strong { color: var(--g-700); }
        .price.jual strong { color: var(--amber); }
        @media (max-width: 720px) {
            .row--harga { grid-template-columns: 46px minmax(0, 1fr); grid-template-areas: "ico main" "ico prices"; }
            .row--harga .prices { margin-top: 8px; gap: 28px; }
        }
    </style>
</head>
<body>
    <?php include VIEW_PATH . '/pengelola/partials/sidebar.php'; ?>

    <div class="main">
        <?php include VIEW_PATH . '/pengelola/partials/topbar.php'; ?>

        <main class="content">
            <div class="wrap">
                <a class="back" href="<?= base_url('pengelola/dashboard') ?>"><?= icon('back', 18) ?>Dashboard</a>

                <?php if (!empty($flash) && !empty($flash['message'])): ?>
                    <div class="notice notice-<?= $flashClass ?>" role="status"><?= icon($flashIcon, 20) ?><span><?= esc($flash['message']) ?></span></div>
                <?php endif; ?>

                <div class="page-head">
                    <div>
                        <h1>Harga Sampah</h1>
                        <p class="lead">Harga beli dari nasabah dan harga jual ke pengepul per kilogram. Perubahan harga dilakukan oleh admin.</p>
                    </div>
                </div>

                <section class="wallet" aria-label="Ringkasan harga">
                    <p class="wallet-label">Harga yang berlaku saat ini</p>
                    <p class="wallet-value"><?= (int) $total ?> kategori</p>
                    <div class="wallet-stats">
                        <span class="chip">Hanya lihat</span>
                        <?php if ($terbaru): ?>
                            <span class="chip">Pembaruan terakhir <?= esc(date('d/m/Y', $terbaru)) ?></span>
                        <?php endif; ?>
                    </div>
                </section>

                <?php if ($total === 0): ?>
                    <div class="empty">
                        <div class="ico"><?= icon('tag', 28) ?></div>
                        <h2>Belum ada harga yang berlaku</h2>
                        <p>Harga beli dan harga jual diatur oleh admin. Hubungi admin untuk mengatur harga setiap kategori sampah.</p>
                    </div>
                <?php else: ?>
                    <div data-filter-scope>
                        <div class="search">
                            <?= icon('search', 20) ?>
                            <input type="search" data-filter-input placeholder="Cari kategori atau jenis" aria-label="Cari harga sampah" autocomplete="off">
                        </div>

                        <?php if (count($jenisList) > 1): ?>
                            <div class="filters" role="group" aria-label="Filter jenis">
                                <button type="button" class="fbtn" data-filter-btn data-value="all" aria-pressed="true">Semua (<?= (int) $total ?>)</button>
                                <?php foreach ($jenisList as $key => $j): ?>
                                    <button type="button" class="fbtn" data-filter-btn data-value="<?= esc($key) ?>" aria-pressed="false"><?= esc($j['label']) ?> (<?= (int) $j['n'] ?>)</button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <p class="sr-only" data-filter-status aria-live="polite"></p>

                        <ul class="list">
                            <?php foreach ($hargaList as $h):
                                $kategori = $h['kategori_nama'] ?? '';
                                $jenis = trim((string) ($h['jenis'] ?? ''));
                            ?>
                                <li class="row row--harga" data-filter-item data-value="<?= esc($lower($jenis)) ?>" data-cari="<?= esc($lower($kategori . ' ' . $jenis)) ?>">
                                    <span class="ico" aria-hidden="true"><?= icon('box', 22) ?></span>
                                    <div class="row-main">
                                        <p class="row-title"><?= esc($kategori) ?></p>
                                        <p class="row-sub">
                                            <?php if ($jenis !== ''): ?><span class="badge"><?= esc($jenis) ?></span><?php endif; ?>
                                            <span class="when"><?= icon('calendar', 15) ?>Berlaku <?= esc(tanggalSingkatIndonesia($h['berlaku_mulai'] ?? '')) ?></span>
                                        </p>
                                    </div>
                                    <div class="prices">
                                        <div class="price beli"><small>Harga beli</small><strong><?= esc(formatRupiah($h['harga_beli'] ?? 0)) ?></strong><em>/kg</em></div>
                                        <div class="price jual"><small>Harga jual</small><strong><?= esc(formatRupiah($h['harga_jual'] ?? 0)) ?></strong><em>/kg</em></div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <div class="no-result" data-filter-empty>Tidak ada harga yang cocok dengan pencarian.</div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <?= pengelolaScripts() ?>
</body>
</html>