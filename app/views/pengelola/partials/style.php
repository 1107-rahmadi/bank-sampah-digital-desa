<?php
/**
 * Gaya bersama untuk halaman pengelola.
 *
 * Dulu berkas ini berisi salinan penuh halaman "Harga Sampah" (dokumen HTML
 * kedua: doctype, head, body, dan skripnya). Setiap halaman yang memakainya
 * menyisipkan dokumen itu di dalam <head>, sehingga:
 * - isi halaman "Harga Sampah" muncul sebagai tabel asing di dalam halaman lain,
 * - tombol menu didaftarkan dua kali sehingga klik tidak mengubah apa pun,
 * - pencarian daftar dijalankan dua kali dengan aturan berbeda.
 *
 * Sekarang isinya hanya memuat stylesheet bersama. Gaya yang khusus untuk satu
 * halaman tetap ditulis di <style> milik view itu sendiri.
 *
 * Pemakaian (view yang punya <head> sendiri):
 *   <?php include VIEW_PATH . '/pengelola/partials/style.php'; ?>
 */

$assetVersion = defined('ASSET_VERSION') ? '?v=' . rawurlencode((string) ASSET_VERSION) : '';
?>
<link rel="stylesheet" href="<?= htmlspecialchars(base_url('assets/css/Pengelola.css') . $assetVersion, ENT_QUOTES, 'UTF-8') ?>">
