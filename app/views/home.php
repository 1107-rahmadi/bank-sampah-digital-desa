<?php
/**
 * Beranda publik
 * Compatible dengan PHP 7.3+
 */

if (!function_exists('icon')) {
    /**
     * Ikon SVG garis yang konsisten (tanpa emoji).
     */
    function icon(string $name, string $class = ''): string
    {
        $paths = [
            'leaf' => '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>',
            'trash' => '<path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2m2 0v14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V6"/><path d="M10 11v6M14 11v6"/>',
            'wallet' => '<path d="M19 7V6a2 2 0 0 0-2-2H5a2 2 0 0 0 0 4h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6"/><path d="M16 12h.01"/>',
            'chart' => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
            'phone' => '<rect x="6" y="2" width="12" height="20" rx="2.5"/><path d="M11 18h2"/>',
            'info' => '<circle cx="12" cy="12" r="9"/><line x1="12" y1="11" x2="12" y2="16"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
        ];

        $body = isset($paths[$name]) ? $paths[$name] : $paths['info'];
        $cls = $class !== '' ? ' class="' . htmlspecialchars($class) . '"' : '';

        return '<svg' . $cls . ' viewBox="0 0 24 24" fill="none" stroke="currentColor"'
            . ' stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"'
            . ' aria-hidden="true" focusable="false">' . $body . '</svg>';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1b5e20">
    <title>Beranda - <?= APP_NAME ?></title>
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
        font-family: 'Plus Jakarta Sans', 'Segoe UI', system-ui, -apple-system, sans-serif;
        font-size: 16px; line-height: 1.55;
        color: var(--ink-2); background: var(--paper);
        -webkit-font-smoothing: antialiased;
    }
    .app-main :focus-visible,
    .app-main a:focus-visible { outline: 3px solid var(--g-600); outline-offset: 2px; border-radius: 6px; }
    .app-main svg { display: block; }

    .app-main { padding: 1.5rem 0 3.5rem; background: var(--paper); }
    .app-main .container { max-width: 1040px; width: 100%; margin: 0 auto; padding: 0 1.75rem; }
    .app-main section + section { margin-top: 2.5rem; }

    /* ---------- Hero ---------- */
    .app-main .hero {
        position: relative; overflow: hidden; color: #fff; text-align: left;
        border-radius: 28px; padding: 3rem 2.5rem;
        background: linear-gradient(135deg, #43a047 0%, #2e7d32 45%, #14501a 100%);
        box-shadow: 0 14px 32px rgba(27,94,32,.22);
    }
    .app-main .hero::before,
    .app-main .hero::after {
        content: ""; position: absolute; border-radius: 50%; pointer-events: none;
        background: rgba(255,255,255,.07);
    }
    .app-main .hero::before { width: 340px; height: 340px; right: -110px; top: -150px; }
    .app-main .hero::after { width: 220px; height: 220px; right: 110px; bottom: -150px; }
    .app-main .hero > * { position: relative; z-index: 1; }
    .hero-mark {
        width: 52px; height: 52px; border-radius: 16px; margin-bottom: 1.25rem;
        background: rgba(255,255,255,.18);
        display: flex; align-items: center; justify-content: center;
    }
    .hero-mark svg { width: 26px; height: 26px; }
    .app-main .hero h1 {
        max-width: 18ch; font-size: 2.25rem; font-weight: 800; color: #fff;
        letter-spacing: -.03em; line-height: 1.15; margin: 0;
    }
    .app-main .hero .lead {
        max-width: 56ch; margin: 1rem 0 0; font-size: 1.0625rem;
        color: rgba(255,255,255,.93);
    }

    /* ---------- Fitur ---------- */
    .app-main .features h2,
    .app-main .cta h2 {
        font-size: 1.375rem; font-weight: 700; color: var(--ink);
        letter-spacing: -.02em; margin: 0 0 1rem;
    }
    .app-main .feature-grid {
        display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem;
    }
    .app-main .feature-card {
        background: var(--card); border: 1px solid var(--line); border-radius: 20px;
        padding: 1.25rem; box-shadow: none;
        transition: transform .2s var(--ease), border-color .2s var(--ease), box-shadow .2s var(--ease);
    }
    .app-main .feature-card:hover {
        transform: translateY(-3px); border-color: var(--g-200);
        box-shadow: 0 10px 24px rgba(27,94,32,.1);
    }
    .feature-ico {
        width: 46px; height: 46px; border-radius: 50%; margin-bottom: .875rem;
        background: var(--g-100); color: var(--g-800);
        display: flex; align-items: center; justify-content: center;
        transition: background .2s var(--ease), color .2s var(--ease);
    }
    .feature-ico svg { width: 22px; height: 22px; }
    .app-main .feature-card:hover .feature-ico { background: var(--g-700); color: #fff; }
    .app-main .feature-card h3 {
        font-size: 1.0625rem; font-weight: 700; color: var(--ink); margin: 0 0 .375rem;
    }
    .app-main .feature-card p { font-size: .9375rem; color: var(--ink-3); margin: 0; }

    /* ---------- Ajakan daftar ---------- */
    .app-main .cta {
        background: var(--card); border: 1px solid var(--line); border-radius: 24px;
        padding: 2rem 2.25rem; text-align: left;
    }
    .app-main .cta h2 { margin-bottom: .375rem; }
    .app-main .cta p { max-width: 56ch; margin: 0; color: var(--ink-3); }
    .app-main .cta-buttons { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 1.25rem; }
    .app-main .btn {
        display: inline-flex; align-items: center; justify-content: center;
        min-height: 52px; padding: 0 1.5rem; border-radius: 14px;
        font-family: inherit; font-size: 1rem; font-weight: 700;
        text-decoration: none; cursor: pointer; border: 1.5px solid transparent;
        transition: transform .18s var(--ease), box-shadow .18s var(--ease), background .18s var(--ease);
    }
    .app-main .btn-primary {
        color: #fff; background: linear-gradient(135deg, var(--g-600), var(--g-800));
        box-shadow: 0 8px 18px rgba(27,94,32,.22);
    }
    .app-main .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 22px rgba(27,94,32,.28); }
    .app-main .btn-outline {
        color: var(--g-800); background: transparent; border-color: var(--g-200);
    }
    .app-main .btn-outline:hover { background: var(--g-100); }

    /* ---------- Responsif ---------- */
    @media (max-width: 1100px) {
        .app-main .feature-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 720px) {
        .app-main { padding: 1rem 0 2.5rem; }
        .app-main .container { padding: 0 1rem; }
        .app-main section + section { margin-top: 2rem; }
        .app-main .hero { padding: 2rem 1.375rem; border-radius: 24px; }
        .app-main .hero h1 { font-size: 1.75rem; }
        .app-main .hero .lead { font-size: 1rem; }
        .app-main .cta { padding: 1.5rem 1.25rem; border-radius: 20px; }
        .app-main .cta-buttons .btn { width: 100%; }
    }
    @media (max-width: 560px) {
        .app-main .feature-grid { grid-template-columns: minmax(0, 1fr); }
    }
    @media (prefers-reduced-motion: reduce) {
        * { transition-duration: .01ms !important; animation-duration: .01ms !important; }
    }
    </style>
</head>
<body>
    <?php include APP_PATH . '/views/partials/header.php'; ?>

    <main class="app-main">
        <div class="container">
            <section class="hero">
                <div class="hero-mark"><?= icon('leaf') ?></div>
                <h1>Selamat Datang di <?= APP_NAME ?></h1>
                <p class="lead">
                    Solusi digital untuk mengelola sampah dan membantu masyarakat desa
                    dalam mengelola sampah bernilai ekonomi.
                </p>
            </section>

            <section class="features">
                <h2>Fitur Utama</h2>
                <div class="feature-grid">
                    <div class="feature-card">
                        <div class="feature-ico"><?= icon('trash') ?></div>
                        <h3>Setor Sampah</h3>
                        <p>Nasabah dapat menyetorkan sampah kering untuk ditimbang dan dikonversi menjadi saldo.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-ico"><?= icon('wallet') ?></div>
                        <h3>Tarik Saldo</h3>
                        <p>Saldo dari hasil setoran sampah dapat ditarik sewaktu-waktu.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-ico"><?= icon('chart') ?></div>
                        <h3>Laporan</h3>
                        <p>Akses laporan keuangan dan operasional secara real-time.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-ico"><?= icon('phone') ?></div>
                        <h3>Mudah Digunakan</h3>
                        <p>Antarmuka yang sederhana dan mudah dipahami oleh semua kalangan.</p>
                    </div>
                </div>
            </section>

            <section class="cta">
                <h2>Mulai Sekarang</h2>
                <p>Daftar sebagai Nasabah dan mulai berkontribusi untuk lingkungan yang lebih bersih.</p>
                <div class="cta-buttons">
                    <a href="#" class="btn btn-primary">Daftar Sekarang</a>
                    <a href="#" class="btn btn-outline">Pelajari Lebih Lanjut</a>
                </div>
            </section>
        </div>
    </main>

    <?php include APP_PATH . '/views/partials/footer.php'; ?>

    <script src="<?= base_url('assets/js/app.js') ?>"></script>
</body>
</html>