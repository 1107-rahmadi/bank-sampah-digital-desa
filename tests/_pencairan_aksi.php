<?php
/**
 * Helper internal untuk tests/test_pencairan_controller.php.
 *
 * Menjalankan satu aksi controller pencairan seperti di request HTTP
 * (POST + token CSRF), lalu keluar. Karena controller memakai redirect()
 * yang memanggil exit, setiap aksi dijalankan di proses terpisah.
 *
 * Pemakaian: php tests/_pencairan_aksi.php <role> <method> <id> [alasan]
 *   role   : PENGELOLA | NASABAH
 *   method : pencairanSetuju | pencairanTolak | pencairanCairkan |
 *            pencairanBatal | batalkanPencairan
 *   id     : id pengajuan
 *   alasan : alasan (opsional, boleh kosong)
 *   userId : id user yang dipakai kalau role NASABAH
 *
 * Helper ini bukan test; file testnya ada di test_pencairan_controller.php.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

$root = dirname(__DIR__);

require_once $root . '/config/app.php';
require_once $root . '/config/database.php';
require_once $root . '/app/helpers/auth.php';
require_once $root . '/app/helpers/money.php';
require_once $root . '/app/helpers/util.php';

$role = $argv[1] ?? 'PENGELOLA';
$method = $argv[2] ?? '';
$id = (int) ($argv[3] ?? 0);
$alasan = $argv[4] ?? '';

session_start();

$_SESSION['user_id'] = 2;                 // user pengelola
$_SESSION['user_nama'] = 'Pengelola Uji';
$_SESSION['user_no_hp'] = '081200000000';
$_SESSION['user_email'] = null;
$_SESSION['user_role'] = 'PENGELOLA';
$_SESSION['user_desa_id'] = 1;
$_SESSION['user_bank_sampah_id'] = 1;
$_SESSION['login_time'] = time();
$_SESSION['csrf_token'] = 'TOKEN-UJI';

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    '_token' => 'TOKEN-UJI',
    'id' => $id,
    'alasan' => $alasan,
];
$_GET = [];
$_REQUEST = $_POST;

if ($role === 'NASABAH') {
    // Nasabah uji dibuat oleh test induk; id user-nya diteruskan sebagai
    // argumen terakhir.
    $userNasabah = (int) ($argv[5] ?? 0);
    $bankSampahId = (int) ($argv[6] ?? 1);

    $_SESSION['user_id'] = $userNasabah;
    $_SESSION['user_nama'] = 'Nasabah Uji';
    $_SESSION['user_role'] = 'NASABAH';
    $_SESSION['user_bank_sampah_id'] = $bankSampahId;

    require_once CONTROLLER_PATH . '/NasabahController.php';
    $controller = new NasabahController();
    $controller->$method();
    exit(0);
}

require_once CONTROLLER_PATH . '/PengelolaController.php';
$controller = new PengelolaController();
$controller->$method();
exit(0);
