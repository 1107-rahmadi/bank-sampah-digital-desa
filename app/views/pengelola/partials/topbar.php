<?php
/**
 * pengelola/partials/topbar.php
 * Topbar bersama untuk SEMUA halaman Pengelola.
 * Compatible dengan PHP 7.3+
 *
 * Cara pakai di setiap view pengelola:
 *   $judulHalaman = 'Transaksi Beli';
 *   <?php include VIEW_PATH . '/pengelola/partials/topbar.php'; ?>
 *
 * Isi topbar:
 * - tombol burger (buka/tutup sidebar di layar kecil), id="burger"
 * - judul halaman
 * - identitas petugas (nama + inisial)
 *
 * Perilaku tombol burger dan tombol "nav" ada di assets/js/Pengelola.js
 * yang dimuat lewat pengelolaScripts() di partials/ui.php.
 * Karena itu halaman WAJIB memuat pengelolaScripts() di akhir <body>,
 * kalau tidak tombol burger tidak bekerja dan sidebar tidak bisa dibuka
 * di layar ponsel.
 *
 * Variabel yang dipakai (disediakan partials/ui.php):
 * - $judulHalaman : teks judul (opsional, default "Pengelola")
 * - $namaUser     : nama lengkap petugas
 * - $inisial      : huruf pertama nama untuk avatar
 * - $appName      : nama aplikasi
 */

$topbarJudul = isset($judulHalaman) && is_string($judulHalaman) && trim($judulHalaman) !== ''
    ? trim($judulHalaman)
    : 'Pengelola';

$topbarNama = isset($namaUser) ? (string) $namaUser : 'Pengelola';
$topbarInisial = isset($inisial) ? (string) $inisial : 'P';
?>
<header class="topbar">
    <button type="button" class="burger" id="burger" aria-controls="sidebar" aria-expanded="false"
            aria-label="Buka atau tutup menu"><?= icon('menu') ?></button>
    <span class="top-title"><?= htmlspecialchars($topbarJudul, ENT_QUOTES, 'UTF-8') ?></span>
    <span class="who">
        <span class="who-name"><?= htmlspecialchars($topbarNama, ENT_QUOTES, 'UTF-8') ?></span>
        <span class="who-avatar" aria-hidden="true"><?= htmlspecialchars($topbarInisial, ENT_QUOTES, 'UTF-8') ?></span>
    </span>
</header>
