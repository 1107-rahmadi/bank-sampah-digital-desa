  # Produk

  ## Platform

  Web, responsif dan mengutamakan layar ponsel (peramban Android kelas bawah menjadi perangkat acuan). Aplikasi Android native adalah tahap berikutnya dan belum masuk cakupan saat ini.

  ## Teknologi

  PHP 7.3+ (tanpa fitur PHP 8), PHP native dengan pola MVC tanpa framework. MySQL/MariaDB saat ini (skema memakai CHECK constraint, jadi butuh MySQL 8.0.16+ atau MariaDB 10.2+), dengan rencana pindah ke PostgreSQL. Apache atau Nginx. TCPDF untuk laporan PDF. Perubahan skema disimpan sebagai file SQL bernomor di folder `migrations/`.

  ## Pengguna

  **Utama:** Nasabah (warga desa yang menabung sampah di bank sampah). Nasabah melihat saldo, riwayat transaksi, dan daftar harga yang berlaku, mengajukan pencairan, dan memantau statusnya. Nasabah tidak pernah menimbang atau mencatat setorannya sendiri: itu dilakukan pengelola.

  **Pendukung:**
  - **Pengelola** — menimbang sampah dan mencatat pembelian dari nasabah maupun non-nasabah, memproses pengajuan pencairan (setujui, tolak dengan alasan, tandai sudah dicairkan), mendaftarkan nasabah dan membuatkan akunnya, mengelola pengepul, memantau stok, mencatat penjualan, dan membuat laporan.
  - **Kepala desa (Kades)** — hanya membaca. Melihat dashboard dan laporan dalam bentuk agregat. Tidak pernah melihat nama, NIK, atau nomor HP warga.
  - **Admin** — mengelola kategori sampah beserta harga beli dan jual (dengan tanggal berlaku), serta akun pengelola dan kepala desa. Admin juga dapat mengelola data nasabah.

  **Pihak luar (tidak login):** Pengepul, yaitu pembeli sampah daur ulang. Mereka hanya menerima nota penjualan dan membayar.

  ## Tujuan Produk

  Bank Sampah Digital Desa membantu bank sampah desa meninggalkan pencatatan kertas dan rekap manual: setiap kilogram sampah yang dibeli dicatat dengan harga hari itu, menjadi tabungan yang dapat dilacak atau dibayar tunai, dan tercermin pada stok, saldo, pencairan, serta laporan tingkat desa.

  **Ukuran keberhasilan:**
  - Nasabah dapat mengecek saldo kapan saja, meminta tabungannya tanpa harus bertanya langsung, dan melihat status permintaannya.
  - Pengelola yang sudah terlatih dapat mencatat satu transaksi dalam waktu kurang dari satu menit, dengan harga terisi otomatis dan tanpa rekap manual sesudahnya.
  - Kepala desa mendapat gambaran singkat dan dapat dipercaya tentang sampah terkumpul, tren, nasabah aktif, komposisi jenis, dan partisipasi per RW, tanpa data pribadi.

  ## Posisi Produk

  Sistem yang dipasang sendiri (self-hosted), tanpa biaya lisensi, untuk satu desa dan satu bank sampah. Sistem ini sengaja menjadi versi sederhana dari sistem tingkat provinsi seperti e-Bank Sampah Jakarta, yang dirancang untuk jenjang BSU/BSI/offtaker dan terlalu berat untuk desa. Versi desa ini memakai empat peran (nasabah, pengelola, kepala desa, admin) dan menambahkan hal yang tidak ada pada sistem provinsi untuk kebutuhan desa: pengajuan pencairan oleh nasabah lengkap dengan status dan notifikasi, pembelian dari non-nasabah (dibayar tunai), stok otomatis dengan penjualan yang dibatasi stok, serta dashboard kepala desa.

  ## Konteks Operasional

  - **Lingkungan:** desa dengan koneksi internet terbatas atau tidak stabil; dipasang di server lokal atau hosting bersama. Banyak pengguna memiliki literasi digital terbatas dan memakai ponsel kelas bawah.
  - **Uang tunai tetap di luar sistem.** Uang diserahkan langsung; sistem mencatat buktinya (pengajuan, persetujuan, pembayaran), bukan uangnya. Rilis pertama belum memakai payment gateway.
  - **Alur kerja:**
    - Pembelian: penjual datang membawa sampah terpilah → pengelola memilih nasabah atau non-nasabah dan menimbang per kategori → sistem memakai harga aktif dan menghitung total → untuk nasabah nilainya masuk tabungan atau dibayar tunai, untuk non-nasabah selalu tunai → stok bertambah → nasabah menerima notifikasi.
    - Pencairan: nasabah mengajukan jumlah dan metode → sistem memvalidasi dan menahan jumlah tersebut → pengelola menyetujui atau menolak (dengan alasan) → setelah uang diserahkan, pengelola menandai sudah dicairkan dan baru saat itu saldo berkurang.
    - Penjualan ke pengepul: pengelola memilih pengepul, kategori, dan berat → sistem memeriksa stok → setelah pengepul membayar, transaksi disimpan dan stok berkurang.
    - Pemantauan: kepala desa membuka dashboard, memilih periode, dan hanya melihat angka agregat.
  - **Ketahanan terhadap sinyal:** draf transaksi pengelola tidak boleh hilang ketika sinyal terputus (kebutuhan NFR-09, prioritas Could). Sinkronisasi offline penuh adalah peningkatan berikutnya, bukan syarat rilis pertama.

  ## Kemampuan dan Batasan

  **Kemampuan (rilis pertama):**
  - Login dengan nomor HP dan kata sandi; akses berdasarkan peran diperiksa di sisi server (Admin, Pengelola, Kades, Nasabah).
  - Nasabah: saldo dan saldo yang bisa dicairkan, riwayat setoran (kategori, berat, nilai) dan pencairan, pengajuan pencairan dengan validasi, status pengajuan dan notifikasi dalam aplikasi, serta daftar harga yang berlaku. Ubah profil dan kata sandi berprioritas Should; membatalkan pengajuan selama masih DIAJUKAN berprioritas Could.
  - Pengelola: mendaftarkan nasabah dan membuat akun; mencatat pembelian dari nasabah dan non-nasabah dengan banyak kategori dalam satu transaksi; menampilkan bukti transaksi; memproses pencairan; mencatat penjualan ke pengepul dengan validasi stok dan status pembayaran; mengelola pengepul; mengoreksi atau membatalkan transaksi dengan alasan; laporan pembelian, penjualan, stok, dan pencairan per periode (Excel atau PDF).
  - Admin: kategori dan harga dengan tanggal berlaku; akun pengelola dan kepala desa.
  - Kepala desa: dashboard (total sampah, tren mingguan, nasabah aktif, komposisi jenis, partisipasi per RW, nilai transaksi, penjualan) dengan filter periode; ringkasan PDF berprioritas Should; catatan tindak lanjut otomatis berprioritas Could.
  - Mutasi saldo (MutasiSaldo), stok, dan audit log untuk perubahan penting (harga, pembatalan, koreksi stok, status pencairan).

  **Aturan bisnis yang memengaruhi antarmuka:**
  - BR-01 Harga mengikuti harga aktif pada tanggal transaksi dan disalin ke detail transaksi, sehingga perubahan harga tidak mengubah transaksi lama.
  - BR-02 Pembelian dari nasabah boleh masuk tabungan atau dibayar tunai; pembelian dari non-nasabah selalu tunai. Nama non-nasabah bersifat opsional.
  - BR-03/04 Saldo = setoran yang masuk tabungan dikurangi pencairan yang sudah selesai, tidak boleh negatif. Saldo tersedia = saldo dikurangi pengajuan berstatus DIAJUKAN atau DISETUJUI.
  - BR-05/06 Pencairan minimal sebesar parameter (contoh Rp10.000) dan paling besar sebesar saldo tersedia. Pencairan dianggap selesai hanya setelah pengelola menandainya, dan saldo berkurang pada saat itu.
  - BR-07 Penjualan tidak boleh melebihi stok per kategori.
  - BR-08 Transaksi tidak dihapus. Kesalahan dikoreksi lewat pembatalan dengan alasan dan tercatat di audit log.
  - BR-09 Kepala desa hanya membaca dan hanya melihat data agregat.
  - Status pencairan: DIAJUKAN, DISETUJUI, DITOLAK, DICAIRKAN, DIBATALKAN.

  **Batasan:**
  - Kompatibel dengan PHP 7.3+; nominal memakai DECIMAL, bukan float; transaksi yang mengubah stok dan saldo berjalan atomik.
  - Satu bank sampah per desa pada rilis pertama. Skema tetap memuat `bank_sampah_id` agar bisa menampung lebih dari satu bank sampah nantinya.
  - Kata sandi disimpan sebagai hash, seluruh lalu lintas memakai HTTPS, NIK dienkripsi, dan data pribadi dibatasi pada yang diperlukan (UU Pelindungan Data Pribadi).
  - Halaman terbuka kurang dari 3 detik pada jaringan 4G; dashboard kurang dari 5 detik.
  - Saat ini login memakai nomor HP. Login dengan email direncanakan (FR-01) tetapi belum tersedia.

  **Di luar cakupan untuk saat ini (kemungkinan tahap berikutnya):** payment gateway, notifikasi push atau WhatsApp, aplikasi Android native, penggunaan multi-desa atau multi-bank sampah, gamifikasi (level, lencana, papan peringkat), dashboard dampak lingkungan, sinkronisasi offline penuh, kartu identitas QR, dan jenjang BSI/offtaker.

  ## Komitmen Merek

  - **Nama:** "Bank Sampah Digital Desa".
  - **Warna:** palet hijau. Utama `#2e7d32`, tua `#1b5e20` dan `#0f3d14`, aksen `#43a047` dan `#66bb6a`, nuansa muda `#e8f5e9` dan `#f4faf4`, latar `#f7faf6`, teks `#17231a`. Amber, merah muda tua (rose), dan biru (sky) hanya dipakai untuk status (peringatan, galat, informasi).
  - **Tipografi:** Plus Jakarta Sans, dengan Segoe UI dan font sistem sebagai cadangan agar tampilan tetap baik ketika font web tidak termuat. Ukuran dasar 16px.
  - **Bahasa visual:** gaya dompet digital untuk nasabah (kartu saldo bergradasi, aksi cepat bulat besar, baris daftar sebagai ganti tabel di ponsel), ikon garis SVG yang konsisten, tanpa emoji.
  - **Bahasa:** Bahasa Indonesia di seluruh aplikasi.
  - **Nada:** sederhana, tenang, dan sopan. Jelaskan fungsi setiap layar dengan kata-kata sehari-hari.

  ## Bukti yang Sudah Ada

  - Dokumen analisis proses bisnis: analisis sistem berjalan e-Bank Sampah Jakarta, sistem usulan, aturan bisnis (BR-01 sampai BR-10), kebutuhan (FR dan NFR), use case, struktur database, dan wireframe.
  - Skema database 16 tabel dengan foreign key dan indeks (migrasi 001), ditambah migrasi 002 (sync_changes) dan 003 (kolom uuid dan perangkat pada pembelian).
  - Halaman nasabah bergaya dompet digital (beranda, harga sampah, riwayat, cairkan, profil) dengan sidebar bersama berupa partial; area pengelola dengan sidebar bersamanya sendiri.
  - Autentikasi API berbasis token dan prototipe endpoint pembelian oleh pengelola yang menghitung ulang total di server (masih dalam pengujian).
  - Data uji: 7 akun yang mencakup keempat peran, 4 nasabah, 10 kategori sampah beserta harga.

  ## Prinsip Produk

  1. **Pengelola adalah sumber kebenaran untuk berat sampah.** Nasabah tidak pernah melaporkan setorannya sendiri.
  2. **Dapat dilacak sepenuhnya.** Setiap perubahan saldo memiliki catatan MutasiSaldo, setiap perubahan penting punya catatan audit, dan tidak ada yang dihapus.
  3. **Setia pada alur kerja nyata.** Sistem meniru alur bank sampah yang sebenarnya (timbang, catat, bayar atau tabung, stok, cairkan), bukan buku besar umum.
  4. **Andal lebih penting daripada banyak fitur.** Alur pembelian → saldo → pencairan harus benar sebelum menambah hal lain.
  5. **Sederhana dulu.** Warga dengan kemampuan ponsel dasar harus bisa berhasil tanpa bantuan; pengelola terlatih menyelesaikan satu catatan kurang dari satu menit.
  6. **Privasi sebagai bawaan.** Pimpinan hanya melihat agregat, data pribadi seminimal mungkin, dan tidak ada yang sensitif di antarmuka maupun di kode QR.
  7. **Dimiliki desa.** Dipasang sendiri, tanpa ketergantungan pada vendor, dan data tetap berada di pemerintah desa.

  ## Aksesibilitas dan Inklusi

  - Berfungsi mulai lebar layar 320px pada ponsel kelas bawah.
  - Teks berkontras tinggi di atas latar terang; status tidak pernah ditunjukkan hanya dengan warna (selalu disertai label atau ikon).
  - Target sentuh besar (minimal 44px, sebaiknya 50px atau lebih) agar nyaman dipakai dengan sarung tangan atau di tempat yang ramai.
  - Menghormati pengaturan pengurangan animasi (reduced motion).
  - Hanya Bahasa Indonesia; tidak perlu bahasa Inggris untuk memakai layar mana pun.