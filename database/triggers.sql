-- =====================================================
-- Trigger integritas data - Bank Sampah Digital Desa
--
-- MariaDB 10.4 (versi yang dipakai di XAMPP) TIDAK menegakkan
-- CHECK constraint. Karena itu chk_stok_positif dan
-- chk_saldo_positif di migrasi 001 tidak berfungsi. Trigger di
-- bawah ini menggantinya supaya stok dan saldo tidak pernah
-- menjadi negatif (NFR-03).
--
-- Cara menjalankan (mysql CLI):
--   cd c:/xampp/mysql/bin
--   ./mysql.exe -uroot bank_sampah_desa < ../../htdocs/belajar_ai/database/triggers.sql
--
-- Di phpMyAdmin: jalankan tiap blok CREATE TRIGGER satu per satu
-- (tanpa DELIMITER).
-- =====================================================

DELIMITER $$

DROP TRIGGER IF EXISTS `trg_stok_tidak_negatif` $$
CREATE TRIGGER `trg_stok_tidak_negatif` BEFORE UPDATE ON `stok_sampah`
FOR EACH ROW
BEGIN
    IF NEW.berat_kg < 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Stok tidak boleh menjadi negatif';
    END IF;
END $$

DROP TRIGGER IF EXISTS `trg_saldo_tidak_negatif` $$
CREATE TRIGGER `trg_saldo_tidak_negatif` BEFORE UPDATE ON `nasabah`
FOR EACH ROW
BEGIN
    IF NEW.saldo < 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Saldo nasabah tidak boleh menjadi negatif';
    END IF;
END $$

DELIMITER ;
