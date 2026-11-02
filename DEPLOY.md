# Deploy ke Server (Ubuntu + Docker + tunnel.my.id)

Aplikasi berjalan dalam dua container:

| Container | Isi | Port |
|---|---|---|
| `web` | PHP 8.2 + Apache, kode aplikasi dan `/api` | `127.0.0.1:8080` (hanya dari server sendiri) |
| `db` | MariaDB 10.11, data di volume `db_data` | tidak dibuka keluar |

Akses publik lewat tunnel.my.id yang diarahkan ke `127.0.0.1:8080`. HTTPS diurus oleh tunnel.

## 1. Pasang Docker (sekali saja)

```bash
sudo apt update
sudo apt install -y docker.io docker-compose-v2 git
sudo usermod -aG docker $USER   # lalu logout dan login lagi
docker compose version          # pastikan muncul versinya
```

## 2. Ambil kode dan isi konfigurasi

```bash
git clone <url-repo> banksampah
cd banksampah
cp docker.env.example docker.env
nano docker.env
```

Yang wajib diisi di `docker.env`:

- `APP_URL`: alamat publik dari tunnel, mis. `https://banksampah.tunnel.my.id`
- `API_SECRET`: hasil `openssl rand -hex 32`
- `DB_PASSWORD` dan `MARIADB_PASSWORD`: sandi yang sama, buat dengan `openssl rand -hex 16`
- `MARIADB_ROOT_PASSWORD`: sandi lain, buat dengan `openssl rand -hex 16`

Biarkan `APP_ENV=production` dan `APP_DEBUG=false`. File `docker.env` berisi sandi, jangan di-commit.

## 3. Jalankan

```bash
docker compose up -d --build
docker compose ps                       # web dan db harus "running"/"healthy"
docker compose logs db | grep init-db   # impor database pertama kali
curl http://127.0.0.1:8080/api/health   # harus "status": "ok"
```

Saat pertama kali, database otomatis diisi skema dan data contoh. Semua akun contoh
(kata sandi `password123`) **wajib ganti kata sandi** saat login pertama.

| Peran | No HP |
|---|---|
| Admin | 081234567801 |
| Pengelola | 081234567802 |
| Kepala Desa | 081234567803 |
| Nasabah | 081234567811, 081234567812, 081234567813 |

## 4. Hubungkan tunnel.my.id

Arahkan tunnel ke `127.0.0.1:8080` sesuai petunjuk tunnel.my.id. Supaya tunnel hidup
otomatis saat server menyala dan tersambung lagi bila putus, jalankan klien tunnel sebagai
service systemd (`Restart=always`). Pakai subdomain yang tetap, karena aplikasi yang sudah
di-install di HP (PWA/Android) menyimpan alamat ini.

Aplikasi membaca header `X-Forwarded-Proto: https` dari tunnel. Kalau halaman terus
dialihkan (redirect berulang), berarti tunnel tidak mengirim header itu.

## 5. Backup database

```bash
mkdir -p ~/backup-banksampah
docker compose exec -T db sh -c 'mariadb-dump -uroot -p"$MARIADB_ROOT_PASSWORD" --routines --triggers "$MARIADB_DATABASE"' \
  | gzip > ~/backup-banksampah/banksampah-$(date +%F).sql.gz
```

Jadwalkan harian dengan `crontab -e`, misalnya pukul 01.00 (sesuaikan path folder):

```
0 1 * * * cd /home/USER/banksampah && docker compose exec -T db sh -c 'mariadb-dump -uroot -p"$MARIADB_ROOT_PASSWORD" --routines --triggers "$MARIADB_DATABASE"' | gzip > /home/USER/backup-banksampah/banksampah-$(date +\%F).sql.gz
```

## 6. Memperbarui aplikasi

```bash
cd banksampah
git pull
docker compose up -d --build
```

Data tetap aman di volume `db_data`. Skrip impor database hanya berjalan saat volume masih
kosong, jadi file migrasi baru (`database/migrations/008_...sql` dst.) harus dijalankan manual:

```bash
docker compose exec -T db sh -c 'mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" "$MARIADB_DATABASE"' < database/migrations/008_xxx.sql
```

**Perhatian:** `docker compose down -v` menghapus volume, artinya **seluruh data hilang**.
Untuk mematikan aplikasi cukup `docker compose down` (tanpa `-v`).

## Menjalankan di komputer sendiri (uji coba)

Sama seperti di atas, tetapi di `docker.env` isi `APP_ENV=local` dan
`APP_URL=http://localhost:8080`, lalu buka http://localhost:8080.
