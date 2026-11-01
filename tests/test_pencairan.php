<?php
/**
 * Test modul Pencairan (Pengelola).
 *
 * Menjalankan seluruh jalur status pengajuan pencairan:
 *   1. Menunggu -> Disetujui -> Dicairkan (saldo berkurang tepat satu kali)
 *   2. Menunggu -> Ditolak (saldo tersedia kembali, alasan tersimpan)
 *   3. Menunggu -> Dibatalkan oleh nasabah
 *   4. Disetujui -> Dibatalkan oleh pengelola (alasan wajib, saldo tersedia kembali)
 *   5. Transisi yang tidak sah ditolak server
 *   6. Jumlah semua chip = total pengajuan
 *
 * Jalankan: php tests/test_pencairan.php
 *
 * Test membuat data sendiri (user + nasabah uji), lalu membersihkannya
 * kembali di akhir, jadi data lama tidak tersentuh.
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

// ---------------------------------------------------------------
// Persiapan data uji
// ---------------------------------------------------------------
$db = getDbConnection();

$bankSampahId = (int) ($db->query("SELECT id FROM bank_sampah ORDER BY id LIMIT 1")->fetch()['id'] ?? 1);
$userPengelolaId = (int) ($db->query("SELECT id FROM users WHERE role = 'PENGELOLA' ORDER BY id LIMIT 1")->fetch()['id'] ?? 0);

$notifMaxId = (int) ($db->query("SELECT COALESCE(MAX(id), 0) AS m FROM notifikasi")->fetch()['m'] ?? 0);
$auditMaxId = (int) ($db->query("SELECT COALESCE(MAX(id), 0) AS m FROM audit_log")->fetch()['m'] ?? 0);

$db->beginTransaction();
$db->exec("
    INSERT INTO users (desa_id, bank_sampah_id, nama, no_hp, email, password_hash, role, status)
    VALUES (NULL, {$bankSampahId}, 'Uji Otomatis Pencairan', '080000000000', 'uji.pencairan@example.test',
            'UJI-BUKAN-PASSWORD', 'NASABAH', 'AKTIF')
");
$userUjiId = (int) $db->lastInsertId();

$noAnggotaUji = 'UJI-' . date('YmdHis') . '-A';

$db->exec("
    INSERT INTO nasabah (user_id, bank_sampah_id, no_anggota, nama, no_hp, nama_bank, no_rekening, saldo, status, tgl_daftar)
    VALUES ({$userUjiId}, {$bankSampahId}, '{$noAnggotaUji}', 'Uji Otomatis Pencairan', '080000000000',
            'Bank Uji', '0000000000', 500000.00, 'AKTIF', CURDATE())
");
$nasabahUjiId = (int) $db->lastInsertId();
$db->commit();

$model = new PengajuanPencairan($db);
$nasabahModel = new Nasabah($db);

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

function saldoNasabah(Nasabah $model, int $nasabahId): float
{
    return (float) $model->getSaldo($nasabahId);
}

function saldoTersedia(Nasabah $model, int $nasabahId): float
{
    return (float) $model->getSaldoTersedia($nasabahId);
}

function jumlahMutasiPencairan(PDO $db, int $nasabahId): int
{
    $stmt = $db->prepare("
        SELECT COUNT(*) AS c FROM mutasi_saldo
        WHERE nasabah_id = :id AND tipe = 'PENCAIRAN'
    ");
    $stmt->execute(['id' => $nasabahId]);

    return (int) $stmt->fetch()['c'];
}

function jumlahMutasiPencairanUntuk(PDO $db, int $nasabahId, int $referensiId): int
{
    $stmt = $db->prepare("
        SELECT COUNT(*) AS c FROM mutasi_saldo
        WHERE nasabah_id = :id AND tipe = 'PENCAIRAN'
          AND referensi_tipe = 'pengajuan_pencairan' AND referensi_id = :ref
    ");
    $stmt->execute(['id' => $nasabahId, 'ref' => $referensiId]);

    return (int) $stmt->fetch()['c'];
}

/**
 * Ambil satu baris audit log terakhir untuk sebuah pengajuan.
 *
 * @return array ['user_id' => int, 'aksi' => string, 'sebelum' => array, 'sesudah' => array]
 */
function auditTerakhir(PDO $db, int $pengajuanId): array
{
    $stmt = $db->prepare("
        SELECT user_id, aksi, sebelum, sesudah
        FROM audit_log
        WHERE tabel = 'pengajuan_pencairan' AND record_id = :id
        ORDER BY id DESC LIMIT 1
    ");
    $stmt->execute(['id' => $pengajuanId]);
    $row = $stmt->fetch();

    if (!$row) {
        return [];
    }

    return [
        'user_id' => (int) $row['user_id'],
        'aksi' => (string) $row['aksi'],
        'sebelum' => json_decode((string) $row['sebelum'], true) ?: [],
        'sesudah' => json_decode((string) $row['sesudah'], true) ?: [],
    ];
}

// ---------------------------------------------------------------
// Jalur 1: Menunggu -> Disetujui -> Dicairkan
// ---------------------------------------------------------------
judul('Jalur 1: Menunggu -> Disetujui -> Dicairkan');

$id1 = buatPengajuan($model, $nasabahUjiId, 50000);

uji('Status awal pengajuan adalah DIAJUKAN (Menunggu)', statusPengajuan($model, $id1) === 'DIAJUKAN', statusPengajuan($model, $id1));
uji('Saldo nasabah awal Rp 500.000', saldoNasabah($nasabahModel, $nasabahUjiId) === 500000.0, nilai(saldoNasabah($nasabahModel, $nasabahUjiId)));
uji('Saldo tersedia awal Rp 450.000 (pengajuan menahan saldo)', saldoTersedia($nasabahModel, $nasabahUjiId) === 450000.0, nilai(saldoTersedia($nasabahModel, $nasabahUjiId)));

$okSetuju = $model->setujui($id1, $userPengelolaId);
uji('Setuju berhasil', $okSetuju === true, (string) $model->getLastError());
uji('Status setelah disetujui DISETUJUI', statusPengajuan($model, $id1) === 'DISETUJUI', statusPengajuan($model, $id1));
uji('Saldo nasabah belum turun saat disetujui', saldoNasabah($nasabahModel, $nasabahUjiId) === 500000.0, nilai(saldoNasabah($nasabahModel, $nasabahUjiId)));
uji('Saldo tersedia tetap Rp 450.000', saldoTersedia($nasabahModel, $nasabahUjiId) === 450000.0, nilai(saldoTersedia($nasabahModel, $nasabahUjiId)));

$auditSetuju = auditTerakhir($db, $id1);
uji('Audit log mencatat aksi SETUJUI', ($auditSetuju['aksi'] ?? '') === 'SETUJUI', nilai($auditSetuju['aksi'] ?? null));
uji('Audit log mencatat pelaku (pengelola)', ($auditSetuju['user_id'] ?? 0) === $userPengelolaId, nilai($auditSetuju['user_id'] ?? null));
uji('Audit log mencatat status lama', ($auditSetuju['sebelum']['status'] ?? '') === 'DIAJUKAN', nilai($auditSetuju['sebelum']['status'] ?? null));
uji('Audit log mencatat status baru', ($auditSetuju['sesudah']['status'] ?? '') === 'DISETUJUI', nilai($auditSetuju['sesudah']['status'] ?? null));

$okCairkan = $model->cairkan($id1, $userPengelolaId);
uji('Pencairan berhasil', $okCairkan === true, (string) $model->getLastError());
uji('Status setelah dicairkan DICAIRKAN', statusPengajuan($model, $id1) === 'DICAIRKAN', statusPengajuan($model, $id1));
uji('Saldo nasabah berkurang menjadi Rp 450.000', saldoNasabah($nasabahModel, $nasabahUjiId) === 450000.0, nilai(saldoNasabah($nasabahModel, $nasabahUjiId)));
uji('Saldo tersedia menjadi Rp 450.000', saldoTersedia($nasabahModel, $nasabahUjiId) === 450000.0, nilai(saldoTersedia($nasabahModel, $nasabahUjiId)));
uji('Mutasi PENCAIRAN tercatat tepat satu kali', jumlahMutasiPencairanUntuk($db, $nasabahUjiId, $id1) === 1, nilai(jumlahMutasiPencairanUntuk($db, $nasabahUjiId, $id1)));

$stmt = $db->prepare("SELECT jumlah FROM mutasi_saldo WHERE referensi_id = :ref AND referensi_tipe = 'pengajuan_pencairan'");
$stmt->execute(['ref' => $id1]);
$mutasi = $stmt->fetch();
uji('Mutasi pencairan bernilai negatif (kurang)', (float) ($mutasi['jumlah'] ?? 0) === -50000.0, nilai($mutasi['jumlah'] ?? null));

$auditCairkan = auditTerakhir($db, $id1);
uji('Audit log mencatat aksi DICAIRKAN', ($auditCairkan['aksi'] ?? '') === 'DICAIRKAN', nilai($auditCairkan['aksi'] ?? null));
uji('Audit log pencairan mencatat status lama DISETUJUI', ($auditCairkan['sebelum']['status'] ?? '') === 'DISETUJUI', nilai($auditCairkan['sebelum']['status'] ?? null));

// ---------------------------------------------------------------
// Jalur 2: Menunggu -> Ditolak
// ---------------------------------------------------------------
judul('Jalur 2: Menunggu -> Ditolak');

$id2 = buatPengajuan($model, $nasabahUjiId, 30000);
uji('Status awal DIAJUKAN', statusPengajuan($model, $id2) === 'DIAJUKAN', statusPengajuan($model, $id2));
// Saldo nasabah Rp 450.000 (sudah dikurangi pencairan Jalur 1), tertahan Rp 30.000.
uji('Saldo tersedia berkurang sementara Rp 420.000', saldoTersedia($nasabahModel, $nasabahUjiId) === 420000.0, nilai(saldoTersedia($nasabahModel, $nasabahUjiId)));

$alasanTolak = 'Saldo tidak mencukupi untuk pencairan bulan ini.';
$okTolak = $model->tolak($id2, $userPengelolaId, $alasanTolak);
uji('Tolak berhasil', $okTolak === true, (string) $model->getLastError());
uji('Status setelah ditolak DITOLAK', statusPengajuan($model, $id2) === 'DITOLAK', statusPengajuan($model, $id2));

$row2 = $model->findById($id2);
uji('Alasan penolakan tersimpan', trim((string) ($row2['alasan_tolak'] ?? '')) === $alasanTolak, nilai($row2['alasan_tolak'] ?? null));
uji('Saldo nasabah tidak berubah', saldoNasabah($nasabahModel, $nasabahUjiId) === 450000.0, nilai(saldoNasabah($nasabahModel, $nasabahUjiId)));
// Pengajuan id2 sudah DITOLAK, jadi tidak ada saldo yang tertahan lagi.
uji('Saldo tersedia kembali ke Rp 450.000', saldoTersedia($nasabahModel, $nasabahUjiId) === 450000.0, nilai(saldoTersedia($nasabahModel, $nasabahUjiId)));
// Tanpa alasan harus ditolak.
$id2b = buatPengajuan($model, $nasabahUjiId, 20000);
$okTolakTanpaAlasan = $model->tolak($id2b, $userPengelolaId, '   ');
uji('Tolak tanpa alasan ditolak server', $okTolakTanpaAlasan === false, nilai($model->getLastError()));
uji('Status tetap DIAJUKAN setelah tolak tanpa alasan', statusPengajuan($model, $id2b) === 'DIAJUKAN', statusPengajuan($model, $id2b));

// ---------------------------------------------------------------
// Jalur 3: Menunggu -> Dibatalkan oleh nasabah
// ---------------------------------------------------------------
judul('Jalur 3: Menunggu -> Dibatalkan oleh nasabah');

$id3 = buatPengajuan($model, $nasabahUjiId, 40000);
uji('Status awal DIAJUKAN', statusPengajuan($model, $id3) === 'DIAJUKAN', statusPengajuan($model, $id3));

$okBatal = $model->batalkanNasabah($id3, $userUjiId);
uji('Pembatalan oleh nasabah berhasil', $okBatal === true, (string) $model->getLastError());
uji('Status setelah dibatalkan DIBATALKAN', statusPengajuan($model, $id3) === 'DIBATALKAN', statusPengajuan($model, $id3));

$row3 = $model->findById($id3);
uji('Pelaku pembatalan tercatat NASABAH', ($row3['dibatalkan_oleh'] ?? null) === 'NASABAH', nilai($row3['dibatalkan_oleh'] ?? null));
uji('Waktu pembatalan tercatat', !empty($row3['dibatalkan_at']), nilai($row3['dibatalkan_at'] ?? null));
uji('Alasan pembatalan tercatat', trim((string) ($row3['alasan_batal'] ?? '')) !== '', nilai($row3['alasan_batal'] ?? null));

$auditBatalNasabah = auditTerakhir($db, $id3);
uji('Audit log mencatat aksi BATAL', ($auditBatalNasabah['aksi'] ?? '') === 'BATAL', nilai($auditBatalNasabah['aksi'] ?? null));
uji('Audit log mencatat pelaku nasabah', ($auditBatalNasabah['user_id'] ?? 0) === $userUjiId, nilai($auditBatalNasabah['user_id'] ?? null));
uji('Audit log mencatat status lama DIAJUKAN', ($auditBatalNasabah['sebelum']['status'] ?? '') === 'DIAJUKAN', nilai($auditBatalNasabah['sebelum']['status'] ?? null));
uji('Audit log mencatat status baru DIBATALKAN', ($auditBatalNasabah['sesudah']['status'] ?? '') === 'DIBATALKAN', nilai($auditBatalNasabah['sesudah']['status'] ?? null));
uji('Audit log mencatat pelaku pembatalan NASABAH', ($auditBatalNasabah['sesudah']['dibatalkan_oleh'] ?? '') === 'NASABAH', nilai($auditBatalNasabah['sesudah']['dibatalkan_oleh'] ?? null));
uji('Saldo nasabah tidak berubah', saldoNasabah($nasabahModel, $nasabahUjiId) === 450000.0, nilai(saldoNasabah($nasabahModel, $nasabahUjiId)));
// Pengajuan uji id2b (Rp 20.000) masih Menunggu, jadi saldo tersedia Rp 430.000.
uji('Saldo tersedia kembali ke Rp 430.000', saldoTersedia($nasabahModel, $nasabahUjiId) === 430000.0, nilai(saldoTersedia($nasabahModel, $nasabahUjiId)));

// ---------------------------------------------------------------
// Jalur 4: Disetujui -> Dibatalkan oleh pengelola
// ---------------------------------------------------------------
judul('Jalur 4: Disetujui -> Dibatalkan oleh pengelola');

$id4 = buatPengajuan($model, $nasabahUjiId, 60000);
$model->setujui($id4, $userPengelolaId);
uji('Status setelah disetujui DISETUJUI', statusPengajuan($model, $id4) === 'DISETUJUI', statusPengajuan($model, $id4));
// Tertahan: id2b Rp 20.000 (Menunggu) + id4 Rp 60.000 (Disetujui).
uji('Saldo tertahan Rp 80.000 (tersedia Rp 370.000)', saldoTersedia($nasabahModel, $nasabahUjiId) === 370000.0, nilai(saldoTersedia($nasabahModel, $nasabahUjiId)));

$okBatalTanpaAlasan = $model->batalkanPengelola($id4, $userPengelolaId, '');
uji('Pembatalan tanpa alasan ditolak server', $okBatalTanpaAlasan === false, nilai($model->getLastError()));
uji('Status tetap DISETUJUI setelah pembatalan tanpa alasan', statusPengajuan($model, $id4) === 'DISETUJUI', statusPengajuan($model, $id4));

$alasanBatal = 'Rekening tujuan tidak sesuai data nasabah.';
$okBatalPengelola = $model->batalkanPengelola($id4, $userPengelolaId, $alasanBatal);
uji('Pembatalan oleh pengelola berhasil', $okBatalPengelola === true, (string) $model->getLastError());
uji('Status setelah dibatalkan DIBATALKAN', statusPengajuan($model, $id4) === 'DIBATALKAN', statusPengajuan($model, $id4));

$row4 = $model->findById($id4);
uji('Pelaku pembatalan tercatat PENGELOLA', ($row4['dibatalkan_oleh'] ?? null) === 'PENGELOLA', nilai($row4['dibatalkan_oleh'] ?? null));
uji('Alasan pembatalan tersimpan', trim((string) ($row4['alasan_batal'] ?? '')) === $alasanBatal, nilai($row4['alasan_batal'] ?? null));

$auditBatalPengelola = auditTerakhir($db, $id4);
uji('Audit log pembatalan mencatat pelaku pengelola', ($auditBatalPengelola['user_id'] ?? 0) === $userPengelolaId, nilai($auditBatalPengelola['user_id'] ?? null));
uji('Audit log pembatalan mencatat alasan', ($auditBatalPengelola['sesudah']['alasan'] ?? '') === $alasanBatal, nilai($auditBatalPengelola['sesudah']['alasan'] ?? null));
uji('Audit log pembatalan mencatat pelaku pembatalan PENGELOLA', ($auditBatalPengelola['sesudah']['dibatalkan_oleh'] ?? '') === 'PENGELOLA', nilai($auditBatalPengelola['sesudah']['dibatalkan_oleh'] ?? null));
uji('Saldo nasabah tidak berubah', saldoNasabah($nasabahModel, $nasabahUjiId) === 450000.0, nilai(saldoNasabah($nasabahModel, $nasabahUjiId)));
// Setelah id4 dibatalkan, yang masih tertahan hanya id2b (Rp 20.000).
uji('Saldo tersedia dilepas kembali ke Rp 430.000', saldoTersedia($nasabahModel, $nasabahUjiId) === 430000.0, nilai(saldoTersedia($nasabahModel, $nasabahUjiId)));

// Nasabah harus menerima notifikasi berisi alasan.
$stmt = $db->prepare("
    SELECT isi FROM notifikasi
    WHERE user_id = :uid AND tipe = 'PENCAIRAN_DIBATALKAN'
    ORDER BY id DESC LIMIT 1
");
$stmt->execute(['uid' => $userUjiId]);
$notif = $stmt->fetch();
uji('Notifikasi pembatalan dikirim ke nasabah', !empty($notif), 'tidak ada notifikasi');
uji('Notifikasi memuat alasan pembatalan', strpos((string) ($notif['isi'] ?? ''), $alasanBatal) !== false, nilai($notif['isi'] ?? null));

// ---------------------------------------------------------------
// Jalur 5: Transisi yang tidak sah ditolak server
// ---------------------------------------------------------------
judul('Jalur 5: Transisi yang tidak sah ditolak server');

// DICAIRKAN -> DIBATALKAN
$ok = $model->batalkanPengelola($id1, $userPengelolaId, 'Percobaan pembatalan setelah dicairkan');
uji('DICAIRKAN -> DIBATALKAN ditolak', $ok === false, nilai($model->getLastError()));
uji('Status tetap DICAIRKAN', statusPengajuan($model, $id1) === 'DICAIRKAN', statusPengajuan($model, $id1));
uji('Saldo tetap Rp 450.000', saldoNasabah($nasabahModel, $nasabahUjiId) === 450000.0, nilai(saldoNasabah($nasabahModel, $nasabahUjiId)));

// DITOLAK -> DISETUJUI
$ok = $model->setujui($id2, $userPengelolaId);
uji('DITOLAK -> DISETUJUI ditolak', $ok === false, nilai($model->getLastError()));
uji('Status tetap DITOLAK', statusPengajuan($model, $id2) === 'DITOLAK', statusPengajuan($model, $id2));

// DIBATALKAN -> DISETUJUI
$ok = $model->setujui($id3, $userPengelolaId);
uji('DIBATALKAN -> DISETUJUI ditolak', $ok === false, nilai($model->getLastError()));
uji('Status tetap DIBATALKAN', statusPengajuan($model, $id3) === 'DIBATALKAN', statusPengajuan($model, $id3));

// DISETUJUI -> DITOLAK (tidak ada jalur ini)
$id5 = buatPengajuan($model, $nasabahUjiId, 25000);
$model->setujui($id5, $userPengelolaId);
$ok = $model->tolak($id5, $userPengelolaId, 'Alasan');
uji('DISETUJUI -> DITOLAK ditolak', $ok === false, nilai($model->getLastError()));
uji('Status tetap DISETUJUI', statusPengajuan($model, $id5) === 'DISETUJUI', statusPengajuan($model, $id5));

// DISETUJUI -> DICAIRKAN harus lewat pencairan (saldo berkurang), bukan updateStatus.
$ok = $model->updateStatus($id5, PengajuanPencairan::STATUS_DICAIRKAN, $userPengelolaId);
uji('DISETUJUI -> DICAIRKAN lewat updateStatus ditolak (harus lewat pencairan)', $ok === false, nilai($model->getLastError()));
uji('Saldo tidak berubah karena pencairan tidak diproses', saldoNasabah($nasabahModel, $nasabahUjiId) === 450000.0, nilai(saldoNasabah($nasabahModel, $nasabahUjiId)));

// ---------------------------------------------------------------
// Jalur 6: Jumlah semua chip = total pengajuan
// ---------------------------------------------------------------
judul('Jalur 6: Jumlah semua chip = total pengajuan');

$statistik = $model->getStatistik($bankSampahId);
$labels = PengajuanPencairan::semuaStatus();

$jumlahChip = 0;
foreach ($labels as $status => $label) {
    $jumlahChip += (int) ($statistik[$status]['jumlah'] ?? 0);
}

$total = (int) ($statistik['TOTAL']['jumlah'] ?? 0);
$totalSemua = $model->countAll($bankSampahId);

uji('Jumlah chip lima status = total pengajuan', $jumlahChip === $total, 'chip=' . $jumlahChip . ' total=' . $total);
uji('Total pengajuan = jumlah baris di database', $total === $totalSemua, 'total=' . $total . ' baris=' . $totalSemua);

// Jumlah per status harus sama dengan hitungan langsung di database.
foreach ($labels as $status => $label) {
    $stmt = $db->prepare("
        SELECT COUNT(*) AS c FROM pengajuan_pencairan p
        JOIN nasabah n ON n.id = p.nasabah_id
        WHERE p.status = :status AND n.bank_sampah_id = :bs
    ");
    $stmt->execute(['status' => $status, 'bs' => $bankSampahId]);
    $langsung = (int) $stmt->fetch()['c'];
    $chip = (int) ($statistik[$status]['jumlah'] ?? 0);

    uji('Jumlah chip ' . $label . ' sama dengan hitungan database', $chip === $langsung, 'chip=' . $chip . ' db=' . $langsung);
}

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
echo "\n=== Hasil Test Modul Pencairan ===\n";
foreach ($hasil['baris'] as $baris) {
    if ($baris[0] === '---') {
        echo "\n" . $baris[1] . "\n";
        continue;
    }
    printf("%-6s %s%s\n", $baris[0], $baris[1], $baris[2] !== '' ? ' -> ' . $baris[2] : '');
}
echo "\nLulus: {$hasil['lulus']} | Gagal: {$hasil['gagal']}\n";

exit($hasil['gagal'] > 0 ? 1 : 0);
