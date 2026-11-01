<?php
/**
 * LaporanDesa Model
 *
 * Data agregat untuk grafik dashboard Kepala Desa
 * Compatible dengan PHP 7.3+
 */

class LaporanDesa
{
    /** @var PDO */
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?? getDbConnection();
    }

    /**
     * Tren nilai pembelian dan penjualan per minggu (Senin sebagai awal minggu).
     * Minggu tanpa transaksi diisi 0 supaya garis grafik tidak terputus.
     *
     * @param array $bankIds  ID bank sampah milik desa
     * @param int   $minggu   jumlah minggu ke belakang (termasuk minggu ini)
     * @return array ['labels' => [], 'beli' => [], 'jual' => []]
     */
    public function trenMingguan(array $bankIds, int $minggu = 8): array
    {
        $awal = new DateTime('monday this week');
        $awal->modify('-' . ($minggu - 1) . ' weeks');
        $mulai = $awal->format('Y-m-d');

        $beli = $this->nilaiPerMinggu('transaksi_beli', $bankIds, $mulai);
        $jual = $this->nilaiPerMinggu('transaksi_jual', $bankIds, $mulai);

        $labels = [];
        $dataBeli = [];
        $dataJual = [];
        $d = clone $awal;
        for ($i = 0; $i < $minggu; $i++) {
            $k = $d->format('Y-m-d');
            $labels[]   = $d->format('d M');
            $dataBeli[] = isset($beli[$k]) ? $beli[$k] : 0;
            $dataJual[] = isset($jual[$k]) ? $jual[$k] : 0;
            $d->modify('+1 week');
        }

        return ['labels' => $labels, 'beli' => $dataBeli, 'jual' => $dataJual];
    }

    /**
     * Jumlahkan total_nilai per minggu. Kunci hasil: tanggal Senin (Y-m-d).
     */
    private function nilaiPerMinggu(string $tabel, array $bankIds, string $mulai): array
    {
        if (empty($bankIds)) {
            return [];
        }

        // Whitelist: nama tabel tidak boleh berasal dari input luar
        if (!in_array($tabel, ['transaksi_beli', 'transaksi_jual'], true)) {
            return [];
        }

        $in = implode(',', array_fill(0, count($bankIds), '?'));
        $sql = "SELECT DATE_SUB(DATE(tanggal), INTERVAL WEEKDAY(tanggal) DAY) AS minggu,
                       SUM(total_nilai) AS nilai
                FROM {$tabel}
                WHERE bank_sampah_id IN ({$in})
                  AND status = 'SAH'
                  AND tanggal >= ?
                GROUP BY minggu";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge(array_values($bankIds), [$mulai]));

        $map = [];
        foreach ($stmt->fetchAll() as $r) {
            $map[$r['minggu']] = (float) $r['nilai'];
        }
        return $map;
    }
}