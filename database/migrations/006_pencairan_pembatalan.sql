-- =====================================================
-- Bank Sampah Digital Desa - Migration: 006
--
-- Menuju aturan status pencairan yang seragam:
--   DIAJUKAN (Menunggu) -> DISETUJUI (Disetujui) -> DICAIRKAN (Dicairkan)
--   DIAJUKAN -> DITOLAK (Ditolak)
--   DIAJUKAN -> DIBATALKAN (Dibatalkan oleh nasabah)
--   DISETUJUI -> DIBATALKAN (Dibatalkan oleh pengelola, alasan wajib)
--
-- Kolom yang sudah ada (dibuat di 001 dan 004):
--   alasan_batal  VARCHAR(200) NULL
--   dibatalkan_at DATETIME NULL
-- Yang ditambahkan di migrasi ini:
--   dibatalkan_oleh ENUM('NASABAH','PENGELOLA') NULL
--   dipakai untuk menampilkan pelaku pembatalan di UI
--   ("Dibatalkan nasabah" / "Dibatalkan pengelola").
--
-- Migrasi ini aman dijalankan berulang kali: setiap perubahan dicek
-- dulu ke information_schema, dan data lama tidak dihapus/diubah
-- statusnya (BR-08: transaksi tidak dihapus, hanya diubah statusnya).
-- Pengajuan DIBATALKAN lama dianggap dibatalkan oleh NASABAH,
-- karena sebelum migrasi ini pembatalan hanya bisa dilakukan nasabah.
-- =====================================================

SET NAMES utf8mb4;

-- -----------------------------------------------------
-- 1. Kolom baru: pelaku pembatalan
-- -----------------------------------------------------
SET @kolom_ada := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'pengajuan_pencairan'
      AND COLUMN_NAME = 'dibatalkan_oleh'
);

SET @sql_tambah := IF(
    @kolom_ada = 0,
    'ALTER TABLE `pengajuan_pencairan`
        ADD COLUMN `dibatalkan_oleh` ENUM(''NASABAH'',''PENGELOLA'') NULL
        AFTER `dibatalkan_at`',
    'SELECT ''kolom dibatalkan_oleh sudah ada'''
);

PREPARE stmt_tambah FROM @sql_tambah;
EXECUTE stmt_tambah;
DEALLOCATE PREPARE stmt_tambah;

-- -----------------------------------------------------
-- 2. Jaring pengaman: alasan_batal & dibatalkan_at
--    Seharusnya sudah ada dari migrasi 001/004, tapi tetap
--    dicek supaya migrasi ini aman pada database yang
--    belum menjalankan 004.
-- -----------------------------------------------------
SET @alasan_ada := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'pengajuan_pencairan'
      AND COLUMN_NAME = 'alasan_batal'
);

SET @sql_alasan := IF(
    @alasan_ada = 0,
    'ALTER TABLE `pengajuan_pencairan`
        ADD COLUMN `alasan_batal` VARCHAR(200) NULL AFTER `alasan_tolak`',
    'SELECT ''kolom alasan_batal sudah ada'''
);

PREPARE stmt_alasan FROM @sql_alasan;
EXECUTE stmt_alasan;
DEALLOCATE PREPARE stmt_alasan;

SET @waktu_ada := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'pengajuan_pencairan'
      AND COLUMN_NAME = 'dibatalkan_at'
);

SET @sql_waktu := IF(
    @waktu_ada = 0,
    'ALTER TABLE `pengajuan_pencairan`
        ADD COLUMN `dibatalkan_at` DATETIME NULL AFTER `dicairkan_at`',
    'SELECT ''kolom dibatalkan_at sudah ada'''
);

PREPARE stmt_waktu FROM @sql_waktu;
EXECUTE stmt_waktu;
DEALLOCATE PREPARE stmt_waktu;

-- -----------------------------------------------------
-- 3. Backfill data lama
--    Pengajuan yang sudah berstatus DIBATALKAN sebelum migrasi
--    ini hanya bisa dibatalkan oleh nasabah, jadi pelaku diisi NASABAH.
--    Kolom dibatalkan_at yang kosong diisi dengan waktu pembatalan
--    yang sudah tercatat (diproses_at), supaya riwayat tetap masuk akal.
-- -----------------------------------------------------
UPDATE `pengajuan_pencairan`
SET `dibatalkan_oleh` = 'NASABAH'
WHERE `status` = 'DIBATALKAN'
  AND (`dibatalkan_oleh` IS NULL OR `dibatalkan_oleh` = '');

UPDATE `pengajuan_pencairan`
SET `dibatalkan_at` = `diproses_at`
WHERE `status` = 'DIBATALKAN'
  AND `dibatalkan_at` IS NULL
  AND `diproses_at` IS NOT NULL;

UPDATE `pengajuan_pencairan`
SET `dibatalkan_at` = NOW()
WHERE `status` = 'DIBATALKAN'
  AND `dibatalkan_at` IS NULL;

-- Pengajuan yang belum DIBATALKAN tidak punya pelaku pembatalan.
UPDATE `pengajuan_pencairan`
SET `dibatalkan_oleh` = NULL
WHERE `status` <> 'DIBATALKAN';

-- =====================================================
-- End of Migration: 006_pencairan_pembatalan.sql
-- =====================================================
