<?php
/**
 * Helper internal untuk tests/test_nasabah_admin.php.
 *
 * Menjalankan satu aksi controller Kelola Nasabah Admin seperti di request
 * HTTP (method, token CSRF, id, aksi), lalu keluar. Karena controller memakai
 * redirect() yang memanggil exit, setiap aksi dijalankan di proses terpisah.
 *
 * Pemakaian: php tests/_nasabah_admin_aksi.php <method> <id> <aksi> [csrf] [method_http] [post_json]
 *   method     : nasabahToggleStatus | nasabahForm | nasabah
 *   id         : id nasabah (0 untuk tambah)
 *   aksi       : nonaktifkan | aktifkan | (kosong untuk form)
 *   csrf       : token CSRF yang dikirim, kosong berarti token tidak dikirim
 *   method_http: POST (bawaan) atau GET
 *   post_json  : data POST tambahan dalam format JSON
 *
 * Helper ini bukan test; file testnya ada di test_nasabah_admin.php.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

$root = dirname(__DIR__);

require_once $root . '/config/app.php';
require_once $root . '/config/database.php';
require_once $root . '/app/helpers/auth.php';
require_once $root . '/app/helpers/money.php';
require_once $root . '/app/helpers/util.php';

$method = $argv[1] ?? '';
$id = (int) ($argv[2] ?? 0);
$aksi = $argv[3] ?? '';
$csrf = $argv[4] ?? '';
$httpMethod = strtoupper($argv[5] ?? 'POST');
$postTambahan = $argv[6] ?? '';

session_start();

$_SESSION['user_id'] = 1;                 // user admin
$_SESSION['user_nama'] = 'Admin Uji';
$_SESSION['user_no_hp'] = '081100000000';
$_SESSION['user_email'] = null;
$_SESSION['user_role'] = 'ADMIN';
$_SESSION['user_desa_id'] = 1;
$_SESSION['user_bank_sampah_id'] = 1;
$_SESSION['login_time'] = time();
$_SESSION['csrf_token'] = 'TOKEN-UJI';

$_SERVER['REQUEST_METHOD'] = $httpMethod;
$_SERVER['REQUEST_URI'] = '/admin/nasabah';
$_GET = [];
$_POST = [];

// Form ubah mengirim id lewat query string, karena form memakai action="".
if ($id > 0) {
    $_GET['id'] = $id;
}

if ($httpMethod === 'POST') {
    $_POST['id'] = $id;
    if ($aksi !== '') {
        $_POST['aksi'] = $aksi;
    }
    if ($csrf !== '') {
        $_POST['csrf_token'] = $csrf;
    }
    if ($postTambahan !== '') {
        $tambahan = json_decode($postTambahan, true);
        if (is_array($tambahan)) {
            foreach ($tambahan as $kunci => $isi) {
                $_POST[$kunci] = $isi;
            }
        }
    }
}
$_REQUEST = $_POST;

require_once CONTROLLER_PATH . '/AdminController.php';
$controller = new AdminController();
$controller->$method();
exit(0);
