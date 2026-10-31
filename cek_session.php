<?php
// cek_session.php - alat sementara. HAPUS setelah dipakai.
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('Hanya bisa dibuka dari komputer ini.');
}
echo 'gc_maxlifetime (detik): ' . ini_get('session.gc_maxlifetime') . '<br>';
echo 'cookie_lifetime (detik, 0 = sampai browser ditutup): ' . ini_get('session.cookie_lifetime') . '<br>';