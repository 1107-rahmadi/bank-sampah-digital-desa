<?php
/**
 * Test modul Kelola Nasabah (Admin).
 *
 * Menguji perilaku baru pada Tahap 1:
 *   1. Total daftar menghitung baris nasabah, bukan jumlah user NASABAH
 *   2. Status nasabah hanya bisa diubah lewat POST dengan token CSRF
 *   3. Menonaktifkan nasabah juga menonaktifkan akun aplikasinya
 *   4. Mengaktifkan kembali nasabah juga mengaktifkan kembali akunnya
 *   5. Riwayat transaksi dan audit log tetap utuh
 *   6. Nasabah tanpa akun tetap muncul di daftar dan di dropdown transaksi beli
 *   7. Nasabah nonaktif tidak muncul di dropdown transaksi beli
 *   8. Pengajuan aktif yang dihitung hanya DIAJUKAN dan DISETUJUI
 *   9. Form tambah dan ubah menolak permintaan tanpa token CSRF
 *  10. Form ubah tidak mengubah status nasabah yang sudah nonaktif
 *  11. Perubahan status, status akun, dan audit log jalan dalam satu transaksi
 *  12. Rute hapus permanen sudah tidak ada
 *
 * Jalankan: php tests/test_nasabah_admin.php
 *
 * Test membuat data sendiri (user + nasabah + pengajuan uji), lalu
 * membersihkannya kembali di akhir, jadi data lama tidak tersentuh.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

$root = dirname(__DIR__);

require_once $root . '/config/app.php';
require_once $root . '/config/database.php';
require_once $root . '/app/helpers/money.php';
require_once $root . '/app/helpers/util.php';
require_once $root . '/app/models/Nasabah.php';
require_once $root . '/app/models/User.php';

// ---------------------------------------------------------------
// Harness assertion sederhana (tanpa dependency)
// ---------------------------------------------------------------
$hasil = ['lulus' => 0, 'gagal' => 0, 'baris' => []];

function uji(string $nama, bool $kondisi, string $detail = ''): void
{
    global $hasil;

    if ($kondisi) {
        $hasil['lulus']++;
        $hasil['baris'][] = ['OK', $nama, ''];
    } else {
        $hasil['gagal']++;
        $hasil['baris'][] = ['GAGAL', $nama, $detail];
    }
}

function judul(string $nama): void
{
    global $hasil;
    $hasil['baris'][] = ['---', $nama, ''];
}

function nilai($v): string
{
    return is_null($v) ? 'null' : (string) $v;
}

/**
 * Jalankan satu aksi controller Admin di proses terpisah.
 *
 * @param string $method     Method controller yang dipanggil
 * @param int    $id         Id nasabah
 * @param string $aksi       nonaktifkan | aktivate | (kosong)
 * @param string $csrf       Token CSRF yang dikirim, kosong berarti tidak dikirim
 * @param string $httpMethod POST atau GET
 * @param array  $post       Data POST tambahan
 */
function jalankanAksi(string $method, int $id = 0, string $aksi = '', string $csrf = 'TOKEN-UJI', string $httpMethod = 'POST', array $post = []): string
{
    $script = dirname(__DIR__) . '/tests/_nasabah_admin_aksi.php';
    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script)
        . ' ' . escapeshellarg($method)
        . ' ' . escapeshellarg((string) $id)
        . ' ' . escapeshellarg($aksi)
        . ' ' . escapeshellarg($csrf)
        . ' ' . escapeshellarg($httpMethod);

    if (!empty($post)) {
        $cmd .= ' ' . escapeshellarg((string) json_encode($post));
    }

    $output = [];
    $exitCode = 0;
    exec($cmd . ' 2>&1', $output, $exitCode);

    return implode("\n", $output);
}

$db = getDbConnection();
$model = new Nasabah($db);
$userModel = new User($db);

$bankSampahId = (int) ($db->query("SELECT id FROM bank_sampah ORDER BY id LIMIT 1")->fetch()['id'] ?? 1);

$auditMaxId = (int) ($db->query("SELECT COALESCE(MAX(id), 0) AS m FROM audit_log")->fetch()['m'] ?? 0);

$noHpUji = '08000000' . date('Ymd') . random_int(100, 999);

// ---------------------------------------------------------------
// Data uji
// ---------------------------------------------------------------
// 1. Akun uji (dipakai untuk uji sinkronisasi status akun).
$db->exec("
    INSERT INTO users (desa_id, bank_sampah_id, nama, no_hp, password_hash, role, status)
    VALUES (NULL, {$bankSampahId}, 'Uji Otomatis Nasabah', '{$noHpUji}',
            'UJI-BUKAN-PASSWORD', 'NASABAH', 'AKTIF')
");
$userUjiId = (int) $db->lastInsertId();

// 2. Nasabah uji dengan akun, saldo nol.
$noAnggotaUji = 'UJI-NA-' . date('YmdHis');
$db->exec("
    INSERT INTO nasabah (user_id, bank_sampah_id, no_anggota, nama, no_hp, saldo, status, tgl_daftar)
    VALUES ({$userUjiId}, {$bankSampahId}, '{$noAnggotaUji}', 'Uji Otomatis Nasabah', '{$noHpUji}',
            0.00, 'AKTIF', CURDATE())
");
$nasabahUjiId = (int) $db->lastInsertId();

// 3. Nasabah uji tanpa akun (user_id kosong).
$noAnggotaTanpaAkun = 'UJI-NK-' . date('YmdHis');
$db->exec("
    INSERT INTO nasabah (user_id, bank_sampah_id, no_anggota, nama, no_hp, saldo, status, tgl_daftar)
    VALUES (NULL, {$bankSampahId}, '{$noAnggotaTanpaAkun}', 'Uji Otomatis Tanpa Akun', '{$noHpUji}1',
            0.00, 'AKTIF', CURDATE())
");
$nasabahTanpaAkunId = (int) $db->lastInsertId();

// 4. Nasabah uji nonaktif, buat uji form ubah tidak mengubah status.
//    user_id dikosongkan karena kolom itu UNIQUE: satu akun hanya boleh
//    tertaut ke satu nasabah.
$noAnggotaNonaktif = 'UJI-NF-' . date('YmdHis');
$db->exec("
    INSERT INTO nasabah (user_id, bank_sampah_id, no_anggota, nama, no_hp, saldo, status, tgl_daftar)
    VALUES (NULL, {$bankSampahId}, '{$noAnggotaNonaktif}', 'Uji Otomatis Nonaktif', '{$noHpUji}2',
            0.00, 'NONAKTIF', CURDATE())
");
$nasabahNonaktifId = (int) $db->lastInsertId();

// 5. Pengajuan dengan tiga status berbeda, buat uji peta pengajuan aktif.
$pengajuanIds = [];
foreach ([['DIAJUKAN', 10000.00], ['DISETUJUI', 20000.00], ['DITOLAK', 30000.00]] as $isi) {
    $noPengajuan = 'UJI-' . strtoupper(substr(md5(uniqid('', true)), 0, 16));
    $db->exec("
        INSERT INTO pengajuan_pencairan (no_pengajuan, nasabah_id, jumlah, metode, status, diajukan_at)
        VALUES ('{$noPengajuan}', {$nasabahUjiId}, {$isi[1]}, 'TUNAI', '{$isi[0]}', NOW())
    ");
    $pengajuanIds[] = (int) $db->lastInsertId();
}

function statusNasabah(PDO $db, int $id): string
{
    $stmt = $db->prepare("SELECT status FROM nasabah WHERE id = :id");
    $stmt->execute(['id' => $id]);
    return strtoupper((string) ($stmt->fetch()['status'] ?? ''));
}

function statusUser(PDO $db, int $id): string
{
    $stmt = $db->prepare("SELECT status FROM users WHERE id = :id");
    $stmt->execute(['id' => $id]);
    return strtoupper((string) ($stmt->fetch()['status'] ?? ''));
}

function jumlahAudit(PDO $db, int $minId, int $recordId): int
{
    $stmt = $db->prepare("SELECT COUNT(*) AS c FROM audit_log WHERE id > :min AND tabel = 'nasabah' AND record_id = :rec");
    $stmt->execute(['min' => $minId, 'rec' => $recordId]);
    return (int) ($stmt->fetch()['c'] ?? 0);
}

// ---------------------------------------------------------------
// 1. Total daftar menghitung baris nasabah
// ---------------------------------------------------------------
judul('1. Total daftar menghitung baris nasabah');

$totalDariModel = $model->countPaginasi(null);
$totalBarisAsli = (int) ($db->query("SELECT COUNT(*) AS c FROM nasabah")->fetch()['c'] ?? 0);

uji('countPaginasi sama dengan jumlah baris tabel nasabah', $totalDariModel === $totalBarisAsli, "model={$totalDariModel} tabel={$totalBarisAsli}");

$jumlahUserNasabah = (int) ($db->query("SELECT COUNT(*) AS c FROM users WHERE role = 'NASABAH'")->fetch()['c'] ?? 0);
$jumlahBarisTanpaAkun = (int) ($db->query("SELECT COUNT(*) AS c FROM nasabah WHERE user_id IS NULL")->fetch()['c'] ?? 0);

uji('Hitungan lama berdasarkan user NASABAH memang berbeda dari jumlah nasabah', $jumlahUserNasabah !== $totalBarisAsli, "user={$jumlahUserNasabah} nasabah={$totalBarisAsli}");
uji('Ada nasabah tanpa akun, jadi dua hitungan itu memang beda', $jumlahBarisTanpaAkun > 0, "tanpa akun={$jumlahBarisTanpaAkun}");

// ---------------------------------------------------------------
// 2. Pengajuan aktif hanya menghitung DIAJUKAN dan DISETUJUI
// ---------------------------------------------------------------
judul('2. Peta pengajuan aktif');

$peta = $model->getPengajuanAktifPeta([$nasabahUjiId]);
$isiPeta = $peta[$nasabahUjiId] ?? null;

uji('Nasabah uji ada di peta pengajuan aktif', $isiPeta !== null, 'tidak ada di peta');
uji('Jumlah pengajuan aktif = 2', (int) ($isiPeta['jumlah_pengajuan'] ?? 0) === 2, nilai($isiPeta['jumlah_pengajuan'] ?? null));
uji('Total nominal pengajuan aktif = 30000', (float) ($isiPeta['jumlah'] ?? 0) === 30000.0, nilai($isiPeta['jumlah'] ?? null));

$petaKosong = $model->getPengajuanAktifPeta([]);
uji('Peta kosong untuk daftar kosong', $petaKosong === [], 'tidak kosong');

// ---------------------------------------------------------------
// 3. Status hanya diubah lewat POST dengan CSRF
// ---------------------------------------------------------------
judul('3. Status hanya diubah lewat POST dengan token CSRF');

jalankanAksi('nasabahToggleStatus', $nasabahUjiId, 'nonaktifkan', '');
uji('Permintaan tanpa token CSRF ditolak', statusNasabah($db, $nasabahUjiId) === 'AKTIF', statusNasabah($db, $nasabahUjiId));
uji('Status akun juga tidak berubah', statusUser($db, $userUjiId) === 'AKTIF', statusUser($db, $userUjiId));

jalankanAksi('nasabahToggleStatus', $nasabahUjiId, 'nonaktifkan', 'TOKEN-UJI', 'GET');
uji('Permintaan GET ditolak', statusNasabah($db, $nasabahUjiId) === 'AKTIF', statusNasabah($db, $nasabahUjiId));

jalankanAksi('nasabahToggleStatus', $nasabahUjiId, 'ngawur', 'TOKEN-UJI');
uji('Aksi yang tidak dikenal ditolak', statusNasabah($db, $nasabahUjiId) === 'AKTIF', statusNasabah($db, $nasabahUjiId));

jalankanAksi('nasabahToggleStatus', 999999, 'nonaktifkan', 'TOKEN-UJI');
uji('Id yang tidak ada ditolak', statusNasabah($db, $nasabahUjiId) === 'AKTIF', statusNasabah($db, $nasabahUjiId));

// ---------------------------------------------------------------
// 4. Menonaktifkan dan mengaktifkan kembali
// ---------------------------------------------------------------
judul('4. Nonaktifkan lalu aktifkan kembali');

$auditSebelum = jumlahAudit($db, $auditMaxId, $nasabahUjiId);

jalankanAksi('nasabahToggleStatus', $nasabahUjiId, 'nonaktifkan', 'TOKEN-UJI');
uji('Status nasabah menjadi NONAKTIF', statusNasabah($db, $nasabahUjiId) === 'NONAKTIF', statusNasabah($db, $nasabahUjiId));
uji('Status akun aplikasi ikut NONAKTIF', statusUser($db, $userUjiId) === 'NONAKTIF', statusUser($db, $userUjiId));
uji('Audit log tercatat', jumlahAudit($db, $auditMaxId, $nasabahUjiId) > $auditSebelum, 'tidak ada audit log');

$saldoSetelahNonaktif = (float) ($model->findById($nasabahUjiId)['saldo'] ?? -1);
uji('Saldo nasabah tidak berubah', $saldoSetelahNonaktif === 0.0, nilai($saldoSetelahNonaktif));

jalankanAksi('nasabahToggleStatus', $nasabahUjiId, 'aktifkan', 'TOKEN-UJI');
uji('Status nasabah kembali AKTIF', statusNasabah($db, $nasabahUjiId) === 'AKTIF', statusNasabah($db, $nasabahUjiId));
uji('Status akun aplikasi kembali AKTIF', statusUser($db, $userUjiId) === 'AKTIF', statusUser($db, $userUjiId));

// ---------------------------------------------------------------
// 5. Nasabah tanpa akun tetap muncul di daftar dan dropdown
// ---------------------------------------------------------------
judul('5. Nasabah tanpa akun tetap tampil');

$daftarAdmin = $model->getAll(100, 0);
$idDiDaftar = array_column($daftarAdmin, 'id');
uji('Nasabah tanpa akun muncul di daftar Admin', in_array($nasabahTanpaAkunId, $idDiDaftar, true), 'tidak ada di daftar');

$daftarAktif = $model->getAll(null, 0, $bankSampahId, 'AKTIF');
$idDiAktif = array_column($daftarAktif, 'id');
uji('Nasabah tanpa akun muncul di dropdown transaksi beli', in_array($nasabahTanpaAkunId, $idDiAktif, true), 'tidak ada di dropdown');

uji('Nasabah nonaktif tidak muncul di dropdown transaksi beli', !in_array($nasabahNonaktifId, $idDiAktif, true), 'masih ada di dropdown');
uji('Nasabah nonaktif tetap muncul di daftar Admin', in_array($nasabahNonaktifId, array_column($model->getAll(100, 0), 'id'), true), 'tidak ada di daftar');

// ---------------------------------------------------------------
// 6. Form tambah dan ubah menolak permintaan tanpa CSRF
// ---------------------------------------------------------------
judul('6. Form tambah dan ubah tanpa CSRF');

$jumlahNasabahSebelum = (int) ($db->query("SELECT COUNT(*) AS c FROM nasabah")->fetch()['c'] ?? 0);
$jumlahUserSebelum = (int) ($db->query("SELECT COUNT(*) AS c FROM users")->fetch()['c'] ?? 0);

$keluaranTambah = jalankanAksi('nasabahForm', 0, '', '');
uji('Form tambah tanpa CSRF ditolak', strpos($keluaranTambah, 'Sesi formulir tidak valid') !== false, nilai(substr($keluaranTambah, 0, 200)));

$keluaranUbah = jalankanAksi('nasabahForm', $nasabahUjiId, '', '');
uji('Form ubah tanpa CSRF ditolak', strpos($keluaranUbah, 'Sesi formulir tidak valid') !== false, nilai(substr($keluaranUbah, 0, 200)));

$jumlahNasabahSesudah = (int) ($db->query("SELECT COUNT(*) AS c FROM nasabah")->fetch()['c'] ?? 0);
$jumlahUserSesudah = (int) ($db->query("SELECT COUNT(*) AS c FROM users")->fetch()['c'] ?? 0);
uji('Tidak ada nasabah baru yang dibuat', $jumlahNasabahSesudah === $jumlahNasabahSebelum, "sebelum={$jumlahNasabahSebelum} sesudah={$jumlahNasabahSesudah}");
uji('Tidak ada user baru yang dibuat', $jumlahUserSesudah === $jumlahUserSebelum, "sebelum={$jumlahUserSebelum} sesudah={$jumlahUserSesudah}");

// ---------------------------------------------------------------
// 7. Form ubah tidak mengubah status nasabah yang nonaktif
// ---------------------------------------------------------------
judul('7. Form ubah tidak mengubah status');

// Data form yang valid, supayapenyimpanan benar-benar berjalan.
$dataFormUbah = [
    'nama' => 'Uji Otomatis Nonaktif',
    'no_hp' => '08000000' . date('Ymd') . '777',
    'bank_sampah_id' => (string) $bankSampahId,
    'no_anggota' => $noAnggotaNonaktif,
    'tipe' => 'PERORANGAN',
];

jalankanAksi('nasabahForm', $nasabahNonaktifId, '', 'TOKEN-UJI', 'POST', $dataFormUbah);
uji('Status nasabah nonaktif tetap NONAKTIF setelah form ubah', statusNasabah($db, $nasabahNonaktifId) === 'NONAKTIF', statusNasabah($db, $nasabahNonaktifId));

$namaSetelahUbah = (string) ($model->findById($nasabahNonaktifId)['nama'] ?? '');
uji('Data lain tetap tersimpan setelah form ubah', $namaSetelahUbah === 'Uji Otomatis Nonaktif', nilai($namaSetelahUbah));

// ---------------------------------------------------------------
// 8. Perubahan status, status akun, dan audit log dalam satu transaksi
// ---------------------------------------------------------------
judul('8. Satu transaksi database');

$triggerAda = false;
try {
    $db->exec("
        CREATE TRIGGER uji_nasabah_gagal BEFORE UPDATE ON users
        FOR EACH ROW
        BEGIN
            IF OLD.no_hp = '{$noHpUji}' THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'uji rollback';
            END IF;
        END
    ");
    $triggerAda = true;
} catch (Throwable $e) {
    $triggerAda = false;
}

if ($triggerAda) {
    $statusSebelum = statusNasabah($db, $nasabahUjiId);
    $statusUserSebelum = statusUser($db, $userUjiId);
    $auditSebelumGagal = jumlahAudit($db, $auditMaxId, $nasabahUjiId);

    jalankanAksi('nasabahToggleStatus', $nasabahUjiId, 'nonaktifkan', 'TOKEN-UJI');

    uji('Status nasabah kembali ke semula setelah langkah kedua gagal', statusNasabah($db, $nasabahUjiId) === $statusSebelum, statusNasabah($db, $nasabahUjiId));
    uji('Status akun kembali ke semula setelah langkah kedua gagal', statusUser($db, $userUjiId) === $statusUserSebelum, statusUser($db, $userUjiId));
    uji('Audit log tidak tertulis saat transaksi dibatalkan', jumlahAudit($db, $auditMaxId, $nasabahUjiId) === $auditSebelumGagal, 'audit log bertambah');

    $db->exec("DROP TRIGGER IF EXISTS uji_nasabah_gagal");
} else {
    $hasil['baris'][] = ['LEWAT', 'Uji rollback dilewati karena trigger tidak bisa dibuat', ''];
}

// ---------------------------------------------------------------
// 9. Rute hapus permanen sudah tidak ada
// ---------------------------------------------------------------
judul('9. Rute hapus permanen dihapus');

$isiRouter = (string) file_get_contents($root . '/public/index.php');
uji('Router tidak lagi memanggil nasabahDelete', strpos($isiRouter, "nasabahDelete") === false, 'masih ada di router');

$blokNasabah = '';
if (preg_match("/if \(\\\$methodName === 'nasabah'\).*?\n    \}/s", $isiRouter, $cocok)) {
    $blokNasabah = $cocok[0];
}
uji('Blok routing nasabah ditemukan', $blokNasabah !== '', 'blok tidak terbaca');
uji('Blok routing nasabah tidak lagi punya param delete', strpos($blokNasabah, 'delete') === false, nilai($blokNasabah));
uji('Blok routing nasabah punya param status', strpos($blokNasabah, "'status'") !== false, nilai($blokNasabah));

$keluaranHapus = jalankanAksi('nasabahDelete', $nasabahUjiId, '', 'TOKEN-UJI');
uji('Method hapus permanen sudah tidak ada', stripos($keluaranHapus, 'undefined method') !== false, nilai(substr($keluaranHapus, 0, 200)));

$keluaranDaftar = jalankanAksi('nasabah', 0, '', 'TOKEN-UJI', 'GET');
uji('Daftar nasabah masih bisa dibuka', strpos($keluaranDaftar, 'Kelola Nasabah') !== false, nilai(substr($keluaranDaftar, 0, 200)));
uji('Tombol Hapus tidak lagi ada di daftar', strpos($keluaranDaftar, 'nasabah/delete') === false, 'masih ada tautan hapus');
uji('Tombol Nonaktifkan tersedia di daftar', strpos($keluaranDaftar, 'Nonaktifkan') !== false, 'tidak ada tombol Nonaktifkan');

$jumlahNasabahAkhir = (int) ($db->query("SELECT COUNT(*) AS c FROM nasabah")->fetch()['c'] ?? 0);
uji('Tidak ada nasabah yang terhapus', $jumlahNasabahAkhir === $jumlahNasabahSebelum, "sebelum={$jumlahNasabahSebelum} akhir={$jumlahNasabahAkhir}");

// ---------------------------------------------------------------
// 10. Dialog peringatan memuat saldo dan pengajuan yang berjalan
// ---------------------------------------------------------------
judul('10. Isi dialog peringatan');

// Beri saldo supaya peringatan saldo ikut muncul.
$db->exec("UPDATE nasabah SET saldo = 125000.50 WHERE id = {$nasabahUjiId}");
$saldoTersimpan = (float) ($model->findById($nasabahUjiId)['saldo'] ?? 0);

$keluaranPeringatan = jalankanAksi('nasabah', 0, '', 'TOKEN-UJI', 'GET');

uji('Dialog peringatan menyebut saldo yang tersimpan', strpos($keluaranPeringatan, 'Saldo Rp') !== false, 'tidak ada penyebutan saldo');
uji('Dialog peringatan memakai nominal yang sama dengan daftar', strpos($keluaranPeringatan, 'Saldo Rp 125.001 masih tersimpan.') !== false, nilai(substr($keluaranPeringatan, 0, 0)));
uji('Dialog peringatan menyebut pengajuan yang belum selesai', strpos($keluaranPeringatan, 'pengajuan pencairan yang belum selesai') !== false, 'tidak ada penyebutan pengajuan');

// ---------------------------------------------------------------
// Bersihkan data uji
// ---------------------------------------------------------------
judul('Bersihkan data uji');

if (!empty($pengajuanIds)) {
    $daftar = implode(',', array_map('intval', $pengajuanIds));
    $db->exec("DELETE FROM pengajuan_pencairan WHERE id IN ({$daftar})");
}

$daftarNasabah = implode(',', [$nasabahUjiId, $nasabahTanpaAkunId, $nasabahNonaktifId]);
$db->exec("DELETE FROM mutasi_saldo WHERE nasabah_id IN ({$daftarNasabah})");
$db->exec("DELETE FROM notifikasi WHERE id > {$auditMaxId} AND user_id = {$userUjiId}");
$db->exec("DELETE FROM audit_log WHERE id > {$auditMaxId} AND tabel = 'nasabah' AND record_id IN ({$daftarNasabah})");
$db->exec("DELETE FROM nasabah WHERE id IN ({$daftarNasabah})");
$db->exec("DELETE FROM users WHERE id = {$userUjiId}");

$sisaNasabahUji = (int) ($db->query("SELECT COUNT(*) AS c FROM nasabah WHERE id IN ({$daftarNasabah})")->fetch()['c'] ?? 0);
$sisaUserUji = (int) ($db->query("SELECT COUNT(*) AS c FROM users WHERE id = {$userUjiId}")->fetch()['c'] ?? 0);
$sisaPengajuanUji = (int) ($db->query("SELECT COUNT(*) AS c FROM pengajuan_pencairan WHERE nasabah_id IN ({$daftarNasabah})")->fetch()['c'] ?? 0);

uji('Data uji nasabah sudah dibersihkan', $sisaNasabahUji === 0, "sisa={$sisaNasabahUji}");
uji('Data uji akun sudah dibersihkan', $sisaUserUji === 0, "sisa={$sisaUserUji}");
uji('Data uji pengajuan sudah dibersihkan', $sisaPengajuanUji === 0, "sisa={$sisaPengajuanUji}");

// ---------------------------------------------------------------
// Tampilkan hasil
// ---------------------------------------------------------------
echo "\n";
echo str_repeat('=', 68) . "\n";
foreach ($hasil['baris'] as $baris) {
    echo str_pad($baris[0], 8) . ' ' . $baris[1] . ($baris[2] !== '' ? ' (' . $baris[2] . ')' : '') . "\n";
}
echo str_repeat('=', 68) . "\n";
echo "Lulus: {$hasil['lulus']}   Gagal: {$hasil['gagal']}\n";

exit($hasil['gagal'] > 0 ? 1 : 0);
