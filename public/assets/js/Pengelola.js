/**
 * Bank Sampah Digital Desa - JavaScript area Pengelola
 * assets/js/pengelola.js
 *
 * Dimuat oleh pengelolaScripts() (partials/ui.php), SESUDAH assets/js/app.js.
 * File ini tidak mengulang isi app.js: konfirmasi form (data-confirm), window.ajax,
 * dan window.showToast tetap berasal dari app.js.
 *
 * Isi:
 *  1. Buka-tutup menu samping (kelas "nav-open" di <html>)
 *  2. Pencarian dan filter daftar yang bisa dipakai ulang  ([data-filter-scope])
 *  3. Cegah form terkirim dua kali                          ([data-once])
 */
(function () {
    'use strict';

    var root = document.documentElement;

    /* ----------------------------------------------------------
       1. Menu samping
       Kondisi awal sudah dipasang oleh skrip kecil di <head>
       (pengelolaHead) supaya tidak berkedip. Di sini hanya perilaku.
       ---------------------------------------------------------- */
    var burger = document.getElementById('burger');
    var scrim = document.getElementById('scrim');
    var mq = window.matchMedia ? window.matchMedia('(min-width: 1101px)') : { matches: true };

    function simpan(nilai) {
        try { localStorage.setItem('nav', nilai); } catch (e) { /* abaikan */ }
    }
    function baca() {
        try { return localStorage.getItem('nav'); } catch (e) { return null; }
    }
    function setNav(buka, ingat) {
        root.classList.toggle('nav-open', buka);
        if (burger) { burger.setAttribute('aria-expanded', buka ? 'true' : 'false'); }
        if (ingat && mq.matches) { simpan(buka ? 'open' : 'closed'); }
    }
    function awal() {
        setNav(mq.matches && baca() !== 'closed', false);
    }

    if (burger) {
        awal();
        burger.addEventListener('click', function () {
            setNav(!root.classList.contains('nav-open'), true);
        });
        if (scrim) {
            scrim.addEventListener('click', function () { setNav(false, false); });
        }
        document.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape' && !mq.matches && root.classList.contains('nav-open')) {
                setNav(false, false);
                burger.focus();
            }
        });
        if (mq.addEventListener) { mq.addEventListener('change', awal); }
        else if (mq.addListener) { mq.addListener(awal); }
    }

    // Aktifkan kembali animasi setelah kondisi awal terpasang
    window.requestAnimationFrame(function () { root.classList.remove('no-anim'); });

    /* ----------------------------------------------------------
       2. Pencarian dan filter daftar
       Markup:
         <div data-filter-scope>
           <input data-filter-input>                       (kotak cari, opsional)
           <button data-filter-btn data-value="all" aria-pressed="true">Semua</button>
           <button data-filter-btn data-value="organik" aria-pressed="false">Organik</button>
           <p data-filter-status class="sr-only" aria-live="polite"></p>
           <ul>
             <li data-filter-item data-value="organik" data-cari="kardus organik">...</li>
           </ul>
           <div data-filter-empty class="no-result">Tidak ada yang cocok.</div>
         </div>
       ---------------------------------------------------------- */
    function aktifkanFilter(scope) {
        var input = scope.querySelector('[data-filter-input]');
        var tombol = [].slice.call(scope.querySelectorAll('[data-filter-btn]'));
        var item = [].slice.call(scope.querySelectorAll('[data-filter-item]'));
        var kosong = scope.querySelector('[data-filter-empty]');
        var status = scope.querySelector('[data-filter-status]');
        var kini = { q: '', nilai: 'all' };

        function terapkan() {
            var tampil = 0;
            item.forEach(function (el) {
                var cocokNilai = kini.nilai === 'all' || el.getAttribute('data-value') === kini.nilai;
                var cocokCari = kini.q === '' || (el.getAttribute('data-cari') || '').indexOf(kini.q) !== -1;
                var lolos = cocokNilai && cocokCari;
                el.hidden = !lolos;
                if (lolos) { tampil++; }
            });
            if (kosong) { kosong.style.display = tampil === 0 ? 'block' : 'none'; }
            if (status) { status.textContent = tampil + ' data ditampilkan'; }
        }

        if (input) {
            input.addEventListener('input', function () {
                kini.q = input.value.trim().toLowerCase();
                terapkan();
            });
        }
        tombol.forEach(function (b) {
            b.addEventListener('click', function () {
                kini.nilai = b.getAttribute('data-value') || 'all';
                tombol.forEach(function (x) {
                    x.setAttribute('aria-pressed', x === b ? 'true' : 'false');
                });
                terapkan();
            });
        });
    }

    [].slice.call(document.querySelectorAll('[data-filter-scope]')).forEach(aktifkanFilter);

    /* ----------------------------------------------------------
       3. Cegah kirim ganda
       Form dengan atribut data-once: tombol kirim dinonaktifkan sesaat setelah
       form benar-benar dikirim. Ini penting untuk transaksi uang (pembelian,
       pencairan, penjualan) agar tidak tercatat dua kali.
       Penonaktifan ditunda satu putaran (setTimeout 0) supaya nilai tombol
       (misalnya name="submit") tetap ikut terkirim.
       ---------------------------------------------------------- */
    document.addEventListener('submit', function (ev) {
        var form = ev.target;
        if (!form || !form.hasAttribute || !form.hasAttribute('data-once')) { return; }
        // Dibatalkan (misalnya konfirmasi ditolak oleh app.js): jangan kunci tombol
        if (ev.defaultPrevented) { return; }

        if (form.getAttribute('data-sending') === '1') {
            ev.preventDefault();
            return;
        }
        form.setAttribute('data-sending', '1');

        window.setTimeout(function () {
            var tombolKirim = form.querySelectorAll('button[type="submit"], input[type="submit"]');
            for (var i = 0; i < tombolKirim.length; i++) {
                tombolKirim[i].disabled = true;
                tombolKirim[i].setAttribute('aria-disabled', 'true');
            }
        }, 0);
    });

    // Tombol "kembali" di peramban: pulihkan form yang tadi terkunci
    window.addEventListener('pageshow', function (ev) {
        if (!ev.persisted) { return; }
        [].slice.call(document.querySelectorAll('form[data-sending="1"]')).forEach(function (form) {
            form.removeAttribute('data-sending');
            [].slice.call(form.querySelectorAll('[aria-disabled="true"]')).forEach(function (b) {
                b.disabled = false;
                b.removeAttribute('aria-disabled');
            });
        });
    });
})();