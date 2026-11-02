#!/bin/bash
# Dijalankan otomatis oleh image MariaDB hanya saat volume database masih kosong
# (pertama kali). Mengimpor skema, data awal, migrasi, dan trigger berurutan.
set -euo pipefail

cd /app-sql

FILES=(
    migrations/001_initial_schema.sql
    seeds/001_sample_data.sql
    patches/001_add_nasabah_users.sql
    migrations/002_create_sync_changes.sql
    migrations/003_offline_columns.sql
    migrations/004_koreksi_batal.sql
    migrations/005_detail_jual_harga.sql
    migrations/006_pencairan_pembatalan.sql
    migrations/007_users_wajib_ganti_sandi.sql
    triggers.sql
)

for f in "${FILES[@]}"; do
    echo "[init-db] $f"
    mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" "$MARIADB_DATABASE" < "$f"
done

# Akun data awal memakai kata sandi contoh. Di server, paksa ganti sandi
# saat login pertama (WAJIB_GANTI_SANDI_AWAL=0 untuk mematikan).
if [ "${WAJIB_GANTI_SANDI_AWAL:-1}" = "1" ]; then
    echo "[init-db] semua akun awal wajib ganti kata sandi saat login pertama"
    mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" "$MARIADB_DATABASE" \
        -e "UPDATE users SET wajib_ganti_sandi = 1;"
fi
