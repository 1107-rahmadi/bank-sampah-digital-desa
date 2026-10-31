ALTER TABLE transaksi_beli
    ADD COLUMN uuid CHAR(36) NULL AFTER id,
    ADD COLUMN device_id VARCHAR(40) NULL,
    ADD COLUMN created_at_device DATETIME NULL,
    ADD COLUMN received_at DATETIME NULL;

-- data lama diberi uuid supaya kolomnya bisa NOT NULL
UPDATE transaksi_beli SET uuid = UUID() WHERE uuid IS NULL;
UPDATE transaksi_beli SET received_at = tanggal WHERE received_at IS NULL;

ALTER TABLE transaksi_beli
    MODIFY COLUMN uuid CHAR(36) NOT NULL,
    ADD UNIQUE INDEX idx_beli_uuid (uuid);

ALTER TABLE detail_beli
    ADD COLUMN harga_id BIGINT UNSIGNED NULL,
    ADD INDEX idx_detailbeli_harga (harga_id),
    ADD CONSTRAINT fk_detailbeli_harga FOREIGN KEY (harga_id)
    REFERENCES harga_sampah (id) ON DELETE SET NULL ON UPDATE CASCADE;