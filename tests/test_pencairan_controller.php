<?php
/**
 * Test level controller (setara request HTTP) untuk modul Pencairan.
 *
 * Menjalankan aksi controller di proses terpisah (karena controller
 * memakai redirect() yang memanggil exit), lalu memeriksa akibatnya di
 * database. Tujuannya: memastikan transisi status really divalidasi di
 * sisi server, bukan hanya disembunyikan lewat tombol di UI.
 *
 * Jalur yang diuji:
 *   1. POST /pengelola/pencairan/batal pada pengajuan Disetujui
 *      -> berhasil, alasan tersimpan, notifikasi ke nasabah.
 *   2. POST /pengelola/pencairan/batal tanpa alasan -> ditolak.
 *   3. POST /pengelola/pencairan/batal pada pengajuan Dicairkan
 *      -> ditolak (DICAIRKAN -> DIBATALKAN tidak sah).
 *   4. POST /nasabah/pencairan/batal pada pengajuan Menunggu
 *      -> berhasil, pelaku pembatalan NASABAH.
 *   5. POST /nasabah/pencairan/batal pada pengajuan Disetujui
 *      -> ditolak (nasabah hanya boleh membatalkan saat Menunggu).
 *   6. POST /pengelola/pencairan/setuju pada pengajuan Ditolak
 *      -> ditolak.
 *
 * Jalankan: php tests/test_pencairan_controller.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

$root = dirname(__DIR__);

require_once $root . '/config/app.php';
require_once $root . '/config/database.php';
require_once $root . '/app/helpers/money.php';
require_once $root . '/app/helpers/util.php';
require_once $root . '/app/models/PengajuanPencairan.php';
require_once $root . '/app/models/Nasabah.php';

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
 * Jalankan satu aksi controller di proses terpisah.
 *
 * @param string[] $args Argumen tambahan (mis. id user nasabah)
 */
function jalankanAksi(string $role, string $method, int $id, string $alasan = '', array $args = []): string
{
    $script = dirname(__DIR__) . '/tests/_pencairan_aksi.php';
    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script)
        . ' ' . escapeshellarg($role) . ' ' . escapeshellarg($method)
        . ' ' . escapeshellarg((string) $id) . ' ' . escapeshellarg($alasan);

    foreach ($args as $arg) {
        $cmd .= ' ' . escapeshellarg((string) $arg);
    }

    $output = [];
    $exitCode = 0;
    exec($cmd . ' 2>&1', $output, $exitCode);

    return implode("\n", $output);
}

$db = getDbConnection();
$model = new PengajuanPencairan($db);
$nasabahModel = new Nasabah($db);

$bankSampahId = (int) ($db->query("SELECT id FROM bank_sampah ORDER BY id LIMIT 1")->fetch()['id'] ?? 1);
$userPengelolaId = (int) ($db->query("SELECT id FROM users WHERE role = 'PENGELOLA' ORDER BY id LIMIT 1")->fetch()['id'] ?? 0);

$notifMaxId = (int) ($db->query("SELECT COALESCE(MAX(id), 0) AS m FROM notifikasi")->fetch()['m'] ?? 0);
$auditMaxId = (int) ($db->query("SELECT COALESCE(MAX(id), 0) AS m FROM audit_log")->fetch()['m'] ?? 0);

// User + nasabah uji.
$db->exec("
    INSERT INTO users (desa_id, bank_sampah_id, nama, no_hp, email, password_hash, role, status)
    VALUES (NULL, {$bankSampahId}, 'Uji Otomatis Controller', '080000000000', 'uji.kontrol@example.test',
            'UJI-BUKAN-PASSWORD', 'NASABAH', 'AKTIF')
");
$userUjiId = (int) $db->lastInsertId();

$noAnggotaUji = 'UJI-CTRL-' . date('YmdHis');
$db->exec("
    INSERT INTO nasabah (user_id, bank_sampah_id, no_anggota, nama, no_hp, nama_bank, no_rekening, saldo, status, tgl_daftar)
    VALUES ({$userUjiId}, {$bankSampahId}, '{$noAnggotaUji}', 'Uji Otomatis Controller', '080000000000',
            'Bank Uji', '0000000001', 500000.00, 'AKTIF', CURDATE())
");
$nasabahUjiId = (int) $db->lastInsertId();

$pengajuanIds = [];

function buatPengajuan(PengajuanPencairan $model, int $nasabahId, int $jumlah = 50000): int
{
    global $pengajuanIds;

    $id = $model->create([
        'nasabah_id' => $nasabahId,
        'jumlah' => $jumlah,
        'metode' => 'TUNAI',
        'tujuan_transfer' => null,
    ]);

    if (!$id) {
        throw new RuntimeException('Gagal membuat pengajuan uji: ' . $model->getLastError());
    }

    $pengajuanIds[] = (int) $id;

    return (int) $id;
}

function statusPengajuan(PengajuanPencairan $model, int $id): string
{
    $row = $model->findById($id);

    return (string) ($row['status'] ?? '');
}

$argsNasabah = [$userUjiId, $bankSampahId];

// ---------------------------------------------------------------
// Jalur 1: Pengelola membatalkan pengajuan Disetujui
// ---------------------------------------------------------------
judul('Jalur 1: POST /pengelola/pencairan/batal (pengajuan Disetujui)');

$id1 = buatPengajuan($model, $nasabahUjiId, 60000);
$model->setujui($id1, $userPengelolaId);

$alasan = 'Rekening tujuan tidak sesuai data nasabah.';
$keluaran = jalankanAksi('PENGELOLA', 'pencairanBatal', $id1, $alasan);

$row = $model->findById($id1);
uji('Status menjadi DIBATALKAN', ($row['status'] ?? '') === 'DIBATALKAN', nilai($row['status'] ?? null));
uji('Alasan pembatalan tersimpan', trim((string) ($row['alasan_batal'] ?? '')) === $alasan, nilai($row['alasan_batal'] ?? null));
uji('Pelaku pembatalan PENGELOLA', ($row['dibatalkan_oleh'] ?? null) === 'PENGELOLA', nilai($row['dibatalkan_oleh'] ?? null));
uji('Saldo tersedia dilepas kembali', $nasabahModel->getSaldoTersedia($nasabahUjiId) === 500000.0, nilai($nasabahModel->getSaldoTersedia($nasabahUjiId)));

$stmt = $db->prepare("SELECT isi FROM notifikasi WHERE user_id = :uid AND tipe = 'PENCAIRAN_DIBATALKAN' ORDER BY id DESC LIMIT 1");
$stmt->execute(['uid' => $userUjiId]);
$notif = $stmt->fetch();
uji('Nasabah menerima notifikasi', !empty($notif), 'tidak ada notifikasi');
uji('Notifikasi berisi alasan pembatalan', strpos((string) ($notif['isi'] ?? ''), $alasan) !== false, nilai($notif['isi'] ?? null));

// ---------------------------------------------------------------
// Jalur 2: Pembatalan tanpa alasan ditolak
// ---------------------------------------------------------------
judul('Jalur 2: POST /pengelola/pencairan/batal tanpa alasan');

$id2 = buatPengajuan($model, $nasabahUjiId, 45000);
$model->setujui($id2, $userPengelolaId);

jalankanAksi('PENGELOLA', 'pencairanBatal', $id2, '');

uji('Status tetap DISETUJUI', statusPengajuan($model, $id2) === 'DISETUJUI', statusPengajuan($model, $id2));
uji('Alasan pembatalan tetap kosong', trim((string) ($model->findById($id2)['alasan_batal'] ?? '')) === '', 'alasan tersimpan');

// ---------------------------------------------------------------
// Jalur 3: DICAIRKAN -> DIBATALKAN ditolak
// ---------------------------------------------------------------
judul('Jalur 3: POST /pengelola/pencairan/batal pada pengajuan Dicairkan');

$id3 = buatPengajuan($model, $nasabahUjiId, 35000);
$model->setujui($id3, $userPengelolaId);
$model->cairkan($id3, $userPengelolaId);

jalankanAksi('PENGELOLA', 'pencairanBatal', $id3, 'Percobaan pembatalan');

uji('Status tetap DICAIRKAN', statusPengajuan($model, $id3) === 'DICAIRKAN', statusPengajuan($model, $id3));
uji('Saldo nasabah tidak berubah', $nasabahModel->getSaldo($nasabahUjiId) === 465000.0, nilai($nasabahModel->getSaldo($nasabahUjiId)));

// ---------------------------------------------------------------
// Jalur 4: Nasabah membatalkan pengajuan Menunggu
// ---------------------------------------------------------------
judul('Jalur 4: POST /nasabah/pencairan/batal (pengajuan Menunggu)');

$id4 = buatPengajuan($model, $nasabahUjiId, 25000);
jalankanAksi('NASABAH', 'batalkanPencairan', $id4, '', $argsNasabah);

$row4 = $model->findById($id4);
uji('Status menjadi DIBATALKAN', ($row4['status'] ?? '') === 'DIBATALKAN', nilai($row4['status'] ?? null));
uji('Pelaku pembatalan NASABAH', ($row4['dibatalkan_oleh'] ?? null) === 'NASABAH', nilai($row4['dibatalkan_oleh'] ?? null));
// id2 masih DISETUJUI (pembatalan tanpa alasan ditolak) dan menahan Rp 45.000.
uji('Saldo tersedia dilepas kembali', $nasabahModel->getSaldoTersedia($nasabahUjiId) === 420000.0, nilai($nasabahModel->getSaldoTersedia($nasabahUjiId)));

// ---------------------------------------------------------------
// Jalur 5: Nasabah tidak boleh membatalkan pengajuan Disetujui
// ---------------------------------------------------------------
judul('Jalur 5: POST /nasabah/pencairan/batal (pengajuan Disetujui)');

$id5 = buatPengajuan($model, $nasabahUjiId, 30000);
$model->setujui($id5, $userPengelolaId);

jalankanAksi('NASABAH', 'batalkanPencairan', $id5, '', $argsNasabah);

uji('Status tetap DISETUJUI', statusPengajuan($model, $id5) === 'DISETUJUI', statusPengajuan($model, $id5));

// ---------------------------------------------------------------
// Jalur 6: Setujui pengajuan yang sudah Ditolak
// ---------------------------------------------------------------
judul('Jalur 6: POST /pengelola/pencairan/setuju pada pengajuan Ditolak');

$id6 = buatPengajuan($model, $nasabahUjiId, 20000);
$model->tolak($id6, $userPengelolaId, 'Saldo tidak cukup');

jalankanAksi('PENGELOLA', 'pencairanSetuju', $id6, '');

uji('Status tetap DITOLAK', statusPengajuan($model, $id6) === 'DITOLAK', statusPengajuan($model, $id6));

// ---------------------------------------------------------------
// Bersihkan data uji
// ---------------------------------------------------------------
if (!empty($pengajuanIds)) {
    $daftar = implode(',', array_map('intval', $pengajuanIds));
    $db->exec("DELETE FROM pengajuan_pencairan WHERE id IN ({$daftar})");
}
$db->exec("DELETE FROM mutasi_saldo WHERE nasabah_id = {$nasabahUjiId}");
$db->exec("DELETE FROM notifikasi WHERE id > {$notifMaxId} AND (user_id = {$userUjiId} OR user_id = {$userPengelolaId})");
$db->exec("DELETE FROM audit_log WHERE id > {$auditMaxId} AND tabel = 'pengajuan_pencairan'");
$db->exec("DELETE FROM nasabah WHERE id = {$nasabahUjiId}");
$db->exec("DELETE FROM users WHERE id = {$userUjiId}");

// ---------------------------------------------------------------
// Cetak hasil
// ---------------------------------------------------------------
echo "\n=== Hasil Test Controller Pencairan ===\n";
foreach ($hasil['baris'] as $baris) {
    if ($baris[0] === '---') {
        echo "\n" . $baris[1] . "\n";
        continue;
    }
    printf("%-6s %s%s\n", $baris[0], $baris[1], $baris[2] !== '' ? ' -> ' . $baris[2] : '');
}
echo "\nLulus: {$hasil['lulus']} | Gagal: {$hasil['gagal']}\n";

exit($hasil['gagal'] > 0 ? 1 : 0);
