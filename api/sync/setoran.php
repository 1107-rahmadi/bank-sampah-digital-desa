<?php
//setoran.php
declare(strict_types=1);
ini_set('display_errors', '0');
error_reporting(E_ALL);

date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../base.php';

$pdo = new PDO(
  'mysql:host=localhost;dbname=bank_sampah_desa;charset=utf8mb4',
  'root', '',   // XAMPP default: user root, password kosong
  [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]
);

// out() memakai format respons yang sama dengan API lain
function out(int $code, array $data): void {
  if ($code >= 400) api_error((string)($data['error'] ?? 'Error'), $code);
  api_success($data, 'OK', $code);
}
function toCents(string $s): int {
  [$i, $d] = array_pad(explode('.', $s), 2, '0');
  return (int)$i * 100 + (int)str_pad(substr($d, 0, 2), 2, '0');
}
function fromCents(int $c): string { return sprintf('%d.%02d', intdiv($c, 100), $c % 100); }
function wib(string $iso): string {
  return (new DateTime($iso))->setTimezone(new DateTimeZone('Asia/Jakarta'))->format('Y-m-d H:i:s');
}

// --- autentikasi (token Bearer) & otorisasi ---
$payload = api_auth();
require_role(['ADMIN', 'PENGELOLA'], $payload);
$userId = (int)$payload['user_id'];

$u = $pdo->prepare("SELECT role, bank_sampah_id, status FROM users WHERE id = ?");
$u->execute([$userId]);
$user = $u->fetch(PDO::FETCH_ASSOC);
if (!$user || $user['status'] !== 'AKTIF' || !$user['bank_sampah_id'])
  out(403, ['error' => 'tidak_berhak']);
$bankId = (int)$user['bank_sampah_id'];

$body = json_decode(file_get_contents('php://input'), true);
$items = is_array($body) ? ($body['items'] ?? null) : null;
if (!is_array($items) || count($items) === 0 || count($items) > 50) out(400, ['error' => 'items_tidak_valid']);

// --- proses satu per satu, masing-masing satu transaksi DB ---
$results = [];
foreach ($items as $it) {
  if (!is_array($it)) continue;
  $uuid = (string)($it['uuid'] ?? '');
  try {
    $pdo->beginTransaction();

    if (!preg_match('/^[0-9a-f-]{36}$/i', $uuid)) throw new RuntimeException('uuid_tidak_valid');

    $cek = $pdo->prepare("SELECT id FROM transaksi_beli WHERE uuid = ?");
    $cek->execute([$uuid]);
    if ($cek->fetch()) { $pdo->rollBack(); $results[] = ['uuid' => $uuid, 'status' => 'duplicate']; continue; }

    $tipe   = $it['tipe_penjual'] ?? '';
    $metode = $it['metode_bayar'] ?? '';
    $nasabahId = null;
    if (!in_array($tipe, ['NASABAH', 'NON_NASABAH'], true) || !in_array($metode, ['TABUNG', 'TUNAI'], true))
      throw new RuntimeException('tipe_atau_metode_tidak_valid');
    if ($tipe === 'NON_NASABAH' && $metode !== 'TUNAI') throw new RuntimeException('non_nasabah_harus_tunai');

    if (empty($it['created_at_device']) || strtotime((string)$it['created_at_device']) === false)
      throw new RuntimeException('tanggal_perangkat_tidak_valid');

    $noTrx = trim((string)($it['no_transaksi'] ?? ''));
    if ($noTrx === '' || strlen($noTrx) > 20)
      throw new RuntimeException('no_transaksi_tidak_valid');

    $saldoC = 0;
    if ($tipe === 'NASABAH') {
      $n = $pdo->prepare("SELECT id, saldo, status, bank_sampah_id FROM nasabah WHERE id = ? FOR UPDATE");
      $n->execute([(int)($it['nasabah_id'] ?? 0)]);
      $nas = $n->fetch(PDO::FETCH_ASSOC);
      if (!$nas || $nas['status'] !== 'AKTIF' || (int)$nas['bank_sampah_id'] !== $bankId)
        throw new RuntimeException('nasabah_tidak_valid');
      $nasabahId = (int)$nas['id'];
      $saldoC = toCents((string)$nas['saldo']);
    }

    // hitung ulang semua dari sisi server
    $details = $it['details'] ?? [];
    if (!is_array($details) || !$details) throw new RuntimeException('detail_kosong');
    $rows = []; $totalBeratC = 0; $totalNilaiC = 0;
    $hs = $pdo->prepare("SELECT kategori_id, bank_sampah_id, harga_beli FROM harga_sampah WHERE id = ?");
    foreach ($details as $d) {
      if (!is_array($d)) throw new RuntimeException('detail_tidak_valid');
      $beratC = toCents((string)($d['berat_kg'] ?? '0'));
      if ($beratC <= 0) throw new RuntimeException('berat_tidak_valid');
      $hs->execute([(int)($d['harga_id'] ?? 0)]);
      $h = $hs->fetch(PDO::FETCH_ASSOC);
      if (!$h || (int)$h['bank_sampah_id'] !== $bankId || (int)$h['kategori_id'] !== (int)($d['kategori_id'] ?? 0)
          || toCents((string)$h['harga_beli']) !== toCents((string)($d['harga_per_kg'] ?? '0')))
        throw new RuntimeException('harga_tidak_valid');
      $hargaC = toCents((string)$h['harga_beli']);
      $subC = intdiv($beratC * $hargaC + 50, 100);      // berat(kg) x harga, dibulatkan
      $rows[] = [(int)$h['kategori_id'], (int)$d['harga_id'], $beratC, $hargaC, $subC];
      $totalBeratC += $beratC; $totalNilaiC += $subC;
    }

    $ins = $pdo->prepare("INSERT INTO transaksi_beli
      (uuid, no_transaksi, bank_sampah_id, tipe_penjual, nasabah_id, nama_penjual, metode_bayar, tanggal,
       total_berat, total_nilai, petugas_id, status, catatan, device_id, created_at_device, received_at)
      VALUES (?,?,?,?,?,?,?,?,?,?,?,'SAH',?,?,?,NOW())");
    $ins->execute([
      $uuid, $noTrx, $bankId, $tipe, $nasabahId,
      $tipe === 'NON_NASABAH' ? substr((string)($it['nama_penjual'] ?? ''), 0, 100) : null,
      $metode, wib((string)$it['created_at_device']), fromCents($totalBeratC), fromCents($totalNilaiC),
      $userId, $it['catatan'] ?? null, substr((string)($it['device_id'] ?? ''), 0, 40),
      wib((string)$it['created_at_device']),
    ]);
    $trxId = (int)$pdo->lastInsertId();

    $di = $pdo->prepare("INSERT INTO detail_beli (transaksi_beli_id, kategori_id, harga_id, berat_kg, harga_per_kg, subtotal)
                         VALUES (?,?,?,?,?,?)");
    $stokUp  = $pdo->prepare("UPDATE stok_sampah SET berat_kg = berat_kg + ? WHERE bank_sampah_id = ? AND kategori_id = ?");
    $stokIns = $pdo->prepare("INSERT INTO stok_sampah (bank_sampah_id, kategori_id, berat_kg) VALUES (?,?,?)");
    foreach ($rows as [$kat, $hid, $bC, $hC, $sC]) {
      $di->execute([$trxId, $kat, $hid, fromCents($bC), fromCents($hC), fromCents($sC)]);
      $stokUp->execute([fromCents($bC), $bankId, $kat]);
      if ($stokUp->rowCount() === 0) $stokIns->execute([$bankId, $kat, fromCents($bC)]);
    }

    if ($metode === 'TABUNG') {
      $baru = $saldoC + $totalNilaiC;
      $pdo->prepare("UPDATE nasabah SET saldo = ? WHERE id = ?")->execute([fromCents($baru), $nasabahId]);
      $pdo->prepare("INSERT INTO mutasi_saldo (nasabah_id, tipe, jumlah, saldo_setelah, referensi_tipe, referensi_id, keterangan)
                     VALUES (?, 'SETOR', ?, ?, 'transaksi_beli', ?, ?)")
          ->execute([$nasabahId, fromCents($totalNilaiC), fromCents($baru), $trxId, 'Setoran ' . $noTrx]);
    }

    $pdo->prepare("INSERT INTO audit_log (user_id, aksi, tabel, record_id, sesudah) VALUES (?, 'SYNC_SETORAN', 'transaksi_beli', ?, ?)")
        ->execute([$userId, $trxId, json_encode(['uuid' => $uuid, 'total_nilai' => fromCents($totalNilaiC)])]);

    $pdo->commit();
    $results[] = ['uuid' => $uuid, 'status' => 'ok', 'id' => $trxId];

  } catch (RuntimeException $e) {                 // data ditolak: jangan dicoba ulang
    if ($pdo->inTransaction()) $pdo->rollBack();
    $results[] = ['uuid' => $uuid, 'status' => 'rejected', 'reason' => $e->getMessage()];
  } catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if (($e->errorInfo[1] ?? 0) === 1062) {       // duplicate key
      $c = $pdo->prepare("SELECT id FROM transaksi_beli WHERE uuid = ?"); $c->execute([$uuid]);
      $results[] = $c->fetch()
        ? ['uuid' => $uuid, 'status' => 'duplicate']
        : ['uuid' => $uuid, 'status' => 'rejected', 'reason' => 'no_transaksi_bentrok'];
    } else {
      error_log($e->getMessage());
      $results[] = ['uuid' => $uuid, 'status' => 'error'];
    }
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log($e->getMessage());
    $results[] = ['uuid' => $uuid, 'status' => 'error'];
  }
}
out(200, ['results' => $results]);