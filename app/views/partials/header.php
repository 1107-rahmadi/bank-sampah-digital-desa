<?php
/**
 * Header / navbar publik
 * Compatible dengan PHP 7.3+
 *
 * Opsional: set $navActive = 'beranda' | 'tentang' | 'login' di view sebelum include
 * untuk menandai menu aktif. Jika tidak diset, dideteksi dari URL.
 */

if (!isset($navActive)) {
    $navPath = trim((string) parse_url(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/', PHP_URL_PATH), '/');
    $navBase = trim((string) parse_url(base_url(), PHP_URL_PATH), '/');
    if ($navBase !== '' && strpos($navPath, $navBase) === 0) {
        $navPath = trim(substr($navPath, strlen($navBase)), '/');
    }
    $navSegment = explode('/', $navPath)[0];
    if ($navSegment === 'about') {
        $navActive = 'tentang';
    } elseif ($navSegment === 'login') {
        $navActive = 'login';
    } else {
        $navActive = 'beranda';
    }
}
?>
<style>
.site-header {
    position: sticky; top: 0; z-index: 50;
    background: rgba(255,255,255,.94);
    -webkit-backdrop-filter: saturate(1.4) blur(10px);
    backdrop-filter: saturate(1.4) blur(10px);
    border-bottom: 1px solid var(--line, #e6ece5);
    color: var(--ink, #17231a); padding: 0;
    font-family: 'Plus Jakarta Sans', 'Segoe UI', system-ui, -apple-system, sans-serif;
}
.site-header svg { display: block; }
.site-header .nav-bar {
    max-width: 1040px; width: 100%; margin: 0 auto; padding: 0 1.75rem;
    min-height: 68px; display: flex; align-items: center; justify-content: space-between; gap: 1rem;
}
.site-header .brand {
    display: inline-flex; align-items: center; gap: .75rem; min-height: 44px;
    font-size: 1.0625rem; font-weight: 800; letter-spacing: -.02em;
    color: var(--g-900, #0f3d14); text-decoration: none;
}
.site-header .brand-mark {
    width: 42px; height: 42px; border-radius: 14px; flex: none; color: #fff;
    background: linear-gradient(135deg, var(--g-600, #43a047), var(--g-800, #1b5e20));
    display: flex; align-items: center; justify-content: center;
}
.site-header .brand-mark svg { width: 22px; height: 22px; }

.site-header .nav-links { display: flex; align-items: center; gap: .375rem; margin: 0; padding: 0; list-style: none; }
.site-header .nav-links a {
    display: inline-flex; align-items: center; min-height: 44px; padding: 0 1rem;
    border-radius: 12px; font-size: 1rem; font-weight: 600;
    color: var(--ink-2, #3b4a3e); text-decoration: none;
    transition: background .18s ease, color .18s ease;
}
.site-header .nav-links a:hover { background: var(--g-50, #f4faf4); color: var(--g-800, #1b5e20); }
.site-header .nav-links a.active { background: var(--g-100, #e8f5e9); color: var(--g-800, #1b5e20); }
.site-header .nav-links a.nav-cta {
    margin-left: .5rem; padding: 0 1.5rem; min-height: 46px; color: #fff; font-weight: 700;
    background: linear-gradient(135deg, var(--g-600, #43a047), var(--g-800, #1b5e20));
    box-shadow: 0 6px 14px rgba(27,94,32,.2);
    transition: transform .18s ease, box-shadow .18s ease;
}
.site-header .nav-links a.nav-cta:hover { color: #fff; background: linear-gradient(135deg, var(--g-600, #43a047), var(--g-800, #1b5e20)); transform: translateY(-1px); box-shadow: 0 10px 18px rgba(27,94,32,.26); }

.site-header .nav-toggle {
    display: none; width: 46px; height: 46px; padding: 0; cursor: pointer;
    border: 1px solid var(--line, #e6ece5); border-radius: 14px; background: #fff;
    color: var(--g-800, #1b5e20); align-items: center; justify-content: center;
}
.site-header .nav-toggle:hover { background: var(--g-50, #f4faf4); }
.site-header .nav-toggle svg { width: 22px; height: 22px; }
.site-header .nav-toggle .ico-close { display: none; }
.site-header .nav-toggle[aria-expanded="true"] .ico-open { display: none; }
.site-header .nav-toggle[aria-expanded="true"] .ico-close { display: block; }

.site-header a:focus-visible,
.site-header button:focus-visible { outline: 3px solid var(--g-600, #43a047); outline-offset: 2px; }

@media (max-width: 720px) {
    .site-header .nav-bar { padding: 0 1rem; }
    .site-header .nav-toggle { display: inline-flex; }
    .site-header .nav-links {
        display: none; position: absolute; left: 0; right: 0; top: 100%;
        flex-direction: column; align-items: stretch; gap: .25rem;
        padding: .75rem 1rem 1rem; background: #fff;
        border-bottom: 1px solid var(--line, #e6ece5);
        box-shadow: 0 16px 28px rgba(15,61,20,.12);
    }
    .site-header .nav-links.open { display: flex; }
    .site-header .nav-links a { min-height: 50px; }
    .site-header .nav-links a.nav-cta { margin: .5rem 0 0; justify-content: center; min-height: 52px; }
}
@media (prefers-reduced-motion: reduce) {
    .site-header * { transition-duration: .01ms !important; }
}
</style>

<header class="site-header">
    <div class="nav-bar">
        <a href="<?= base_url() ?>" class="brand">
            <span class="brand-mark">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/></svg>
            </span>
            <span><?= APP_NAME ?></span>
        </a>

        <button type="button" class="nav-toggle" id="navToggle" aria-expanded="false" aria-controls="navLinks" aria-label="Buka menu">
            <svg class="ico-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            <svg class="ico-close" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>

        <nav aria-label="Menu utama">
            <ul class="nav-links" id="navLinks">
                <li><a href="<?= base_url() ?>"<?= $navActive === 'beranda' ? ' class="active" aria-current="page"' : '' ?>>Beranda</a></li>
                <li><a href="<?= base_url('about') ?>"<?= $navActive === 'tentang' ? ' class="active" aria-current="page"' : '' ?>>Tentang</a></li>
                <li><a href="<?= base_url('login') ?>" class="nav-cta"<?= $navActive === 'login' ? ' aria-current="page"' : '' ?>>Masuk</a></li>
            </ul>
        </nav>
    </div>
</header>

<script>
(function () {
    var btn = document.getElementById('navToggle');
    var menu = document.getElementById('navLinks');
    if (!btn || !menu) { return; }

    function setOpen(open) {
        menu.classList.toggle('open', open);
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        btn.setAttribute('aria-label', open ? 'Tutup menu' : 'Buka menu');
    }

    btn.addEventListener('click', function () {
        setOpen(btn.getAttribute('aria-expanded') !== 'true');
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && btn.getAttribute('aria-expanded') === 'true') {
            setOpen(false);
            btn.focus();
        }
    });
    document.addEventListener('click', function (e) {
        if (btn.getAttribute('aria-expanded') === 'true' && !menu.contains(e.target) && !btn.contains(e.target)) {
            setOpen(false);
        }
    });
    window.addEventListener('resize', function () {
        if (window.innerWidth > 720) { setOpen(false); }
    });
})();
</script>