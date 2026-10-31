<?php
/**
 * Layout utama publik (memuat partial header & footer)
 * Compatible dengan PHP 7.3+
 *
 * Variabel opsional dari controller:
 * - $pageTitle : judul halaman
 * - $content   : HTML isi halaman
 * - $navActive : 'beranda' | 'tentang' | 'login' (jika kosong, header
 *                menentukan menu aktif otomatis dari URL)
 */

/* ASUMSI: partial ada di APP_PATH/views/partials. Jika APP_PATH belum
   didefinisikan, dipakai VIEW_PATH/partials sebagai cadangan. */
$partialDir = defined('APP_PATH')
    ? APP_PATH . '/views/partials'
    : VIEW_PATH . '/partials';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1b5e20">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : '' ?><?= APP_NAME ?></title>
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
        --ease: cubic-bezier(0.4, 0, 0.2, 1);
    }
    html { -webkit-text-size-adjust: 100%; }
    body {
        margin: 0; min-height: 100vh; display: flex; flex-direction: column;
        font-family: 'Plus Jakarta Sans', 'Segoe UI', system-ui, -apple-system, sans-serif;
        font-size: 16px; line-height: 1.55;
        color: var(--ink-2); background: var(--paper);
        -webkit-font-smoothing: antialiased;
    }
    .app-main { flex: 1 0 auto; padding: 1.5rem 0 3.5rem; background: var(--paper); }
    .app-main .container { max-width: 1040px; width: 100%; margin: 0 auto; padding: 0 1.75rem; }
    .app-main h2 { font-size: 1.375rem; font-weight: 700; color: var(--ink); letter-spacing: -.02em; margin: 0 0 .5rem; }
    .app-main p { margin: 0 0 1rem; }
    .app-main :focus-visible { outline: 3px solid var(--g-600); outline-offset: 2px; border-radius: 6px; }

    @media (max-width: 720px) {
        .app-main { padding: 1rem 0 2.5rem; }
        .app-main .container { padding: 0 1rem; }
    }
    @media (prefers-reduced-motion: reduce) {
        * { transition-duration: .01ms !important; animation-duration: .01ms !important; }
    }
    </style>
</head>
<body>
    <?php include $partialDir . '/header.php'; ?>

    <main class="app-main">
        <div class="container">
            <?php if (isset($content)): ?>
                <?= $content ?>
            <?php else: ?>
                <h2>Selamat Datang</h2>
                <p>Sistem Bank Sampah Digital Desa sedang dalam pengembangan.</p>
            <?php endif; ?>
        </div>
    </main>

    <?php include $partialDir . '/footer.php'; ?>

    <script src="<?= base_url('assets/js/app.js') ?>"></script>
</body>
</html>