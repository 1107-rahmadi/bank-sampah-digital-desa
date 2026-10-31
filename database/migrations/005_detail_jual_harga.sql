-- =====================================================
-- Bank Sampah Digital Desa - Migration: 005
--
-- BR-01: harga per kg disalin ke detail transaksi. Untuk penjualan,
-- detail_jual juga perlu menunjuk harga yang dipakai (harga_id),
-- seperti detail_beli.
-- =====================================================

SET NAMES utf8mb4;

ALTER TABLE `detail_jual`
    ADD COLUMN `harga_id` BIGINT UNSIGNED NULL AFTER `subtotal`,
    ADD INDEX `idx_detailjual_harga` (`harga_id`),
    ADD CONSTRAINT `fk_detailjual_harga` FOREIGN KEY (`harga_id`)
        REFERENCES `harga_sampah` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- =====================================================
-- End of Migration: 005_detail_jual_harga.sql
-- =====================================================
