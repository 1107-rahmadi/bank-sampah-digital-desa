CREATE TABLE IF NOT EXISTS sync_changes (
  seq         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  entitas     VARCHAR(30) NOT NULL,      -- nasabah | harga_sampah | kategori_sampah
  entitas_id  BIGINT UNSIGNED NOT NULL,
  aksi        VARCHAR(10) NOT NULL,      -- upsert | delete
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_sync_entitas (entitas, entitas_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;