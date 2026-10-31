<?php
/**nasabah/partials/notifikasi.php
 * Notifikasi dalam aplikasi untuk semua halaman nasabah:
 * lonceng + panel, pop-up dari atas, dan logika baca/tutup.
 *
 * Compatible dengan PHP 7.3+
 *
 * PEMAKAIAN di setiap halaman nasabah (5 baris):
 *
 *   1. Paling atas file, di dalam blok PHP:
 *        require_once VIEW_PATH . '/nasabah/partials/notifikasi.php';
 *
 *   2. Di <head>, tepat setelah </style> halaman:
 *        <?php notifikasiStyle(); ?>
 *
 *   3. Di dalam <header class="topbar">, sebelum <a class="who">:
 *        <?php notifikasiLonceng(); ?>
 *
 *   4. Tepat setelah include sidebar:
 *        <?php notifikasiPopup(); ?>
 *
 *   5. Sebelum </body>:
 *        <?php notifikasiScript(); ?>
 *
 * Data dimuat sendiri oleh file ini (model Notifikasi + currentUserId()),
 * jadi controller tidak perlu mengirim variabel apa pun.
 *
 * ATURAN POP-UP:
 * Pop-up hanya muncul untuk notifikasi pencairan yang BELUM DIBACA
 * (diajukan, disetujui, ditolak, dicairkan). Begitu pop-up tampil,
 * notifikasi langsung ditandai sudah dibaca, jadi pindah halaman
 * tidak memunculkannya lagi. Notifikasi tipe lain (mis. setoran)
 * hanya masuk ke lonceng.
 *
 * Kebutuhan: base_url(), currentUserId(), MODEL_PATH, model Notifikasi.
 * Semua class CSS memakai awalan "nt-" agar tidak bentrok dengan CSS halaman.
 */

if (!function_exists('notifIkon')) {
    /**
     * Ikon SVG garis (stroke 1.75), berdiri sendiri supaya tidak bergantung
     * pada fungsi icon() milik masing-masing halaman.
     */
    function notifIkon(string $name): string
    {
        $paths = [
            'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/>',
            'x' => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
            'check-circle' => '<path d="M22 11.1V12a10 10 0 1 1-5.9-9.1"/><polyline points="22 4 12 14.01 9 11.01"/>',
            'clock' => '<circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/>',
            'x-circle' => '<circle cx="12" cy="12" r="9"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>',
            'info' => '<circle cx="12" cy="12" r="9"/><line x1="12" y1="11" x2="12" y2="16"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
            'leaf' => '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>',
        ];
        $body = isset($paths[$name]) ? $paths[$name] : $paths['info'];

        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"'
            . ' stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"'
            . ' aria-hidden="true" focusable="false">' . $body . '</svg>';
    }
}

if (!function_exists('notifTampilan')) {
    /**
     * Warna dan ikon notifikasi berdasarkan kolom `tipe`.
     * ASUMSI: tipe memuat kata kunci status, mis. PENCAIRAN_DISETUJUI,
     * PENCAIRAN_DITOLAK, PENCAIRAN_DICAIRKAN, PENCAIRAN_DIAJUKAN, SETORAN.
     *
     * @return array [kelas warna, ikon]
     */
    function notifTampilan($tipe): array
    {
        $t = strtoupper((string) $tipe);
        if (strpos($t, 'DITOLAK') !== false) {
            return ['danger', 'x-circle'];
        }
        if (strpos($t, 'DICAIRKAN') !== false) {
            return ['success', 'check-circle'];
        }
        if (strpos($t, 'DISETUJUI') !== false) {
            return ['info', 'clock'];
        }
        if (strpos($t, 'DIBATALKAN') !== false) {
            return ['neutral', 'x-circle'];
        }
        if (strpos($t, 'SETOR') !== false) {
            return ['success', 'leaf'];
        }
        if (strpos($t, 'DIAJUKAN') !== false) {
            return ['warning', 'clock'];
        }
        return ['info', 'info'];
    }
}

if (!function_exists('notifLayakPopup')) {
    /**
     * Hanya notifikasi pencairan yang tampil sebagai pop-up:
     * saat nasabah mengajukan, dan saat pengelola menyetujui,
     * menolak, atau mencairkan. Tipe lain hanya masuk ke lonceng.
     *
     * @param string|null $tipe
     */
    function notifLayakPopup($tipe): bool
    {
        $t = strtoupper((string) $tipe);
        foreach (['DIAJUKAN', 'DISETUJUI', 'DITOLAK', 'DICAIRKAN'] as $kata) {
            if (strpos($t, $kata) !== false) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('notifWaktuRelatif')) {
    /**
     * Waktu singkat dalam bahasa Indonesia, mis. "5 menit lalu".
     *
     * @param string|null $waktu
     */
    function notifWaktuRelatif($waktu): string
    {
        $t = strtotime((string) $waktu);
        if (!$t) {
            return '';
        }
        $selisih = time() - $t;
        if ($selisih < 60) {
            return 'Baru saja';
        }
        if ($selisih < 3600) {
            return floor($selisih / 60) . ' menit lalu';
        }
        if ($selisih < 86400) {
            return floor($selisih / 3600) . ' jam lalu';
        }
        if ($selisih < 7 * 86400) {
            return floor($selisih / 86400) . ' hari lalu';
        }
        return date('d/m/Y', $t);
    }
}

if (!function_exists('notifData')) {
    /**
     * Muat notifikasi milik nasabah yang sedang login (sekali per permintaan).
     *
     * @return array ['daftar' => array, 'belumDibaca' => int, 'toast' => array|null]
     */
    function notifData(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $daftar = [];
        $belumDibaca = 0;

        try {
            require_once MODEL_PATH . '/Notifikasi.php';
            $userId = (int) currentUserId();
            if ($userId > 0) {
                $model = new Notifikasi();
                $daftar = $model->terbaru($userId, 8);
                $belumDibaca = $model->hitungBelumDibaca($userId);
            }
        } catch (\Throwable $e) {
            error_log('[notifikasi] gagal memuat: ' . $e->getMessage());
            $daftar = [];
            $belumDibaca = 0;
        }

        // Pop-up: notifikasi pencairan belum dibaca yang paling baru, dan
        // hanya SEKALI per notifikasi. Yang sudah pernah ditampilkan dicatat
        // di session, jadi pindah halaman tidak memunculkannya lagi, walau
        // route "tandai dibaca" gagal atau belum ada.
        $toast = null;
        $sudahTampil = [];
        $pakaiSession = session_status() === PHP_SESSION_ACTIVE;
        if ($pakaiSession) {
            $sudahTampil = isset($_SESSION['nt_popup_tampil']) && is_array($_SESSION['nt_popup_tampil'])
                ? $_SESSION['nt_popup_tampil']
                : [];
        }
        foreach ($daftar as $n) {
            if (!empty($n['dibaca_at']) || !notifLayakPopup($n['tipe'] ?? '')) {
                continue;
            }
            $kunci = isset($n['id'])
                ? (string) $n['id']
                : md5((string) ($n['judul'] ?? '') . '|' . (string) ($n['created_at'] ?? ''));
            if (in_array($kunci, $sudahTampil, true)) {
                continue;
            }
            $toast = $n;
            if ($pakaiSession) {
                $sudahTampil[] = $kunci;
                $_SESSION['nt_popup_tampil'] = array_slice($sudahTampil, -100);
            }
            break;
        }

        $cache = ['daftar' => $daftar, 'belumDibaca' => $belumDibaca, 'toast' => $toast];
        return $cache;
    }
}

if (!function_exists('notifikasiStyle')) {
    /** CSS notifikasi. Panggil di <head>, setelah <style> halaman. */
    function notifikasiStyle(): void
    {
        ?>
<style>
/* ---------- Notifikasi (partials/notifikasi.php) ---------- */
.nt-vh { position: absolute; width: 1px; height: 1px; margin: -1px; padding: 0; overflow: hidden; clip: rect(0,0,0,0); border: 0; }
.nt-wrap { position: relative; margin-left: auto; flex-shrink: 0; }
.nt-wrap + .who { margin-left: 0; }
.nt-bell {
    position: relative; width: 46px; height: 46px; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    border: 1px solid var(--line, #e6ece5); border-radius: 14px;
    background: var(--card, #ffffff); color: var(--g-800, #1b5e20);
    font-family: inherit; transition: background .18s var(--ease, ease);
}
.nt-bell:hover, .nt-bell[aria-expanded="true"] { background: var(--g-100, #e8f5e9); }
.nt-bell svg { display: block; width: 22px; height: 22px; }
.nt-badge {
    position: absolute; top: -6px; right: -6px;
    min-width: 22px; height: 22px; padding: 0 5px; border-radius: 999px;
    display: flex; align-items: center; justify-content: center;
    background: var(--rose, #b3261e); color: #fff; border: 2px solid var(--paper, #f7faf6);
    font-size: .75rem; font-weight: 700; line-height: 1;
}
.nt-panel {
    position: absolute; right: 0; top: calc(100% + 8px);
    width: 380px; max-width: calc(100vw - 2rem);
    background: var(--card, #ffffff); border: 1px solid var(--line, #e6ece5); border-radius: 20px;
    box-shadow: 0 18px 40px rgba(15,61,20,.16); overflow: hidden; z-index: 70;
}
.nt-panel[hidden], .nt-toast[hidden] { display: none !important; }
.nt-head {
    display: flex; align-items: center; justify-content: space-between; gap: .75rem;
    padding: 1rem 1.25rem; border-bottom: 1px solid var(--line, #e6ece5);
    font-size: 1.0625rem; font-weight: 700; color: var(--ink, #17231a);
}
.nt-count {
    padding: .125rem .625rem; border-radius: 999px;
    background: var(--g-100, #e8f5e9); color: var(--g-800, #1b5e20);
    font-size: .8125rem; font-weight: 600;
}
.nt-list { max-height: min(60vh, 440px); overflow-y: auto; }
.nt-item { display: flex; gap: .75rem; padding: .875rem 1.25rem; border-bottom: 1px solid var(--line, #e6ece5); }
.nt-item:last-child { border-bottom: 0; }
.nt-item.baru { background: var(--g-50, #f4faf4); }
.nt-ico {
    width: 40px; height: 40px; border-radius: 50%; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
}
.nt-ico svg { display: block; width: 19px; height: 19px; }
.nt-ico.success { background: var(--g-100, #e8f5e9); color: var(--g-700, #2e7d32); }
.nt-ico.warning { background: var(--amber-bg, #fff1d6); color: var(--amber, #a85a00); }
.nt-ico.info { background: var(--sky-bg, #e2edff); color: var(--sky, #1a56c4); }
.nt-ico.danger { background: var(--rose-bg, #fde8e6); color: var(--rose, #b3261e); }
.nt-ico.neutral { background: #eef1ec; color: var(--ink-3, #5b6a5e); }
.nt-body { flex: 1; min-width: 0; }
.nt-judul { display: block; font-size: .9375rem; font-weight: 700; color: var(--ink, #17231a); }
.nt-isi { display: block; font-size: .9375rem; color: var(--ink-2, #3b4a3e); margin-top: .125rem; }
.nt-waktu { display: block; font-size: .8125rem; color: var(--ink-3, #5b6a5e); margin-top: .25rem; }
.nt-empty { text-align: center; padding: 2rem 1.25rem; }
.nt-empty .nt-ico { width: 52px; height: 52px; margin: 0 auto .875rem; background: var(--g-100, #e8f5e9); color: var(--g-700, #2e7d32); }
.nt-empty .nt-ico svg { width: 24px; height: 24px; }
.nt-empty h3 { font-size: 1rem; font-weight: 700; color: var(--ink, #17231a); }
.nt-empty p { font-size: .9375rem; color: var(--ink-3, #5b6a5e); margin-top: .25rem; }

/* Pop-up dari atas */
.nt-toast {
    position: fixed; z-index: 120; left: 50%;
    top: calc(env(safe-area-inset-top, 0px) + 12px);
    width: min(520px, calc(100vw - 2rem));
    display: flex; align-items: flex-start; gap: .25rem;
    padding: .75rem .5rem .75rem .875rem;
    background: var(--card, #ffffff); border: 1px solid var(--line, #e6ece5);
    border-left: 5px solid var(--sky, #1a56c4); border-radius: 18px;
    box-shadow: 0 18px 40px rgba(15,61,20,.22);
    transform: translate(-50%, -160%); opacity: 0; visibility: hidden;
    transition: transform .35s var(--ease, ease), opacity .25s var(--ease, ease), visibility .35s var(--ease, ease);
}
.nt-toast.show { transform: translate(-50%, 0); opacity: 1; visibility: visible; }
.nt-toast.success { border-left-color: var(--g-600, #43a047); }
.nt-toast.warning { border-left-color: var(--amber, #a85a00); }
.nt-toast.danger { border-left-color: var(--rose, #b3261e); }
.nt-toast.neutral { border-left-color: var(--ink-3, #5b6a5e); }
.nt-toast-main {
    flex: 1; min-width: 0; display: flex; align-items: flex-start; gap: .75rem;
    padding: 0; border: 0; background: none; text-align: left; cursor: pointer;
    font: inherit; color: inherit; border-radius: 12px;
}
.nt-toast-main .nt-ico { width: 42px; height: 42px; }
.nt-toast-text { flex: 1; min-width: 0; }
.nt-toast-judul { display: block; font-size: 1rem; font-weight: 700; color: var(--ink, #17231a); }
.nt-toast-isi { display: block; font-size: .9375rem; color: var(--ink-2, #3b4a3e); margin-top: .125rem; }
.nt-toast-more { display: block; font-size: .875rem; font-weight: 700; color: var(--g-700, #2e7d32); margin-top: .375rem; }
.nt-toast-close {
    width: 44px; height: 44px; flex-shrink: 0; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    border: 0; border-radius: 14px; background: transparent; color: var(--ink-3, #5b6a5e);
    transition: background .18s var(--ease, ease);
}
.nt-toast-close:hover { background: var(--g-100, #e8f5e9); }
.nt-toast-close svg { display: block; width: 20px; height: 20px; }

@media (max-width: 720px) {
    /* Panel selebar layar, menempel di bawah topbar */
    .nt-wrap { position: static; }
    .nt-panel { left: 1rem; right: 1rem; width: auto; max-width: none; top: calc(100% + 4px); }
    .nt-item { padding: .875rem 1rem; }
}
@media (prefers-reduced-motion: reduce) {
    .nt-toast, .nt-bell, .nt-toast-close { transition-duration: .01ms !important; }
}
</style>
<?php
    }
}

if (!function_exists('notifikasiLonceng')) {
    /** Lonceng + panel daftar notifikasi. Panggil di dalam topbar, sebelum avatar. */
    function notifikasiLonceng(): void
    {
        $d = notifData();
        $daftar = $d['daftar'];
        $belumDibaca = (int) $d['belumDibaca'];
        $badge = $belumDibaca > 9 ? '9+' : (string) $belumDibaca;
        $label = $belumDibaca > 0 ? 'Notifikasi, ' . $belumDibaca . ' belum dibaca' : 'Notifikasi';
        ?>
<div class="nt-wrap" id="ntWrap">
    <button type="button" class="nt-bell" id="ntBell" aria-label="<?= htmlspecialchars($label) ?>" aria-expanded="false" aria-controls="ntPanel">
        <?= notifIkon('bell') ?>
        <?php if ($belumDibaca > 0): ?>
            <span class="nt-badge" id="ntBadge" aria-hidden="true"><?= htmlspecialchars($badge) ?></span>
        <?php endif; ?>
    </button>

    <div class="nt-panel" id="ntPanel" role="region" aria-label="Daftar notifikasi" hidden>
        <div class="nt-head">
            <span>Notifikasi</span>
            <?php if ($belumDibaca > 0): ?>
                <span class="nt-count" id="ntCount"><?= $belumDibaca ?> baru</span>
            <?php endif; ?>
        </div>
        <div class="nt-list">
            <?php if (!empty($daftar)): ?>
                <?php foreach ($daftar as $n): ?>
                    <?php
                    list($kelas, $ikon) = notifTampilan($n['tipe'] ?? '');
                    $baru = empty($n['dibaca_at']);
                    ?>
                    <div class="nt-item<?= $baru ? ' baru' : '' ?>">
                        <span class="nt-ico <?= $kelas ?>" aria-hidden="true"><?= notifIkon($ikon) ?></span>
                        <span class="nt-body">
                            <span class="nt-judul"><?= htmlspecialchars((string) ($n['judul'] ?? ''), ENT_QUOTES, 'UTF-8') ?><?= $baru ? '<span class="nt-vh"> (baru)</span>' : '' ?></span>
                            <span class="nt-isi"><?= htmlspecialchars((string) ($n['isi'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="nt-waktu"><?= htmlspecialchars(notifWaktuRelatif($n['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="nt-empty">
                    <div class="nt-ico" aria-hidden="true"><?= notifIkon('bell') ?></div>
                    <h3>Belum ada notifikasi</h3>
                    <p>Kabar tentang pengajuan pencairan Anda akan muncul di sini.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
    }
}

if (!function_exists('notifikasiPopup')) {
    /**
     * Pop-up dari atas untuk notifikasi pencairan belum dibaca terbaru.
     * Tidak menampilkan apa pun bila tidak ada kejadian baru.
     * Letakkan di level <body> (di luar topbar), setelah include sidebar.
     */
    function notifikasiPopup(): void
    {
        $d = notifData();
        $toast = $d['toast'];
        if ($toast === null) {
            return;
        }
        $lainnya = max(0, (int) $d['belumDibaca'] - 1);
        list($kelas, $ikon) = notifTampilan($toast['tipe'] ?? '');
        ?>
<div class="nt-toast <?= $kelas ?>" id="ntToast" role="status" aria-live="polite">
    <button type="button" class="nt-toast-main" id="ntToastMain" aria-label="Buka daftar notifikasi">
        <span class="nt-ico <?= $kelas ?>" aria-hidden="true"><?= notifIkon($ikon) ?></span>
        <span class="nt-toast-text">
            <span class="nt-toast-judul"><?= htmlspecialchars((string) ($toast['judul'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
            <span class="nt-toast-isi"><?= htmlspecialchars((string) ($toast['isi'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
            <?php if ($lainnya > 0): ?>
                <span class="nt-toast-more">+<?= $lainnya ?> notifikasi baru lainnya</span>
            <?php endif; ?>
        </span>
    </button>
    <button type="button" class="nt-toast-close" id="ntToastClose" aria-label="Tutup pemberitahuan"><?= notifIkon('x') ?></button>
</div>
<?php
    }
}

if (!function_exists('notifikasiScript')) {
    /** JavaScript notifikasi. Panggil sebelum </body>. */
    function notifikasiScript(): void
    {
        ?>
<script>
(function () {
    var wrap = document.getElementById('ntWrap');
    var bell = document.getElementById('ntBell');
    var panel = document.getElementById('ntPanel');
    if (!wrap || !bell || !panel) { return; }

    var toast = document.getElementById('ntToast');
    var toastTimer = null;
    var sudahMenandai = false;

    /* ASUMSI: route POST untuk menandai semua notifikasi sudah dibaca */
    var URL_BACA = <?= json_encode(base_url('nasabah/notifikasi-baca')) ?>;

    function tandaiDibaca() {
        var badge = document.getElementById('ntBadge');
        if (!badge || !window.fetch || sudahMenandai) { return; }
        sudahMenandai = true;
        fetch(URL_BACA, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) {
            if (!r.ok) { sudahMenandai = false; return; }
            if (badge.parentNode) { badge.parentNode.removeChild(badge); }
            bell.setAttribute('aria-label', 'Notifikasi');
            var count = document.getElementById('ntCount');
            if (count && count.parentNode) { count.parentNode.removeChild(count); }
        }).catch(function () { sudahMenandai = false; /* tetap tampil sebagai baru */ });
    }

    function setOpen(open) {
        panel.hidden = !open;
        bell.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) { tandaiDibaca(); }
    }

    /* ----- Pop-up dari atas ----- */
    function tutupToast() {
        if (!toast || !toast.classList.contains('show')) { return; }
        window.clearTimeout(toastTimer);
        toast.classList.remove('show');
    }
    function jadwalkanTutup(ms) {
        window.clearTimeout(toastTimer);
        toastTimer = window.setTimeout(tutupToast, ms);
    }

    if (toast) {
        var tombolTutup = document.getElementById('ntToastClose');
        var tombolUtama = document.getElementById('ntToastMain');

        /* Tandai dibaca begitu halaman dimuat, bukan saat pop-up ditutup,
           supaya pindah halaman sebelum pop-up hilang tidak memunculkannya lagi. */
        tandaiDibaca();

        window.setTimeout(function () {
            toast.classList.add('show');
            jadwalkanTutup(8000);
        }, 400);

        toast.addEventListener('mouseenter', function () { window.clearTimeout(toastTimer); });
        toast.addEventListener('mouseleave', function () { jadwalkanTutup(3000); });
        toast.addEventListener('focusin', function () { window.clearTimeout(toastTimer); });
        toast.addEventListener('focusout', function () { jadwalkanTutup(3000); });

        if (tombolTutup) { tombolTutup.addEventListener('click', tutupToast); }
        if (tombolUtama) {
            tombolUtama.addEventListener('click', function () {
                tutupToast();
                setOpen(true);
            });
        }
    }

    /* ----- Panel lonceng ----- */
    bell.addEventListener('click', function () { setOpen(panel.hidden); });
    document.addEventListener('click', function (e) {
        if (panel.hidden) { return; }
        if (wrap.contains(e.target) || (toast && toast.contains(e.target))) { return; }
        setOpen(false);
    });
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') { return; }
        if (toast && toast.classList.contains('show')) { tutupToast(); return; }
        if (!panel.hidden) {
            setOpen(false);
            bell.focus();
        }
    });
})();
</script>
<?php
    }
}