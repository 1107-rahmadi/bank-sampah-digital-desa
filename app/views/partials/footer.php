<?php
/**
 * Footer publik
 * Compatible dengan PHP 7.3+
 */
?>
<style>
.site-footer {
    position: relative; overflow: hidden; margin-top: 0; padding: 3rem 0 0;
    color: rgba(255,255,255,.82);
    background: linear-gradient(135deg, #1b5e20 0%, #14501a 55%, #0f3d14 100%);
    font-family: 'Plus Jakarta Sans', 'Segoe UI', system-ui, -apple-system, sans-serif;
    font-size: 1rem; line-height: 1.55;
}
.site-footer::before {
    content: ""; position: absolute; width: 320px; height: 320px; border-radius: 50%;
    right: -120px; top: -160px; background: rgba(255,255,255,.06); pointer-events: none;
}
.site-footer svg { display: block; }
.site-footer .foot-inner {
    position: relative; max-width: 1040px; width: 100%; margin: 0 auto; padding: 0 1.75rem;
}
.site-footer .foot-top {
    display: grid; grid-template-columns: minmax(0, 1.6fr) repeat(2, minmax(0, 1fr)); gap: 2rem;
}
.site-footer .foot-brand { display: inline-flex; align-items: center; gap: .75rem; color: #fff; font-size: 1.0625rem; font-weight: 800; letter-spacing: -.02em; text-decoration: none; min-height: 44px; }
.site-footer .foot-mark {
    width: 42px; height: 42px; border-radius: 14px; flex: none;
    background: rgba(255,255,255,.16); color: #fff;
    display: flex; align-items: center; justify-content: center;
}
.site-footer .foot-mark svg { width: 22px; height: 22px; }
.site-footer .foot-desc { max-width: 38ch; margin: .75rem 0 0; color: rgba(255,255,255,.78); font-size: .9375rem; }
.site-footer h2.foot-title { margin: 0 0 .5rem; font-size: 1rem; font-weight: 700; color: #fff; letter-spacing: 0; }
.site-footer .foot-links { margin: 0; padding: 0; list-style: none; }
.site-footer .foot-links a {
    display: inline-flex; align-items: center; min-height: 44px;
    color: rgba(255,255,255,.8); text-decoration: none; font-size: .9375rem;
    transition: color .18s ease;
}
.site-footer .foot-links a:hover { color: #fff; text-decoration: underline; text-underline-offset: 4px; }
.site-footer .foot-bottom {
    margin-top: 1.5rem; padding: 1.25rem 0 1.5rem;
    border-top: 1px solid rgba(255,255,255,.16);
    font-size: .875rem; color: rgba(255,255,255,.72);
}
.site-footer a:focus-visible { outline: 3px solid #c8e6c9; outline-offset: 2px; border-radius: 6px; }

@media (max-width: 720px) {
    .site-footer { padding-top: 2.25rem; }
    .site-footer .foot-inner { padding: 0 1rem; }
    .site-footer .foot-top { grid-template-columns: minmax(0, 1fr); gap: 1.25rem; }
}
@media (prefers-reduced-motion: reduce) {
    .site-footer * { transition-duration: .01ms !important; }
}
</style>

<footer class="site-footer">
    <div class="foot-inner">
        <div class="foot-top">
            <div>
                <a href="<?= base_url() ?>" class="foot-brand">
                    <span class="foot-mark">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/></svg>
                    </span>
                    <span><?= APP_NAME ?></span>
                </a>
                <p class="foot-desc">Mendukung pengelolaan sampah berbasis komunitas.</p>
            </div>

            <nav aria-label="Navigasi footer">
                <h2 class="foot-title">Halaman</h2>
                <ul class="foot-links">
                    <li><a href="<?= base_url() ?>">Beranda</a></li>
                    <li><a href="<?= base_url('about') ?>">Tentang</a></li>
                    <li><a href="<?= base_url('login') ?>">Masuk</a></li>
                </ul>
            </nav>

            <nav aria-label="Bantuan dan kebijakan">
                <h2 class="foot-title">Bantuan</h2>
                <ul class="foot-links">
                    <li><a href="#">Hubungi Kami</a></li>
                    <li><a href="#">Kebijakan Privasi</a></li>
                    <li><a href="#">Syarat &amp; Ketentuan</a></li>
                </ul>
            </nav>
        </div>

        <div class="foot-bottom">
            &copy; <?= date('Y') ?> <?= APP_NAME ?>. Hak cipta dilindungi.
        </div>
    </div>
</footer>