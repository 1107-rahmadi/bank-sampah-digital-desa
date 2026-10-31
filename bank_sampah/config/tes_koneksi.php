<?php

session_start();
echo '<pre>'; print_r($_SESSION);

ini_set('display_errors', '1');
error_reporting(E_ALL);

require __DIR__ . '/config/koneksi.php';

echo "File koneksi terbaca.<br>";
echo isset($pdo) ? "Variabel \$pdo ADA.<br>" : "Variabel \$pdo TIDAK ADA.<br>";
echo "Jumlah transaksi_beli: " . $pdo->query("SELECT COUNT(*) FROM transaksi_beli")->fetchColumn();