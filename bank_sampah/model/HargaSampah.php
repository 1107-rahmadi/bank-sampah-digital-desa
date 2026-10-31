<?php
/**
 * HargaSampah Model
 *
 * Model untuk interaksi dengan tabel harga_sampah
 *
 * BR-01: harga yang dipakai transaksi adalah harga yang berlaku pada
 * tanggal transaksi, yaitu harga dengan `berlaku_mulai` terbaru yang
 * tidak lebih besar dari tanggal transaksi. Harga yang berlaku di masa
 * depan tidak boleh menggantikan harga hari ini.
 *
 * Compatible dengan PHP 7.3+
 */

class HargaSampah
{
    /** @var PDO */
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?? getDbConnection();
    }

    /**
     * Ambil harga sampah untuk bank tertentu
     *
     * @param int         $bankSampahId
     * @param bool|null   $aktif Filter aktif saja
     * @return array
     */
    public function getByBankSampah($bankSampahId, $aktif = true): array
    {
        $sql = "
            SELECT h.*, k.nama as kategori_nama, k.jenis, k.satuan
            FROM harga_sampah h
            JOIN kategori_sampah k ON h.kategori_id = k.id
            WHERE h.bank_sampah_id = :bank_id
        ";
        $params = ['bank_id' => $bankSampahId];

        if ($aktif !== null) {
            $sql .= " AND h.aktif = :aktif";
            $params['aktif'] = $aktif ? 1 : 0;
        }

        $sql .= " ORDER BY k.jenis, k.nama ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Harga terbaru per kategori yang berlaku pada tanggal tertentu.
     *
     * Hanya harga dengan `berlaku_mulai` <= tanggal transaksi yang dipakai,
     * sehingga harga masa depan tidak menggantikan harga hari ini (BR-01).
     *
     * @param int    $bankSampahId
     * @param string $tanggal Format Y-m-d
     * @return array
     */
    public function getLatestByBank($bankSampahId, $tanggal = null): array
    {
        $tanggal = $this->normalisasiTanggal($tanggal);

        $stmt = $this->db->prepare("
            SELECT h.*, k.nama as kategori_nama, k.jenis, k.satuan
            FROM harga_sampah h
            JOIN kategori_sampah k ON h.kategori_id = k.id
            WHERE h.bank_sampah_id = :bank_id
              AND h.aktif = 1
              AND h.berlaku_mulai <= :tanggal
              AND h.berlaku_mulai = (
                    SELECT MAX(h2.berlaku_mulai)
                    FROM harga_sampah h2
                    WHERE h2.bank_sampah_id = h.bank_sampah_id
                      AND h2.kategori_id = h.kategori_id
                      AND h2.aktif = 1
                      AND h2.berlaku_mulai <= :tanggal2
              )
            ORDER BY k.jenis, k.nama ASC
        ");
        $stmt->execute([
            'bank_id' => $bankSampahId,
            'tanggal' => $tanggal,
            'tanggal2' => $tanggal,
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Peta harga aktif per kategori pada tanggal tertentu.
     *
     * @param int    $bankSampahId
     * @param string $tanggal Format Y-m-d
     * @return array kategori_id => ['id', 'harga_beli', 'harga_jual', 'kategori_nama', 'berlaku_mulai']
     */
    public function getPetaHarga(int $bankSampahId, $tanggal = null): array
    {
        $peta = [];

        foreach ($this->getLatestByBank($bankSampahId, $tanggal) as $h) {
            $peta[(int) $h['kategori_id']] = [
                'id' => (int) $h['id'],
                'harga_beli' => (int) toCents($h['harga_beli']),
                'harga_jual' => (int) toCents($h['harga_jual']),
                'kategori_nama' => $h['kategori_nama'],
                'berlaku_mulai' => $h['berlaku_mulai'],
            ];
        }

        return $peta;
    }

    /**
     * Normalisasi tanggal: selalu Y-m-d, default hari ini.
     *
     * @param string|null $tanggal
     * @return string
     */
    private function normalisasiTanggal($tanggal = null): string
    {
        if (is_string($tanggal) && preg_match('/^\d{4}-\d{2}-\d{2}/', $tanggal)) {
            return substr($tanggal, 0, 10);
        }

        return date('Y-m-d');
    }

    /**
     * Cari harga berdasarkan ID
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id)
    {
        $stmt = $this->db->prepare("
            SELECT h.*, k.nama as kategori_nama, k.jenis
            FROM harga_sampah h
            JOIN kategori_sampah k ON h.kategori_id = k.id
            WHERE h.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    /**
     * Buat harga baru
     *
     * @param array $data
     * @return bool
     */
    public function create(array $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO harga_sampah (bank_sampah_id, kategori_id, harga_beli, harga_jual, berlaku_mulai, aktif)
            VALUES (:bank_id, :kategori_id, :harga_beli, :harga_jual, :berlaku_mulai, :aktif)
        ");

        return $stmt->execute([
            'bank_id' => $data['bank_sampah_id'],
            'kategori_id' => $data['kategori_id'],
            'harga_beli' => $data['harga_beli'],
            'harga_jual' => $data['harga_jual'],
            'berlaku_mulai' => $data['berlaku_mulai'] ?? date('Y-m-d'),
            'aktif' => isset($data['aktif']) ? ($data['aktif'] ? 1 : 0) : 1
        ]);
    }

    /**
     * Update harga
     *
     * @param int   $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE harga_sampah
            SET harga_beli = :harga_beli,
                harga_jual = :harga_jual,
                berlaku_mulai = :berlaku_mulai,
                aktif = :aktif
            WHERE id = :id
        ");

        return $stmt->execute([
            'id' => $id,
            'harga_beli' => $data['harga_beli'],
            'harga_jual' => $data['harga_jual'],
            'berlaku_mulai' => $data['berlaku_mulai'] ?? date('Y-m-d'),
            'aktif' => isset($data['aktif']) ? ($data['aktif'] ? 1 : 0) : 1
        ]);
    }

    /**
     * Nonaktifkan harga lama saat buat harga baru.
     *
     * Harga yang berlaku di masa depan tidak dinonaktifkan, karena masih
     * boleh dipakai pada tanggal berlakunya.
     *
     * @param int    $bankSampahId
     * @param int    $kategoriId
     * @param string $berlakuMulai
     * @return void
     */
    public function deactivateOld($bankSampahId, $kategoriId, $berlakuMulai = null): void
    {
        $berlakuMulai = $berlakuMulai ?? date('Y-m-d');

        $stmt = $this->db->prepare("
            UPDATE harga_sampah
            SET aktif = 0
            WHERE bank_sampah_id = :bank_id
              AND kategori_id = :kategori_id
              AND aktif = 1
              AND berlaku_mulai < :berlaku_mulai
        ");
        $stmt->execute([
            'bank_id' => $bankSampahId,
            'kategori_id' => $kategoriId,
            'berlaku_mulai' => $berlakuMulai,
        ]);
    }
}
