<?php
/**
 * pengelola/transaksi_jual/form.php
 * Form Transaksi Jual Baru (Pengelola).
 *
 * Memakai partial bersama: partials/ui.php, sidebar.php, topbar.php,
 * pengelolaHead() dan pengelolaScripts(), sama seperti index.php dan detail.php.
 *
 * Compatible dengan PHP 7.3+
 *
 * @var array      $errors         daftar pesan kesalahan
 * @var array      $pengepulList   tiap item: id, nama, no_hp
 * @var array      $stokList       tiap item: kategori_id, kategori_nama, berat_kg
 * @var array      $hargaMap       kunci = kategori_id, nilai berisi harga_jual
 * @var array      $reserved       (opsional) kunci = kategori_id, nilai = berat tertahan dalam satuan 1/100 kg
 * @var array|null $oldInput       (opsional) jika tidak ada, dipakai $_POST
 */

require_once VIEW_PATH . '/pengelola/partials/ui.php';

$menuAktif = 'pengelola/transaksi-jual';
$judulHalaman = 'Transaksi Jual Baru';

$errors = (isset($errors) && is_array($errors)) ? $errors : [];
$pengepulList = (isset($pengepulList) && is_array($pengepulList)) ? $pengepulList : [];
$stokList = (isset($stokList) && is_array($stokList)) ? $stokList : [];
$hargaMap = (isset($hargaMap) && is_array($hargaMap)) ? $hargaMap : [];
$reserved = (isset($reserved) && is_array($reserved)) ? $reserved : [];

/* Isi ulang form saat validasi gagal ($oldInput, atau $_POST jika tidak dikirim) */
$old = (isset($oldInput) && is_array($oldInput)) ? $oldInput : $_POST;
$oldPengepul = (isset($old['pengepul_id']) && is_scalar($old['pengepul_id'])) ? (string) $old['pengepul_id'] : '';
$oldBerat = (isset($old['berat']) && is_array($old['berat'])) ? $old['berat'] : [];
$oldLunas = isset($old['status_bayar']) && is_scalar($old['status_bayar']) && (string) $old['status_bayar'] === '1';
$oldCatatan = (isset($old['catatan']) && is_scalar($old['catatan'])) ? (string) $old['catatan'] : '';

/* Hanya kategori yang stoknya ada */
$stokTersedia = [];
foreach ($stokList as $s) {
    if (isset($s['berat_kg']) && (float) $s['berat_kg'] > 0) {
        $stokTersedia[] = $s;
    }
}

/* Cari harga jual per kategori (pencocokan longgar, sama seperti versi lama) */
$cariHarga = function ($kategoriId) use ($hargaMap) {
    foreach ($hargaMap as $hid => $hv) {
        if ($hid == $kategoriId) {
            return $hv;
        }
    }
    return null;
};

$e = function ($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?= pengelolaHead($judulHalaman) ?>
    <style>
        /* Gaya khusus form transaksi jual. */
        .item { padding: 1rem; border: 1.5px solid var(--line); border-radius: 16px; background: var(--paper); }
        .item + .item { margin-top: .75rem; }
        .item-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; margin-bottom: .75rem; }
        .item-name { font-size: 1rem; font-weight: 700; color: var(--ink); min-width: 0; overflow-wrap: anywhere; }
        .item-stok {
            flex-shrink: 0; padding: .1875rem .625rem; border-radius: 999px;
            font-size: .8125rem; font-weight: 600; background: var(--sky-bg); color: var(--sky);
        }
        .berat-row { display: flex; gap: .5rem; }
        .berat-row .input { flex: 1; min-width: 0; }
        .btn-mini {
            flex-shrink: 0; min-height: 52px; padding: 0 1rem; border-radius: 14px; cursor: pointer;
            font-size: .9375rem; font-weight: 700; color: var(--g-800);
            background: transparent; border: 1.5px solid var(--g-200);
        }
        .btn-mini:hover { background: var(--g-100); }
        .btn-mini:disabled { opacity: .5; cursor: not-allowed; }
        .item-warn { margin-top: .5rem; font-size: .875rem; font-weight: 600; color: var(--rose); }
        .item-calc {
            display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap;
            margin-top: .875rem; padding-top: .75rem; border-top: 1px dashed var(--g-200);
            font-size: .875rem; color: var(--ink-3);
        }
        .item-calc strong { display: block; font-size: 1rem; font-weight: 700; color: var(--ink); font-variant-numeric: tabular-nums; }
        .item-calc span:last-child { text-align: right; }
        .item-calc .subtotal-cell { color: var(--sky); }
        .item-calc .harga-kosong { color: var(--amber); }
        .input.is-invalid { border-color: var(--rose); background: #fffafa; }

        select.input {
            -webkit-appearance: none; appearance: none; padding-right: 2.75rem; cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%235b6a5e' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 1rem center; background-size: 18px;
        }

        /* Pengalih "Sudah lunas" */
        .switch { display: flex; align-items: center; gap: .875rem; cursor: pointer; min-height: 52px; }
        .switch input { position: absolute; opacity: 0; width: 1px; height: 1px; }
        .switch-track {
            position: relative; width: 52px; height: 30px; flex-shrink: 0; border-radius: 999px;
            background: #cfd8cf; transition: background .2s var(--ease);
        }
        .switch-track::after {
            content: ""; position: absolute; top: 3px; left: 3px; width: 24px; height: 24px; border-radius: 50%;
            background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,.25); transition: transform .2s var(--ease);
        }
        .switch input:checked + .switch-track { background: var(--g-600); }
        .switch input:checked + .switch-track::after { transform: translateX(22px); }
        .switch input:focus-visible + .switch-track { outline: 3px solid var(--g-600); outline-offset: 2px; }
        .switch-text { font-size: 1rem; font-weight: 600; color: var(--ink); }
        .switch-sub { display: block; font-size: .875rem; font-weight: 500; color: var(--ink-3); }

        .btn-batal {
            display: inline-flex; align-items: center; justify-content: center;
            min-height: 54px; padding: 0 1.5rem; border-radius: 14px;
            font-size: 1.0625rem; font-weight: 700; text-decoration: none;
            color: var(--g-800); background: transparent; border: 1.5px solid var(--g-200);
        }
        .btn-batal:hover { background: var(--g-100); }
        .form-actions .btn-primary { flex: 1; }

        @media (max-width: 720px) {
            .form-actions { flex-direction: column-reverse; }
            .form-actions .btn-primary, .form-actions .btn-batal { width: 100%; }
        }
    </style>
</head>
<body>
    <?php include VIEW_PATH . '/pengelola/partials/sidebar.php'; ?>
    <div class="scrim" id="scrim"></div>

    <div class="main">
        <?php include VIEW_PATH . '/pengelola/partials/topbar.php'; ?>

        <main class="content">
            <div class="wrap">
                <a class="back" href="<?= base_url('pengelola/transaksi-jual') ?>"><?= icon('arrow-left') ?>Transaksi Jual</a>

                <div class="page-head">
                    <div>
                        <h1>Transaksi Jual Baru</h1>
                        <p class="lead">Jual sampah dari stok ke pengepul. Stok baru keluar setelah transaksi ditandai lunas.</p>
                    </div>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="notice notice-error" role="alert">
                        <?= icon('x-circle') ?>
                        <div>
                            <strong>Transaksi belum bisa disimpan:</strong>
                            <ul style="margin:.25rem 0 0 1.125rem;">
                                <?php foreach ($errors as $err): ?>
                                    <li><?= $e($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="formJual" data-once>
                    <?= csrfField() ?>

                    <section class="card" aria-labelledby="pengepul-title">
                        <h2 class="card-title" id="pengepul-title"><span class="ico"><?= icon('truck') ?></span>Data Pengepul</h2>
                        <div class="field">
                            <label for="pengepul_id" class="label">Pilih pengepul <span class="req" aria-hidden="true">*</span></label>
                            <select name="pengepul_id" id="pengepul_id" class="input" required>
                                <option value="">-- Pilih Pengepul --</option>
                                <?php foreach ($pengepulList as $p): ?>
                                    <option value="<?= $e($p['id'] ?? '') ?>"<?= ($oldPengepul !== '' && $oldPengepul === (string) ($p['id'] ?? '')) ? ' selected' : '' ?>><?= $e($p['nama'] ?? '-') ?> - <?= $e($p['no_hp'] ?? '-') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </section>

                    <section class="card" aria-labelledby="item-title">
                        <h2 class="card-title" id="item-title"><span class="ico"><?= icon('box') ?></span>Item yang Dijual</h2>
                        <?php if (!empty($stokTersedia)): ?>
                            <p class="card-hint">
                                Stok yang sudah dipesan pengepul lain (belum lunas) tidak bisa dijual lagi.
                                Kosongkan berat untuk jenis yang tidak dijual.
                            </p>
                            <div id="itemsBody">
                                <?php foreach ($stokTersedia as $s): ?>
                                    <?php
                                    $kid = (string) ($s['kategori_id'] ?? '');
                                    $h = $cariHarga($s['kategori_id'] ?? null);
                                    $hargaJual = ($h && isset($h['harga_jual'])) ? (float) $h['harga_jual'] : 0.0;
                                    $beratLama = (isset($oldBerat[$kid]) && is_scalar($oldBerat[$kid])) ? (string) $oldBerat[$kid] : '';

                                    /* Satuan 1/100 kg agar perbandingan stok tidak terganggu pecahan desimal */
                                    $stokCents = (int) round(((float) $s['berat_kg']) * 100);
                                    $reservedCents = (int) ($reserved[(int) $kid] ?? 0);
                                    $tersediaCents = max(0, $stokCents - $reservedCents);
                                    $tersediaKg = $tersediaCents / 100;
                                    $habis = $tersediaCents <= 0;
                                    ?>
                                    <div class="item">
                                        <div class="item-head">
                                            <span class="item-name"><?= $e($s['kategori_nama'] ?? '-') ?></span>
                                            <span class="item-stok">Stok <?= $e(number_format((float) $s['berat_kg'], 1, ',', '.')) ?> kg<?= $reservedCents > 0 ? ' &middot; tersedia ' . $e(number_format($tersediaKg, 2, ',', '.')) . ' kg' : '' ?></span>
                                        </div>
                                        <label class="label" for="berat_<?= $e($kid) ?>">Berat dijual (kg)</label>
                                        <div class="berat-row">
                                            <input type="number" id="berat_<?= $e($kid) ?>" name="berat[<?= $e($kid) ?>]"
                                                   step="any" min="0" max="<?= $e(sprintf('%.2f', $tersediaKg)) ?>"
                                                   inputmode="decimal" placeholder="0" class="input berat-input"
                                                   data-harga="<?= $e(sprintf('%.2f', $hargaJual)) ?>"
                                                   value="<?= $e($beratLama) ?>"<?= $habis ? ' disabled' : '' ?>>
                                            <button type="button" class="btn-mini" data-all<?= $habis ? ' disabled' : '' ?>>Semua</button>
                                            <input type="hidden" name="kategori_id[]" value="<?= $e($kid) ?>">
                                        </div>
                                        <p class="item-warn" hidden role="alert">Melebihi stok yang tersedia.</p>
                                        <div class="item-calc">
                                            <span>Harga jual per kg <strong class="<?= $hargaJual > 0 ? '' : 'harga-kosong' ?>"><?= $hargaJual > 0 ? $e(formatRupiah($hargaJual)) : 'Belum diatur' ?></strong></span>
                                            <span>Subtotal <strong class="subtotal-cell">Rp 0</strong></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty">
                                <span class="ico" aria-hidden="true"><?= icon('inbox') ?></span>
                                <h2>Stok kosong</h2>
                                <p>Belum ada sampah di stok yang bisa dijual.</p>
                            </div>
                        <?php endif; ?>
                    </section>

                    <section class="wallet" role="status" aria-label="Total penjualan">
                        <span class="wallet-label"><?= icon('wallet') ?>Total penjualan</span>
                        <p class="wallet-value" id="totalDisplay" aria-live="polite">Rp 0</p>
                        <p id="totalBerat" style="margin-top:.25rem;font-size:.9375rem;color:rgba(255,255,255,.92);">0 kg dijual</p>
                    </section>

                    <section class="card" aria-labelledby="bayar-title" style="margin-top:1rem;">
                        <h2 class="card-title" id="bayar-title"><span class="ico"><?= icon('banknote') ?></span>Pembayaran</h2>
                        <label class="switch">
                            <input type="checkbox" name="status_bayar" value="1"<?= $oldLunas ? ' checked' : '' ?>>
                            <span class="switch-track" aria-hidden="true"></span>
                            <span class="switch-text">Sudah lunas<span class="switch-sub">Jika tidak diaktifkan, penjualan dicatat sebagai piutang dan stok belum berkurang.</span></span>
                        </label>
                        <div class="field" style="margin-top:1rem;">
                            <label for="catatan" class="label">Catatan <span class="hint" style="display:inline;">(opsional)</span></label>
                            <input type="text" name="catatan" id="catatan" class="input" placeholder="Opsional" value="<?= $e($oldCatatan) ?>">
                        </div>
                    </section>

                    <div class="form-actions" style="margin-top:1.5rem;">
                        <button type="submit" class="btn btn-primary"<?= empty($stokTersedia) ? ' disabled' : '' ?>><?= icon('check') ?>Simpan Transaksi</button>
                        <a href="<?= base_url('pengelola/transaksi-jual') ?>" class="btn-batal">Batal</a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <?= pengelolaScripts() ?>
    <script>
    (function () {
        var itemsBody = document.getElementById('itemsBody');
        if (!itemsBody) { return; }

        function fmtRp(n) {
            return 'Rp ' + Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        function hitungBaris(row) {
            var input = row.querySelector('.berat-input');
            var harga = parseFloat(input.getAttribute('data-harga')) || 0;
            var berat = parseFloat(input.value) || 0;
            var maks = parseFloat(input.getAttribute('max'));
            var lebih = !isNaN(maks) && berat > maks + 0.0001;
            var subtotal = harga * berat;
            row.querySelector('.subtotal-cell').textContent = fmtRp(subtotal);
            row.setAttribute('data-subtotal', subtotal);
            row.setAttribute('data-berat', berat);
            row.querySelector('.item-warn').hidden = !lebih;
            input.classList.toggle('is-invalid', lebih);
        }

        function hitungTotal() {
            var total = 0, kg = 0;
            Array.prototype.forEach.call(itemsBody.querySelectorAll('.item'), function (row) {
                total += parseFloat(row.getAttribute('data-subtotal')) || 0;
                kg += parseFloat(row.getAttribute('data-berat')) || 0;
            });
            document.getElementById('totalDisplay').textContent = fmtRp(total);
            document.getElementById('totalBerat').textContent =
                (Math.round(kg * 100) / 100).toString().replace('.', ',') + ' kg dijual';
        }

        function hitung(input) {
            hitungBaris(input.closest('.item'));
            hitungTotal();
        }

        itemsBody.addEventListener('input', function (ev) {
            if (ev.target.classList.contains('berat-input')) { hitung(ev.target); }
        });
        itemsBody.addEventListener('click', function (ev) {
            var btn = ev.target.closest('[data-all]');
            if (!btn || btn.disabled) { return; }
            var input = btn.closest('.item').querySelector('.berat-input');
            input.value = input.getAttribute('max');
            hitung(input);
            input.focus();
        });

        /* Cegah kirim bila ada baris yang melebihi stok (server tetap memvalidasi ulang) */
        var form = document.getElementById('formJual');
        form.addEventListener('submit', function (ev) {
            var salah = itemsBody.querySelector('.berat-input.is-invalid');
            if (salah) {
                ev.preventDefault();
                salah.focus();
            }
        });

        /* Hitung ulang saat halaman dimuat (termasuk saat form diisi ulang setelah gagal) */
        Array.prototype.forEach.call(itemsBody.querySelectorAll('.item'), hitungBaris);
        hitungTotal();
    })();
    </script>
</body>
</html>