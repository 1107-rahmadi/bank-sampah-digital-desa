<?php
// api/sync/pull.php - GET: ambil data referensi untuk dipakai offline
declare(strict_types=1);
ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../base.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') api_error('Method not allowed', 405);

$pdo = new PDO(
  'mysql:host=localhost;dbname=bank_sampah_desa;charset=utf8mb4',
  'root', '',
  [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]
);

// --- autentikasi & otorisasi ---
$payload = api_auth();
require_role(['ADMIN', 'PENGELOLA'], $payload);
$userId = (int)$payload['user_id'];

$u = $pdo->prepare("SELECT bank_sampah_id, status FROM users WHERE id = ?");
$u->execute([$userId]);
$user = $u->fetch();
if (!$user || $user['status'] !== 'AKTIF' || !$user['bank_sampah_id']) api_error('Tidak berhak', 403);
$bankId = (int)$user['bank_sampah_id'];

$since = isset($_GET['since']) ? max(0, (int)$_GET['since']) : 0;
$BATAS = 500;

function tanda_tanya(array $ids): string { return implode(',', array_fill(0, count($ids), '?')); }

// $ids = null berarti semua, array berarti hanya id itu
function ambil(PDO $pdo, string $sql, array $params, ?array $ids): array {
  if ($ids !== null) {
    if (!$ids) return [];
    $sql .= ' AND id IN (' . tanda_tanya($ids) . ')';
    $params = array_merge($params, $ids);
  }
  $st = $pdo->prepare($sql . ' ORDER BY id');
  $st->execute($params);
  return $st->fetchAll();
}

function data_nasabah(PDO $pdo, int $bankId, ?array $ids): array {
  $rows = ambil($pdo,
    "SELECT id, no_anggota, nama, saldo FROM nasabah WHERE bank_sampah_id = ? AND status = 'AKTIF'",
    [$bankId], $ids);
  foreach ($rows as &$r) { $r['id'] = (int)$r['id']; $r['saldo'] = (string)$r['saldo']; }
  unset($r);
  return $rows;
}

function data_harga(PDO $pdo, int $bankId, ?array $ids): array {
  $rows = ambil($pdo,
    "SELECT id, kategori_id, harga_beli, berlaku_mulai FROM harga_sampah WHERE bank_sampah_id = ? AND aktif = 1",
    [$bankId], $ids);
  foreach ($rows as &$r) {
    $r['id'] = (int)$r['id']; $r['kategori_id'] = (int)$r['kategori_id'];
    $r['harga_beli'] = (string)$r['harga_beli'];
  }
  unset($r);
  return $rows;
}

function data_kategori(PDO $pdo, ?array $ids): array {
  $rows = ambil($pdo, "SELECT id, nama, jenis, satuan FROM kategori_sampah WHERE aktif = 1", [], $ids);
  foreach ($rows as &$r) { $r['id'] = (int)$r['id']; }
  unset($r);
  return $rows;
}

function id_yang_hilang(array $diminta, array $rows): array {
  $ada = [];
  foreach ($rows as $r) $ada[$r['id']] = true;
  $hilang = [];
  foreach ($diminta as $id) if (!isset($ada[$id])) $hilang[] = $id;
  return $hilang;
}

try {
  if ($since === 0) {
    // Ambil cursor SEBELUM membaca data, supaya perubahan di antaranya tidak terlewat
    $cursor = (int)$pdo->query("SELECT COALESCE(MAX(seq), 0) FROM sync_changes")->fetchColumn();
    api_success([
      'full'     => true,
      'cursor'   => $cursor,
      'has_more' => false,
      'nasabah'  => data_nasabah($pdo, $bankId, null),
      'harga'    => data_harga($pdo, $bankId, null),
      'kategori' => data_kategori($pdo, null),
      'hapus'    => ['nasabah' => [], 'harga' => [], 'kategori' => []],
    ], 'OK');
  }

  $st = $pdo->prepare("SELECT seq, entitas, entitas_id FROM sync_changes WHERE seq > ? ORDER BY seq LIMIT " . $BATAS);
  $st->execute([$since]);
  $perubahan = $st->fetchAll();

  $cursor = $since;
  $id = ['nasabah' => [], 'harga' => [], 'kategori' => []];
  foreach ($perubahan as $c) {
    $cursor = (int)$c['seq'];
    if (isset($id[$c['entitas']])) $id[$c['entitas']][(int)$c['entitas_id']] = true;
  }
  foreach ($id as $k => $v) $id[$k] = array_keys($v);

  $nas = data_nasabah($pdo, $bankId, $id['nasabah']);
  $har = data_harga($pdo, $bankId, $id['harga']);
  $kat = data_kategori($pdo, $id['kategori']);

  api_success([
    'full'     => false,
    'cursor'   => $cursor,
    'has_more' => count($perubahan) === $BATAS,
    'nasabah'  => $nas,
    'harga'    => $har,
    'kategori' => $kat,
    'hapus'    => [
      'nasabah'  => id_yang_hilang($id['nasabah'], $nas),
      'harga'    => id_yang_hilang($id['harga'], $har),
      'kategori' => id_yang_hilang($id['kategori'], $kat),
    ],
  ], 'OK');

} catch (Throwable $e) {
  error_log($e->getMessage());
  api_error('Terjadi kesalahan pada server', 500);
}