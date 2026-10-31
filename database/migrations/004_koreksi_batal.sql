-- =====================================================
-- Bank Sampah Digital Desa - Migration: 004
--
-- Menuju BR-08 (transaksi tidak dihapus, koreksi lewat pembatalan
-- dengan alasan), NFR-03 (integritas stok & saldo) dan NFR-08
-- (auditabilitas).
--
-- Catatan: database yang berjalan adalah MariaDB 10.4 yang TIDAK
-- menegakkan CHECK constraint. Kepunyakannya dipindahkan ke trigger
-- di bawah ini supaya stok dan saldo tidak pernah menjadi negatif.
-- =====================================================

SET NAMES utf8mb4;

-- -----------------------------------------------------
-- 1. Alasan pembatalan transaksi
-- Transaksi tidak dihapus (BR-08). Kesalahan dikoreksi lewat
-- pembatalan dengan alasan, jadi alasannya harus tersimpan.
-- -----------------------------------------------------
ALTER TABLE `transaksi_beli`
    ADD COLUMN `alasan_batal` VARCHAR(200) NULL AFTER `catatan`;

ALTER TABLE `transaksi_jual`
    ADD COLUMN `alasan_batal` VARCHAR(200) NULL AFTER `catatan`,
    ADD COLUMN `dibayar_at` DATETIME NULL AFTER `status_bayar`;

ALTER TABLE `pengajuan_pencairan`
    ADD COLUMN `alasan_batal` VARCHAR(200) NULL AFTER `alasan_tolak`;

-- -----------------------------------------------------
-- 2. Penanda pencairan dibatalkan oleh nasabah (FR-08)
-- -----------------------------------------------------
ALTER TABLE `pengajuan_pencairan`
    ADD COLUMN `dibatalkan_at` DATETIME NULL AFTER `dicairkan_at`;

-- -----------------------------------------------------
-- 3. Integritas stok & saldo
--
-- MariaDB 10.4 tidak menegakkan CHECK constraint, sehingga
-- chk_stok_positif dan chk_saldo_positif tidak melindungi data.
-- Pemeriksaan negatif dilakukan di sisi server (NFR-03) dan
-- dilindungi trigger pada file database/triggers.sql
-- (file itu memakai DELIMITER, jadi dijalankan lewat mysql CLI).
-- -----------------------------------------------------

-- -----------------------------------------------------
-- 4. Catatan aturan yang tidak bisa ditegakkan MariaDB
--    BR-02 (non-nasabah wajib dibayar tunai) dipaksa di sisi
--    server, di application/controllers/PengelolaController.php.
-- -----------------------------------------------------

-- =====================================================
-- End of Migration: 004_koreksi_batal.sql
-- =====================================================
