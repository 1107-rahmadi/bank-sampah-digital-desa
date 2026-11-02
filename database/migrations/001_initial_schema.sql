-- =====================================================
-- Bank Sampah Digital Desa - Initial Schema
-- Migration: 001_initial_schema.sql
-- Engine: InnoDB | Charset: utf8mb4
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------
-- 1. Tabel: desa
-- Master data desa/kelurahan
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `desa` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `kode_wilayah` VARCHAR(20) NULL,
    `nama` VARCHAR(100) NOT NULL,
    `kecamatan` VARCHAR(100) NOT NULL,
    `kabupaten` VARCHAR(100) NOT NULL,
    `provinsi` VARCHAR(100) NOT NULL DEFAULT 'Jawa Barat',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_desa_kode` (`kode_wilayah`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 2. Tabel: bank_sampah
-- Data unit bank sampah
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `bank_sampah` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `desa_id` BIGINT UNSIGNED NOT NULL,
    `nama` VARCHAR(150) NOT NULL,
    `alamat` VARCHAR(150) NULL,
    `latitude` DECIMAL(10,7) NULL,
    `longitude` DECIMAL(10,7) NULL,
    `penanggung_jawab` VARCHAR(100) NULL,
    `no_hp` VARCHAR(100) NULL,
    `status` ENUM('AKTIF','NONAKTIF') NOT NULL DEFAULT 'AKTIF',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_bank_desa` (`desa_id`),
    CONSTRAINT `fk_bank_desa` FOREIGN KEY (`desa_id`)
        REFERENCES `desa` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 3. Tabel: users
-- Akun pengguna sistem
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `desa_id` BIGINT UNSIGNED NULL,
    `bank_sampah_id` BIGINT UNSIGNED NULL,
    `nama` VARCHAR(100) NOT NULL,
    `no_hp` VARCHAR(20) NOT NULL,
    `email` VARCHAR(100) NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('ADMIN','PENGELOLA','KADES','NASABAH') NOT NULL,
    `status` ENUM('AKTIF','NONAKTIF') NOT NULL DEFAULT 'AKTIF',
    `last_login_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `idx_users_nohp` (`no_hp`),
    UNIQUE INDEX `idx_users_email` (`email`),
    INDEX `idx_users_role` (`role`),
    INDEX `idx_users_desa` (`desa_id`),
    CONSTRAINT `fk_users_desa` FOREIGN KEY (`desa_id`)
        REFERENCES `desa` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_users_bank` FOREIGN KEY (`bank_sampah_id`)
        REFERENCES `bank_sampah` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 4. Tabel: nasabah
-- Data anggota/nasabah bank sampah
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `nasabah` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NULL,
    `bank_sampah_id` BIGINT UNSIGNED NOT NULL,
    `no_anggota` VARCHAR(20) NOT NULL,
    `nama` VARCHAR(100) NOT NULL,
    `no_hp` VARCHAR(100) NOT NULL,
    `nik` VARCHAR(32) NULL COMMENT 'Rencana dienkripsi di level aplikasi',
    `alamat` VARCHAR(255) NULL,
    `rt` VARCHAR(5) NULL,
    `rw` VARCHAR(5) NULL,
    `tipe` ENUM('PERORANGAN','INSTANSI') NOT NULL DEFAULT 'PERORANGAN',
    `nama_bank` VARCHAR(100) NULL,
    `no_rekening` VARCHAR(50) NULL,
    `saldo` DECIMAL(14,2) NOT NULL DEFAULT 0,
    `status` ENUM('AKTIF','NONAKTIF') NOT NULL DEFAULT 'AKTIF',
    `tgl_daftar` DATE NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `idx_nasabah_user` (`user_id`),
    UNIQUE INDEX `idx_nasabah_noanggota` (`no_anggota`),
    INDEX `idx_nasabah_bank` (`bank_sampah_id`),
    INDEX `idx_nasabah_nama` (`nama`),
    CONSTRAINT `fk_nasabah_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_nasabah_bank` FOREIGN KEY (`bank_sampah_id`)
        REFERENCES `bank_sampah` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `chk_saldo_positif` CHECK (`saldo` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 5. Tabel: kategori_sampah
-- Kategori dan jenis sampah
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `kategori_sampah` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nama` VARCHAR(100) NOT NULL,
    `jenis` ENUM('ORGANIK','ANORGANIK') NOT NULL,
    `satuan` VARCHAR(10) NOT NULL DEFAULT 'kg',
    `aktif` BOOLEAN NOT NULL DEFAULT TRUE,
    PRIMARY KEY (`id`),
    INDEX `idx_kategori_jenis` (`jenis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 6. Tabel: harga_sampah
-- Harga beli/jual per kategori per bank sampah
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `harga_sampah` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `bank_sampah_id` BIGINT UNSIGNED NOT NULL,
    `kategori_id` BIGINT UNSIGNED NOT NULL,
    `harga_beli` DECIMAL(14,2) NOT NULL,
    `harga_jual` DECIMAL(14,2) NOT NULL,
    `berlaku_mulai` DATE NOT NULL,
    `aktif` BOOLEAN NOT NULL DEFAULT TRUE,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `idx_harga_unique` (`bank_sampah_id`, `kategori_id`, `berlaku_mulai`),
    INDEX `idx_harga_bank` (`bank_sampah_id`),
    INDEX `idx_harga_kategori` (`kategori_id`),
    CONSTRAINT `fk_harga_bank` FOREIGN KEY (`bank_sampah_id`)
        REFERENCES `bank_sampah` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_harga_kategori` FOREIGN KEY (`kategori_id`)
        REFERENCES `kategori_sampah` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 7. Tabel: pengepul
-- Data pengepul/pedagang pengumpul
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `pengepul` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `bank_sampah_id` BIGINT UNSIGNED NOT NULL,
    `nama` VARCHAR(150) NOT NULL,
    `no_hp` VARCHAR(150) NULL,
    `alamat` VARCHAR(150) NULL,
    `status` ENUM('AKTIF','NONAKTIF') NOT NULL DEFAULT 'AKTIF',
    PRIMARY KEY (`id`),
    INDEX `idx_pengepul_bank` (`bank_sampah_id`),
    CONSTRAINT `fk_pengepul_bank` FOREIGN KEY (`bank_sampah_id`)
        REFERENCES `bank_sampah` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 8. Tabel: transaksi_beli
-- Transaksi pembelian sampah dari nasabah/non-nasabah
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `transaksi_beli` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `no_transaksi` VARCHAR(20) NOT NULL,
    `bank_sampah_id` BIGINT UNSIGNED NOT NULL,
    `tipe_penjual` ENUM('NASABAH','NON_NASABAH') NOT NULL,
    `nasabah_id` BIGINT UNSIGNED NULL,
    `nama_penjual` VARCHAR(100) NULL COMMENT 'Untuk non-nasabah',
    `metode_bayar` ENUM('TABUNG','TUNAI') NOT NULL,
    `tanggal` DATETIME NOT NULL,
    `total_berat` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `total_nilai` DECIMAL(14,2) NOT NULL DEFAULT 0,
    `petugas_id` BIGINT UNSIGNED NOT NULL,
    `status` ENUM('SAH','BATAL') NOT NULL DEFAULT 'SAH',
    `catatan` TEXT NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `idx_beli_notrans` (`no_transaksi`),
    INDEX `idx_beli_bank_tanggal` (`bank_sampah_id`, `tanggal`),
    INDEX `idx_beli_nasabah` (`nasabah_id`),
    INDEX `idx_beli_petugas` (`petugas_id`),
    INDEX `idx_beli_status` (`status`),
    CONSTRAINT `fk_beli_bank` FOREIGN KEY (`bank_sampah_id`)
        REFERENCES `bank_sampah` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    -- RESTRICT, bukan SET NULL/CASCADE: nasabah_id dipakai di chk_beli_tipe,
    -- dan MariaDB >= 10.5 menolak kolom CHECK yang diubah otomatis oleh FK.
    -- SET NULL juga akan melanggar chk_beli_tipe (BR-08: transaksi tidak dihapus).
    CONSTRAINT `fk_beli_nasabah` FOREIGN KEY (`nasabah_id`)
        REFERENCES `nasabah` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT `fk_beli_petugas` FOREIGN KEY (`petugas_id`)
        REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    -- CHECK constraint: harus sesuai tipe
    CONSTRAINT `chk_beli_tipe` CHECK (
        (`tipe_penjual` = 'NASABAH' AND `nasabah_id` IS NOT NULL)
        OR (`tipe_penjual` = 'NON_NASABAH' AND `nasabah_id` IS NULL AND `metode_bayar` = 'TUNAI')
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 9. Tabel: detail_beli
-- Detail item transaksi pembelian
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `detail_beli` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `transaksi_beli_id` BIGINT UNSIGNED NOT NULL,
    `kategori_id` BIGINT UNSIGNED NOT NULL,
    `berat_kg` DECIMAL(10,2) NOT NULL,
    `harga_per_kg` DECIMAL(14,2) NOT NULL,
    `subtotal` DECIMAL(14,2) NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_detailbeli_trans` (`transaksi_beli_id`),
    INDEX `idx_detailbeli_kategori` (`kategori_id`),
    CONSTRAINT `fk_detailbeli_trans` FOREIGN KEY (`transaksi_beli_id`)
        REFERENCES `transaksi_beli` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_detailbeli_kategori` FOREIGN KEY (`kategori_id`)
        REFERENCES `kategori_sampah` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `chk_berat_positif` CHECK (`berat_kg` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 10. Tabel: transaksi_jual
-- Transaksi penjualan sampah ke pengepul
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `transaksi_jual` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `no_transaksi` VARCHAR(20) NOT NULL,
    `bank_sampah_id` BIGINT UNSIGNED NOT NULL,
    `pengepul_id` BIGINT UNSIGNED NOT NULL,
    `petugas_id` BIGINT UNSIGNED NOT NULL,
    `tanggal` DATETIME NOT NULL,
    `total_berat` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `total_nilai` DECIMAL(14,2) NOT NULL DEFAULT 0,
    `status_bayar` ENUM('BELUM','LUNAS') NOT NULL DEFAULT 'BELUM',
    `status` ENUM('SAH','BATAL') NOT NULL DEFAULT 'SAH',
    `catatan` TEXT NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `idx_jual_notrans` (`no_transaksi`),
    INDEX `idx_jual_bank_tanggal` (`bank_sampah_id`, `tanggal`),
    INDEX `idx_jual_pengepul` (`pengepul_id`),
    INDEX `idx_jual_petugas` (`petugas_id`),
    INDEX `idx_jual_statusbayar` (`status_bayar`),
    CONSTRAINT `fk_jual_bank` FOREIGN KEY (`bank_sampah_id`)
        REFERENCES `bank_sampah` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_jual_pengepul` FOREIGN KEY (`pengepul_id`)
        REFERENCES `pengepul` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_jual_petugas` FOREIGN KEY (`petugas_id`)
        REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 11. Tabel: detail_jual
-- Detail item transaksi penjualan
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `detail_jual` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `transaksi_jual_id` BIGINT UNSIGNED NOT NULL,
    `kategori_id` BIGINT UNSIGNED NOT NULL,
    `berat_kg` DECIMAL(10,2) NOT NULL,
    `harga_per_kg` DECIMAL(14,2) NOT NULL,
    `subtotal` DECIMAL(14,2) NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_detailjual_trans` (`transaksi_jual_id`),
    INDEX `idx_detailjual_kategori` (`kategori_id`),
    CONSTRAINT `fk_detailjual_trans` FOREIGN KEY (`transaksi_jual_id`)
        REFERENCES `transaksi_jual` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_detailjual_kategori` FOREIGN KEY (`kategori_id`)
        REFERENCES `kategori_sampah` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 12. Tabel: stok_sampah
-- Stok per kategori per bank sampah (komulatif)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `stok_sampah` (
    `bank_sampah_id` BIGINT UNSIGNED NOT NULL,
    `kategori_id` BIGINT UNSIGNED NOT NULL,
    `berat_kg` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`bank_sampah_id`, `kategori_id`),
    INDEX `idx_stok_kategori` (`kategori_id`),
    CONSTRAINT `fk_stok_bank` FOREIGN KEY (`bank_sampah_id`)
        REFERENCES `bank_sampah` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_stok_kategori` FOREIGN KEY (`kategori_id`)
        REFERENCES `kategori_sampah` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `chk_stok_positif` CHECK (`berat_kg` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 13. Tabel: mutasi_saldo
-- Riwayat perubahan saldo anggota
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mutasi_saldo` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nasabah_id` BIGINT UNSIGNED NOT NULL,
    `tipe` ENUM('SETOR','PENCAIRAN','KOREKSI') NOT NULL,
    `jumlah` DECIMAL(14,2) NOT NULL COMMENT 'Positif=tambah, Negatif=kurang',
    `saldo_setelah` DECIMAL(14,2) NOT NULL,
    `referensi_tipe` VARCHAR(30) NULL COMMENT 'misal: transaksi_beli, pengajuan_pencairan',
    `referensi_id` BIGINT UNSIGNED NULL,
    `keterangan` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_mutasi_nasabah_waktu` (`nasabah_id`, `created_at`),
    INDEX `idx_mutasi_tipe` (`tipe`),
    INDEX `idx_mutasi_ref` (`referensi_tipe`, `referensi_id`),
    CONSTRAINT `fk_mutasi_nasabah` FOREIGN KEY (`nasabah_id`)
        REFERENCES `nasabah` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 14. Tabel: pengajuan_pencairan
-- Permintaan pencairan saldo oleh nasabah
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `pengajuan_pencairan` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `no_pengajuan` VARCHAR(20) NOT NULL,
    `nasabah_id` BIGINT UNSIGNED NOT NULL,
    `jumlah` DECIMAL(14,2) NOT NULL,
    `metode` ENUM('TUNAI','TRANSFER') NOT NULL,
    `tujuan_transfer` VARCHAR(100) NULL,
    `status` ENUM('DIAJUKAN','DISETUJUI','DITOLAK','DICAIRKAN','DIBATALKAN') NOT NULL DEFAULT 'DIAJUKAN',
    `alasan_tolak` VARCHAR(200) NULL,
    `diajukan_at` DATETIME NOT NULL,
    `diproses_at` DATETIME NULL,
    `dicairkan_at` DATETIME NULL,
    `diproses_oleh` BIGINT UNSIGNED NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `idx_pencairan_nopeng` (`no_pengajuan`),
    INDEX `idx_pencairan_status_nasabah` (`status`, `nasabah_id`),
    INDEX `idx_pencairan_nasabah` (`nasabah_id`),
    INDEX `idx_pencairan_tanggal` (`diajukan_at`),
    CONSTRAINT `fk_pencairan_nasabah` FOREIGN KEY (`nasabah_id`)
        REFERENCES `nasabah` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_pencairan_diproses` FOREIGN KEY (`diproses_oleh`)
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 15. Tabel: notifikasi
-- Sistem notifikasi ke pengguna
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifikasi` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `judul` VARCHAR(150) NOT NULL,
    `isi` TEXT NOT NULL,
    `tipe` VARCHAR(30) NOT NULL,
    `dibaca_at` DATETIME NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_notif_user` (`user_id`),
    INDEX `idx_notif_dibaca` (`dibaca_at`),
    INDEX `idx_notif_tipe` (`tipe`),
    CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 16. Tabel: audit_log
-- Log aktivitas untuk audit trail
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_log` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NULL,
    `aksi` VARCHAR(30) NOT NULL,
    `tabel` VARCHAR(50) NOT NULL,
    `record_id` BIGINT UNSIGNED NOT NULL,
    `sebelum` JSON NULL,
    `sesudah` JSON NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_audit_user` (`user_id`),
    INDEX `idx_audit_tabel` (`tabel`),
    INDEX `idx_audit_record` (`tabel`, `record_id`),
    INDEX `idx_audit_waktu` (`created_at`),
    CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Enable foreign key checks
-- -----------------------------------------------------
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- End of Migration: 001_initial_schema.sql
-- =====================================================
