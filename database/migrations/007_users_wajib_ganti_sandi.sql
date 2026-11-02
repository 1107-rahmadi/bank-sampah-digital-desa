-- =====================================================
-- Bank Sampah Digital Desa - Migration: 007
--
-- Kolom penanda wajib ganti kata sandi pada login berikutnya.
-- Dipakai oleh User.php, AdminController (reset sandi),
-- PasswordController dan AkunGuard, tetapi belum ada di 001-006.
-- =====================================================

SET NAMES utf8mb4;

ALTER TABLE `users`
    ADD COLUMN `wajib_ganti_sandi` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`;
