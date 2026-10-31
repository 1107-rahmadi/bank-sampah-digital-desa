<?php
/**
 * Helper umum aplikasi.
 *
 * Compatible dengan PHP 7.3+
 */

if (!function_exists('uuidV4')) {
    /**
     * Generate UUID v4.
     *
     * @return string
     */
    function uuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

if (!function_exists('nomorUrut')) {
    /**
     * Nomor urut dengan awalan, misal 'PC' -> 'PC-0001'.
     *
     * @param string $awalan
     * @param int    $jumlah
     * @return string
     */
    function nomorUrut(string $awalan, int $jumlah = 4): string
    {
        return $awalan . '-' . str_pad((string) $jumlah, 4, '0', STR_PAD_LEFT);
    }
}
