<?php
/**pencairan/detail.php
 * Detail Pengajuan Pencairan (Pengelola)
 * Compatible dengan PHP 7.3+
 *
 * Variabel dari controller: $pengajuan
 * Aksi (POST, nama field dipertahankan):
 *   pengelola/pencairan/setuju   (id)
 *   pengelola/pencairan/tolak    (id, alasan)
 *   pengelola/pencairan/cairkan  (id)
 */

if (!function_exists('formatRupiah')) {
    function formatRupiah($number): string
    {
        return 'Rp ' . number_format((float) $number, 0, ',', '.');
    }
}

if (!function_exists('formatTanggal')) {
    /**
     * "2026-10-24 14:30:00" -> "24 Okt 2026 14:30". Kosong/tidak valid -> "-".
     */
    function formatTanggal($date): string
    {
        $t = $date ? strtotime((string) $date) : false;
        if (!$t) {
            return '-';
        }
        $bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        return date('d', $t) . ' ' . $bulan[(int) date('n', $t) - 1] . ' ' . date('Y H:i', $t);
    }
}

if (!function_exists('icon')) {
    /**
     * Ikon SVG garis yang konsisten (tanpa emoji).
     */
    function icon(string $name, string $class = ''): string
    {
        $paths = [
            'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
            'close' => '<path d="M6 6l12 12M18 6L6 18"/>',
            'leaf' => '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>',
            'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
            'cart' => '<circle cx="9" cy="20" r="1.25"/><circle cx="18" cy="20" r="1.25"/><path d="M2 3h3l2.6 12.4a1 1 0 0 0 .8.8h8.8a1 1 0 0 0 1-.76L20 8H6"/>',
            'upload' => '<path d="M12 16V4M7 9l5-5 5 5"/><path d="M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/>',
            'box' => '<path d="M21 8l-9-5-9 5v8l9 5 9-5V8Z"/><path d="M3.3 7.5L12 12l8.7-4.5M12 12v9"/>',
            'tag' => '<path d="M20.6 13.4l-7.2 7.2a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8Z"/><path d="M7.5 7.5h.01"/>',
            'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
            'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/>',
            'truck' => '<path d="M1 6h13v11H1zM14 10h4l3 3v4h-7"/><circle cx="6" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',
            'chart' => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
            'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/>',
            'arrow-left' => '<path d="M19 12H5M12 19l-7-7 7-7"/>',
            'chevron-right' => '<path d="M9 6l6 6-6 6"/>',
            'chevron-left' => '<path d="M15 6l-6 6 6 6"/>',
            'download' => '<path d="M12 4v12M7 11l5 5 5-5"/><path d="M4 20h16"/>',
            'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
            'wallet' => '<path d="M19 7V6a2 2 0 0 0-2-2H5a2 2 0 0 0 0 4h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6"/><path d="M16 12h.01"/>',
            'cash' => '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/>',
            'bank' => '<path d="M3 10l9-6 9 6H3Z"/><path d="M6 10v8M10 10v8M14 10v8M18 10v8M3 21h18"/>',
            'card' => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>',
            'phone' => '<rect x="6" y="2" width="12" height="20" rx="2.5"/><path d="M11 18h2"/>',
            'hash' => '<path d="M4 9h16M4 15h16M10 3L8 21M16 3l-2 18"/>',
            'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
            'send' => '<path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7Z"/>',
            'file' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Z"/><path d="M14 2v6h6M9 14h6M9 18h4"/>',
            'check' => '<path d="M5 12.5l4.5 4.5L19 7"/>',
            'alert' => '<path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/>',
            'info' => '<circle cx="12" cy="12" r="9"/><line x1="12" y1="11" x2="12" y2="16"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
        ];

        $body = isset($paths[$name]) ? $paths[$name] : $paths['info'];
        $cls = $class !== '' ? ' class="' . htmlspecialchars($class) . '"' : '';

        return '<svg' . $cls . ' viewBox="0 0 24 24" fill="none" stroke="currentColor"'
            . ' stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"'
            . ' aria-hidden="true" focusable="false">' . $body . '</svg>';
    }
}

$e = function ($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};

$user = currentUser();
$userName = 'Pengelola';
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

$flash = getFlashMessage();
$flashType = 'info';
if (!empty($flash['type']) && in_array($flash['type'], ['success', 'error', 'warning', 'info'], true)) {
    $flashType = $flash['type'];
}
$flashIcon = ['success' => 'check', 'error' => 'alert', 'warning' => 'alert', 'info' => 'info'][$flashType];

// [label, kelas badge]
$statusMeta = [
    'DIAJUKAN'   => ['Ditunggu', 'b-amber'],
    'DISETUJUI'  => ['Setuju', 'b-green'],
    'DITOLAK'    => ['Ditolak', 'b-rose'],
    'DICAIRKAN'  => ['Dicairkan', 'b-sky'],
    'DIBATALKAN' => ['Dibatalkan', 'b-grey'],
];

$menu = [
    ['pengelola/dashboard',      'Dashboard',      'grid'],
    ['pengelola/transaksi-beli', 'Transaksi Beli', 'cart'],
    ['pengelola/transaksi-jual', 'Transaksi Jual', 'upload'],
    ['pengelola/kategori',       'Kategori',       'box'],
    ['pengelola/harga',          'Harga',          'tag'],
    ['pengelola/nasabah',        'Nasabah',        'users'],
    ['pengelola/pencairan',      'Pencairan',      'wallet'],
    ['pengelola/pengepul',       'Pengepul',       'truck'],
    ['pengelola/laporan',        'Laporan',        'chart'],
];
$menuAktif = 'pengelola/pencairan';

$pengajuan = (isset($pengajuan) && is_array($pengajuan)) ? $pengajuan : [];
$pv = function ($key, $default = '') use ($pengajuan) {
    return (isset($pengajuan[$key]) && $pengajuan[$key] !== null) ? $pengajuan[$key] : $default;
};

$status = (string) $pv('status');
$meta = isset($statusMeta[$status]) ? $statusMeta[$status] : [$status !== '' ? $status : '-', 'b-grey'];
$noPengajuan = (string) $pv('no_pengajuan', '-');
$idPengajuan = (string) $pv('id');
$tunai = ($pv('metode') === 'TUNAI');
$sudahDiproses = !empty($pengajuan['diproses_at']);
$sudahDicairkan = !empty($pengajuan['dicairkan_at']);
$alasanTolak = trim((string) $pv('alasan_tolak'));
$listUrl = base_url('pengelola/pencairan');

// Baris data nasabah: [ikon, label, nilai]
$rincian = [
    ['user', 'Nasabah', $pv('nama_nasabah', '-')],
    ['phone', 'No. HP', $pv('hp_nasabah', '-')],
    ['leaf', 'Bank Sampah', $pv('nama_bank_sampah', '-')],
    ['card', 'No. Rekening', $pv('no_rekening', '-')],
    [$tunai ? 'cash' : 'bank', 'Metode pencairan', $tunai ? 'Tunai' : 'Transfer'],
];
if ($pv('metode') === 'TRANSFER' && trim((string) $pv('tujuan_transfer')) !== '') {
    $rincian[] = ['send', 'Tujuan transfer', $pv('tujuan_transfer')];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1b5e20">
    <title>Detail Pengajuan <?= $e($noPengajuan) ?> - <?= APP_NAME ?></title>
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
        .wrap { max-width:760px; margin:0 auto; }

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
        .btn-danger { color:#fff; background:var(--rose); }
        .btn-danger:hover { background:#8f1d17; }
        .btn-danger-ghost { color:var(--rose); background:#fff; border-color:#f1b7b2; }
        .btn-danger-ghost:hover { background:var(--rose-bg); }
        .btn-block { width:100%; }

        /* Notice */
        .notice { display:flex; align-items:flex-start; gap:12px; padding:14px 16px; border-radius:16px; margin-bottom:18px; font-weight:500; }
        .notice svg { flex-shrink:0; margin-top:2px; width:20px; height:20px; }
        .notice-success { background:var(--g-50); color:#14532d; border:1px solid var(--g-200); }
        .notice-error { background:var(--rose-bg); color:var(--rose); border:1px solid #f8c9c5; }
        .notice-warning { background:var(--amber-bg); color:var(--amber); border:1px solid #f6d9a0; }
        .notice-info { background:var(--sky-bg); color:var(--sky); border:1px solid #c4d8fa; }

        /* Kartu ringkasan */
        .wallet { position:relative; overflow:hidden; border-radius:24px; padding:24px; margin-bottom:22px; color:#fff; background:linear-gradient(135deg,#43a047 0%,#2e7d32 45%,#14501a 100%); box-shadow:0 14px 32px rgba(27,94,32,.22); }
        .wallet::before, .wallet::after { content:""; position:absolute; border-radius:50%; background:rgba(255,255,255,.07); pointer-events:none; }
        .wallet::before { width:220px; height:220px; right:-70px; top:-90px; }
        .wallet::after { width:140px; height:140px; right:60px; bottom:-85px; }
        .wallet > * { position:relative; z-index:1; }
        .wallet-top { display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; margin-bottom:14px; }
        .wallet-label { margin:0; font-size:1rem; font-weight:600; opacity:.95; }
        .wallet-value { margin:2px 0 0; font-size:2.25rem; font-weight:800; line-height:1.15; letter-spacing:-.03em; overflow-wrap:anywhere; }
        .chips { display:flex; flex-wrap:wrap; gap:10px; margin-top:16px; }
        .chip { display:inline-flex; align-items:center; gap:8px; min-height:34px; padding:4px 14px; border-radius:999px; background:rgba(255,255,255,.14); border:1px solid rgba(255,255,255,.18); font-size:.9rem; font-weight:600; color:#fff; text-decoration:none; }
        .chip svg { width:16px; height:16px; }
        a.chip:hover { background:rgba(255,255,255,.24); }
        a.chip:focus-visible { outline-color:#fff; }

        /* Badge status */
        .badge { display:inline-flex; align-items:center; padding:3px 11px; border-radius:999px; font-size:.78rem; font-weight:700; white-space:nowrap; }
        .b-amber { background:var(--amber-bg); color:var(--amber); }
        .b-green { background:var(--g-100); color:var(--g-800); }
        .b-rose { background:var(--rose-bg); color:var(--rose); }
        .b-sky { background:var(--sky-bg); color:var(--sky); }
        .b-grey { background:#eef1ee; color:var(--ink-3); }

        .ico { width:46px; height:46px; border-radius:50%; flex:none; background:var(--g-100); color:var(--g-700); display:grid; place-items:center; }
        .ico svg { width:22px; height:22px; }
        .ico.warn { background:var(--amber-bg); color:var(--amber); }
        .ico.sky { background:var(--sky-bg); color:var(--sky); }

        .card { background:#fff; border:1px solid var(--line); border-radius:20px; padding:20px; margin-bottom:22px; }
        .card-title { display:flex; align-items:center; gap:14px; margin:0 0 16px; font-size:1.125rem; font-weight:700; letter-spacing:-.01em; }
        .card-title .ico { width:42px; height:42px; }

        .kv { display:flex; flex-direction:column; }
        .kv-row { display:flex; align-items:center; gap:16px; padding:14px 0; border-top:1px solid var(--line); }
        .kv-row:first-child { border-top:0; padding-top:0; }
        .kv-row:last-child { padding-bottom:0; }
        .kv-text { min-width:0; }
        .kv-label { display:block; font-size:.85rem; font-weight:600; color:var(--ink-3); }
        .kv-val { display:block; font-weight:700; color:var(--ink); overflow-wrap:anywhere; }

        .tl { list-style:none; margin:0; padding:0; }
        .tl li { position:relative; display:grid; grid-template-columns:34px minmax(0,1fr); gap:14px; padding-bottom:22px; }
        .tl li:last-child { padding-bottom:0; }
        .tl li:not(:last-child)::before { content:""; position:absolute; left:16px; top:34px; bottom:0; width:2px; background:var(--line); }
        .dot { width:34px; height:34px; border-radius:50%; background:#eef1ee; color:var(--ink-3); display:grid; place-items:center; }
        .dot svg { width:18px; height:18px; }
        .tl li.on .dot { background:var(--g-100); color:var(--g-700); }
        .tl li.bad .dot { background:var(--rose-bg); color:var(--rose); }
        .tl-title { font-weight:700; color:var(--ink); }
        .tl-date { font-size:.9rem; color:var(--ink-3); }
        .tl-desc { margin-top:4px; font-size:.95rem; color:var(--ink-2); }
        .tl-desc.bad { color:var(--rose); }
        .tl-desc.good { color:var(--g-700); }

        .aksi-note { margin:0 0 16px; color:var(--ink-2); }
        .actions { display:flex; flex-wrap:wrap; gap:12px; }
        .actions form { margin:0; }
        .tolak-box { margin-top:20px; padding:20px; border-radius:20px; background:#fffafa; border:1px solid #f8c9c5; }
        .tolak-box[hidden] { display:none; }
        .field label { display:block; margin-bottom:6px; font-size:.9375rem; font-weight:600; color:var(--ink-2); }
        .field .req { color:var(--rose); }
        .field textarea { width:100%; min-height:110px; padding:14px 16px; border:1.5px solid var(--line); border-radius:16px; background:#fff; font:inherit; color:var(--ink); resize:vertical; }
        .field textarea:focus { outline:none; border-color:var(--g-600); box-shadow:0 0 0 4px var(--g-100); }
        .tolak-box .actions { margin-top:16px; }
        .state { display:flex; align-items:flex-start; gap:14px; }
        .state p { margin:0; }
        .state p + p { margin-top:6px; }
        .state strong { color:var(--ink); }

        @media (max-width:720px) {
            h1 { font-size:1.5rem; }
            .content { padding:18px 16px 56px; }
            .topbar { padding:0 16px; }
            .wallet-value { font-size:2rem; }
            .actions .btn, .actions form { width:100%; }
            .actions form .btn { width:100%; }
        }
        @media (prefers-reduced-motion:reduce) { *, *::before, *::after { transition:none !important; animation:none !important; } }
    </style>
</head>
<body>
    <?php include VIEW_PATH . '/pengelola/partials/sidebar.php'; ?>
    <div class="scrim" id="scrim"></div>

    <div class="main">
        <header class="topbar">
            <button type="button" class="burger" id="burger" aria-controls="sidebar" aria-expanded="false" aria-label="Buka atau tutup menu"><?= icon('menu') ?></button>
            <span class="top-title">Detail pengajuan</span>
            <span class="avatar" title="<?= $e($userName) ?>" aria-hidden="true"><?= $e($userInitial) ?></span>
        </header>

        <main class="content">
            <div class="wrap">
                <?php if (!empty($flash) && !empty($flash['message'])): ?>
                    <div class="notice notice-<?= $flashType ?>" role="status"><?= icon($flashIcon) ?><span><?= $e($flash['message']) ?></span></div>
                <?php endif; ?>

                <a class="back" href="<?= $e($listUrl) ?>"><?= icon('arrow-left') ?>Pengajuan Pencairan</a>

                <div class="page-head">
                    <h1>Detail Pengajuan</h1>
                    <p class="lead">Rincian pengajuan pencairan saldo nasabah.</p>
                </div>

                <section class="wallet" aria-label="Ringkasan pengajuan">
                    <div class="wallet-top">
                        <span class="chip"><?= icon('hash') ?><?= $e($noPengajuan) ?></span>
                        <span class="badge <?= $e($meta[1]) ?>"><?= $e($meta[0]) ?></span>
                    </div>
                    <p class="wallet-label">Jumlah pencairan</p>
                    <p class="wallet-value"><?= $e(formatRupiah($pv('jumlah', 0))) ?></p>
                    <div class="chips">
                        <span class="chip"><?= icon($tunai ? 'cash' : 'bank') ?><?= $tunai ? 'Tunai' : 'Transfer' ?></span>
                        <span class="chip"><?= icon('calendar') ?>Diajukan <?= $e(formatTanggal($pv('diajukan_at'))) ?></span>
                    </div>
                </section>

                <section class="card" aria-labelledby="rincian-title">
                    <h2 class="card-title" id="rincian-title"><span class="ico"><?= icon('user') ?></span>Data nasabah</h2>
                    <div class="kv">
                        <?php foreach ($rincian as $r): ?>
                            <div class="kv-row">
                                <span class="ico"><?= icon($r[0]) ?></span>
                                <span class="kv-text">
                                    <span class="kv-label"><?= $e($r[1]) ?></span>
                                    <span class="kv-val"><?= $e($r[2]) ?></span>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="card" aria-labelledby="riwayat-title">
                    <h2 class="card-title" id="riwayat-title"><span class="ico"><?= icon('clock') ?></span>Riwayat proses</h2>
                    <ol class="tl">
                        <li class="on">
                            <span class="dot"><?= icon('send') ?></span>
                            <div>
                                <div class="tl-title">Pengajuan dibuat</div>
                                <div class="tl-date"><?= $e(formatTanggal($pv('diajukan_at'))) ?></div>
                                <div class="tl-desc">Nasabah mengajukan pencairan.</div>
                            </div>
                        </li>

                        <?php if ($sudahDiproses): ?>
                            <li class="<?= $status === 'DITOLAK' ? 'bad' : ($status !== 'DIAJUKAN' ? 'on' : '') ?>">
                                <span class="dot"><?= icon($status === 'DITOLAK' ? 'close' : 'check') ?></span>
                                <div>
                                    <div class="tl-title">
                                        <?php if ($status === 'DITOLAK'): ?>
                                            Pengajuan ditolak
                                        <?php elseif ($status === 'DISETUJUI' || $status === 'DICAIRKAN'): ?>
                                            Pengajuan disetujui
                                        <?php else: ?>
                                            Sedang diproses
                                        <?php endif; ?>
                                    </div>
                                    <div class="tl-date"><?= $e(formatTanggal($pv('diproses_at'))) ?></div>
                                    <?php if ($pv('diproses_oleh_nama') !== ''): ?>
                                        <div class="tl-desc">Oleh <?= $e($pv('diproses_oleh_nama')) ?></div>
                                    <?php endif; ?>
                                    <?php if ($alasanTolak !== ''): ?>
                                        <div class="tl-desc bad">Alasan: <?= $e($alasanTolak) ?></div>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endif; ?>

                        <?php if ($sudahDicairkan): ?>
                            <li class="on">
                                <span class="dot"><?= icon('wallet') ?></span>
                                <div>
                                    <div class="tl-title">Saldo dicairkan</div>
                                    <div class="tl-date"><?= $e(formatTanggal($pv('dicairkan_at'))) ?></div>
                                    <div class="tl-desc good">Saldo nasabah telah dikurangi.</div>
                                </div>
                            </li>
                        <?php endif; ?>
                    </ol>
                </section>

                <?php if ($status === 'DIAJUKAN'): ?>
                    <section class="card" aria-labelledby="aksi-title">
                        <h2 class="card-title" id="aksi-title"><span class="ico warn"><?= icon('alert') ?></span>Perlu tindakan</h2>
                        <p class="aksi-note">Setujui pengajuan ini atau tolak dengan menyertakan alasan.</p>

                        <div class="actions">
                            <form method="POST" action="<?= base_url('pengelola/pencairan/setuju') ?>" onsubmit="return confirm('Setujui pengajuan ini?')">
                                <?= csrfField() ?>
                                <input type="hidden" name="id" value="<?= $e($idPengajuan) ?>">
                                <button type="submit" class="btn btn-primary"><?= icon('check') ?>Setujui</button>
                            </form>
                            <button type="button" class="btn btn-danger-ghost" id="btnTolak" aria-controls="tolakForm" aria-expanded="false" onclick="showTolakForm()"><?= icon('close') ?>Tolak</button>
                        </div>

                        <div class="tolak-box" id="tolakForm" hidden>
                            <form method="POST" action="<?= base_url('pengelola/pencairan/tolak') ?>">
                                <?= csrfField() ?>
                                <input type="hidden" name="id" value="<?= $e($idPengajuan) ?>">
                                <div class="field">
                                    <label for="alasan">Alasan penolakan <span class="req" aria-hidden="true">*</span></label>
                                    <textarea name="alasan" id="alasan" rows="3" required placeholder="Jelaskan alasan penolakan agar nasabah paham."></textarea>
                                </div>
                                <div class="actions">
                                    <button type="submit" class="btn btn-danger">Konfirmasi tolak</button>
                                    <button type="button" class="btn btn-ghost" onclick="hideTolakForm()">Batal</button>
                                </div>
                            </form>
                        </div>
                    </section>

                <?php elseif ($status === 'DISETUJUI'): ?>
                    <section class="card" aria-labelledby="aksi-title">
                        <h2 class="card-title" id="aksi-title"><span class="ico"><?= icon('check') ?></span>Siap dicairkan</h2>
                        <p class="aksi-note">Pengajuan sudah disetujui. Serahkan dana ke nasabah, lalu tandai sudah dicairkan agar saldo nasabah dikurangi.</p>
                        <form method="POST" action="<?= base_url('pengelola/pencairan/cairkan') ?>" onsubmit="return confirm('Yakin ingin mencairkan saldo nasabah? Saldo akan dikurangi secara permanen.')">
                            <?= csrfField() ?>
                            <input type="hidden" name="id" value="<?= $e($idPengajuan) ?>">
                            <button type="submit" class="btn btn-primary btn-block"><?= icon('wallet') ?>Tandai sudah dicairkan</button>
                        </form>
                    </section>

                <?php elseif ($status === 'DITOLAK'): ?>
                    <div class="notice notice-error state" role="status">
                        <?= icon('close') ?>
                        <div>
                            <p><strong>Pengajuan ini telah ditolak.</strong></p>
                            <?php if ($alasanTolak !== ''): ?><p>Alasan: <?= $e($alasanTolak) ?></p><?php endif; ?>
                        </div>
                    </div>

                <?php elseif ($status === 'DICAIRKAN'): ?>
                    <div class="notice notice-success state" role="status">
                        <?= icon('check') ?>
                        <div>
                            <p><strong>Pengajuan ini telah dicairkan.</strong></p>
                            <p>Saldo nasabah dikurangi sebesar <?= $e(formatRupiah($pv('jumlah', 0))) ?>.</p>
                        </div>
                    </div>

                <?php elseif ($status === 'DIBATALKAN'): ?>
                    <div class="notice notice-info state" role="status">
                        <?= icon('info') ?>
                        <div><p><strong>Pengajuan ini dibatalkan.</strong></p></div>
                    </div>
                <?php endif; ?>
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
    <script>
        function showTolakForm() {
            var box = document.getElementById('tolakForm');
            var btn = document.getElementById('btnTolak');
            box.hidden = false;
            if (btn) { btn.setAttribute('aria-expanded', 'true'); }
            document.getElementById('alasan').focus();
        }

        function hideTolakForm() {
            var box = document.getElementById('tolakForm');
            var btn = document.getElementById('btnTolak');
            box.hidden = true;
            document.getElementById('alasan').value = '';
            if (btn) { btn.setAttribute('aria-expanded', 'false'); btn.focus(); }
        }
    </script>
</body>
</html>