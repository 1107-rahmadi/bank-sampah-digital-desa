# Ringkasan Peran Pengelola — Bank Sampah Digital Desa

Ringkasan ini diambil dari dokumen *Analisis Proses Bisnis Bank Sampah Digital Desa*, dan hanya membahas bagian **Pengelola**. Dalam dokumen, Pengelola adalah penyederhanaan dari Bank Sampah Unit (BSU) pada panduan e-Bank Sampah Jakarta.

---

## 1. Pengelola pada sistem berjalan (e-Bank Sampah Jakarta)

Pada sistem berjalan, peran yang setara adalah **BSU**. Tugasnya:

- Menimbang dan mencatat tabungan nasabah.
- Menetapkan harga sampah per kategori.
- Mencatat penjualan ke BSI atau offtaker.
- Mengunduh laporan Excel.

**Menu BSU:** Profil dan status; Data (Nasabah, Bank Sampah Induk, Aktivitas, Harga Sampah, Offtaker); Transaksi (Nasabah, BSI, Offtaker, Laporan).

**Aktivitas utama BSU:**

| No | Aktivitas | Data yang dicatat |
| --- | --- | --- |
| 1 | Mendaftar sebagai bank sampah, melengkapi profil, memilih lokasi di peta | Profil, penanggung jawab, badan hukum, luas dan status lahan, koordinat |
| 2 | Menetapkan harga per kategori, memilih BSI mitra dan offtaker, mengatur aktivitas dan fasilitas | Kategori dan harga, daftar mitra |
| 3 | Menimbang sampah dan mencatat transaksi nasabah | Tanggal, petugas, nasabah, jenis sampah, berat (kg) |
| 4 | Mencatat penjualan ke BSI atau offtaker | Tanggal, tujuan penjualan, jenis sampah, berat |
| 5 | Mengunduh laporan Excel menurut rentang tanggal | Laporan rekap |

### Kelemahan sistem berjalan yang berdampak pada pengelola

| Temuan | Dampak | Usulan |
| --- | --- | --- |
| Form transaksi mewajibkan nama nasabah | Pembelian tunai dari warga non-nasabah tidak tercatat, sehingga volume sampah terlapor lebih kecil dari kenyataan | Transaksi non-nasabah dengan bayar tunai |
| Menu stok tidak ditemukan pada panduan | Sampah yang belum terjual sulit dipantau dan selisih sulit dideteksi | Stok otomatis (pembelian dikurangi penjualan), penjualan tidak boleh melebihi stok |
| Struktur berjenjang (BSU, BSI, offtaker, banyak master data) untuk skala provinsi | Beban input dan pelatihan terlalu berat untuk pengelola desa | Sederhanakan menjadi empat peran: nasabah, pengelola, kepala desa, admin |
| Laporan berupa unduhan Excel yang diolah manual | Rekap memakan waktu | Laporan dan dashboard otomatis |
| Tidak ada jalur pengajuan pencairan | Pencairan bergantung komunikasi manual dan sulit dilacak | Pengajuan pencairan dengan status dan notifikasi |

---

## 2. Pengelola pada sistem usulan

### 2.1 Hak akses Pengelola

| Fitur | Akses |
| --- | --- |
| Login | ✓ |
| Kelola data nasabah | ✓ (bersama Admin) |
| Catat pembelian (nasabah dan non-nasabah) | ✓ (hanya Pengelola) |
| Catat penjualan ke pengepul | ✓ (hanya Pengelola) |
| Proses pengajuan pencairan | ✓ (hanya Pengelola) |
| Lihat stok | ✓ |
| Kelola kategori dan harga | Hanya lihat (Admin yang mengelola) |
| Laporan transaksi | ✓ |
| Dashboard ringkasan desa | Hanya lihat |

Pengelola **tidak** bisa mengelola akun pengguna, dan tidak bisa mengubah harga.

### 2.2 Alur proses yang dijalankan Pengelola

**Pembelian sampah**
1. Penjual datang membawa sampah terpilah.
2. Pengelola memilih jenis penjual (nasabah atau non-nasabah) dan menimbang per jenis.
3. Sistem memakai harga beli aktif dan menghitung total.
4. Nasabah: nilai masuk tabungan atau dibayar tunai. Non-nasabah: selalu tunai.
5. Stok bertambah dan nasabah menerima notifikasi.

**Pencairan tabungan**
1. Nasabah mengajukan jumlah dan metode lewat aplikasi. Sistem memvalidasi dan menahan sebagian saldo.
2. Pengelola menyetujui, atau menolak dengan alasan.
3. Setelah uang diserahkan (di luar sistem), pengelola menandai **sudah dicairkan**, dan saldo berkurang pada saat itu.

**Penjualan ke pengepul**
1. Pengelola memilih pengepul, jenis, dan berat.
2. Sistem memeriksa stok dan menghitung total dengan harga jual.
3. Setelah pengepul membayar, pengelola menyimpan transaksi.
4. Stok berkurang dan dashboard diperbarui.

### 2.3 Aturan bisnis yang berlaku bagi Pengelola

| Kode | Aturan |
| --- | --- |
| BR-01 | Harga mengikuti harga aktif pada tanggal transaksi. Harga per kg disimpan sebagai salinan di detail transaksi, sehingga perubahan harga tidak mengubah transaksi lama. |
| BR-02 | Pembelian dari nasabah boleh masuk tabungan atau tunai. Pembelian dari non-nasabah selalu tunai. |
| BR-05 | Pencairan minimal sesuai parameter (contoh Rp10.000) dan paling besar sebesar saldo tersedia. |
| BR-06 | Pencairan dianggap selesai hanya setelah pengelola menandainya. Saldo berkurang saat itu. |
| BR-07 | Penjualan ke pengepul tidak boleh melebihi stok per jenis sampah. |
| BR-08 | Transaksi tidak dihapus. Kesalahan dikoreksi lewat pembatalan dengan alasan, dan tercatat di audit log. |
| BR-10 | Nama penjual non-nasabah bersifat opsional. |

---

## 3. Kebutuhan sistem terkait Pengelola

### Kebutuhan pemangku kepentingan
Pengelola membutuhkan: pencatatan transaksi cepat (kurang dari satu menit), harga terisi otomatis, stok terpantau, dan tidak perlu rekap manual.

### Kebutuhan fungsional

| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| FR-01 | Login dengan nomor HP atau email, akses sesuai peran | Must |
| FR-02 | Mendaftarkan nasabah (nama, HP, alamat, RT/RW, nomor anggota) dan membuatkan akun aplikasi | Must |
| FR-10 | Mencatat pembelian dari nasabah: banyak jenis sampah, harga otomatis, pilihan tabungan atau tunai | Must |
| FR-11 | Mencatat pembelian dari non-nasabah tanpa registrasi, dibayar tunai | Must |
| FR-12 | Menampilkan atau membagikan bukti transaksi | Should |
| FR-13 | Memproses pengajuan pencairan: setujui, tolak dengan alasan, tandai sudah dicairkan | Must |
| FR-14 | Stok per jenis bertambah saat pembelian dan berkurang saat penjualan. Koreksi stok memerlukan alasan | Must |
| FR-15 | Mencatat penjualan ke pengepul dengan validasi stok dan status pembayaran | Must |
| FR-16 | Mengelola data pengepul | Should |
| FR-17 | Laporan pembelian, penjualan, stok, dan pencairan per periode, dapat diunduh (Excel atau PDF) | Must |
| FR-18 | Membatalkan atau mengoreksi transaksi dengan alasan, tercatat di audit log | Should |

### Kebutuhan non-fungsional yang paling relevan

- **NFR-03 (Integritas data):** transaksi yang mengubah stok dan saldo berjalan atomik, yaitu semua berhasil atau semua dibatalkan.
- **NFR-05 (Kemudahan pakai):** bahasa Indonesia, dan satu transaksi tercatat kurang dari 1 menit oleh pengelola terlatih.
- **NFR-08 (Auditabilitas):** perubahan penting (pembatalan, koreksi stok, status pencairan) tercatat dengan pelaku dan waktu.
- **NFR-09 (Ketahanan sinyal):** draf transaksi tidak hilang saat sinyal terputus (prioritas Could).

### Batasan
- Fase awal tanpa payment gateway. Uang diserahkan di luar sistem, sistem hanya mencatat pengajuan, persetujuan, dan pencairan.
- Satu bank sampah per desa pada rilis pertama.

---

## 4. Use case Pengelola

| ID | Nama | Aktor | Kebutuhan |
| --- | --- | --- | --- |
| UC06 | Kelola data nasabah | Pengelola | FR-02 |
| UC07 | Catat pembelian dari nasabah | Pengelola | FR-10, FR-12 |
| UC08 | Catat pembelian dari non-nasabah | Pengelola | FR-11 |
| UC09 | Proses pengajuan pencairan | Pengelola | FR-13 |
| UC10 | Catat penjualan ke pengepul | Pengelola, Pengepul | FR-15 |
| UC11 | Perbarui stok otomatis | Sistem (di-include) | FR-14 |
| UC12 | Lihat laporan transaksi | Pengelola, Kepala Desa | FR-17 |
| UC16 | Kelola data pengepul | Pengelola | FR-16 |

### Spesifikasi use case utama

**UC07 — Catat pembelian dari nasabah**
- *Prasyarat:* pengelola login, nasabah terdaftar, harga aktif tersedia.
- *Alur utama:* pilih jenis penjual Nasabah, pilih nasabah, tambah jenis sampah dan isi berat, pilih cara bayar (tabungan atau tunai), simpan. Sistem menghitung total, menyimpan transaksi, dan menambah stok. Jika lewat tabungan, saldo bertambah dan nasabah diberi notifikasi.
- *Alternatif:* berat kosong, sistem meminta pengisian. Jenis sampah tanpa harga aktif, sistem menolak dan menyarankan menghubungi admin.
- *Hasil:* transaksi tersimpan, stok dan saldo terbarui, bukti transaksi tersedia.

**UC08 — Catat pembelian dari non-nasabah**
- *Alur utama:* pilih jenis penjual Bukan nasabah, isi nama (opsional), jenis sampah, dan berat, lalu simpan. Sistem menetapkan pembayaran tunai.
- *Hasil:* transaksi tunai tersimpan dan stok bertambah tanpa mengubah saldo siapa pun.

**UC09 — Proses pengajuan pencairan**
- *Prasyarat:* ada pengajuan berstatus DIAJUKAN.
- *Alur utama:* buka daftar pengajuan dan periksa saldo nasabah, setujui (DISETUJUI), serahkan uang, tandai sudah dicairkan (DICAIRKAN).
- *Alternatif:* ditolak, pengelola memilih alasan, status DITOLAK, dan saldo tertahan dilepas. Saldo tidak cukup saat ditandai cair, sistem menolak.
- *Hasil:* saldo nasabah berkurang, mutasi tercatat, nasabah diberi notifikasi.

**UC10 — Catat penjualan ke pengepul**
- *Prasyarat:* stok tersedia.
- *Alur utama:* pilih pengepul, jenis, dan berat. Sistem memeriksa stok dan membuat nota dengan harga jual. Pengepul menimbang dan membayar. Pengelola menyimpan, lalu stok berkurang.
- *Alternatif:* berat melebihi stok, sistem menolak dan menampilkan stok tersedia.
- *Hasil:* transaksi penjualan tersimpan, stok dan dashboard terbarui.

---

## 5. Activity dan sequence diagram (sisi Pengelola)

Diagram aslinya gambar (Mermaid). Berikut inti alurnya dalam teks:

- **Pembelian sampah:** pilih jenis penjual → timbang per jenis → harga aktif diterapkan → simpan → sistem menyimpan transaksi, menambah stok, dan (jika tabungan) menambah saldo dalam **satu transaksi database**.
- **Pengajuan dan pencairan:** terima pengajuan → setujui atau tolak (dengan alasan) → serahkan uang → tandai dicairkan → saldo berkurang dan notifikasi terkirim.
- **Penjualan ke pengepul:** pilih pengepul dan jenis → sistem cek stok → bila cukup, nota dibuat → pengepul bayar → simpan → stok berkurang.

---

## 6. Struktur database yang dipakai Pengelola

| Tabel | Fungsi bagi Pengelola |
| --- | --- |
| `users` | Akun login. Pengelola punya `role = PENGELOLA` dan `bank_sampah_id` terisi. |
| `nasabah` | Data anggota penabung (nomor anggota, RT/RW, rekening, saldo). |
| `kategori_sampah`, `harga_sampah` | Katalog jenis dan harga beli/jual berjangka waktu (dikelola Admin, dilihat Pengelola). |
| `pengepul` | Mitra pembeli sampah. |
| `transaksi_beli`, `detail_beli` | Pembelian. Berisi `tipe_penjual` (NASABAH atau NON_NASABAH), `metode_bayar` (TABUNG atau TUNAI), `petugas_id`, dan `status` (SAH atau BATAL). |
| `transaksi_jual`, `detail_jual` | Penjualan ke pengepul, dengan `status_bayar` (BELUM atau LUNAS). |
| `stok_sampah` | Stok per jenis, tidak boleh negatif (CHECK). |
| `mutasi_saldo` | Buku besar tabungan nasabah (SETOR, PENCAIRAN, KOREKSI). |
| `pengajuan_pencairan` | Antrean yang diproses pengelola. Status: DIAJUKAN, DISETUJUI, DITOLAK, DICAIRKAN, DIBATALKAN. |
| `notifikasi`, `audit_log` | Pesan dalam aplikasi dan jejak perubahan penting. |

**Aturan penting:** pembelian dari non-nasabah harus `NON_NASABAH`, `nasabah_id` kosong, dan `metode_bayar = TUNAI`.

**Indeks relevan:** `transaksi_beli` dan `transaksi_jual` pada (bank_sampah_id, tanggal), `pengajuan_pencairan` pada (status, nasabah_id).

---

## 7. Wireframe layar Pengelola (web)

- **W3 — Catat pembelian sampah:** pilihan nasabah atau bukan nasabah. Untuk nasabah, saldo saat ini ditampilkan. Untuk bukan nasabah, kolom menjadi nama penjual (opsional) dan pembayaran dikunci tunai. Setelah disimpan, muncul konfirmasi dan bukti transaksi.
- **W4 — Catat penjualan ke pengepul:** stok tiap jenis tampil di samping isian berat. Melebihi stok ditolak. Menyimpan mengurangi stok dan memperbarui dashboard.
- **W5 — Daftar pengajuan pencairan:** menolak meminta alasan yang terlihat oleh nasabah. Tombol "tandai sudah dicairkan" dipakai setelah uang diserahkan, dan saldo berkurang saat ditekan.

---

## 8. Prototipe untuk peran Pengelola

| Tab | Yang bisa dicoba | Kebutuhan |
| --- | --- | --- |
| Beli sampah | Pembelian dari nasabah (tabungan atau tunai) dan non-nasabah (tunai), harga otomatis, total langsung dihitung | FR-10, FR-11, FR-14 |
| Jual ke pengepul | Penjualan dengan validasi stok | FR-14, FR-15 |
| Pencairan | Setujui, tolak dengan alasan, tandai sudah dicairkan | FR-13 |
| Stok dan harga | Stok per jenis, harga beli dan jual | FR-14, FR-19 (tampilan) |

**Batas prototipe:** belum ada login, basis data, dan notifikasi nyata. Semua logika berjalan di peramban. Pengelolaan akun, kategori, dan harga oleh admin belum dibuat.

---

## 9. Catatan validasi

Kebutuhan Pengelola perlu divalidasi lewat wawancara singkat tentang alur harian dan metode pembayaran sebelum dikunci. Besaran seperti minimal pencairan Rp10.000 hanyalah parameter contoh yang bisa diatur admin.