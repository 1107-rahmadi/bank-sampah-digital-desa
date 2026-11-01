<?php
/**
 * Audit Log - Admin View
 * Compatible dengan PHP 7.3+
 *
 * Variabel dari controller: $logs (opsional $flash)
 * $logs: tiap item berisi created_at, user_nama, aksi, tabel, record_id, sebelum, sesudah
 * Filter dibaca dari $_GET: tabel, aksi, tanggal_from, tanggal_to
 *
 * Data sebelum/sesudah (JSON) ditampilkan seperti struk, bukan JSON mentah.
 */

if (!function_exists('icon')) {
    /**
     * Ikon SVG garis yang konsisten (tanpa emoji).
     */
    function icon(string $name, string $class = ''): string
    {
        $paths = [
            'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
            'leaf' => '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>',
            'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
            'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
            'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/>',
            'building' => '<path d="M4 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16"/><path d="M16 9h2a2 2 0 0 1 2 2v10M2 21h20M8 7h4M8 11h4M8 15h4"/>',
            'pin' => '<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
            'file' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Z"/><path d="M14 2v6h6M9 14h6M9 18h4"/>',
            'settings' => '<path d="M4 6h8M16 6h4M4 12h4M12 12h8M4 18h10M18 18h2"/><circle cx="14" cy="6" r="2"/><circle cx="10" cy="12" r="2"/><circle cx="16" cy="18" r="2"/>',
            'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/>',
            'arrow-left' => '<path d="M19 12H5M12 19l-7-7 7-7"/>',
            'plus' => '<path d="M12 5v14M5 12h14"/>',
            'edit' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
            'trash' => '<path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2m2 0v14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V6"/><path d="M10 11v6M14 11v6"/>',
            'search' => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/>',
            'check' => '<path d="M5 12.5l4.5 4.5L19 7"/>',
            'alert' => '<path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/>',
            'info' => '<circle cx="12" cy="12" r="9"/><line x1="12" y1="11" x2="12" y2="16"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
            'inbox' => '<path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.5 5.1L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.5-6.9A2 2 0 0 0 16.8 4H7.2a2 2 0 0 0-1.7 1.1Z"/>',
            'chevron-right' => '<path d="M9 6l6 6-6 6"/>',
        ];

        $body = isset($paths[$name]) ? $paths[$name] : $paths['info'];
        $cls = $class !== '' ? ' class="' . htmlspecialchars($class) . '"' : '';

        return '<svg' . $cls . ' viewBox="0 0 24 24" fill="none" stroke="currentColor"'
            . ' stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"'
            . ' aria-hidden="true" focusable="false">' . $body . '</svg>';
    }
}

/* ---------- Helper tampilan struk ---------- */

if (!function_exists('auditH')) {
    function auditH($v): string
    {
        return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('auditDecode')) {
    /**
     * Ubah JSON menjadi array. Return null jika kosong / bukan JSON array.
     */
    function auditDecode($raw)
    {
        if (is_array($raw)) {
            return $raw;
        }
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }
        $data = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            return $data;
        }
        return null;
    }
}

if (!function_exists('auditLabel')) {
    /** nama_kolom -> Nama Kolom */
    function auditLabel($key): string
    {
        $key = trim((string) $key);
        if ($key === 'id') {
            return 'ID';
        }
        $key = preg_replace('/_id$/', ' ID', $key);
        $key = str_replace('_', ' ', $key);
        return ucwords($key);
    }
}

if (!function_exists('auditValue')) {
    /** Nilai kolom -> teks yang mudah dibaca. */
    function auditValue($key, $v): string
    {
        if (preg_match('/pass|token|secret/i', (string) $key)) {
            return '••••••';
        }
        if ($v === null || $v === '') {
            return '-';
        }
        if (is_bool($v)) {
            return $v ? 'Ya' : 'Tidak';
        }
        if (is_array($v)) {
            $out = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return $out !== false ? $out : '-';
        }
        if (is_numeric($v) && preg_match('/harga|total|saldo|nominal|upah|rupiah/i', (string) $key)) {
            return 'Rp ' . number_format((float) $v, 0, ',', '.');
        }
        if (is_numeric($v) && preg_match('/berat|kg/i', (string) $key)) {
            return rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ',') . ' kg';
        }
        return (string) $v;
    }
}

if (!function_exists('auditLine')) {
    /** Satu baris struk: label ........ nilai */
    function auditLine($label, $value, $strong = false): string
    {
        return '<div class="s-line"><span class="s-k">' . auditH($label) . '</span>'
            . '<span class="s-lead" aria-hidden="true"></span>'
            . '<span class="s-v' . ($strong ? ' s-strong' : '') . '">' . auditH($value) . '</span></div>';
    }
}

if (!function_exists('auditStruk')) {
    /**
     * Susun struk perubahan.
     * INSERT -> daftar data baru, DELETE -> daftar data yang dihapus,
     * UPDATE -> hanya kolom yang berubah (sebelum -> sesudah).
     */
    function auditStruk(string $aksi, $sebelum, $sesudah, array $meta): string
    {
        $b = auditDecode($sebelum);
        $s = auditDecode($sesudah);

        $judul = [
            'INSERT' => 'DATA DITAMBAHKAN',
            'UPDATE' => 'DATA DIUBAH',
            'DELETE' => 'DATA DIHAPUS',
        ];

        $h = '<div class="struk">';
        $h .= '<div class="s-title">' . auditH(isset($judul[$aksi]) ? $judul[$aksi] : 'RINCIAN PERUBAHAN') . '</div>';
        $h .= '<div class="s-sep"></div>';
        foreach ($meta as $k => $v) {
            $h .= auditLine($k, $v);
        }
        $h .= '<div class="s-sep"></div>';

        if ($aksi === 'UPDATE' && $b !== null && $s !== null) {
            $keys = array_values(array_unique(array_merge(array_keys($b), array_keys($s))));
            $berubah = 0;
            $sama = 0;
            foreach ($keys as $k) {
                $vb = array_key_exists($k, $b) ? $b[$k] : null;
                $vs = array_key_exists($k, $s) ? $s[$k] : null;
                if ($vb === $vs || (string) json_encode($vb) === (string) json_encode($vs)) {
                    $sama++;
                    continue;
                }
                $berubah++;
                $h .= '<div class="s-change">'
                    . '<div class="s-field">' . auditH(auditLabel($k)) . '</div>'
                    . '<div class="s-from"><span>Sebelum</span><s>' . auditH(auditValue($k, $vb)) . '</s></div>'
                    . '<div class="s-to"><span>Sesudah</span><b>' . auditH(auditValue($k, $vs)) . '</b></div>'
                    . '</div>';
            }
            if ($berubah === 0) {
                $h .= '<div class="s-note">Tidak ada nilai yang berubah.</div>';
            }
            if ($sama > 0) {
                $h .= '<div class="s-sep"></div><div class="s-note">' . (int) $sama . ' data lain tidak berubah</div>';
            }
        } else {
            $data = ($aksi === 'DELETE') ? ($b !== null ? $b : $s) : ($s !== null ? $s : $b);
            if ($data !== null && count($data) > 0) {
                foreach ($data as $k => $v) {
                    $h .= auditLine(auditLabel($k), auditValue($k, $v));
                }
            } else {
                /* Bukan JSON valid: tampilkan teks apa adanya */
                $mentah = trim((string) ($sesudah !== '' && $sesudah !== null ? $sesudah : $sebelum));
                $h .= '<div class="s-note">' . ($mentah !== '' ? auditH($mentah) : 'Tidak ada rincian data.') . '</div>';
            }
        }

        $h .= '<div class="s-sep"></div><div class="s-foot">*** Catatan sistem ***</div>';
        $h .= '</div>';
        return $h;
    }
}

$e = function ($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};

$user = currentUser();
$userName = 'Administrator';
if (is_array($user)) {
    foreach (['nama', 'name', 'username'] as $k) {
        if (!empty($user[$k])) {
            $userName = (string) $user[$k];
            break;
        }
    }
}
$userInitial = function_exists('mb_substr')
    ? mb_strtoupper(mb_substr($userName, 0, 1, 'UTF-8'), 'UTF-8')
    : strtoupper(substr($userName, 0, 1));

if (!isset($flash)) {
    $flash = function_exists('getFlashMessage') ? getFlashMessage() : null;
}
$flashType = 'info';
if (!empty($flash['type']) && in_array($flash['type'], ['success', 'error', 'warning', 'info'], true)) {
    $flashType = $flash['type'];
}
$flashIcon = ['success' => 'check', 'error' => 'alert', 'warning' => 'alert', 'info' => 'info'][$flashType];

$logs = (isset($logs) && is_array($logs)) ? $logs : [];
$appName = defined('APP_NAME') ? APP_NAME : 'Bank Sampah';

$menu = [
    ['admin/dashboard',   'Dashboard',          'grid'],
    ['admin/users',       'Kelola Pengguna',    'users'],
    ['admin/nasabah',     'Kelola Nasabah',     'user'],
    ['admin/bank-sampah', 'Kelola Bank Sampah', 'building'],
    ['admin/desa',        'Kelola Desa',        'pin'],
    ['admin/audit-log',   'Audit Log',          'file'],
   // ['admin/pengaturan',  'Pengaturan',         'settings'],
];
$menuAktif = 'admin/audit-log';

/* Filter dari URL (nilai di-escape saat ditampilkan) */
$get = function ($key) {
    return (isset($_GET[$key]) && is_scalar($_GET[$key])) ? (string) $_GET[$key] : '';
};
$fTabel = $get('tabel');
$fAksi = $get('aksi');
$fFrom = $get('tanggal_from');
$fTo = $get('tanggal_to');
$adaFilter = ($fTabel !== '' || $fAksi !== '' || $fFrom !== '' || $fTo !== '');

$tabelOpsi = [
    'users' => 'Users',
    'nasabah' => 'Nasabah',
    'transaksi_beli' => 'Transaksi Beli',
    'transaksi_jual' => 'Transaksi Jual',
];
$aksiOpsi = [
    'INSERT' => 'Tambah (INSERT)',
    'UPDATE' => 'Ubah (UPDATE)',
    'DELETE' => 'Hapus (DELETE)',
];
/* label badge, kelas badge, kata kerja deskripsi */
$aksiMeta = [
    'INSERT' => ['CREATE', 'b-green', 'Menambahkan data'],
    'UPDATE' => ['UPDATE', 'b-sky', 'Mengubah data'],
    'DELETE' => ['DELETE', 'b-rose', 'Menghapus data'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1b5e20">
    <title>Audit Log - <?= $e($appName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        (function () {
            var r = document.documentElement;
            r.classList.add('no-anim');
            var s = null;
            try { s = localStorage.getItem('nav'); } catch (e) {}
            if (window.innerWidth > 1100 && s !== 'closed') { r.classList.add('nav-open'); }
        })();
    </script>
    <style>
        :root {
            --g-900:#0f3d14; --g-800:#1b5e20; --g-700:#2e7d32; --g-600:#43a047; --g-500:#66bb6a;
            --g-200:#c8e6c9; --g-100:#e8f5e9; --g-50:#f4faf4;
            --paper:#f7faf6; --ink:#17231a; --ink-2:#3b4a3e; --ink-3:#5b6a5e; --line:#e6ece5;
            --amber:#a85a00; --amber-bg:#fff1d6; --rose:#b3261e; --rose-bg:#fde8e6; --sky:#1a56c4; --sky-bg:#e2edff;
            --nav-w:280px;
        }
        * { box-sizing:border-box; }
        html { -webkit-text-size-adjust:100%; }
        body { margin:0; font-family:'Plus Jakarta Sans','Segoe UI',system-ui,sans-serif; font-size:16px; line-height:1.5; color:var(--ink); background:var(--paper); }
        a { color:inherit; }
        svg { display:block; }
        button, input, select { font-family:inherit; }
        [hidden] { display:none !important; }
        :focus-visible { outline:3px solid var(--g-600); outline-offset:2px; }
        .no-anim *, .no-anim *::before, .no-anim *::after { transition:none !important; }

        /* Sidebar */
        .sidebar { position:fixed; top:0; left:0; bottom:0; width:var(--nav-w); max-width:86vw; z-index:60; display:flex; flex-direction:column; padding:20px 14px; color:#fff;
            background:linear-gradient(170deg,var(--g-700) 0%,var(--g-800) 60%,var(--g-900) 100%); transform:translateX(-100%); transition:transform .25s ease; overflow-y:auto; }
        .nav-open .sidebar { transform:none; }
        .brand { display:flex; align-items:center; gap:12px; padding:4px 8px 20px; }
        .brand-mark { width:42px; height:42px; border-radius:14px; background:rgba(255,255,255,.16); display:grid; place-items:center; flex-shrink:0; }
        .brand-mark svg { width:22px; height:22px; }
        .brand strong { display:block; font-weight:800; line-height:1.2; }
        .brand small { opacity:.75; font-size:.85rem; }
        .sidebar nav { display:flex; flex-direction:column; gap:4px; flex:1; }
        .sidebar nav a { display:flex; align-items:center; gap:12px; min-height:50px; padding:0 14px; border-radius:14px; text-decoration:none; color:rgba(255,255,255,.95); font-weight:500; transition:background .15s; }
        .sidebar nav a svg { width:20px; height:20px; flex:none; }
        .sidebar nav a:hover { background:rgba(255,255,255,.1); color:#fff; }
        .sidebar nav a.active { background:rgba(255,255,255,.2); color:#fff; font-weight:700; }
        .sidebar nav a.logout { color:#ffd9d6; margin-top:auto; border-top:1px solid rgba(255,255,255,.14); border-radius:0 0 14px 14px; }
        .sidebar nav a:focus-visible { outline-color:#fff; }
        .sidebar nav a.logout:hover { background:rgba(230,90,80,.22); color:#ffd9d6; }
        .scrim { position:fixed; top:0; right:0; bottom:0; left:0; background:rgba(15,61,20,.45); opacity:0; visibility:hidden; transition:opacity .25s; z-index:50; }

        /* Layout */
        .main { transition:margin-left .25s ease; min-height:100vh; }
        @media (min-width:1101px) { .nav-open .main { margin-left:var(--nav-w); } }
        @media (max-width:1100px) { .nav-open .scrim { opacity:1; visibility:visible; } .nav-open body { overflow:hidden; } }
        .topbar { position:sticky; top:0; z-index:40; height:68px; display:flex; align-items:center; gap:14px; padding:0 20px; background:rgba(247,250,246,.92); -webkit-backdrop-filter:blur(8px); backdrop-filter:blur(8px); border-bottom:1px solid var(--line); }
        .burger { width:46px; height:46px; border-radius:14px; border:1px solid var(--line); background:#fff; color:var(--g-800); display:grid; place-items:center; cursor:pointer; }
        .burger:hover { background:var(--g-50); }
        .burger svg { width:22px; height:22px; }
        .top-title { font-weight:700; color:var(--ink-2); }
        .avatar { margin-left:auto; width:42px; height:42px; border-radius:50%; display:grid; place-items:center; color:#fff; font-weight:800; background:linear-gradient(140deg,var(--g-500),var(--g-700)); }
        .content { padding:24px 20px 64px; }
        .wrap { max-width:880px; margin:0 auto; }

        .back { display:inline-flex; align-items:center; gap:8px; min-height:44px; margin-bottom:8px; color:var(--g-700); font-weight:600; text-decoration:none; }
        .back:hover { color:var(--g-800); }
        .back svg { width:18px; height:18px; }
        .page-head { margin-bottom:22px; }
        h1 { margin:0; font-size:1.75rem; line-height:1.25; font-weight:800; letter-spacing:-.01em; }
        .lead { margin:4px 0 0; color:var(--ink-3); }

        .btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; min-height:52px; padding:0 22px; border-radius:16px; border:1.5px solid transparent; font:inherit; font-weight:700; text-decoration:none; cursor:pointer; }
        .btn svg { width:20px; height:20px; }
        .btn-primary { color:#fff; background:linear-gradient(135deg,var(--g-600),var(--g-700)); box-shadow:0 8px 20px rgba(27,94,32,.22); }
        .btn-primary:hover { background:linear-gradient(135deg,var(--g-700),var(--g-800)); }
        .btn-ghost { color:var(--g-800); background:#fff; border-color:var(--g-200); }
        .btn-ghost:hover { background:var(--g-100); }

        .notice { display:flex; align-items:flex-start; gap:12px; padding:14px 16px; border-radius:16px; margin-bottom:18px; font-weight:500; }
        .notice svg { flex-shrink:0; margin-top:2px; width:20px; height:20px; }
        .notice-success { background:var(--g-50); color:#14532d; border:1px solid var(--g-200); }
        .notice-error { background:var(--rose-bg); color:var(--rose); border:1px solid #f8c9c5; }
        .notice-warning { background:var(--amber-bg); color:var(--amber); border:1px solid #f6d9a0; }
        .notice-info { background:var(--sky-bg); color:var(--sky); border:1px solid #c4d8fa; }

        .badge { display:inline-flex; align-items:center; padding:3px 12px; border-radius:999px; font-size:.78rem; font-weight:700; letter-spacing:.02em; white-space:nowrap; }
        .b-green { background:var(--g-100); color:var(--g-800); }
        .b-rose { background:var(--rose-bg); color:var(--rose); }
        .b-sky { background:var(--sky-bg); color:var(--sky); }

        .ico { width:46px; height:46px; border-radius:50%; flex:none; background:var(--g-100); color:var(--g-700); display:grid; place-items:center; }
        .ico svg { width:22px; height:22px; }

        /* Filter */
        .panel { background:#fff; border:1px solid var(--line); border-radius:20px; padding:18px; margin-bottom:22px; }
        .filter { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; align-items:end; }
        .label { display:block; margin-bottom:6px; font-size:.9rem; font-weight:600; color:var(--ink); }
        .input { width:100%; height:52px; padding:0 14px; font-size:1rem; color:var(--ink); background:#fff; border:1.5px solid var(--line); border-radius:14px; }
        .input:hover { border-color:var(--g-200); }
        .input:focus { outline:none; border-color:var(--g-600); box-shadow:0 0 0 4px rgba(67,160,71,.16); }
        select.input { -webkit-appearance:none; appearance:none; padding-right:42px; cursor:pointer;
            background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%235b6a5e' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
            background-repeat:no-repeat; background-position:right 14px center; background-size:18px; }
        .filter-actions { grid-column:1 / -1; display:flex; gap:12px; flex-wrap:wrap; }

        /* Tabel log (gaya kartu seperti contoh) */
        .sec-head { display:flex; align-items:baseline; justify-content:space-between; gap:12px; margin:0 0 10px; }
        .sec-title { margin:0; font-size:1.125rem; font-weight:700; letter-spacing:-.01em; }
        .sec-count { font-size:.9rem; color:var(--ink-3); }

        .log-card { background:#fff; border:1px solid var(--line); border-radius:20px; overflow:hidden; }
        .log-cols, .log { display:grid; grid-template-columns:96px minmax(0,1fr) minmax(0,1.5fr); gap:0 14px; align-items:start; padding:14px 18px; }
        .log-cols { padding-top:16px; padding-bottom:12px; font-weight:800; border-bottom:1.5px solid var(--line); }
        .logs { list-style:none; margin:0; padding:0; }
        .log { border-bottom:1px solid var(--line); }
        .log:last-child { border-bottom:0; }
        .c-time { font-size:.9rem; color:var(--ink-2); line-height:1.45; }
        .c-time .d { font-weight:600; color:var(--ink); display:block; }
        .c-user strong { display:block; font-weight:800; overflow-wrap:anywhere; }
        .c-user small { display:block; font-size:.85rem; color:var(--ink-3); overflow-wrap:anywhere; }
        .c-act .badge { margin-bottom:4px; }
        .c-act p { margin:0; color:var(--ink-2); overflow-wrap:anywhere; }

        /* Rincian (struk) */
        .log-detail { grid-column:1 / -1; margin-top:4px; }
        .dt summary { display:inline-flex; align-items:center; gap:6px; min-height:44px; cursor:pointer; font-weight:700; color:var(--g-700); list-style:none; }
        .dt summary::-webkit-details-marker { display:none; }
        .dt summary svg { width:16px; height:16px; transition:transform .15s; }
        .dt[open] summary svg { transform:rotate(90deg); }

        .struk { max-width:420px; margin:6px 0 8px; padding:18px 18px 16px; background:#fffef9; color:#2a2a24;
            font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace; font-size:.84rem; line-height:1.55;
            border:1px solid #e8e4d4; border-radius:6px; box-shadow:0 2px 10px rgba(23,35,26,.07); position:relative; }
        .struk::before, .struk::after { content:""; position:absolute; left:0; right:0; height:8px;
            background:linear-gradient(-45deg,transparent 6px,#fffef9 0) 0 0/12px 8px repeat-x, linear-gradient(45deg,transparent 6px,#fffef9 0) 0 0/12px 8px repeat-x; }
        .s-title { text-align:center; font-weight:800; letter-spacing:.08em; }
        .s-sep { border-top:2px dashed #bdb8a2; margin:10px 0; }
        .s-line { display:flex; align-items:flex-end; gap:6px; padding:1px 0; }
        .s-k { flex:none; max-width:46%; overflow-wrap:anywhere; }
        .s-lead { flex:1; min-width:10px; border-bottom:2px dotted #c9c4ae; margin-bottom:5px; }
        .s-v { flex:none; max-width:54%; text-align:right; font-weight:700; overflow-wrap:anywhere; }
        .s-change { padding:6px 0 8px; border-bottom:1px dotted #d6d1bb; }
        .s-change:last-of-type { border-bottom:0; }
        .s-field { font-weight:800; margin-bottom:2px; }
        .s-from, .s-to { display:flex; gap:8px; justify-content:space-between; }
        .s-from span, .s-to span { flex:none; color:#6d6a58; }
        .s-from s { color:var(--rose); text-decoration:line-through; text-align:right; overflow-wrap:anywhere; }
        .s-to b { color:var(--g-800); text-align:right; overflow-wrap:anywhere; }
        .s-note, .s-foot { text-align:center; color:#6d6a58; overflow-wrap:anywhere; }
        .s-foot { font-size:.78rem; letter-spacing:.04em; }

        .empty { text-align:center; padding:40px 24px; background:#fff; border:1px solid var(--line); border-radius:20px; }
        .empty .ico { margin:0 auto 14px; width:64px; height:64px; }
        .empty .ico svg { width:28px; height:28px; }
        .empty h3 { margin:0 0 4px; font-size:1.0625rem; }
        .empty p { margin:0 auto 18px; max-width:40ch; color:var(--ink-3); }

        @media (max-width:900px) { .filter { grid-template-columns:repeat(2,minmax(0,1fr)); } }
        @media (max-width:720px) {
            .content { padding:18px 16px 56px; }
            .topbar { padding:0 16px; }
            h1 { font-size:1.5rem; }
            .panel { padding:16px; }
            .filter { grid-template-columns:minmax(0,1fr); }
            .filter-actions .btn { width:100%; }
            .log-cols, .log { grid-template-columns:78px minmax(0,1fr) minmax(0,1.35fr); gap:0 10px; padding:14px 12px; }
            .c-time { font-size:.8rem; }
            .struk { max-width:none; font-size:.8rem; }
        }
        @media (prefers-reduced-motion:reduce) { *, *::before, *::after { transition:none !important; animation:none !important; } }
    </style>
</head>
<body>
    <aside class="sidebar" id="sidebar" aria-label="Menu utama">
        <div class="brand">
            <span class="brand-mark"><?= icon('leaf') ?></span>
            <div><strong><?= $e($appName) ?></strong><small>Administrator</small></div>
        </div>
        <nav>
            <?php foreach ($menu as $m): $isActive = ($m[0] === $menuAktif); ?>
                <a href="<?= base_url($m[0]) ?>"<?= $isActive ? ' class="active" aria-current="page"' : '' ?>><?= icon($m[2]) ?><span><?= $e($m[1]) ?></span></a>
            <?php endforeach; ?>
            <a href="<?= base_url('logout') ?>" class="logout"><?= icon('logout') ?><span>Keluar</span></a>
        </nav>
    </aside>
    <div class="scrim" id="scrim"></div>

    <div class="main">
        <header class="topbar">
            <button type="button" class="burger" id="burger" aria-controls="sidebar" aria-expanded="false" aria-label="Buka atau tutup menu"><?= icon('menu') ?></button>
            <span class="top-title">Audit Log</span>
            <span class="avatar" title="<?= $e($userName) ?>" aria-hidden="true"><?= $e($userInitial) ?></span>
        </header>

        <main class="content">
            <div class="wrap">
                <a class="back" href="<?= base_url('admin/dashboard') ?>"><?= icon('arrow-left') ?>Dashboard</a>

                <?php if (!empty($flash) && !empty($flash['message'])): ?>
                    <div class="notice notice-<?= $flashType ?>" role="status"><?= icon($flashIcon) ?><span><?= $e($flash['message']) ?></span></div>
                <?php endif; ?>

                <div class="page-head">
                    <h1>Audit Log</h1>
                    <p class="lead">Riwayat perubahan data di sistem</p>
                </div>

                <form method="GET" class="panel" aria-label="Filter audit log">
                    <div class="filter">
                        <div>
                            <label for="tabel" class="label">Tabel</label>
                            <select name="tabel" id="tabel" class="input">
                                <option value="">Semua Tabel</option>
                                <?php foreach ($tabelOpsi as $val => $lbl): ?>
                                    <option value="<?= $e($val) ?>"<?= $fTabel === $val ? ' selected' : '' ?>><?= $e($lbl) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="aksi" class="label">Aksi</label>
                            <select name="aksi" id="aksi" class="input">
                                <option value="">Semua Aksi</option>
                                <?php foreach ($aksiOpsi as $val => $lbl): ?>
                                    <option value="<?= $e($val) ?>"<?= $fAksi === $val ? ' selected' : '' ?>><?= $e($lbl) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="tanggal_from" class="label">Dari tanggal</label>
                            <input type="date" name="tanggal_from" id="tanggal_from" class="input" value="<?= $e($fFrom) ?>">
                        </div>
                        <div>
                            <label for="tanggal_to" class="label">Sampai tanggal</label>
                            <input type="date" name="tanggal_to" id="tanggal_to" class="input" value="<?= $e($fTo) ?>">
                        </div>
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary"><?= icon('search') ?>Tampilkan</button>
                            <?php if ($adaFilter): ?>
                                <a class="btn btn-ghost" href="<?= base_url('admin/audit-log') ?>">Hapus filter</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>

                <section aria-labelledby="log-title">
                    <div class="sec-head">
                        <h2 class="sec-title" id="log-title">Catatan perubahan</h2>
                        <?php if (!empty($logs)): ?><span class="sec-count"><?= number_format(count($logs), 0, ',', '.') ?> catatan</span><?php endif; ?>
                    </div>

                    <?php if (empty($logs)): ?>
                        <div class="empty">
                            <div class="ico"><?= icon('inbox') ?></div>
                            <h3>Tidak ada data log</h3>
                            <p><?= $adaFilter ? 'Tidak ada catatan yang cocok dengan filter ini.' : 'Perubahan data akan tercatat di sini.' ?></p>
                            <?php if ($adaFilter): ?><a class="btn btn-ghost" href="<?= base_url('admin/audit-log') ?>">Hapus filter</a><?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="log-card">
                            <div class="log-cols" aria-hidden="true">
                                <span>Waktu</span><span>Pengguna</span><span>Aktivitas</span>
                            </div>
                            <ul class="logs">
                                <?php foreach ($logs as $log):
                                    $ts = isset($log['created_at']) ? strtotime((string) $log['created_at']) : false;
                                    $tglTxt = $ts ? date('d/m/y', $ts) : '-';
                                    $jamTxt = $ts ? date('H:i:s', $ts) : '';
                                    $tglLengkap = $ts ? date('d/m/Y H:i:s', $ts) : '-';

                                    $aksi = strtoupper((string) (isset($log['aksi']) ? $log['aksi'] : ''));
                                    $am = isset($aksiMeta[$aksi]) ? $aksiMeta[$aksi] : [($aksi !== '' ? $aksi : '-'), 'b-sky', 'Mengubah data'];

                                    $pelaku = (isset($log['user_nama']) && $log['user_nama'] !== '' && $log['user_nama'] !== null) ? $log['user_nama'] : 'System';
                                    $recId = (isset($log['record_id']) && $log['record_id'] !== '' && $log['record_id'] !== null) ? $log['record_id'] : '-';
                                    $tabelRaw = isset($log['tabel']) ? (string) $log['tabel'] : '';
                                    $tabelTxt = $tabelRaw !== '' ? (isset($tabelOpsi[$tabelRaw]) ? $tabelOpsi[$tabelRaw] : ucwords(str_replace('_', ' ', $tabelRaw))) : '-';
                                    $sebelum = isset($log['sebelum']) ? $log['sebelum'] : '';
                                    $sesudah = isset($log['sesudah']) ? $log['sesudah'] : '';

                                    $struk = auditStruk($aksi, $sebelum, $sesudah, [
                                        'Waktu' => $tglLengkap,
                                        'Oleh' => (string) $pelaku,
                                        'Tabel' => $tabelTxt,
                                        'ID Data' => (string) $recId,
                                    ]);
                                ?>
                                    <li class="log">
                                        <div class="c-time"><span class="d"><?= $e($tglTxt) ?></span><?= $e($jamTxt) ?></div>
                                        <div class="c-user">
                                            <strong><?= $e($pelaku) ?></strong>
                                            <small><?= $e($tabelTxt) ?></small>
                                        </div>
                                        <div class="c-act">
                                            <span class="badge <?= $e($am[1]) ?>"><?= $e($am[0]) ?></span>
                                            <p><?= $e($am[2]) ?> <?= $e(strtolower($tabelTxt)) ?> (ID <?= $e($recId) ?>)</p>
                                        </div>

                                        <?php if ($sebelum || $sesudah): ?>
                                            <div class="log-detail">
                                                <details class="dt">
                                                    <summary><?= icon('chevron-right') ?>Lihat rincian</summary>
                                                    <?= $struk /* sudah di-escape di dalam helper */ ?>
                                                </details>
                                            </div>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>

    <script>
    (function () {
        var root = document.documentElement;
        var burger = document.getElementById('burger');
        var scrim = document.getElementById('scrim');
        var mq = window.matchMedia('(min-width:1101px)');

        function setNav(open, save) {
            root.classList.toggle('nav-open', open);
            burger.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (save && mq.matches) {
                try { localStorage.setItem('nav', open ? 'open' : 'closed'); } catch (e) {}
            }
        }
        function initial() {
            var s = null;
            try { s = localStorage.getItem('nav'); } catch (e) {}
            setNav(mq.matches && s !== 'closed', false);
        }
        initial();
        window.requestAnimationFrame(function () { root.classList.remove('no-anim'); });

        burger.addEventListener('click', function () { setNav(!root.classList.contains('nav-open'), true); });
        scrim.addEventListener('click', function () { setNav(false, false); });
        document.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape' && !mq.matches && root.classList.contains('nav-open')) {
                setNav(false, false);
                burger.focus();
            }
        });
        if (mq.addEventListener) { mq.addEventListener('change', initial); } else if (mq.addListener) { mq.addListener(initial); }
    })();
    </script>
</body>
</html>