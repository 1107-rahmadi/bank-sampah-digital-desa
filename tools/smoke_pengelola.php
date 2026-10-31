<?php
/**
 * Smoke test sementara: merender setiap halaman pengelola dengan sesi
 * pengelola dan memeriksa galat PHP (notice/warning/fatal).
 *
 * Jalankan: php tools/smoke_pengelola.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

$root = dirname(__DIR__);

// Saneer untuk menggagalkan echo dari view.
ob_start();

set_error_handler(function ($no, $str, $file, $line) {
    throw new ErrorException($str, 0, $no, $file, $line);
});

require_once $root . '/config/app.php';
require_once $root . '/config/database.php';
require_once $root . '/app/helpers/auth.php';
require_once $root . '/app/helpers/money.php';

// Sesi pengelola.
$_SESSION['user_id'] = 2;
$_SESSION['user_nama'] = 'Pengelola Uji';
$_SESSION['user_no_hp'] = '081200000000';
$_SESSION['user_email'] = null;
$_SESSION['user_role'] = 'PENGELOLA';
$_SESSION['user_desa_id'] = 1;
$_SESSION['user_bank_sampah_id'] = 1;
$_SESSION['login_time'] = time();
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

require_once CONTROLLER_PATH . '/PengelolaController.php';

// Halaman yang dirender: [label, metode, argumen GET]
$halaman = [
    ['Dashboard', 'dashboard', []],
    ['Transaksi Beli', 'transaksiBeli', []],
    ['Transaksi Beli Form', 'transaksiBeliForm', []],
    ['Transaksi Beli Detail', 'transaksiBeliDetail', ['id' => 1]],
    ['Transaksi Jual', 'transaksiJual', []],
    ['Transaksi Jual Form', 'transaksiJualForm', []],
    ['Transaksi Jual Detail', 'transaksiJualDetail', ['id' => 1]],
    ['Stok', 'stok', []],
    ['Harga', 'harga', []],
    ['Nasabah', 'nasabah', []],
    ['Nasabah Form', 'nasabahForm', []],
    ['Nasabah Form Edit', 'nasabahForm', ['id' => 1]],
    ['Pengepul', 'pengepul', []],
    ['Pengepul Form', 'pengepulForm', []],
    ['Pencairan', 'pencairan', []],
    ['Pencairan Detail', 'pencairanDetail', ['id' => 1]],
    ['Laporan', 'laporan', []],
];

$hasil = [];
foreach ($halaman as $h) {
    list($label, $metode, $get) = $h;

    $_GET = $get;
    $_POST = [];
    $_REQUEST = $get;
    $_SERVER['REQUEST_METHOD'] = 'GET';

    ob_start();
    try {
        $controller = new PengelolaController();
        $controller->$metode();
        $isi = ob_get_clean();
        $panjang = strlen($isi);
        $ada = strpos($isi, 'fatal error') !== false || strpos($isi, 'Warning:') !== false
            || strpos($isi, 'Notice:') !== false || strpos($isi, 'Undefined ') !== false;
        $hasil[] = [$label, $ada ? 'PERHATIAN' : 'OK', $panjang, $ada ? substr(strip_tags($isi), 0, 400) : ''];
    } catch (Throwable $e) {
        ob_end_clean();
        $hasil[] = [$label, 'GAGAL', 0, get_class($e) . ': ' . $e->getMessage()
            . ' @ ' . basename($e->getFile()) . ':' . $e->getLine()];
    }
}

restore_error_handler();
ob_end_clean();

$adaGagal = false;
foreach ($hasil as $h) {
    printf("%-24s %-10s %7d byte\n", $h[0], $h[1], $h[2]);
    if ($h[3] !== '') {
        $adaGagal = true;
        echo "    -> " . str_replace("\n", "\n       ", $h[3]) . "\n";
    }
}

echo $adaGagal ? "\nADA MASALAH.\n" : "\nSemua halaman dirender bersih.\n";
