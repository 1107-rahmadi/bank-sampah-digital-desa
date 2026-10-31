<?php
/**
 * Helper nilai uang & bobot.
 *
 * Semua perhitungan nominal dan berat memakai integer (satuan sen /
 * sen-kg), bukan float, supaya tidak ada galat pembulatan dan sesuai
 * PROSES_BISNIS.md bagian 8 ("Nominal DECIMAL(14,2), berat DECIMAL(10,2).
 * Tidak memakai float") serta NFR-03.
 *
 * Aturan pembulatan: setengah ke atas (round half up).
 *
 * Compatible dengan PHP 7.3+
 */

if (!function_exists('toCents')) {
    /**
     * Ubah nilai desimal (string/int/float) menjadi integer sen.
     *
     * Menerima format Indonesia ("12.500,50") maupun format biasa
     * ("12500.50" / "12500"). Titik sebagai pemisah ribuan hanya
     * dianggap pemisah ribuan bila diikuti tepat 3 digit, selain itu
     * dianggap tanda desimal.
     *
     * @param mixed $nilai
     * @return int
     */
    function toCents($nilai): int
    {
        if (is_int($nilai)) {
            return $nilai * 100;
        }

        if (is_float($nilai)) {
            $nilai = number_format($nilai, 2, '.', '');
        }

        $teks = trim((string) $nilai);
        if ($teks === '') {
            return 0;
        }

        // Buang semua karakter kecuali angka, titik dan koma.
        $teks = preg_replace('/[^0-9.,]/', '', $teks);
        if ($teks === '') {
            return 0;
        }

        $koma = strrpos($teks, ',');
        $titik = strrpos($teks, '.');

        if ($koma !== false && $titik !== false) {
            // Dua-duanya ada: yang paling kanan adalah desimal.
            if ($koma > $titik) {
                $teks = str_replace('.', '', $teks);
                $teks = str_replace(',', '.', $teks);
            } else {
                $teks = str_replace(',', '', $teks);
            }
        } elseif ($koma !== false) {
            // Hanya koma: desimal bila diikuti 1-2 digit, selain itu ribuan.
            $desimal = strlen($teks) - $koma - 1;
            if ($desimal === 1 || $desimal === 2) {
                $teks = str_replace(',', '.', $teks);
            } else {
                $teks = str_replace(',', '', $teks);
            }
        } elseif ($titik !== false) {
            // Hanya titik: desimal bila diikuti 1-2 digit, selain itu ribuan.
            $desimal = strlen($teks) - $titik - 1;
            if ($desimal !== 1 && $desimal !== 2) {
                $teks = str_replace('.', '', $teks);
            }
        }

        if (!is_numeric($teks)) {
            return 0;
        }

        return (int) round(((float) $teks) * 100);
    }
}

if (!function_exists('fromCents')) {
    /**
     * Ubah integer sen menjadi string desimal untuk disimpan ke DECIMAL.
     *
     * @param int $sen
     * @return string
     */
    function fromCents(int $sen): string
    {
        $negatif = $sen < 0;
        $sen = abs($sen);

        $teks = sprintf('%d.%02d', intdiv($sen, 100), $sen % 100);

        return $negatif ? '-' . $teks : $teks;
    }
}

if (!function_exists('formatRupiahCents')) {
    /**
     * Format integer sen untuk ditampilkan (tanpa desimal bila bulat).
     *
     * @param int $sen
     * @return string
     */
    function formatRupiahCents(int $sen): string
    {
        $negatif = $sen < 0;
        $sen = abs($sen);

        $bulat = intdiv($sen, 100);
        $pecahan = $sen % 100;

        $teks = number_format($bulat, 0, ',', '.');

        if ($pecahan !== 0) {
            $teks .= ',' . str_pad((string) $pecahan, 2, '0', STR_PAD_LEFT);
        }

        return ($negatif ? '-' : '') . 'Rp ' . $teks;
    }
}

if (!function_exists('hitungSubtotalCents')) {
    /**
     * Hitung subtotal dari berat (sen-kg) dan harga per kg (sen/kg).
     * Hasil dibulatkan ke sen terdekat, sama seperti DECIMAL(14,2).
     *
     * @param int $beratCents
     * @param int $hargaCents
     * @return int
     */
    function hitungSubtotalCents(int $beratCents, int $hargaCents): int
    {
        return intdiv($beratCents * $hargaCents + 50, 100);
    }
}

if (!function_exists('formatBerat')) {
    /**
     * Format berat (sen-kg) menjadi "12,50 kg".
     *
     * @param int $senKg
     * @return string
     */
    function formatBerat(int $senKg): string
    {
        $negatif = $senKg < 0;
        $senKg = abs($senKg);

        $teks = number_format(intdiv($senKg, 100), 0, ',', '.') . ',' . str_pad((string) ($senKg % 100), 2, '0', STR_PAD_LEFT);
        $teks = rtrim(rtrim($teks, '0'), ',');

        return ($negatif ? '-' : '') . ($teks === '' ? '0' : $teks) . ' kg';
    }
}
