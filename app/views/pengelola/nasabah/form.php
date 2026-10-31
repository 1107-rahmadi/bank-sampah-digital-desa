<?php
/**
 * Form Tambah/Ubah Nasabah (Pengelola)
 * Compatible dengan PHP 7.3+
 *
 * Variabel dari controller:
 *   $editNasabah      data nasabah saat ubah (kosong/null saat tambah);
 *                     kolom: id, no_anggota, nama, no_hp, nik, email, tipe, alamat,
 *                     rt, rw, nama_bank, no_rekening, saldo, status, user_id,
 *                     user_email, user_status
 *   $suggestNoAnggota nomor anggota yang disarankan (khusus tambah)
 *   $errors           daftar pesan kesalahan
 *   $oldInput         (opsional) isian terakhir bila validasi gagal
 *   $flash            (opsional)
 *
 * Field POST: nama, no_hp, no_anggota, nik, email, tipe, alamat, rt, rw,
 *             nama_bank, no_rekening, status (hanya saat ubah),
 *             buat_akun (1), password
 */

if (!isset($flash) && function_exists('getFlashMessage')) {
    $flash = getFlashMessage();
}
include VIEW_PATH . '/pengelola/partials/ui.php';

$e = function ($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};

$editNasabah = (isset($editNasabah) && is_array($editNasabah) && $editNasabah) ? $editNasabah : null;
$oldInput = (isset($oldInput) && is_array($oldInput)) ? $oldInput : [];
$errors = (isset($errors) && is_array($errors)) ? $errors : [];
$isEdit = $editNasabah !== null;
$judul = $isEdit ? 'Ubah Nasabah' : 'Tambah Nasabah';
$menuAktif = 'pengelola/nasabah';

/* Nomor anggota yang disarankan; boleh diubah pengelola. */
$suggestNoAnggota = isset($suggestNoAnggota) && $suggestNoAnggota !== ''
    ? (string) $suggestNoAnggota
    : 'N-0001';

/* Nilai awal field: isian terakhir ($_POST / $oldInput), lalu data tersimpan */
$nilai = function ($key) use ($editNasabah, $oldInput) {
    if (isset($_POST[$key]) && is_scalar($_POST[$key])) {
        return (string) $_POST[$key];
    }
    if (isset($oldInput[$key]) && is_scalar($oldInput[$key])) {
        return (string) $oldInput[$key];
    }
    if ($editNasabah !== null && isset($editNasabah[$key]) && is_scalar($editNasabah[$key])) {
        return (string) $editNasabah[$key];
    }
    return '';
};

/* Email: EMAIL akun lebih dulu, lalu kolom email di tabel nasabah. */
$nilaiEmail = function () use ($editNasabah, $nilai) {
    if (isset($_POST['email']) && is_scalar($_POST['email'])) {
        return (string) $_POST['email'];
    }
    if ($editNasabah !== null) {
        $dariAkun = $editNasabah['user_email'] ?? '';
        if ($dariAkun !== '') {
            return (string) $dariAkun;
        }
    }
    return $nilai('email');
};

$vTipe = strtoupper($nilai('tipe')) === 'INSTANSI' ? 'INSTANSI' : 'PERORANGAN';
$vStatus = strtoupper($nilai('status')) === 'NONAKTIF' ? 'NONAKTIF' : 'AKTIF';

/* Nomor anggota: isi terakhir, lalu data tersimpan, lalu saran. */
$vNoAnggota = $nilai('no_anggota');
if ($vNoAnggota === '') {
    $vNoAnggota = $isEdit ? (string) ($editNasabah['no_anggota'] ?? '') : $suggestNoAnggota;
}

/* Akun aplikasi */
$punyaAkun = $isEdit && !empty($editNasabah['user_id']);
$akunNonaktif = $punyaAkun && strtoupper((string) ($editNasabah['user_status'] ?? 'AKTIF')) === 'NONAKTIF';
$sudahKirim = ($_SERVER['REQUEST_METHOD'] === 'POST') || !empty($oldInput);
$buatAkun = $sudahKirim
    ? (!empty($_POST['buat_akun']) || !empty($oldInput['buat_akun']))
    : (!$isEdit);
$tampilAkun = $punyaAkun || $buatAkun;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1b5e20">
    <title><?= $e($judul) ?> - <?= $e($appName) ?></title>
    <script>
        (function () {
            var r = document.documentElement;
            r.classList.add('no-anim');
            var s = null;
            try { s = localStorage.getItem('nav'); } catch (e) {}
            if (window.innerWidth > 1100 && s !== 'closed') { r.classList.add('nav-open'); }
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php include VIEW_PATH . '/pengelola/partials/style.php'; ?>
    <style>
        .page { max-width: 760px; }
        .section-title { margin: 1.5rem 0 .625rem; }
        .section-title:first-of-type { margin-top: 0; }
        .alert strong { display: block; margin-bottom: .25rem; }
        .alert ul { margin: .375rem 0 0 1.125rem; }

        /* Pilihan tipe */
        .seg { display: grid; grid-template-columns: 1fr 1fr; gap: 4px; padding: 4px; background: var(--g-100); border-radius: 16px; }
        .seg-opt { position: relative; }
        .seg-opt input { position: absolute; opacity: 0; width: 1px; height: 1px; }
        .seg-opt label { display: flex; align-items: center; justify-content: center; min-height: 46px; border-radius: 12px; cursor: pointer; font-weight: 600; color: var(--ink-3); transition: background .18s var(--ease), color .18s var(--ease); }
        .seg-opt label:hover { color: var(--g-800); }
        .seg-opt input:checked + label { background: var(--card); color: var(--g-800); font-weight: 700; box-shadow: 0 2px 8px rgba(27,94,32,.14); }
        .seg-opt input:focus-visible + label { outline: 3px solid var(--g-600); outline-offset: 2px; }

        /* Saklar akun */
        .switch { display: flex; align-items: center; gap: .875rem; cursor: pointer; min-height: 52px; margin-bottom: 1rem; }
        .switch input { position: absolute; opacity: 0; width: 1px; height: 1px; }
        .switch-track { position: relative; width: 52px; height: 30px; flex-shrink: 0; border-radius: 999px; background: #cfd8cf; transition: background .2s var(--ease); }
        .switch-track::after { content: ""; position: absolute; top: 3px; left: 3px; width: 24px; height: 24px; border-radius: 50%; background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,.25); transition: transform .2s var(--ease); }
        .switch input:checked + .switch-track { background: var(--g-600); }
        .switch input:checked + .switch-track::after { transform: translateX(22px); }
        .switch input:focus-visible + .switch-track { outline: 3px solid var(--g-600); outline-offset: 2px; }
        .switch-text { font-weight: 600; color: var(--ink); }
        .switch-sub { display: block; font-size: .875rem; font-weight: 500; color: var(--ink-3); }

        .pw { position: relative; }
        .pw .input { padding-right: 3.5rem; }
        .toggle { position: absolute; right: .375rem; top: 50%; transform: translateY(-50%); width: 44px; height: 44px; border: 0; border-radius: 12px; cursor: pointer; display: grid; place-items: center; background: transparent; color: var(--ink-3); }
        .toggle:hover { background: var(--g-100); color: var(--g-800); }
        .toggle svg { width: 20px; height: 20px; }
        .toggle .i-off { display: none; }
        .toggle[aria-pressed="true"] .i-on { display: none; }
        .toggle[aria-pressed="true"] .i-off { display: block; }

        .saldo-box { display: flex; align-items: center; gap: 1rem; }
        .saldo-box .ico { width: 46px; height: 46px; border-radius: 50%; background: var(--g-100); color: var(--g-700); display: grid; place-items: center; flex-shrink: 0; }
        .saldo-box .ico svg { width: 22px; height: 22px; }
        .saldo-box strong { display: block; font-size: 1.25rem; color: var(--ink); font-variant-numeric: tabular-nums; }
        .saldo-box span { font-size: .875rem; color: var(--ink-3); }

        .info-akun { display: flex; align-items: flex-start; gap: .625rem; padding: 12px 14px; border-radius: var(--radius-sm); background: var(--g-50); color: var(--ink-2); font-size: .9375rem; }
        .info-akun svg { width: 18px; height: 18px; flex-shrink: 0; margin-top: 2px; color: var(--g-700); }
    </style>
</head>
<body>
    <div class="scrim" id="scrim"></div>
    <?php include VIEW_PATH . '/pengelola/partials/sidebar.php'; ?>

    <div class="main">
        <header class="topbar">
            <button type="button" class="burger" id="burger" aria-controls="sidebar" aria-expanded="false" aria-label="Buka atau tutup menu"><?= icon('menu') ?></button>
            <span class="top-title"><?= $e($judul) ?></span>
            <span class="avatar" title="<?= $e($namaUser) ?>" aria-hidden="true"><?= $e($inisial) ?></span>
        </header>

        <main class="page">
            <a class="back" href="<?= base_url('pengelola/nasabah') ?>"><?= icon('arrow-left') ?>Data Nasabah</a>

            <div class="page-head">
                <div>
                    <h1 class="page-title"><?= $e($judul) ?></h1>
                    <p class="page-sub"><?= $isEdit ? 'Perbarui data nasabah, lalu simpan.' : 'Daftarkan nasabah baru. Kolom bertanda * wajib diisi.' ?></p>
                </div>
            </div>

            <?php if ($flash && !empty($flash['message'])): ?>
                <div class="flash flash-<?= $flashClass ?>" role="status"><?= icon($flashIcon) ?><span><?= $e($flash['message']) ?></span></div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error" role="alert">
                    <strong>Data belum bisa disimpan</strong>
                    <ul><?php foreach ($errors as $err): ?><li><?= $e($err) ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>

            <?php if ($isEdit): ?>
                <div class="card">
                    <div class="saldo-box">
                        <span class="ico"><?= icon('wallet') ?></span>
                        <div>
                            <span>Saldo tabungan saat ini</span>
                            <strong><?= $e(formatRupiah($editNasabah['saldo'] ?? 0)) ?></strong>
                            <span>Saldo hanya berubah lewat transaksi dan pencairan, tidak bisa diubah di sini.</span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="formNasabah">
                <?= csrfField() ?>
                <input type="hidden" name="id" value="<?= $isEdit ? $e($editNasabah['id'] ?? '') : '' ?>">

                <h2 class="section-title">Data nasabah</h2>
                <div class="card">
                    <div class="field">
                        <label for="no_anggota">Nomor anggota <span class="req" aria-hidden="true">*</span></label>
                        <input type="text" id="no_anggota" name="no_anggota" class="input" value="<?= $e($vNoAnggota) ?>" maxlength="20" required>
                        <p class="hint">Nomor anggota dipakai sebagai identitas nasabah. Boleh diubah selama belum dipakai nasabah lain.</p>
                    </div>

                    <div class="field">
                        <span class="label" id="lbl-tipe">Tipe nasabah</span>
                        <div class="seg" role="radiogroup" aria-labelledby="lbl-tipe">
                            <div class="seg-opt">
                                <input type="radio" name="tipe" id="tipe1" value="PERORANGAN"<?= $vTipe === 'PERORANGAN' ? ' checked' : '' ?>>
                                <label for="tipe1">Perorangan</label>
                            </div>
                            <div class="seg-opt">
                                <input type="radio" name="tipe" id="tipe2" value="INSTANSI"<?= $vTipe === 'INSTANSI' ? ' checked' : '' ?>>
                                <label for="tipe2">Instansi</label>
                            </div>
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="field">
                            <label for="nama">Nama lengkap <span class="req" aria-hidden="true">*</span></label>
                            <input type="text" name="nama" id="nama" class="input" value="<?= $e($nilai('nama')) ?>" maxlength="100" autocomplete="name" required>
                        </div>
                        <div class="field">
                            <label for="no_hp">Nomor HP <span class="req" aria-hidden="true">*</span></label>
                            <input type="tel" name="no_hp" id="no_hp" class="input" value="<?= $e($nilai('no_hp')) ?>" inputmode="tel" placeholder="08xxxxxxxxxx" autocomplete="off" required>
                            <p class="hint">Nomor ini juga dipakai nasabah untuk masuk ke aplikasi.</p>
                        </div>
                    </div>

                    <div class="field">
                        <label for="nik">NIK <span class="hint" style="font-weight:500">(opsional)</span></label>
                        <input type="text" name="nik" id="nik" class="input" value="<?= $e($nilai('nik')) ?>" inputmode="numeric" maxlength="32" autocomplete="off">
                    </div>
                </div>

                <h2 class="section-title">Alamat</h2>
                <div class="card">
                    <div class="field">
                        <label for="alamat">Alamat</label>
                        <input type="text" name="alamat" id="alamat" class="input" value="<?= $e($nilai('alamat')) ?>" autocomplete="street-address">
                    </div>
                    <div class="grid-2">
                        <div class="field">
                            <label for="rt">RT</label>
                            <input type="text" name="rt" id="rt" class="input" value="<?= $e($nilai('rt')) ?>" inputmode="numeric" maxlength="5" placeholder="01" autocomplete="off">
                        </div>
                        <div class="field">
                            <label for="rw">RW</label>
                            <input type="text" name="rw" id="rw" class="input" value="<?= $e($nilai('rw')) ?>" inputmode="numeric" maxlength="5" placeholder="05" autocomplete="off">
                        </div>
                    </div>
                </div>

                <h2 class="section-title">Rekening bank <span class="hint" style="font-weight:500">(opsional)</span></h2>
                <div class="card">
                    <div class="grid-2">
                        <div class="field">
                            <label for="nama_bank">Nama bank</label>
                            <input type="text" name="nama_bank" id="nama_bank" class="input" value="<?= $e($nilai('nama_bank')) ?>" placeholder="BRI, BNI, Mandiri, dll" autocomplete="off">
                        </div>
                        <div class="field">
                            <label for="no_rekening">Nomor rekening</label>
                            <input type="text" name="no_rekening" id="no_rekening" class="input" value="<?= $e($nilai('no_rekening')) ?>" inputmode="numeric" autocomplete="off">
                        </div>
                    </div>
                    <p class="hint">Dipakai sebagai isian awal saat nasabah memilih pencairan lewat transfer.</p>
                </div>

                <h2 class="section-title">Akun aplikasi</h2>
                <div class="card">
                    <?php if ($punyaAkun): ?>
                        <p class="note">
                            Nasabah ini sudah punya akun aplikasi.
                            Nasabah masuk memakai nomor HP <strong><?= $e($editNasabah['no_hp'] ?? '-') ?></strong><?= $akunNonaktif ? ', tapi akunnya sedang nonaktif.' : '.' ?>
                        </p>
                    <?php else: ?>
                        <label class="switch" for="buat_akun">
                            <input type="checkbox" name="buat_akun" id="buat_akun" value="1"<?= $buatAkun ? ' checked' : '' ?>>
                            <span class="switch-track" aria-hidden="true"></span>
                            <span class="switch-text">Buatkan akun aplikasi<span class="switch-sub">Nasabah bisa melihat saldo dan mengajukan pencairan dari ponsel. Bila dimatikan, nasabah tetap bisa dicatat di transaksi.</span></span>
                        </label>
                    <?php endif; ?>

                    <div id="akunFields"<?= $tampilAkun ? '' : ' hidden' ?>>
                        <div class="field">
                            <label for="email">Email <span class="hint" style="font-weight:500">(opsional)</span></label>
                            <input type="email" name="email" id="email" class="input" value="<?= $e($nilaiEmail()) ?>" autocomplete="off">
                            <p class="hint">Dipakai untuk pemberitahuan. Kosongkan bila tidak ada.</p>
                        </div>
                        <div class="field">
                            <label for="password"><?= $punyaAkun ? 'Atur ulang kata sandi' : 'Kata sandi awal' ?><?= ($punyaAkun || !$buatAkun) ? '' : ' <span class="req" aria-hidden="true">*</span>' ?></label>
                            <div class="pw">
                                <input type="password" name="password" id="password" class="input" autocomplete="new-password" minlength="6"<?= (!$punyaAkun && $buatAkun) ? ' required' : '' ?>>
                                <button type="button" class="toggle" id="togglePw" aria-pressed="false" aria-label="Tampilkan kata sandi" aria-controls="password">
                                    <span class="i-on"><?= icon('eye') ?></span>
                                    <span class="i-off"><?= icon('eye-off') ?></span>
                                </button>
                            </div>
                            <p class="hint"><?= $punyaAkun ? 'Kosongkan bila tidak diubah. ' : '' ?>Minimal 6 karakter. Nasabah masuk dengan nomor HP dan kata sandi ini.</p>
                        </div>
                    </div>
                </div>

                <?php if ($isEdit): ?>
                    <h2 class="section-title">Status</h2>
                    <div class="card">
                        <div class="field" style="margin-bottom:0">
                            <label for="status">Status nasabah</label>
                            <select name="status" id="status" class="input">
                                <option value="AKTIF"<?= $vStatus === 'AKTIF' ? ' selected' : '' ?>>Aktif</option>
                                <option value="NONAKTIF"<?= $vStatus === 'NONAKTIF' ? ' selected' : '' ?>>Nonaktif</option>
                            </select>
                            <p class="hint">Nasabah nonaktif tidak bisa dipilih untuk transaksi baru, tetapi riwayatnya tetap tersimpan. Mengaktifkan kembali juga mengaktifkan akun aplikasinya.</p>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="btnSimpan"><span>Simpan</span></button>
                    <a href="<?= base_url('pengelola/nasabah') ?>" class="btn btn-ghost">Batal</a>
                </div>
            </form>
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

        /* Bagian akun aplikasi */
        var sw = document.getElementById('buat_akun');
        var box = document.getElementById('akunFields');
        var pw = document.getElementById('password');
        if (sw && box && pw) {
            var apply = function () {
                box.hidden = !sw.checked;
                pw.required = sw.checked;
                if (!sw.checked) { pw.value = ''; }
            };
            sw.addEventListener('change', apply);
            apply();
        }

        /* Tampilkan / sembunyikan kata sandi */
        var tg = document.getElementById('togglePw');
        if (tg) {
            tg.addEventListener('click', function () {
                var show = pw.type === 'password';
                pw.type = show ? 'text' : 'password';
                tg.setAttribute('aria-pressed', show ? 'true' : 'false');
                tg.setAttribute('aria-label', show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
            });
        }

        /* Cegah kirim ganda */
        var form = document.getElementById('formNasabah');
        var btn = document.getElementById('btnSimpan');
        form.addEventListener('submit', function () {
            if (!form.checkValidity()) { return; }
            btn.disabled = true;
            btn.setAttribute('aria-busy', 'true');
            btn.querySelector('span').textContent = 'Menyimpan...';
        });
        window.addEventListener('pageshow', function (ev) {
            if (ev.persisted) {
                btn.disabled = false;
                btn.removeAttribute('aria-busy');
                btn.querySelector('span').textContent = 'Simpan';
            }
        });
    })();
    </script>
</body>
</html>
