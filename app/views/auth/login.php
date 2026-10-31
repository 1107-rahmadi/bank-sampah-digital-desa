<?php
/**
 * Halaman Masuk + Daftar (satu halaman, dua tab)
 * Compatible dengan PHP 7.3+
 *
 * @var array|null  $flash
 * @var string|null $error
 * @var array|null  $oldInput
 * @var string|null $activeTab  'masuk' (default) | 'daftar'
 */

if (!function_exists('icon')) {
    /**
     * Ikon SVG garis yang konsisten (tanpa emoji).
     */
    function icon(string $name, string $class = ''): string
    {
        $paths = [
            'leaf' => '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>',
            'phone' => '<rect x="6" y="2" width="12" height="20" rx="2.5"/><path d="M11 18h2"/>',
            'lock' => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
            'user' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
            'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><polyline points="3 7 12 13 21 7"/>',
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

$flash = isset($flash) ? $flash : null;
$oldInput = (isset($oldInput) && is_array($oldInput)) ? $oldInput : [];
$activeTab = (isset($activeTab) && $activeTab === 'daftar') ? 'daftar' : 'masuk';

$flashTipe = $flash && isset($flash['type']) ? strtolower((string) $flash['type']) : '';
$flashClass = in_array($flashTipe, ['success', 'error', 'warning', 'info'], true) ? $flashTipe : 'info';
$flashIcon = $flashClass === 'success' ? 'check-circle'
    : ($flashClass === 'error' ? 'x-circle' : ($flashClass === 'warning' ? 'alert' : 'info'));

$hasError = isset($error) && $error !== '' && $error !== null;
$errMasuk = $hasError && $activeTab === 'masuk';
$errDaftar = $hasError && $activeTab === 'daftar';

$oldNoHp = isset($oldInput['no_hp']) ? (string) $oldInput['no_hp'] : '';
$oldNama = isset($oldInput['nama']) ? (string) $oldInput['nama'] : '';
$oldEmail = isset($oldInput['email']) ? (string) $oldInput['email'] : '';

$subMasuk = 'Masuk ke akun Anda';
$subDaftar = 'Buat akun nasabah baru';
$appName = defined('APP_NAME') ? APP_NAME : 'Bank Sampah';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#1b5e20">
<title><?= $activeTab === 'daftar' ? 'Daftar' : 'Masuk' ?> - <?= htmlspecialchars($appName) ?></title>
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
    --ease: cubic-bezier(0.4, 0, 0.2, 1);
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { -webkit-text-size-adjust: 100%; }
body {
    min-height: 100vh;
    font-family: 'Plus Jakarta Sans', 'Segoe UI', system-ui, -apple-system, sans-serif;
    font-size: 16px; line-height: 1.5;
    color: var(--ink-2); background: var(--paper);
    -webkit-font-smoothing: antialiased;
}
svg { display: block; }
a { color: inherit; }
button, input { font-family: inherit; }
[hidden] { display: none !important; }
:focus-visible { outline: 3px solid var(--g-600); outline-offset: 2px; border-radius: 6px; }

.login-page {
    min-height: 100vh; display: flex; align-items: center; justify-content: center;
    padding: 2rem 1.25rem;
}
.login-wrap { width: 100%; max-width: 440px; }

.back {
    display: inline-flex; align-items: center; gap: .5rem;
    min-height: 44px; padding: 0 .75rem; margin: 0 0 .5rem -.75rem;
    border-radius: 12px; font-size: .9375rem; font-weight: 600;
    color: var(--g-700); text-decoration: none;
    transition: background .18s var(--ease);
}
.back:hover { background: var(--g-100); }
.back svg { width: 18px; height: 18px; }

.login-card {
    background: var(--card); border: 1px solid var(--line);
    border-radius: 28px; overflow: hidden;
    box-shadow: 0 18px 40px rgba(27,94,32,.12);
}

/* ---------- Kepala kartu ---------- */
.login-head {
    position: relative; overflow: hidden; color: #fff;
    padding: 2rem 2rem 1.75rem;
    background: linear-gradient(135deg, #43a047 0%, #2e7d32 45%, #14501a 100%);
}
.login-head::before, .login-head::after {
    content: ""; position: absolute; border-radius: 50%; pointer-events: none;
    background: rgba(255,255,255,.07);
}
.login-head::before { width: 260px; height: 260px; right: -90px; top: -120px; }
.login-head::after { width: 170px; height: 170px; right: 70px; bottom: -120px; }
.login-head > * { position: relative; z-index: 1; }
.login-mark {
    width: 52px; height: 52px; border-radius: 16px; margin-bottom: 1.25rem;
    background: rgba(255,255,255,.18);
    display: flex; align-items: center; justify-content: center;
}
.login-mark svg { width: 26px; height: 26px; }
.login-head h1 {
    font-size: 1.75rem; font-weight: 800; color: #fff;
    letter-spacing: -.03em; line-height: 1.2;
}
.login-head p { margin-top: .375rem; font-size: 1rem; color: rgba(255,255,255,.92); }

/* ---------- Isi ---------- */
.login-body { padding: 1.5rem 2rem 1.5rem; }

.tabs {
    display: grid; grid-template-columns: 1fr 1fr; gap: 4px;
    padding: 4px; margin-bottom: 1.5rem;
    background: var(--g-100); border-radius: 16px;
}
.tab {
    min-height: 46px; border: 0; border-radius: 12px; cursor: pointer;
    font-size: 1rem; font-weight: 600; color: var(--ink-3); background: transparent;
    transition: background .18s var(--ease), color .18s var(--ease), box-shadow .18s var(--ease);
}
.tab:hover { color: var(--g-800); }
.tab[aria-selected="true"] {
    background: var(--card); color: var(--g-800); font-weight: 700;
    box-shadow: 0 2px 8px rgba(27,94,32,.14);
}

.flash {
    display: flex; gap: .625rem; align-items: flex-start;
    padding: .875rem 1.125rem; border-radius: 14px; margin-bottom: 1.25rem;
    font-size: .9375rem; font-weight: 500; border: 1px solid transparent;
}
.flash svg { width: 20px; height: 20px; flex-shrink: 0; margin-top: 2px; }
.flash-success { background: var(--g-50); color: #14532d; border-color: var(--g-200); }
.flash-error { background: var(--rose-bg); color: var(--rose); border-color: #f8c9c5; }
.flash-warning { background: var(--amber-bg); color: var(--amber); border-color: #f6d9a0; }
.flash-info { background: var(--sky-bg); color: var(--sky); border-color: #c4d8fa; }

.field + .field { margin-top: 1.125rem; }
.label { display: block; margin-bottom: .5rem; font-size: .9375rem; font-weight: 600; color: var(--ink); }
.label .opt { font-weight: 500; color: var(--ink-3); }
.control { position: relative; }
.control > .ico {
    position: absolute; left: 1rem; top: 50%; transform: translateY(-50%);
    width: 20px; height: 20px; color: var(--ink-3); pointer-events: none;
}
.control > .ico svg { width: 20px; height: 20px; }
.input {
    width: 100%; height: 52px; padding: 0 1rem 0 2.875rem;
    font-size: 1rem; color: var(--ink);
    background: var(--card); border: 1.5px solid var(--line); border-radius: 14px;
    transition: border-color .18s var(--ease), box-shadow .18s var(--ease), background .18s var(--ease);
}
.input::placeholder { color: #8a988c; }
.input:hover { border-color: var(--g-200); }
.input:focus { outline: none; border-color: var(--g-600); box-shadow: 0 0 0 4px rgba(67,160,71,.16); }
.input.is-invalid { border-color: var(--rose); background: #fffafa; }
.input.is-invalid:focus { box-shadow: 0 0 0 4px rgba(179,38,30,.14); }
.input.has-toggle { padding-right: 3.5rem; }
.toggle {
    position: absolute; right: .375rem; top: 50%; transform: translateY(-50%);
    width: 44px; height: 44px; border: 0; border-radius: 12px; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    background: transparent; color: var(--ink-3);
    transition: background .18s var(--ease), color .18s var(--ease);
}
.toggle:hover { background: var(--g-100); color: var(--g-800); }
.toggle svg { width: 20px; height: 20px; }
.toggle .i-off { display: none; }
.toggle[aria-pressed="true"] .i-on { display: none; }
.toggle[aria-pressed="true"] .i-off { display: block; }

.btn-primary {
    width: 100%; min-height: 54px; margin-top: 1.5rem; padding: 0 1.5rem;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 1.0625rem; font-weight: 700; color: #fff; cursor: pointer;
    border: 0; border-radius: 14px;
    background: linear-gradient(135deg, var(--g-600), var(--g-800));
    box-shadow: 0 8px 18px rgba(27,94,32,.22);
    transition: transform .18s var(--ease), box-shadow .18s var(--ease);
}
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 22px rgba(27,94,32,.28); }
.btn-primary:active { transform: translateY(0); }

.switch {
    margin-top: 1.25rem; text-align: center; font-size: .9375rem; color: var(--ink-3);
}
.switch button {
    min-height: 44px; padding: 0 .5rem; border: 0; border-radius: 10px; cursor: pointer;
    background: transparent; font-size: .9375rem; font-weight: 700; color: var(--g-700);
    transition: background .18s var(--ease);
}
.switch button:hover { background: var(--g-100); }

.login-foot {
    padding: 1.125rem 2rem 1.5rem; text-align: center;
    border-top: 1px solid var(--line); font-size: .875rem; color: var(--ink-3);
}

@media (max-width: 720px) {
    .login-page { align-items: flex-start; padding: 1rem 1rem 2rem; }
    .login-card { border-radius: 24px; }
    .login-head { padding: 1.5rem 1.375rem 1.375rem; }
    .login-head h1 { font-size: 1.5rem; }
    .login-body { padding: 1.25rem 1.375rem 1.25rem; }
    .login-foot { padding: 1rem 1.375rem 1.25rem; }
}
@media (prefers-reduced-motion: reduce) {
    * { transition-duration: .01ms !important; animation-duration: .01ms !important; }
}
</style>
</head>
<body>
<div class="login-page">
    <div class="login-wrap">
        <a href="<?= base_url() ?>" class="back"><?= icon('arrow-left') ?> Beranda</a>

        <div class="login-card">
            <div class="login-head">
                <div class="login-mark" aria-hidden="true"><?= icon('leaf') ?></div>
                <h1><?= htmlspecialchars($appName) ?></h1>
                <p id="subtitle"><?= $activeTab === 'daftar' ? $subDaftar : $subMasuk ?></p>
            </div>

            <div class="login-body">
                <?php if ($flash && !empty($flash['message'])): ?>
                    <div class="flash flash-<?= $flashClass ?>" role="status">
                        <?= icon($flashIcon) ?>
                        <span><?= htmlspecialchars($flash['message']) ?></span>
                    </div>
                <?php endif; ?>

                <div class="tabs" role="tablist" aria-label="Masuk atau daftar">
                    <button type="button" class="tab" role="tab" id="tab-masuk" data-tab="masuk"
                            aria-controls="panel-masuk" aria-selected="<?= $activeTab === 'masuk' ? 'true' : 'false' ?>"
                            tabindex="<?= $activeTab === 'masuk' ? '0' : '-1' ?>">Masuk</button>
                    <button type="button" class="tab" role="tab" id="tab-daftar" data-tab="daftar"
                            aria-controls="panel-daftar" aria-selected="<?= $activeTab === 'daftar' ? 'true' : 'false' ?>"
                            tabindex="<?= $activeTab === 'daftar' ? '0' : '-1' ?>">Daftar</button>
                </div>

                <!-- ========== PANEL MASUK ========== -->
                <div id="panel-masuk" role="tabpanel" aria-labelledby="tab-masuk"<?= $activeTab === 'masuk' ? '' : ' hidden' ?>>
                    <?php if ($errMasuk): ?>
                        <div class="flash flash-error" role="alert" id="login-error">
                            <?= icon('x-circle') ?>
                            <span><?= htmlspecialchars($error) ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?= base_url('login') ?>">
                        <div class="field">
                            <label for="no_hp" class="label">Nomor HP</label>
                            <div class="control">
                                <span class="ico"><?= icon('phone') ?></span>
                                <input
                                    type="text"
                                    id="no_hp"
                                    name="no_hp"
                                    class="input<?= $errMasuk ? ' is-invalid' : '' ?>"
                                    placeholder="08xxxxxxxxxx"
                                    value="<?= $activeTab === 'masuk' ? htmlspecialchars($oldNoHp) : '' ?>"
                                    required
                                    inputmode="tel"
                                    autocomplete="tel"
                                    <?= $errMasuk ? 'aria-invalid="true" aria-describedby="login-error"' : '' ?>
                                >
                            </div>
                        </div>

                        <div class="field">
                            <label for="password" class="label">Kata Sandi</label>
                            <div class="control">
                                <span class="ico"><?= icon('lock') ?></span>
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    class="input has-toggle<?= $errMasuk ? ' is-invalid' : '' ?>"
                                    placeholder="Masukkan kata sandi"
                                    required
                                    autocomplete="current-password"
                                    <?= $errMasuk ? 'aria-invalid="true" aria-describedby="login-error"' : '' ?>
                                >
                                <button type="button" class="toggle" data-toggle="password" aria-pressed="false" aria-label="Tampilkan kata sandi" aria-controls="password">
                                    <span class="i-on"><?= icon('eye') ?></span>
                                    <span class="i-off"><?= icon('eye-off') ?></span>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn-primary">Masuk</button>
                    </form>

                    <p class="switch">Belum punya akun? <button type="button" data-go="daftar">Daftar di sini</button></p>
                </div>

                <!-- ========== PANEL DAFTAR ========== -->
                <div id="panel-daftar" role="tabpanel" aria-labelledby="tab-daftar"<?= $activeTab === 'daftar' ? '' : ' hidden' ?>>
                    <?php if ($errDaftar): ?>
                        <div class="flash flash-error" role="alert" id="register-error">
                            <?= icon('x-circle') ?>
                            <span><?= htmlspecialchars($error) ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?= base_url('register') ?>">
                        <div class="field">
                            <label for="reg_nama" class="label">Nama Lengkap</label>
                            <div class="control">
                                <span class="ico"><?= icon('user') ?></span>
                                <input type="text" id="reg_nama" name="nama" class="input"
                                       placeholder="Masukkan nama lengkap"
                                       value="<?= $activeTab === 'daftar' ? htmlspecialchars($oldNama) : '' ?>"
                                       required autocomplete="name">
                            </div>
                        </div>

                        <div class="field">
                            <label for="reg_no_hp" class="label">Nomor HP</label>
                            <div class="control">
                                <span class="ico"><?= icon('phone') ?></span>
                                <input type="text" id="reg_no_hp" name="no_hp" class="input"
                                       placeholder="08xxxxxxxxxx"
                                       value="<?= $activeTab === 'daftar' ? htmlspecialchars($oldNoHp) : '' ?>"
                                       required inputmode="tel" autocomplete="tel">
                            </div>
                        </div>

                        <div class="field">
                            <label for="reg_email" class="label">Email <span class="opt">(opsional)</span></label>
                            <div class="control">
                                <span class="ico"><?= icon('mail') ?></span>
                                <input type="email" id="reg_email" name="email" class="input"
                                       placeholder="nama@email.com"
                                       value="<?= $activeTab === 'daftar' ? htmlspecialchars($oldEmail) : '' ?>"
                                       autocomplete="email">
                            </div>
                        </div>

                        <div class="field">
                            <label for="reg_password" class="label">Kata Sandi</label>
                            <div class="control">
                                <span class="ico"><?= icon('lock') ?></span>
                                <input type="password" id="reg_password" name="password" class="input has-toggle"
                                       placeholder="Minimal 6 karakter" required autocomplete="new-password">
                                <button type="button" class="toggle" data-toggle="reg_password" aria-pressed="false" aria-label="Tampilkan kata sandi" aria-controls="reg_password">
                                    <span class="i-on"><?= icon('eye') ?></span>
                                    <span class="i-off"><?= icon('eye-off') ?></span>
                                </button>
                            </div>
                        </div>

                        <div class="field">
                            <label for="reg_password_confirmation" class="label">Konfirmasi Kata Sandi</label>
                            <div class="control">
                                <span class="ico"><?= icon('lock') ?></span>
                                <input type="password" id="reg_password_confirmation" name="password_confirmation" class="input has-toggle"
                                       placeholder="Ulangi kata sandi" required autocomplete="new-password">
                                <button type="button" class="toggle" data-toggle="reg_password_confirmation" aria-pressed="false" aria-label="Tampilkan kata sandi" aria-controls="reg_password_confirmation">
                                    <span class="i-on"><?= icon('eye') ?></span>
                                    <span class="i-off"><?= icon('eye-off') ?></span>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn-primary">Daftar</button>
                    </form>

                    <p class="switch">Sudah punya akun? <button type="button" data-go="masuk">Masuk di sini</button></p>
                </div>
            </div>

            <div class="login-foot">
                &copy; <?= date('Y') ?> <?= htmlspecialchars($appName) ?>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var subtitles = {
        masuk: <?= json_encode($subMasuk) ?>,
        daftar: <?= json_encode($subDaftar) ?>
    };
    var tabs = document.querySelectorAll('.tab');
    var panels = {
        masuk: document.getElementById('panel-masuk'),
        daftar: document.getElementById('panel-daftar')
    };
    var subtitle = document.getElementById('subtitle');
    var current = <?= json_encode($activeTab) ?>;

    function show(name, focusFirst) {
        if (!panels[name]) { return; }
        current = name;
        for (var i = 0; i < tabs.length; i++) {
            var on = tabs[i].getAttribute('data-tab') === name;
            tabs[i].setAttribute('aria-selected', on ? 'true' : 'false');
            tabs[i].setAttribute('tabindex', on ? '0' : '-1');
        }
        panels.masuk.hidden = name !== 'masuk';
        panels.daftar.hidden = name !== 'daftar';
        subtitle.textContent = subtitles[name];
        try { history.replaceState(null, '', name === 'daftar' ? '#daftar' : location.pathname + location.search); } catch (e) {}
        if (focusFirst) {
            var first = panels[name].querySelector('input');
            if (first) { first.focus(); }
        }
    }

    for (var i = 0; i < tabs.length; i++) {
        tabs[i].addEventListener('click', function () { show(this.getAttribute('data-tab'), false); });
        tabs[i].addEventListener('keydown', function (e) {
            if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
                var next = current === 'masuk' ? 'daftar' : 'masuk';
                show(next, false);
                document.getElementById('tab-' + next).focus();
            }
        });
    }

    var gos = document.querySelectorAll('[data-go]');
    for (var j = 0; j < gos.length; j++) {
        gos[j].addEventListener('click', function () { show(this.getAttribute('data-go'), true); });
    }

    /* Tampilkan / sembunyikan kata sandi */
    var toggles = document.querySelectorAll('[data-toggle]');
    for (var k = 0; k < toggles.length; k++) {
        toggles[k].addEventListener('click', function () {
            var input = document.getElementById(this.getAttribute('data-toggle'));
            if (!input) { return; }
            var reveal = input.type === 'password';
            input.type = reveal ? 'text' : 'password';
            this.setAttribute('aria-pressed', reveal ? 'true' : 'false');
            this.setAttribute('aria-label', reveal ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
        });
    }

    /* Tautan seperti /login#daftar langsung membuka tab Daftar */
    if (location.hash === '#daftar' && current !== 'daftar') { show('daftar', false); }

    /* Fokus awal ke kolom pertama, hanya jika tidak ada pesan error */
    var firstInput = panels[current].querySelector('input');
    if (firstInput && !document.querySelector('[role="alert"]')) { firstInput.focus(); }
})();
</script>
</body>
</html>