<?php
/**
 * Halaman Ganti Kata Sandi
 * Compatible dengan PHP 7.3+
 *
 * Variabel dari controller: $wajib (bool), $errors (array per kolom),
 * $csrfToken, $minPanjang, $flash
 * Kunci $errors: password_lama, password_baru, password_konfirmasi, umum
 */

if (!function_exists('icon')) {
    /**
     * Ikon SVG garis yang konsisten (tanpa emoji).
     */
    function icon(string $name, string $class = ''): string
    {
        $paths = [
            'leaf' => '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>',
            'lock' => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
            'key' => '<circle cx="8" cy="15" r="4"/><path d="M10.8 12.2L21 2M17 6l3 3M14 9l2 2"/>',
            'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
            'eye-off' => '<path d="M17.9 17.9A10.1 10.1 0 0 1 12 19c-6.5 0-10-7-10-7a18 18 0 0 1 4.1-5.1M9.9 5.2A9.7 9.7 0 0 1 12 5c6.5 0 10 7 10 7a18 18 0 0 1-2.2 3.2"/><path d="M14.1 14.1a3 3 0 1 1-4.2-4.2"/><line x1="2" y1="2" x2="22" y2="22"/>',
            'check-circle' => '<path d="M22 11.1V12a10 10 0 1 1-5.9-9.1"/><polyline points="22 4 12 14.01 9 11.01"/>',
            'x-circle' => '<circle cx="12" cy="12" r="9"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>',
            'alert' => '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
            'info' => '<circle cx="12" cy="12" r="9"/><line x1="12" y1="11" x2="12" y2="16"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
            'arrow-left' => '<line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>',
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

$wajib = !empty($wajib);
$errors = (isset($errors) && is_array($errors)) ? $errors : [];
$csrfToken = isset($csrfToken) ? (string) $csrfToken : '';
$minPanjang = isset($minPanjang) ? (int) $minPanjang : 8;
$appName = defined('APP_NAME') ? APP_NAME : 'Bank Sampah';

$flash = isset($flash) ? $flash : null;
$flashTipe = $flash && isset($flash['type']) ? strtolower((string) $flash['type']) : '';
$flashClass = in_array($flashTipe, ['success', 'error', 'warning', 'info'], true) ? $flashTipe : 'info';
$flashIcon = $flashClass === 'success' ? 'check-circle'
    : ($flashClass === 'error' ? 'x-circle' : ($flashClass === 'warning' ? 'alert' : 'info'));

$errMsg = function ($k) use ($errors) {
    return isset($errors[$k]) ? (string) (is_array($errors[$k]) ? implode(' ', $errors[$k]) : $errors[$k]) : '';
};
$kelasInput = function ($k) use ($errMsg) {
    return 'input has-toggle' . ($errMsg($k) !== '' ? ' is-invalid' : '');
};
$ariaInput = function ($k, $hintId = '') use ($errMsg) {
    $ids = [];
    if ($errMsg($k) !== '') { $ids[] = 'err-' . $k; }
    if ($hintId !== '') { $ids[] = $hintId; }
    return ($errMsg($k) !== '' ? ' aria-invalid="true"' : '') . ($ids ? ' aria-describedby="' . implode(' ', $ids) . '"' : '');
};
$errHtml = function ($k) use ($errMsg, $e) {
    $m = $errMsg($k);
    return $m === '' ? '' : '<p class="err" id="err-' . $k . '">' . icon('alert') . '<span>' . $e($m) . '</span></p>';
};

$labelLama = $wajib ? 'Kata sandi sementara' : 'Kata sandi saat ini';
$judul = $wajib ? 'Ganti Kata Sandi Sementara' : 'Ganti Kata Sandi';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#1b5e20">
<title><?= $e($judul) ?> - <?= $e($appName) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
<style>
:root {
    --g-900:#0f3d14; --g-800:#1b5e20; --g-700:#2e7d32; --g-600:#43a047; --g-500:#66bb6a;
    --g-200:#c8e6c9; --g-100:#e8f5e9; --g-50:#f4faf4;
    --paper:#f7faf6; --card:#ffffff; --ink:#17231a; --ink-2:#3b4a3e; --ink-3:#4a5a4d; --line:#dde5dc;
    --amber:#7a4100; --amber-bg:#ffefcc; --rose:#9b1c15; --rose-bg:#fde8e6; --sky:#12408f; --sky-bg:#e2edff;
    --ease:cubic-bezier(0.4,0,0.2,1);
}
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
html { -webkit-text-size-adjust:100%; }
body { min-height:100vh; font-family:'Plus Jakarta Sans','Segoe UI',system-ui,-apple-system,sans-serif; font-size:16px; line-height:1.5; color:var(--ink-2); background:var(--paper); -webkit-font-smoothing:antialiased; }
svg { display:block; }
a { color:inherit; }
button, input { font-family:inherit; }
:focus-visible { outline:3px solid var(--g-600); outline-offset:2px; border-radius:6px; }

.page { min-height:100vh; display:flex; align-items:center; justify-content:center; padding:2rem 1.25rem; }
.wrap { width:100%; max-width:460px; }
.card { background:var(--card); border:1px solid var(--line); border-radius:28px; overflow:hidden; box-shadow:0 18px 40px rgba(27,94,32,.12); }

.head { position:relative; overflow:hidden; color:#fff; padding:2rem 2rem 1.75rem; background:linear-gradient(135deg,#43a047 0%,#2e7d32 45%,#14501a 100%); }
.head::before { content:""; position:absolute; width:260px; height:260px; right:-90px; top:-120px; border-radius:50%; background:rgba(255,255,255,.07); pointer-events:none; }
.head > * { position:relative; z-index:1; }
.mark { width:52px; height:52px; border-radius:16px; margin-bottom:1.25rem; background:rgba(255,255,255,.18); display:flex; align-items:center; justify-content:center; }
.mark svg { width:26px; height:26px; }
.head h1 { font-size:1.625rem; font-weight:800; color:#fff; letter-spacing:-.03em; line-height:1.2; }
.head p { margin-top:.375rem; color:rgba(255,255,255,.95); }

.body { padding:1.5rem 2rem 1.5rem; }
.notice { display:flex; gap:.625rem; align-items:flex-start; padding:.875rem 1.125rem; border-radius:14px; margin-bottom:1.25rem; font-size:.9375rem; font-weight:500; border:1px solid transparent; }
.notice svg { width:20px; height:20px; flex-shrink:0; margin-top:2px; }
.notice ul { margin:.25rem 0 0 1.125rem; }
.notice a { font-weight:700; text-decoration:underline; }
.n-success { background:var(--g-50); color:#14532d; border-color:var(--g-200); }
.n-error { background:var(--rose-bg); color:var(--rose); border-color:#f8c9c5; }
.n-warning { background:var(--amber-bg); color:var(--amber); border-color:#f6d9a0; }
.n-info { background:var(--sky-bg); color:var(--sky); border-color:#c4d8fa; }

.field + .field { margin-top:1.125rem; }
.label { display:block; margin-bottom:.5rem; font-size:.9375rem; font-weight:600; color:var(--ink); }
.control { position:relative; }
.control > .ico { position:absolute; left:1rem; top:50%; transform:translateY(-50%); width:20px; height:20px; color:var(--ink-3); pointer-events:none; }
.control > .ico svg { width:20px; height:20px; }
.input { width:100%; height:52px; padding:0 1rem 0 2.875rem; font-size:1rem; color:var(--ink); background:var(--card); border:1.5px solid #b9c7ba; border-radius:14px; transition:border-color .18s var(--ease), box-shadow .18s var(--ease); }
.input.has-toggle { padding-right:3.5rem; }
.input::placeholder { color:#5f6e62; opacity:1; }
.input:hover { border-color:var(--g-600); }
.input:focus { outline:none; border-color:var(--g-600); box-shadow:0 0 0 4px rgba(67,160,71,.16); }
.input.is-invalid { border-color:var(--rose); background:#fffafa; }
.input.is-invalid:focus { box-shadow:0 0 0 4px rgba(179,38,30,.14); }
.toggle { position:absolute; right:.375rem; top:50%; transform:translateY(-50%); width:44px; height:44px; border:0; border-radius:12px; cursor:pointer; display:flex; align-items:center; justify-content:center; background:transparent; color:var(--ink-3); }
.toggle:hover { background:var(--g-100); color:var(--g-800); }
.toggle svg { width:20px; height:20px; }
.toggle .i-off { display:none; }
.toggle[aria-pressed="true"] .i-on { display:none; }
.toggle[aria-pressed="true"] .i-off { display:block; }
.hint { margin-top:.375rem; font-size:.9rem; color:var(--ink-3); }
.err { display:flex; align-items:flex-start; gap:.375rem; margin-top:.375rem; font-size:.9rem; font-weight:700; color:var(--rose); }
.err svg { width:16px; height:16px; flex:none; margin-top:3px; }

.btn-primary { width:100%; min-height:54px; margin-top:1.5rem; padding:0 1.5rem; display:inline-flex; align-items:center; justify-content:center; font-size:1.0625rem; font-weight:700; color:#fff; cursor:pointer; border:0; border-radius:14px; background:linear-gradient(135deg,var(--g-600),var(--g-800)); box-shadow:0 8px 18px rgba(27,94,32,.22); transition:transform .18s var(--ease), box-shadow .18s var(--ease); }
.btn-primary:hover { transform:translateY(-2px); box-shadow:0 12px 22px rgba(27,94,32,.28); }
.btn-primary[disabled] { opacity:.7; cursor:wait; transform:none; }
.alt { margin-top:1rem; text-align:center; }
.alt a { display:inline-flex; align-items:center; gap:.5rem; min-height:44px; padding:0 .75rem; border-radius:12px; font-weight:700; color:var(--g-800); text-decoration:none; }
.alt a:hover { background:var(--g-100); }
.alt svg { width:18px; height:18px; }

@media (max-width:720px) {
    .page { align-items:flex-start; padding:1rem 1rem 2rem; }
    .card { border-radius:24px; }
    .head { padding:1.5rem 1.375rem 1.375rem; }
    .head h1 { font-size:1.5rem; }
    .body { padding:1.25rem 1.375rem; }
}
@media (prefers-reduced-motion:reduce) { * { transition-duration:.01ms !important; animation-duration:.01ms !important; } }
</style>
</head>
<body>
<div class="page">
    <div class="wrap">
        <div class="card">
            <div class="head">
                <div class="mark" aria-hidden="true"><?= icon($wajib ? 'key' : 'lock') ?></div>
                <h1><?= $e($judul) ?></h1>
                <p><?= $wajib ? 'Demi keamanan, buat kata sandi baru sebelum melanjutkan.' : 'Perbarui kata sandi akun Anda.' ?></p>
            </div>

            <div class="body">
                <?php if ($flash && !empty($flash['message'])): ?>
                    <div class="notice n-<?= $flashClass ?>" role="status"><?= icon($flashIcon) ?><span><?= $e($flash['message']) ?></span></div>
                <?php endif; ?>

                <?php if ($wajib && empty($errors)): ?>
                    <div class="notice n-warning" role="status"><?= icon('info') ?><span>Anda masuk dengan kata sandi sementara. Halaman lain baru bisa dibuka setelah kata sandi diganti.</span></div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="notice n-error" role="alert" tabindex="-1" id="ringkasan-error">
                        <?= icon('x-circle') ?>
                        <div>
                            <strong>Kata sandi belum bisa diganti.</strong>
                            <?php if ($errMsg('umum') !== ''): ?><div><?= $e($errMsg('umum')) ?></div><?php else: ?><div>Periksa isian yang ditandai di bawah.</div><?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="formSandi" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">

                    <div class="field">
                        <label for="password_lama" class="label"><?= $e($labelLama) ?></label>
                        <div class="control">
                            <span class="ico"><?= icon('lock') ?></span>
                            <input type="password" id="password_lama" name="password_lama" class="<?= $kelasInput('password_lama') ?>" autocomplete="current-password" required<?= $ariaInput('password_lama') ?>>
                            <button type="button" class="toggle" data-toggle="password_lama" aria-pressed="false" aria-label="Tampilkan kata sandi" aria-controls="password_lama">
                                <span class="i-on"><?= icon('eye') ?></span><span class="i-off"><?= icon('eye-off') ?></span>
                            </button>
                        </div>
                        <?= $errHtml('password_lama') ?>
                    </div>

                    <div class="field">
                        <label for="password_baru" class="label">Kata sandi baru</label>
                        <div class="control">
                            <span class="ico"><?= icon('lock') ?></span>
                            <input type="password" id="password_baru" name="password_baru" class="<?= $kelasInput('password_baru') ?>" autocomplete="new-password" required<?= $ariaInput('password_baru', 'hint-baru') ?>>
                            <button type="button" class="toggle" data-toggle="password_baru" aria-pressed="false" aria-label="Tampilkan kata sandi" aria-controls="password_baru">
                                <span class="i-on"><?= icon('eye') ?></span><span class="i-off"><?= icon('eye-off') ?></span>
                            </button>
                        </div>
                        <p class="hint" id="hint-baru">Minimal <?= $minPanjang ?> karakter, mengandung huruf dan angka.</p>
                        <?= $errHtml('password_baru') ?>
                    </div>

                    <div class="field">
                        <label for="password_konfirmasi" class="label">Ulangi kata sandi baru</label>
                        <div class="control">
                            <span class="ico"><?= icon('lock') ?></span>
                            <input type="password" id="password_konfirmasi" name="password_konfirmasi" class="<?= $kelasInput('password_konfirmasi') ?>" autocomplete="new-password" required<?= $ariaInput('password_konfirmasi') ?>>
                            <button type="button" class="toggle" data-toggle="password_konfirmasi" aria-pressed="false" aria-label="Tampilkan kata sandi" aria-controls="password_konfirmasi">
                                <span class="i-on"><?= icon('eye') ?></span><span class="i-off"><?= icon('eye-off') ?></span>
                            </button>
                        </div>
                        <?= $errHtml('password_konfirmasi') ?>
                    </div>

                    <button type="submit" class="btn-primary" id="btnSimpan">Simpan kata sandi baru</button>
                </form>

                <p class="alt">
                    <?php if ($wajib): ?>
                        <a href="<?= base_url('logout') ?>">Keluar</a>
                    <?php else: ?>
                        <a href="<?= base_url('dashboard') ?>"><?= icon('arrow-left') ?>Kembali</a>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var toggles = document.querySelectorAll('[data-toggle]');
    for (var i = 0; i < toggles.length; i++) {
        toggles[i].addEventListener('click', function () {
            var input = document.getElementById(this.getAttribute('data-toggle'));
            if (!input) { return; }
            var tampil = input.type === 'password';
            input.type = tampil ? 'text' : 'password';
            this.setAttribute('aria-pressed', tampil ? 'true' : 'false');
            this.setAttribute('aria-label', tampil ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
        });
    }

    var ringkasan = document.getElementById('ringkasan-error');
    if (ringkasan) { ringkasan.focus(); }
    else { var pertama = document.getElementById('password_lama'); if (pertama) { pertama.focus(); } }

    var form = document.getElementById('formSandi');
    var btn = document.getElementById('btnSimpan');
    form.addEventListener('submit', function () {
        btn.setAttribute('disabled', 'disabled');
        window.setTimeout(function () { btn.removeAttribute('disabled'); }, 8000);
    });
})();
</script>
</body>
</html>