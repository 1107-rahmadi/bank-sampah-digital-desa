<?php
/**
 * Pengepul Model
 *
 * Model untuk interaksi dengan tabel pengepul
 * Compatible dengan PHP 7.3+
 */

class Pengepul
{
    /** @var PDO */
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?? getDbConnection();
    }

    /**
     * Ambil semua pengepul
     *
     * @param int|null $bankSampahId Filter berdasarkan bank sampah
     * @param string|null $status Filter status
     * @return array
     */
    public function getAll($bankSampahId = null, $status = null): array
    {
        $sql = "SELECT * FROM pengepul WHERE 1=1";
        $params = [];

        if ($bankSampahId !== null) {
            $sql .= " AND bank_sampah_id = :bank_id";
            $params['bank_id'] = $bankSampahId;
        }

        if ($status !== null) {
            $sql .= " AND status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY nama ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Cari pengepul berdasarkan ID
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id)
    {
        $stmt = $this->db->prepare("SELECT * FROM pengepul WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Buat pengepul baru
     *
     * @param array $data
     * @return bool
     */
    public function create(array $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO pengepul (bank_sampah_id, nama, no_hp, alamat, status)
            VALUES (:bank_id, :nama, :no_hp, :alamat, :status)
        ");

        return $stmt->execute([
            'bank_id' => $data['bank_sampah_id'],
            'nama' => $data['nama'],
            'no_hp' => $data['no_hp'] ?? null,
            'alamat' => $data['alamat'] ?? null,
            'status' => $data['status'] ?? 'AKTIF'
        ]);
    }

    /**
     * Update pengepul
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE pengepul
            SET nama = :nama,
                no_hp = :no_hp,
                alamat = :alamat,
                status = :status
            WHERE id = :id
        ");

        return $stmt->execute([
            'id' => $id,
            'nama' => $data['nama'],
            'no_hp' => $data['no_hp'] ?? null,
            'alamat' => $data['alamat'] ?? null,
            'status' => $data['status'] ?? 'AKTIF'
        ]);
    }

    /**
     * Nonaktifkan pengepul.
     *
     * Pengepul yang sudah punya transaksi tidak boleh dihapus (BR-08).
     * Datanya ditandai NONAKTIF supaya riwayat tetap utuh (keputusan 9).
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("
            UPDATE pengepul SET status = 'NONAKTIF' WHERE id = :id
        ");
        return $stmt->execute(['id' => $id]);
    }
}
