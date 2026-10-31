<?php
/**nasabah/profil.php
 * Profil Saya (versi e-wallet)
 *
 * Melihat dan mengedit profil nasabah. Compatible dengan PHP 7.3+
 * Desain sama dengan dashboard & riwayat.
 *
 * @var array      $nasabah
 * @var array      $oldInput
 * @var array      $errors
 * @var array|null $flash
 */

$nasabah = $nasabah ?? [];
$oldInput = $oldInput ?? [];
$errors = $errors ?? [];

if (!function_exists('icon')) {
    function icon(string $name, string $class = ''): string
    {
        $paths = [
            'leaf' => '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>',
            'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/>',
            'trash' => '<path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2m2 0v14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V6"/><path d="M10 11v6M14 11v6"/>',
            'history' => '<path d="M3 12a9 9 0 1 0 2.6-6.4"/><polyline points="3 4 3 9 8 9"/><path d="M12 7v5l3 2"/>',
            'banknote' => '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/>',
            'user' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
            'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
            'check-circle' => '<path d="M22 11.1V12a10 10 0 1 1-5.9-9.1"/><polyline points="22 4 12 14.01 9 11.01"/>',
            'x-circle' => '<circle cx="12" cy="12" r="9"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>',
            'alert' => '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
            'info' => '<circle cx="12" cy="12" r="9"/><line x1="12" y1="11" x2="12" y2="16"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
            'chevron-left' => '<polyline points="15 6 9 12 15 18"/>',
            'id' => '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="11" r="2"/><path d="M6 16c.5-1.5 1.7-2 3-2s2.5.5 3 2M14 10h4M14 14h4"/>',
            'phone' => '<rect x="6" y="2" width="12" height="20" rx="3"/><path d="M11 18h2"/>',
        ];

        $body = isset($paths[$name]) ? $paths[$name] : $paths['info'];
        $cls = $class !== '' ? ' class="' . htmlspecialchars($class) . '"' : '';

        return '<svg' . $cls . ' viewBox="0 0 24 24" fill="none" stroke="currentColor"'
            . ' stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"'
            . ' aria-hidden="true" focusable="false">' . $body . '</svg>';
    }
}

if (!function_exists('nilaiForm')) {
    /**
     * Nilai isian: prioritaskan input lama (setelah gagal simpan), lalu data tersimpan.
     */
    function nilaiForm(array $old, array $data, string $key): string
    {
        if (array_key_exists($key, $old)) {
            return htmlspecialchars((string) $old[$key]);
        }
        return htmlspecialchars((string) ($data[$key] ?? ''));
    }
}

$user = function_exists('currentUser') ? currentUser() : [];
$namaUser = (string) ((isset($nasabah['nama']) && $nasabah['nama'] !== '') ? $nasabah['nama'] : ($user['nama'] ?? 'Nasabah'));
$inisial = function_exists('mb_substr') ? mb_strtoupper(mb_substr($namaUser, 0, 1)) : strtoupper(substr($namaUser, 0, 1));
$appName = defined('APP_NAME') ? APP_NAME : 'Bank Sampah';

$flashTipe = isset($flash) && $flash && isset($flash['type']) ? strtolower((string) $flash['type']) : '';
$flashClass = in_array($flashTipe, ['success', 'error', 'warning', 'info'], true) ? $flashTipe : 'info';

// Notifikasi dalam aplikasi (lonceng saja; pop-up hanya di halaman cairkan)
require_once VIEW_PATH . '/nasabah/partials/notifikasi.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#1b5e20">
<title>Profil Saya - <?= htmlspecialchars($appName) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
<style>
:root {
    --g-900: #0f3d14;
    --g-800: #1b5e20;
    --g-700: #2e7d32;
    --g-600: #43a047;
    --g-500: #66bb6a;
    --g-200: #c8e6c9;
    --g-100: #e8f5e9;
    --g-50: #f4faf4;
    --paper: #f7faf6;
    --card: #ffffff;
    --ink: #17231a;
    --ink-2: #3b4a3e;
    --ink-3: #5b6a5e;
    --line: #e6ece5;
    --amber: #a85a00;
    --amber-bg: #fff1d6;
    --rose: #b3261e;
    --rose-bg: #fde8e6;
    --sky: #1a56c4;
    --sky-bg: #e2edff;
    --sidebar-w: 272px;
    --topbar-h: 68px;
    --ease: cubic-bezier(0.4, 0, 0.2, 1);
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { -webkit-text-size-adjust: 100%; }
body {
    font-family: 'Plus Jakarta Sans', 'Segoe UI', system-ui, -apple-system, sans-serif;
    font-size: 16px; line-height: 1.55;
    color: var(--ink-2); background: var(--paper);
    -webkit-font-smoothing: antialiased;
}
svg { display: block; }
a { color: inherit; }
:focus-visible { outline: 3px solid var(--g-600); outline-offset: 2px; border-radius: 6px; }
button { font-family: inherit; }

/* ---------- Sidebar ---------- */
.scrim {
    position: fixed; inset: 0; background: rgba(15,30,18,0.5);
    opacity: 0; visibility: hidden; z-index: 90;
    transition: opacity .25s var(--ease), visibility .25s var(--ease);
}
.sidebar {
    position: fixed; top: 0; left: 0; bottom: 0; z-index: 100;
    width: var(--sidebar-w); display: flex; flex-direction: column;
    background: linear-gradient(170deg, var(--g-700) 0%, var(--g-800) 60%, var(--g-900) 100%);
    color: #fff; transform: translateX(-100%);
    transition: transform .3s var(--ease);
}
.side-head { padding: 1.25rem 1.25rem 1rem; }
.side-brand { display: flex; align-items: center; gap: .75rem; text-decoration: none; }
.side-mark {
    width: 42px; height: 42px; border-radius: 13px; flex-shrink: 0;
    background: rgba(255,255,255,.16);
    display: flex; align-items: center; justify-content: center;
}
.side-mark svg { width: 22px; height: 22px; }
.side-name { display: block; font-size: 1rem; font-weight: 700; line-height: 1.25; }
.side-sub { display: block; font-size: .8125rem; color: rgba(255,255,255,.85); }
.side-nav { flex: 1; overflow-y: auto; padding: .5rem .875rem; }
.side-group + .side-group { margin-top: 1.25rem; }
.side-group-label { font-size: .8125rem; font-weight: 600; color: rgba(255,255,255,.8); padding: 0 .75rem; margin-bottom: .375rem; }
.side-link {
    display: flex; align-items: center; gap: .75rem;
    min-height: 50px; padding: .625rem .875rem;
    border-radius: 13px; text-decoration: none;
    font-size: 1rem; font-weight: 500; color: rgba(255,255,255,.95);
    transition: background .18s var(--ease);
}
.side-link + .side-link { margin-top: 3px; }
.side-link svg { width: 20px; height: 20px; flex-shrink: 0; }
.side-link:hover { background: rgba(255,255,255,.1); }
.side-link.active { background: rgba(255,255,255,.2); font-weight: 700; }
.side-foot { padding: .875rem .875rem 1.125rem; border-top: 1px solid rgba(255,255,255,.12); }
.side-link.danger { color: #ffd9d6; }
.side-link.danger:hover { background: rgba(230,90,80,.22); }

body.nav-open .sidebar { transform: translateX(0); }
body.nav-open .scrim { opacity: 1; visibility: visible; }
body.nav-open { overflow: hidden; }

/* ---------- Topbar ---------- */
.main { min-width: 0; transition: margin-left .3s var(--ease); }
.topbar {
    height: var(--topbar-h); display: flex; align-items: center; gap: .875rem;
    padding: 0 2rem; position: sticky; top: 0; z-index: 60;
    background: rgba(247,250,246,.9);
    backdrop-filter: saturate(180%) blur(12px);
    -webkit-backdrop-filter: saturate(180%) blur(12px);
}
.burger {
    width: 46px; height: 46px; flex-shrink: 0; cursor: pointer;
    display: flex; flex-direction: column; justify-content: center; gap: 5px; padding: 0 13px;
    border: 1px solid var(--line); border-radius: 14px; background: var(--card);
    transition: background .18s var(--ease);
}
.burger:hover { background: var(--g-100); }
.burger-bar { display: block; width: 20px; height: 2px; border-radius: 2px; background: var(--g-800); transition: transform .25s var(--ease), opacity .15s var(--ease); }
body.nav-open .burger-bar:nth-child(1) { transform: translateY(7px) rotate(45deg); }
body.nav-open .burger-bar:nth-child(2) { opacity: 0; }
body.nav-open .burger-bar:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }
.topbar-brand { font-size: 1rem; font-weight: 700; color: var(--ink); letter-spacing: -.015em; }
.who { margin-left: auto; display: flex; align-items: center; gap: .625rem; text-decoration: none; padding: .25rem .25rem .25rem .75rem; border-radius: 999px; }
.who-name { font-size: .9375rem; font-weight: 600; color: var(--ink); }
.who-avatar {
    width: 42px; height: 42px; border-radius: 50%; flex-shrink: 0;
    background: linear-gradient(140deg, var(--g-500), var(--g-700)); color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; font-weight: 700;
}

/* ---------- Halaman ---------- */
.page { max-width: 760px; width: 100%; margin: 0 auto; padding: .75rem 2rem 3.5rem; }
.back {
    display: inline-flex; align-items: center; gap: .25rem; min-height: 44px;
    margin-left: -.5rem; padding: 0 .75rem 0 .5rem; border-radius: 12px;
    font-size: .9375rem; font-weight: 600; color: var(--g-700); text-decoration: none;
}
.back:hover { background: var(--g-100); }
.back svg { width: 18px; height: 18px; }
.head { margin: .25rem 0 1.5rem; }
.title { font-size: 1.75rem; font-weight: 800; color: var(--ink); letter-spacing: -.03em; line-height: 1.2; }
.lead { font-size: .9375rem; color: var(--ink-3); margin-top: .25rem; }

.flash, .alert {
    display: flex; gap: .75rem; align-items: flex-start;
    padding: .875rem 1.125rem; border-radius: 14px; margin-bottom: 1rem;
    font-size: .9375rem; font-weight: 500; border: 1px solid transparent;
}
.flash { margin-bottom: 1.25rem; }
.flash svg, .alert svg { width: 20px; height: 20px; flex-shrink: 0; margin-top: 2px; }
.flash-success { background: var(--g-50); color: #14532d; border-color: var(--g-200); }
.flash-error, .alert { background: var(--rose-bg); color: var(--rose); border-color: #f8c9c5; }
.flash-warning { background: var(--amber-bg); color: var(--amber); border-color: #f6d9a0; }
.flash-info { background: var(--sky-bg); color: var(--sky); border-color: #c4d8fa; }

/* ---------- Kartu identitas ---------- */
.idcard {
    position: relative; overflow: hidden; color: #fff;
    border-radius: 24px; padding: 1.5rem 1.75rem;
    background: linear-gradient(135deg, #43a047 0%, #2e7d32 45%, #14501a 100%);
    box-shadow: 0 14px 32px rgba(27,94,32,.22);
}
.idcard::before {
    content: ""; position: absolute; width: 260px; height: 260px; right: -90px; top: -120px;
    border-radius: 50%; background: rgba(255,255,255,.07); pointer-events: none;
}
.idcard > * { position: relative; }
.id-top { display: flex; align-items: center; gap: 1rem; }
.id-avatar {
    width: 60px; height: 60px; border-radius: 50%; flex-shrink: 0;
    background: rgba(255,255,255,.2); border: 1px solid rgba(255,255,255,.25);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem; font-weight: 800;
}
.id-name { font-size: 1.25rem; font-weight: 700; letter-spacing: -.02em; line-height: 1.25; overflow-wrap: anywhere; }
.id-role { font-size: .9375rem; color: rgba(255,255,255,.92); }
.id-split { display: grid; grid-template-columns: 1fr 1fr; gap: .875rem; margin-top: 1.25rem; }
.id-item { padding: .875rem 1rem; border-radius: 16px; background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.18); min-width: 0; }
.id-label { display: flex; align-items: center; gap: .375rem; font-size: .875rem; color: rgba(255,255,255,.92); }
.id-label svg { width: 16px; height: 16px; }
.id-value { font-size: 1.0625rem; font-weight: 700; font-variant-numeric: tabular-nums; margin-top: .125rem; overflow-wrap: anywhere; }

/* ---------- Formulir ---------- */
.form-wrap { margin-top: 2rem; }
.section-title { font-size: 1.125rem; font-weight: 700; color: var(--ink); letter-spacing: -.02em; margin-bottom: .75rem; }
.group { background: var(--card); border: 1px solid var(--line); border-radius: 20px; padding: 1.5rem 1.5rem .5rem; margin-bottom: 1.25rem; }
.group-title { font-size: 1rem; font-weight: 700; color: var(--ink); }
.group-hint { font-size: .875rem; color: var(--ink-3); margin: .125rem 0 1.25rem; }
.grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0 1.25rem; }
.full { grid-column: 1 / -1; }
.field { margin-bottom: 1.125rem; }
.label { display: block; margin-bottom: .375rem; font-size: .9375rem; font-weight: 600; color: var(--ink); }
.req { color: var(--rose); }
.input {
    width: 100%; height: 52px; padding: 0 1rem;
    font: inherit; font-size: 1rem; color: var(--ink);
    background: #fbfcfa; border: 1.5px solid #d9e1d8; border-radius: 14px;
    transition: border-color .18s var(--ease), box-shadow .18s var(--ease), background .18s var(--ease);
}
.input::placeholder { color: #8a968c; }
.input:hover { border-color: #bccabb; }
.input:focus { outline: none; background: #fff; border-color: var(--g-700); box-shadow: 0 0 0 4px rgba(67,160,71,.18); }
.input[readonly] { background: #f1f4f0; color: var(--ink-3); cursor: not-allowed; }
.hint { font-size: .875rem; color: var(--ink-3); margin-top: .375rem; }

.actions { display: flex; gap: .75rem; margin-top: 1.5rem; }
.btn {
    display: inline-flex; align-items: center; justify-content: center;
    min-height: 52px; padding: 0 1.75rem; border-radius: 14px; border: 0;
    font-size: 1rem; font-weight: 700; text-decoration: none; cursor: pointer;
    transition: background .18s var(--ease), transform .18s var(--ease);
}
.btn-primary { background: linear-gradient(135deg, var(--g-600), var(--g-800)); color: #fff; box-shadow: 0 8px 18px rgba(27,94,32,.22); }
.btn-primary:hover { background: linear-gradient(135deg, var(--g-700), var(--g-900)); transform: translateY(-1px); }
.btn-ghost { background: var(--card); color: var(--ink-2); border: 1.5px solid #d9e1d8; }
.btn-ghost:hover { background: var(--g-100); }

/* ---------- Responsif ---------- */
@media (min-width: 1101px) {
    body.nav-open { overflow: auto; }
    body.nav-open .scrim { opacity: 0; visibility: hidden; }
    body.nav-open .main { margin-left: var(--sidebar-w); }
}
@media (max-width: 720px) {
    :root { --topbar-h: 62px; }
    .topbar { padding: 0 1rem; }
    .page { padding: .5rem 1rem 3rem; }
    .who { padding-left: 0; }
    .who-name { display: none; }
    .title { font-size: 1.5rem; }
    .idcard { padding: 1.25rem; border-radius: 20px; }
    .grid { grid-template-columns: 1fr; }
    .group { padding: 1.25rem 1.125rem .375rem; border-radius: 18px; }
    .actions { flex-direction: column-reverse; }
    .btn { width: 100%; }
}
@media (max-width: 380px) {
    .topbar-brand { display: none; }
    .id-split { grid-template-columns: 1fr; }
}
@media (prefers-reduced-motion: reduce) {
    * { transition-duration: .01ms !important; animation-duration: .01ms !important; }
}
</style>
<?php notifikasiStyle(); ?>
<script>
(function () {
    try {
        window.__navOpen = window.innerWidth > 1100 && localStorage.getItem('nav') !== 'closed';
    } catch (e) {
        window.__navOpen = window.innerWidth > 1100;
    }
})();
</script>
</head>
<body>
<script>
if (window.__navOpen) { document.body.classList.add('nav-open'); }
</script>

<div class="scrim" id="scrim"></div>

<?php $menuAktif = 'nasabah/profil'; include VIEW_PATH . '/nasabah/partials/sidebar.php'; ?>

<div class="main">
    <header class="topbar">
        <button type="button" class="burger" id="burger" aria-label="Buka menu" aria-expanded="false" aria-controls="sidebar">
            <span class="burger-bar"></span><span class="burger-bar"></span><span class="burger-bar"></span>
        </button>
        <div class="topbar-brand"><?= htmlspecialchars($appName) ?></div>

        <?php notifikasiLonceng(); ?>

        <a href="<?= base_url('nasabah/profil') ?>" class="who" aria-label="Profil saya">
            <span class="who-name"><?= htmlspecialchars($namaUser) ?></span>
            <span class="who-avatar" aria-hidden="true"><?= htmlspecialchars($inisial) ?></span>
        </a>
    </header>

    <main class="page">
        <a href="<?= base_url('nasabah/dashboard') ?>" class="back"><?= icon('chevron-left') ?> Beranda</a>

        <?php if (isset($flash) && $flash && !empty($flash['message'])): ?>
            <div class="flash flash-<?= $flashClass ?>" role="status">
                <?= icon($flashClass === 'success' ? 'check-circle' : ($flashClass === 'error' ? 'x-circle' : ($flashClass === 'warning' ? 'alert' : 'info'))) ?>
                <span><?= htmlspecialchars($flash['message']) ?></span>
            </div>
        <?php endif; ?>

        <div class="head">
            <h1 class="title">Profil saya</h1>
            <p class="lead">Kelola data diri dan rekening pencairan Anda</p>
        </div>

        <section class="idcard" aria-label="Informasi akun">
            <div class="id-top">
                <span class="id-avatar" aria-hidden="true"><?= htmlspecialchars($inisial) ?></span>
                <div>
                    <div class="id-name"><?= htmlspecialchars($namaUser) ?></div>
                    <div class="id-role">Nasabah <?= htmlspecialchars($appName) ?></div>
                </div>
            </div>
            <div class="id-split">
                <div class="id-item">
                    <div class="id-label"><?= icon('id') ?> Nomor anggota</div>
                    <div class="id-value"><?= htmlspecialchars($nasabah['no_anggota'] ?? '-') ?></div>
                </div>
                <div class="id-item">
                    <div class="id-label"><?= icon('phone') ?> Nomor HP terdaftar</div>
                    <div class="id-value"><?= htmlspecialchars($nasabah['no_hp'] ?? '-') ?></div>
                </div>
            </div>
        </section>

        <div class="form-wrap">
            <h2 class="section-title">Ubah data pribadi</h2>

            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $error): ?>
                    <div class="alert" role="alert"><?= icon('x-circle') ?><span><?= htmlspecialchars($error) ?></span></div>
                <?php endforeach; ?>
            <?php endif; ?>

            <form method="POST" action="<?= base_url('nasabah/profil') ?>">
                <div class="group">
                    <h3 class="group-title">Data diri</h3>
                    <p class="group-hint">Tanda <span class="req">*</span> wajib diisi</p>
                    <div class="grid">
                        <div class="field">
                            <label for="nama" class="label">Nama lengkap <span class="req">*</span></label>
                            <input type="text" id="nama" name="nama" class="input" placeholder="Masukkan nama lengkap" autocomplete="name" value="<?= nilaiForm($oldInput, $nasabah, 'nama') ?>" required>
                        </div>
                        <div class="field">
                            <label for="no_hp" class="label">Nomor HP (untuk masuk)</label>
                            <input type="tel" inputmode="tel" id="no_hp" name="no_hp" class="input" autocomplete="tel" value="<?= htmlspecialchars((string) ($nasabah['no_hp'] ?? '')) ?>" readonly aria-describedby="no_hp_hint">
                            <p class="hint" id="no_hp_hint">Nomor ini dipakai untuk masuk. Untuk menggantinya, hubungi pengelola.</p>
                        </div>
                        <div class="field full">
                            <label for="email" class="label">Email</label>
                            <input type="email" id="email" name="email" class="input" placeholder="nama@email.com" autocomplete="email" value="<?= nilaiForm($oldInput, $nasabah, 'email') ?>">
                        </div>
                    </div>
                </div>

                <div class="group">
                    <h3 class="group-title">Alamat</h3>
                    <p class="group-hint">Alamat tempat tinggal Anda</p>
                    <div class="grid">
                        <div class="field full">
                            <label for="alamat" class="label">Alamat</label>
                            <input type="text" id="alamat" name="alamat" class="input" placeholder="Jln. Contoh No. 123" autocomplete="street-address" value="<?= nilaiForm($oldInput, $nasabah, 'alamat') ?>">
                        </div>
                        <div class="field">
                            <label for="rt" class="label">RT</label>
                            <input type="text" inputmode="numeric" id="rt" name="rt" class="input" maxlength="5" placeholder="01" value="<?= nilaiForm($oldInput, $nasabah, 'rt') ?>">
                        </div>
                        <div class="field">
                            <label for="rw" class="label">RW</label>
                            <input type="text" inputmode="numeric" id="rw" name="rw" class="input" maxlength="5" placeholder="05" value="<?= nilaiForm($oldInput, $nasabah, 'rw') ?>">
                        </div>
                    </div>
                </div>

                <div class="group">
                    <h3 class="group-title">Rekening bank</h3>
                    <p class="group-hint">Dipakai saat Anda mencairkan saldo</p>
                    <div class="grid">
                        <div class="field">
                            <label for="nama_bank" class="label">Nama bank</label>
                            <input type="text" id="nama_bank" name="nama_bank" class="input" placeholder="BRI, BNI, Mandiri, dll" value="<?= nilaiForm($oldInput, $nasabah, 'nama_bank') ?>">
                        </div>
                        <div class="field">
                            <label for="no_rekening" class="label">Nomor rekening</label>
                            <input type="text" inputmode="numeric" id="no_rekening" name="no_rekening" class="input" placeholder="123456789012" value="<?= nilaiForm($oldInput, $nasabah, 'no_rekening') ?>">
                        </div>
                    </div>
                </div>

                <div class="actions">
                    <a href="<?= base_url('nasabah/dashboard') ?>" class="btn btn-ghost">Batal</a>
                    <button type="submit" name="submit" class="btn btn-primary">Simpan perubahan</button>
                </div>
            </form>
        </div>
    </main>
</div>

<script>
(function () {
    var body = document.body;
    var burger = document.getElementById('burger');
    var scrim = document.getElementById('scrim');
    var mq = window.matchMedia('(min-width: 1101px)');

    function save(v) { try { localStorage.setItem('nav', v); } catch (e) {} }

    function syncBurger() {
        var open = body.classList.contains('nav-open');
        burger.setAttribute('aria-expanded', open ? 'true' : 'false');
        burger.setAttribute('aria-label', open ? 'Tutup menu' : 'Buka menu');
    }
    function setNav(open) {
        body.classList.toggle('nav-open', open);
        if (mq.matches) { save(open ? 'open' : 'closed'); }
        syncBurger();
    }
    burger.addEventListener('click', function () { setNav(!body.classList.contains('nav-open')); });
    scrim.addEventListener('click', function () { setNav(false); });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && body.classList.contains('nav-open') && !mq.matches) {
            setNav(false);
            burger.focus();
        }
    });
    var onChange = function () {
        var saved = null;
        try { saved = localStorage.getItem('nav'); } catch (e) {}
        body.classList.toggle('nav-open', mq.matches && saved !== 'closed');
        syncBurger();
    };
    if (mq.addEventListener) { mq.addEventListener('change', onChange); }
    else if (mq.addListener) { mq.addListener(onChange); }
    syncBurger();
})();
</script>
<?php notifikasiScript(); ?>
</body>
</html>