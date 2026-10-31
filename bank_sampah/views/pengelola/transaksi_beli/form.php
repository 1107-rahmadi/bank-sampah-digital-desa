<?php
/**
 * pengelola/transaksi_beli/form.php
 * Form Transaksi Beli Baru (Pengelola) - UC07, UC08.
 *
 * Alur: pilih nasabah atau non-nasabah, timbang per kategori, harga terisi
 * otomatis dari harga yang berlaku (BR-01), lalu tentukan masuk tabungan atau
 * dibayar tunai. Untuk non-nasabah pembayaran selalu tunai (BR-02).
 *
 * Harga di layar hanya untuk membantu petugas. Server menghitung ulang
 * subtotal dan total dari harga aktif, jadi angka dari browser tidak dipercaya.
 *
 * Tanggal dikunci ke $tanggalTransaksi (hari ini). Harga yang dimuat ($hargaList)
 * berlaku untuk tanggal itu, jadi total di layar selalu sama dengan total di server.
 * Pencatatan mundur belum didukung; bila nanti diperlukan, harga harus dimuat ulang
 * per tanggal.
 *
 * Compatible dengan PHP 7.3+
 *
 * @var array      $errors       daftar pesan kesalahan
 * @var array      $nasabahList  tiap item: id, no_anggota, nama, saldo (opsional, untuk W3)
 * @var array      $hargaList    tiap item: kategori_id, kategori_nama, harga_beli
 * @var string     $tanggalTransaksi  Format Y-m-d (hari ini)
 * @var array|null $oldInput     isian sebelumnya saat validasi gagal
 */

require_once VIEW_PATH . '/pengelola/partials/ui.php';

$menuAktif = 'pengelola/transaksi-beli';
$judulHalaman = 'Transaksi Beli Baru';

$e = function ($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};

// Angka untuk atribut data-*: selalu titik desimal, tidak terpengaruh locale PHP.
$num = function ($v) {
    return number_format((float) $v, 2, '.', '');
};

$errors = isset($errors) && is_array($errors) ? $errors : [];
$nasabahList = isset($nasabahList) && is_array($nasabahList) ? $nasabahList : [];
$hargaList = isset($hargaList) && is_array($hargaList) ? $hargaList : [];
$tanggalTransaksi = (isset($tanggalTransaksi) && is_string($tanggalTransaksi) && $tanggalTransaksi !== '')
    ? $tanggalTransaksi
    : date('Y-m-d');

// Isi ulang form saat validasi gagal.
$old = (isset($oldInput) && is_array($oldInput)) ? $oldInput : $_POST;
$oldTipe = (isset($old['tipe_penjual']) && $old['tipe_penjual'] === 'NON_NASABAH') ? 'NON_NASABAH' : 'NASABAH';
$oldNasabah = (isset($old['nasabah_id']) && is_scalar($old['nasabah_id'])) ? (string) $old['nasabah_id'] : '';
$oldNama = (isset($old['nama_penjual']) && is_scalar($old['nama_penjual'])) ? (string) $old['nama_penjual'] : '';
$oldMetode = (isset($old['metode_bayar']) && $old['metode_bayar'] === 'TUNAI') ? 'TUNAI' : 'TABUNG';
$oldCatatan = (isset($old['catatan']) && is_scalar($old['catatan'])) ? (string) $old['catatan'] : '';
$oldKategori = (isset($old['kategori_id']) && is_array($old['kategori_id'])) ? array_values($old['kategori_id']) : [];
$oldBerat = (isset($old['berat']) && is_array($old['berat'])) ? array_values($old['berat']) : [];

// Satu kategori hanya boleh muncul di satu baris, jadi jumlah baris dibatasi jumlah kategori.
$jumlahKategori = count($hargaList);
$jumlahItem = max(1, count($oldKategori), count($oldBerat));
if ($jumlahKategori > 0) {
    $jumlahItem = min($jumlahItem, $jumlahKategori);
}

// Harga tampil dalam rupiah penuh, supaya perkalian di browser langsung menghasilkan rupiah.
$hargaRupiah = [];
foreach ($hargaList as $h) {
    if (isset($h['kategori_id'])) {
        $hargaRupiah[(int) $h['kategori_id']] = (float) ($h['harga_beli'] ?? 0);
    }
}

/**
 * Satu kartu item sampah (dipakai untuk baris awal dan template tombol "Tambah Item").
 */
$renderItem = function ($kategoriDipilih, $beratNilai, $nomor, $tampilHapus) use ($hargaList, $hargaRupiah, $e, $num) {
    ob_start();
    ?>
    <div class="item">
        <div class="item-head">
            <span class="item-no">Item <?= (int) $nomor ?></span>
            <button type="button" class="item-remove" data-remove aria-label="Hapus item ini"<?= $tampilHapus ? '' : ' hidden' ?>><?= icon('close') ?></button>
        </div>
        <div class="item-grid">
            <label class="field">
                <span class="label">Kategori</span>
                <select name="kategori_id[]" class="input kategori-select">
                    <option value="">-- Pilih --</option>
                    <?php foreach ($hargaList as $h):
                        $kid = (int) ($h['kategori_id'] ?? 0);
                    ?>
                        <option value="<?= $e($kid) ?>"
                                data-harga="<?= $e($num($hargaRupiah[$kid] ?? 0)) ?>"
                                <?= ((string) $kategoriDipilih !== '' && (string) $kategoriDipilih === (string) $kid) ? ' selected' : '' ?>><?= $e($h['kategori_nama'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field">
                <span class="label">Berat (kg)</span>
                <input type="number" name="berat[]" step="0.01" min="0.01" inputmode="decimal" class="input berat-input" placeholder="0"
                       value="<?= $e($beratNilai) ?>">
            </label>
        </div>
        <div class="item-calc">
            <span>Harga per kg <strong class="harga-cell">Rp 0</strong></span>
            <span>Subtotal <strong class="subtotal-cell">Rp 0</strong></span>
        </div>
    </div>
    <?php
    return ob_get_clean();
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?= pengelolaHead($judulHalaman) ?>
    <style>
        /* Gaya khusus halaman form transaksi beli. */

        /* Atribut hidden harus menang atas display yang diatur class lain. */
        [hidden] { display: none !important; }

        /* Pemilih tipe penjual (radio yang tampilannya seperti tombol). */
        .seg-wrap { margin-bottom: 18px; }
        .seg { display: flex; flex-wrap: wrap; gap: 8px; }
        .seg-opt { flex: 1 1 160px; }
        .seg-opt input { position: absolute; opacity: 0; width: 1px; height: 1px; }
        .seg-opt label {
            display: flex; align-items: center; justify-content: center;
            min-height: 52px; padding: 0 16px; cursor: pointer;
            font-weight: 700; color: var(--ink-2);
            background: var(--card); border: 1.5px solid var(--line); border-radius: var(--radius-sm);
            transition: background .18s var(--ease), border-color .18s var(--ease), color .18s var(--ease);
        }
        .seg-opt label:hover { background: var(--g-50); }
        .seg-opt input:checked + label { background: var(--g-100); border-color: var(--g-600); color: var(--g-800); }
        .seg-opt input:focus-visible + label { outline: 3px solid var(--g-600); outline-offset: 2px; }

        /* Kartu item sampah. */
        .item { padding: 16px; margin-bottom: 14px; background: var(--g-50); border: 1px solid var(--line); border-radius: var(--radius-sm); }
        .item:last-child { margin-bottom: 0; }
        .item-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 12px; }
        .item-no { font-weight: 700; color: var(--ink-2); }
        .item-remove {
            width: 44px; height: 44px; display: grid; place-items: center; cursor: pointer;
            color: var(--rose); background: var(--card); border: 1px solid var(--line); border-radius: 12px;
        }
        .item-remove:hover { background: var(--rose-bg); }
        .item-grid { display: grid; grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); gap: 0 14px; }
        .item-grid .field { margin-bottom: 12px; }
        .item-calc { display: flex; flex-wrap: wrap; gap: 8px 24px; font-size: .9375rem; color: var(--ink-2); }
        .item-calc strong { font-variant-numeric: tabular-nums; }
        .item-warn { margin-top: .75rem; font-size: .9375rem; font-weight: 600; color: var(--rose); }
        .btn-add { margin-top: 14px; }

        .wallet-amount { margin-top: 4px; font-size: 2.25rem; font-weight: 800; letter-spacing: -.03em; line-height: 1.15; font-variant-numeric: tabular-nums; }
        .opt { font-weight: 500; color: var(--ink-3); }

        @media (max-width: 560px) {
            .item-grid { grid-template-columns: minmax(0, 1fr); }
            .wallet-amount { font-size: 2rem; }
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
                <a class="back" href="<?= base_url('pengelola/transaksi-beli') ?>"><?= icon('arrow-left') ?>Transaksi Beli</a>

                <div class="page-head">
                    <div>
                        <h1>Transaksi Beli Baru</h1>
                        <p class="lead">Catat sampah yang disetor warga. Harga terisi otomatis dari harga yang berlaku.</p>
                    </div>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="notice notice-error" role="alert">
                        <?= icon('x-circle') ?>
                        <div>
                            <strong>Transaksi belum tersimpan:</strong>
                            <ul style="margin:.25rem 0 0 1.125rem;">
                                <?php foreach ($errors as $err): ?>
                                    <li><?= $e($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (empty($hargaList)): ?>
                    <div class="notice notice-warning" role="alert">
                        <?= icon('alert') ?>
                        <span>Belum ada harga yang berlaku untuk kategori sampah mana pun. Hubungi admin sebelum mencatat transaksi.</span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= base_url('pengelola/transaksi-beli/add') ?>" id="formBeli" data-once>
                    <?= csrfField() ?>

                    <section class="card" aria-labelledby="penjual-title">
                        <h2 class="card-title" id="penjual-title"><span class="ico"><?= icon('users') ?></span>Data Penjual</h2>

                        <div class="seg-wrap">
                            <span class="label" id="lbl-tipe">Tipe penjual</span>
                            <div class="seg" role="radiogroup" aria-labelledby="lbl-tipe">
                                <div class="seg-opt">
                                    <input type="radio" name="tipe_penjual" id="tipeNasabah" value="NASABAH"<?= $oldTipe === 'NASABAH' ? ' checked' : '' ?>>
                                    <label for="tipeNasabah">Nasabah</label>
                                </div>
                                <div class="seg-opt">
                                    <input type="radio" name="tipe_penjual" id="tipeNon" value="NON_NASABAH"<?= $oldTipe === 'NON_NASABAH' ? ' checked' : '' ?>>
                                    <label for="tipeNon">Non-Nasabah</label>
                                </div>
                            </div>
                        </div>

                        <div class="field" id="fieldNasabah"<?= $oldTipe === 'NASABAH' ? '' : ' hidden' ?>>
                            <label class="label" for="nasabah_id">Pilih nasabah <span class="req" aria-hidden="true">*</span></label>
                            <select name="nasabah_id" id="nasabah_id" class="input">
                                <option value="">-- Pilih --</option>
                                <?php foreach ($nasabahList as $n):
                                    $saldoN = (isset($n['saldo']) && is_numeric($n['saldo'])) ? (float) $n['saldo'] : null;
                                ?>
                                    <option value="<?= $e($n['id'] ?? '') ?>"<?= $saldoN !== null ? ' data-saldo="' . $e($num($saldoN)) . '"' : '' ?><?= ($oldNasabah !== '' && $oldNasabah === (string) ($n['id'] ?? '')) ? ' selected' : '' ?>><?= $e($n['no_anggota'] ?? '') ?> - <?= $e($n['nama'] ?? '') ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="hint" id="saldoInfo" hidden></p>
                            <p class="hint">Pilih nomor anggota, lalu namanya. Nasabah bisa juga ditambahkan lewat menu Data Nasabah.</p>
                        </div>

                        <div class="field" id="fieldNama"<?= $oldTipe === 'NON_NASABAH' ? '' : ' hidden' ?>>
                            <label class="label" for="nama_penjual">Nama penjual <span class="opt">(opsional)</span></label>
                            <input class="input" type="text" name="nama_penjual" id="nama_penjual" maxlength="100" placeholder="Nama lengkap" value="<?= $e($oldNama) ?>" autocomplete="off">
                            <p class="hint">Untuk warga yang belum jadi nasabah, nama tidak wajib diisi.</p>
                        </div>

                        <div class="field">
                            <label class="label" for="tanggal">Tanggal transaksi</label>
                            <input class="input" type="date" name="tanggal" id="tanggal" value="<?= $e($tanggalTransaksi) ?>" min="<?= $e($tanggalTransaksi) ?>" max="<?= $e($tanggalTransaksi) ?>">
                            <p class="hint">Harga yang dipakai adalah harga yang berlaku pada tanggal ini. Pencatatan untuk tanggal lain belum didukung.</p>
                        </div>
                    </section>

                    <section class="card" aria-labelledby="item-title">
                        <h2 class="card-title" id="item-title"><span class="ico"><?= icon('box') ?></span>Item Sampah</h2>
                        <div id="itemsBody">
                            <?php for ($i = 0; $i < $jumlahItem; $i++):
                                $kSel = (isset($oldKategori[$i]) && is_scalar($oldKategori[$i])) ? (string) $oldKategori[$i] : '';
                                $bVal = (isset($oldBerat[$i]) && is_scalar($oldBerat[$i])) ? (string) $oldBerat[$i] : '';
                                echo $renderItem($kSel, $bVal, $i + 1, $jumlahItem > 1);
                            endfor; ?>
                        </div>
                        <p class="item-warn" id="itemError" role="alert" hidden>Isi minimal satu item: pilih kategori dan isi berat lebih dari 0.</p>
                        <button type="button" id="addItemBtn" class="btn btn-ghost btn-add"><?= icon('plus') ?>Tambah Item</button>
                    </section>

                    <section class="wallet" aria-label="Total pembelian">
                        <span class="wallet-label"><?= icon('wallet') ?>Total pembelian</span>
                        <div class="wallet-amount" id="totalDisplay" aria-live="polite">Rp 0</div>
                    </section>

                    <section class="card" aria-labelledby="bayar-title" style="margin-top:1rem;">
                        <h2 class="card-title" id="bayar-title"><span class="ico"><?= icon('banknote') ?></span>Pembayaran</h2>
                        <div class="field">
                            <label class="label" for="metode_bayar">Metode bayar</label>
                            <select name="metode_bayar" id="metode_bayar" class="input">
                                <option value="TABUNG"<?= $oldMetode === 'TABUNG' ? ' selected' : '' ?>>Masuk tabungan nasabah</option>
                                <option value="TUNAI"<?= $oldMetode === 'TUNAI' ? ' selected' : '' ?>>Dibayar tunai</option>
                            </select>
                            <p class="hint" id="hintMetode">Nilai untuk nasabah bisa masuk tabungannya atau dibayar langsung.</p>
                        </div>
                        <div class="field">
                            <label class="label" for="catatan">Catatan <span class="opt">(opsional)</span></label>
                            <input class="input" type="text" name="catatan" id="catatan" placeholder="Opsional" value="<?= $e($oldCatatan) ?>">
                        </div>
                    </section>

                    <div class="form-actions" style="margin-top:1.5rem;">
                        <button type="submit" class="btn btn-primary"<?= empty($hargaList) ? ' disabled' : '' ?>><?= icon('save') ?>Simpan Transaksi</button>
                        <a href="<?= base_url('pengelola/transaksi-beli') ?>" class="btn btn-ghost">Batal</a>
                    </div>
                </form>

                <template id="itemTemplate"><?= $renderItem('', '', 1, true) ?></template>
            </div>
        </main>
    </div>

    <?= pengelolaScripts() ?>

    <script>
    /* ---------- Form transaksi beli ---------- */
    (function () {
        var itemsBody = document.getElementById('itemsBody');
        var itemError = document.getElementById('itemError');
        var addItemBtn = document.getElementById('addItemBtn');
        var tipeNasabah = document.getElementById('tipeNasabah');
        var tipeNon = document.getElementById('tipeNon');
        var fieldNasabah = document.getElementById('fieldNasabah');
        var fieldNama = document.getElementById('fieldNama');
        var nasabahSelect = document.getElementById('nasabah_id');
        var namaInput = document.getElementById('nama_penjual');
        var saldoInfo = document.getElementById('saldoInfo');
        var metodeSelect = document.getElementById('metode_bayar');
        var hintMetode = document.getElementById('hintMetode');

        /* Jumlah kategori yang punya harga berlaku = batas jumlah baris item. */
        var totalKategori = <?= (int) count($hargaList) ?>;

        function fmtRp(n) {
            return 'Rp ' + Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        /* Saldo nasabah terpilih (wireframe W3). Hanya tampil bila server mengirim saldo. */
        function tampilSaldo() {
            var opt = nasabahSelect.options[nasabahSelect.selectedIndex];
            var saldo = opt ? opt.getAttribute('data-saldo') : null;
            if (tipeNasabah.checked && saldo !== null && saldo !== '') {
                saldoInfo.textContent = 'Saldo tabungan saat ini: ' + fmtRp(parseFloat(saldo) || 0);
                saldoInfo.hidden = false;
            } else {
                saldoInfo.hidden = true;
            }
        }

        /*
         * Tipe penjual menentukan field dan metode bayar.
         * Field yang disembunyikan juga dinonaktifkan supaya tidak ikut terkirim
         * (nasabah_id harus kosong untuk non-nasabah). Non-nasabah selalu tunai (BR-02).
         */
        function togglePenjual() {
            var isNasabah = tipeNasabah.checked;
            fieldNasabah.hidden = !isNasabah;
            fieldNama.hidden = isNasabah;
            nasabahSelect.disabled = !isNasabah;
            nasabahSelect.required = isNasabah;
            namaInput.disabled = isNasabah;

            var opsiTabung = metodeSelect.querySelector('option[value="TABUNG"]');
            opsiTabung.disabled = !isNasabah;
            if (!isNasabah) {
                metodeSelect.value = 'TUNAI';
                hintMetode.textContent = 'Pembelian dari non-nasabah selalu dibayar tunai.';
            } else {
                hintMetode.textContent = 'Nilai untuk nasabah bisa masuk tabungannya atau dibayar langsung.';
            }
            tampilSaldo();
        }

        /* Satu kategori hanya boleh dipilih di satu baris. */
        function syncKategori() {
            var selects = itemsBody.querySelectorAll('.kategori-select');
            var dipakai = {};
            Array.prototype.forEach.call(selects, function (s) {
                if (s.value !== '') { dipakai[s.value] = true; }
            });
            Array.prototype.forEach.call(selects, function (s) {
                Array.prototype.forEach.call(s.options, function (o) {
                    if (o.value === '') { return; }
                    o.disabled = (o.value !== s.value) && !!dipakai[o.value];
                });
            });
        }

        function hitungBaris(row) {
            var select = row.querySelector('.kategori-select');
            var beratInput = row.querySelector('.berat-input');
            var opt = select.options[select.selectedIndex];
            var harga = parseFloat(opt && opt.getAttribute('data-harga')) || 0;
            var berat = parseFloat(beratInput.value) || 0;
            var subtotal = harga * berat;
            row.querySelector('.harga-cell').textContent = fmtRp(harga);
            row.querySelector('.subtotal-cell').textContent = fmtRp(subtotal);
            row.setAttribute('data-subtotal', subtotal);

            /* Kategori dipilih tanpa berat, atau berat diisi tanpa kategori, ditolak browser. */
            beratInput.required = select.value !== '';
            select.required = beratInput.value.trim() !== '';
        }

        function hitungTotal() {
            var total = 0;
            Array.prototype.forEach.call(itemsBody.querySelectorAll('.item'), function (row) {
                total += parseFloat(row.getAttribute('data-subtotal')) || 0;
            });
            document.getElementById('totalDisplay').textContent = fmtRp(total);
        }

        /* Tombol tambah mati bila semua kategori sudah punya baris (tidak ada pilihan tersisa). */
        function updateAddBtn() {
            addItemBtn.disabled = totalKategori === 0 ||
                itemsBody.querySelectorAll('.item').length >= totalKategori;
        }

        function nomorUlang() {
            var rows = itemsBody.querySelectorAll('.item');
            Array.prototype.forEach.call(rows, function (row, i) {
                row.querySelector('.item-no').textContent = 'Item ' + (i + 1);
                row.querySelector('[data-remove]').hidden = rows.length < 2;
            });
            updateAddBtn();
        }

        function addItem() {
            if (addItemBtn.disabled) { return; }
            var tpl = document.getElementById('itemTemplate');
            var node = tpl.content.firstElementChild.cloneNode(true);
            itemsBody.appendChild(node);
            hitungBaris(node);
            syncKategori();
            nomorUlang();
            hitungTotal();
            var select = node.querySelector('.kategori-select');
            if (select) { select.focus(); }
        }

        tipeNasabah.addEventListener('change', togglePenjual);
        tipeNon.addEventListener('change', togglePenjual);
        nasabahSelect.addEventListener('change', tampilSaldo);

        itemsBody.addEventListener('change', function (e) {
            if (e.target.classList.contains('kategori-select')) {
                hitungBaris(e.target.closest('.item'));
                syncKategori();
                hitungTotal();
            }
        });
        itemsBody.addEventListener('input', function (e) {
            if (e.target.classList.contains('berat-input')) {
                hitungBaris(e.target.closest('.item'));
                hitungTotal();
            }
        });
        itemsBody.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-remove]');
            if (!btn) { return; }
            var row = btn.closest('.item');
            if (row && itemsBody.querySelectorAll('.item').length > 1) {
                row.parentNode.removeChild(row);
                syncKategori();
                nomorUlang();
                hitungTotal();
                /* Tombol yang diklik hilang; kembalikan fokus supaya pengguna keyboard tidak tersesat. */
                var fokus = itemsBody.querySelector('.kategori-select');
                if (fokus) { fokus.focus(); }
            }
        });
        addItemBtn.addEventListener('click', addItem);

        /*
         * Dipasang di fase capture pada document supaya berjalan sebelum handler data-once
         * milik pengelolaScripts(). Pastikan ada minimal satu item yang valid, lalu nonaktifkan
         * baris yang benar-benar kosong agar tidak ikut terkirim (urutan kategori_id[] dan
         * berat[] tetap sejajar). Server tetap memvalidasi ulang.
         */
        document.addEventListener('submit', function (ev) {
            if (!ev.target || ev.target.id !== 'formBeli') { return; }
            var rows = itemsBody.querySelectorAll('.item');
            var valid = 0;

            /* Aktifkan kembali dulu, kalau-kalau baris sempat dinonaktifkan oleh pengiriman sebelumnya. */
            Array.prototype.forEach.call(rows, function (row) {
                row.querySelector('.kategori-select').disabled = false;
                row.querySelector('.berat-input').disabled = false;
            });

            Array.prototype.forEach.call(rows, function (row) {
                var s = row.querySelector('.kategori-select');
                var b = row.querySelector('.berat-input');
                if (s.value !== '' && (parseFloat(b.value) || 0) > 0) { valid++; }
            });
            if (valid === 0) {
                ev.preventDefault();
                ev.stopPropagation();
                itemError.hidden = false;
                var first = itemsBody.querySelector('.kategori-select');
                if (first) { first.focus(); }
                return;
            }
            itemError.hidden = true;
            Array.prototype.forEach.call(rows, function (row) {
                var s = row.querySelector('.kategori-select');
                var b = row.querySelector('.berat-input');
                if (s.value === '' && b.value.trim() === '') {
                    s.disabled = true;
                    b.disabled = true;
                }
            });
        }, true);

        /* Hitung ulang saat halaman dimuat (termasuk saat form diisi ulang setelah gagal). */
        Array.prototype.forEach.call(itemsBody.querySelectorAll('.item'), hitungBaris);
        syncKategori();
        nomorUlang();
        hitungTotal();
        togglePenjual();
    })();
    </script>
</body>
</html>