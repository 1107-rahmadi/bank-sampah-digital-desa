<?php
/**
 * Tentang Kami
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
            'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
            'target' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>',
            'check-circle' => '<path d="M22 11.1V12a10 10 0 1 1-5.9-9.1"/><polyline points="22 4 12 14.01 9 11.01"/>',
            'users' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
            'trash' => '<path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2m2 0v14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V6"/><path d="M10 11v6M14 11v6"/>',
            'tag' => '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L2 12V2h10l8.6 8.6a2 2 0 0 1 0 2.8Z"/><circle cx="7" cy="7" r="1.5"/>',
            'truck' => '<rect x="1" y="6" width="14" height="11" rx="1.5"/><path d="M15 9h4l3 3v5h-7"/><circle cx="6" cy="18" r="2"/><circle cx="18" cy="18" r="2"/>',
            'wallet' => '<path d="M19 7V6a2 2 0 0 0-2-2H5a2 2 0 0 0 0 4h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6"/><path d="M16 12h.01"/>',
            'banknote' => '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/>',
            'chart' => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
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
    <title>Tentang Kami - <?= APP_NAME ?></title>
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
    .app-main .container { max-width: 880px; width: 100%; margin: 0 auto; padding: 0 1.75rem; }
    .app-main section + section { margin-top: 2.5rem; }
    .app-main h2.sec-title {
        font-size: 1.375rem; font-weight: 700; color: var(--ink);
        letter-spacing: -.02em; margin: 0 0 1rem;
    }

    /* ---------- Hero ---------- */
    .app-main .about-section {
        position: relative; overflow: hidden; color: #fff; text-align: left;
        border-radius: 28px; padding: 2.5rem 2.5rem;
        background: linear-gradient(135deg, #43a047 0%, #2e7d32 45%, #14501a 100%);
        box-shadow: 0 14px 32px rgba(27,94,32,.22);
    }
    .app-main .about-section::before,
    .app-main .about-section::after {
        content: ""; position: absolute; border-radius: 50%; pointer-events: none;
        background: rgba(255,255,255,.07);
    }
    .app-main .about-section::before { width: 320px; height: 320px; right: -110px; top: -150px; }
    .app-main .about-section::after { width: 210px; height: 210px; right: 100px; bottom: -150px; }
    .app-main .about-section > * { position: relative; z-index: 1; }
    .about-mark {
        width: 52px; height: 52px; border-radius: 16px; margin-bottom: 1.25rem;
        background: rgba(255,255,255,.18);
        display: flex; align-items: center; justify-content: center;
    }
    .about-mark svg { width: 26px; height: 26px; }
    .app-main .about-section h1 {
        font-size: 2rem; font-weight: 800; color: #fff;
        letter-spacing: -.03em; line-height: 1.2; margin: 0;
    }
    .app-main .about-section .lead {
        max-width: 56ch; margin: .875rem 0 0; font-size: 1.0625rem;
        color: rgba(255,255,255,.93);
    }

    /* ---------- Visi & Misi ---------- */
    .vm-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
    .app-main .card {
        background: var(--card); border: 1px solid var(--line); border-radius: 20px;
        padding: 1.5rem; box-shadow: none; margin: 0;
    }
    .card-ico {
        width: 46px; height: 46px; border-radius: 50%; margin-bottom: .875rem;
        background: var(--g-100); color: var(--g-800);
        display: flex; align-items: center; justify-content: center;
    }
    .card-ico svg { width: 22px; height: 22px; }
    .app-main .card h3 { font-size: 1.125rem; font-weight: 700; color: var(--ink); margin: 0 0 .5rem; }
    .app-main .card p { font-size: 1rem; color: var(--ink-2); margin: 0; }
    .app-main .card ul { list-style: none; margin: 0; padding: 0; }
    .app-main .card li {
        position: relative; padding: .375rem 0 .375rem 1.5rem; font-size: 1rem; color: var(--ink-2);
    }
    .app-main .card li::before {
        content: ""; position: absolute; left: 0; top: .95rem;
        width: 8px; height: 8px; border-radius: 50%; background: var(--g-600);
    }

    /* ---------- Daftar fitur ---------- */
    .fitur-list {
        list-style: none; margin: 0; padding: .25rem 1.25rem;
        background: var(--card); border: 1px solid var(--line); border-radius: 24px;
    }
    .fitur-list li {
        display: flex; align-items: center; gap: 1rem;
        padding: .875rem 0; border-bottom: 1px solid var(--line);
    }
    .fitur-list li:last-child { border-bottom: 0; }
    .fitur-ico {
        width: 46px; height: 46px; border-radius: 50%; flex-shrink: 0;
        background: var(--g-100); color: var(--g-700);
        display: flex; align-items: center; justify-content: center;
    }
    .fitur-ico svg { width: 21px; height: 21px; }
    .fitur-text { min-width: 0; color: var(--ink-3); font-size: .9375rem; }
    .fitur-text strong { display: block; font-size: 1rem; font-weight: 600; color: var(--ink); }

    /* ---------- Tombol ---------- */
    .app-main .about-actions { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 2rem; }
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
    .app-main .btn-outline { color: var(--g-800); background: transparent; border-color: var(--g-200); }
    .app-main .btn-outline:hover { background: var(--g-100); }

    /* ---------- Responsif ---------- */
    @media (max-width: 720px) {
        .app-main { padding: 1rem 0 2.5rem; }
        .app-main .container { padding: 0 1rem; }
        .app-main section + section { margin-top: 2rem; }
        .app-main .about-section { padding: 1.75rem 1.375rem; border-radius: 24px; }
        .app-main .about-section h1 { font-size: 1.625rem; }
        .app-main .about-section .lead { font-size: 1rem; }
        .vm-grid { grid-template-columns: minmax(0, 1fr); }
        .fitur-list { padding: .25rem 1rem; border-radius: 20px; }
        .fitur-list li { gap: .75rem; }
        .app-main .about-actions .btn { width: 100%; }
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
            <section class="about-section">
                <div class="about-mark"><?= icon('leaf') ?></div>
                <h1>Tentang <?= APP_NAME ?></h1>
                <p class="lead">
                    Sistem informasi digital untuk mengelola Bank Sampah di tingkat desa,
                    membantu masyarakat dalam mengelola sampah bernilai ekonomi.
                </p>
            </section>

            <section class="features">
                <h2 class="sec-title">Visi &amp; Misi</h2>
                <div class="vm-grid">
                    <div class="card">
                        <div class="card-ico"><?= icon('eye') ?></div>
                        <h3>Visi</h3>
                        <p>Menjadi solusi pengelolaan sampah berbasis komunitas yang transparan,
                        efisien, dan memberdayakan masyarakat desa.</p>
                    </div>
                    <div class="card">
                        <div class="card-ico"><?= icon('target') ?></div>
                        <h3>Misi</h3>
                        <ul>
                            <li>Menyediakan sistem pencatatan transaksi yang transparan</li>
                            <li>Memudahkan masyarakat dalam menyetorkan sampah</li>
                            <li>Menyediakan laporan keuangan yang akurat</li>
                            <li>Meningkatkan nilai ekonomi dari sampah</li>
                        </ul>
                    </div>
                </div>
            </section>

            <section>
                <h2 class="sec-title">Fitur Utama</h2>
                <ul class="fitur-list">
                    <li>
                        <span class="fitur-ico"><?= icon('users') ?></span>
                        <span class="fitur-text"><strong>Manajemen Nasabah</strong> Pendaftaran dan pengelolaan data anggota</span>
                    </li>
                    <li>
                        <span class="fitur-ico"><?= icon('trash') ?></span>
                        <span class="fitur-text"><strong>Transaksi Setoran</strong> Pencatatan setoran sampah dengan timbangan</span>
                    </li>
                    <li>
                        <span class="fitur-ico"><?= icon('tag') ?></span>
                        <span class="fitur-text"><strong>Manajemen Harga</strong> Pengaturan harga beli per kategori sampah</span>
                    </li>
                    <li>
                        <span class="fitur-ico"><?= icon('truck') ?></span>
                        <span class="fitur-text"><strong>Transaksi Penjualan</strong> Penjualan ke pengepul</span>
                    </li>
                    <li>
                        <span class="fitur-ico"><?= icon('wallet') ?></span>
                        <span class="fitur-text"><strong>Tabungan Nasabah</strong> Saldo otomatis dari setoran sampah</span>
                    </li>
                    <li>
                        <span class="fitur-ico"><?= icon('banknote') ?></span>
                        <span class="fitur-text"><strong>Pengajuan Pencairan</strong> Proses pencairan saldo</span>
                    </li>
                    <li>
                        <span class="fitur-ico"><?= icon('chart') ?></span>
                        <span class="fitur-text"><strong>Laporan</strong> Laporan keuangan dan operasional</span>
                    </li>
                </ul>
            </section>

            <div class="about-actions">
                <a href="<?= base_url('login') ?>" class="btn btn-primary">Masuk ke Sistem</a>
                <a href="<?= base_url() ?>" class="btn btn-outline">Kembali</a>
            </div>
        </div>
    </main>

    <?php include APP_PATH . '/views/partials/footer.php'; ?>

    <script src="<?= base_url('assets/js/app.js') ?>"></script>
</body>
</html>