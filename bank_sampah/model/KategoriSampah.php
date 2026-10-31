<?php
/**
 * KategoriSampah Model
 *
 * Model untuk tabel kategori_sampah.
 *
 * Kategori adalah data master global (dipakai bersama semua bank sampah).
 * Pengelolaannya berada di tangan Admin (FR-19), sesuai tabel peran pada
 * PROSES_BISNIS.md bagian 1. Pengelola hanya memakai model ini untuk
 * mengisi dropdown kategori.
 *
 * Compatible dengan PHP 7.3+
 */

class KategoriSampah
{
    /** @var PDO */
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?? getDbConnection();
    }

    /**
     * Ambil semua kategori sampah
     *
     * @param bool|null $aktif Filter berdasarkan status aktif
     * @return array
     */
    public function getAll($aktif = null): array
    {
        $sql = "SELECT * FROM kategori_sampah";
        $params = [];

        if ($aktif !== null) {
            $sql .= " WHERE aktif = :aktif";
            $params['aktif'] = $aktif ? 1 : 0;
        }

        $sql .= " ORDER BY jenis, nama ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Cari kategori berdasarkan ID
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id)
    {
        $stmt = $this->db->prepare("SELECT * FROM kategori_sampah WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    /**
     * Buat kategori baru
     *
     * @param array $data nama, jenis, satuan, aktif
     * @return bool
     */
    public function create(array $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO kategori_sampah (nama, jenis, satuan, aktif)
            VALUES (:nama, :jenis, :satuan, :aktif)
        ");

        return $stmt->execute([
            'nama' => $data['nama'],
            'jenis' => $data['jenis'],
            'satuan' => $data['satuan'] ?? 'kg',
            'aktif' => isset($data['aktif']) ? ($data['aktif'] ? 1 : 0) : 1,
        ]);
    }

    /**
     * Ubah kategori
     *
     * @param int   $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE kategori_sampah
            SET nama = :nama, jenis = :jenis, satuan = :satuan, aktif = :aktif
            WHERE id = :id
        ");

        return $stmt->execute([
            'id' => $id,
            'nama' => $data['nama'],
            'jenis' => $data['jenis'],
            'satuan' => $data['satuan'] ?? 'kg',
            'aktif' => isset($data['aktif']) ? ($data['aktif'] ? 1 : 0) : 1,
        ]);
    }

    /**
     * Aktifkan/nonaktifkan kategori.
     * Kategori tidak dihapus karena sudah dipakai transaksi lama (BR-08).
     *
     * @param int  $id
     * @param bool $aktif
     * @return bool
     */
    public function setAktif(int $id, bool $aktif): bool
    {
        $stmt = $this->db->prepare("
            UPDATE kategori_sampah SET aktif = :aktif WHERE id = :id
        ");

        return $stmt->execute(['id' => $id, 'aktif' => $aktif ? 1 : 0]);
    }

    /**
     * Hitung berapa transaksi yang memakai kategori ini.
     *
     * @param int $id
     * @return int
     */
    public function countDipakai(int $id): int
    {
        $stmt = $this->db->prepare("
            SELECT
                (SELECT COUNT(*) FROM detail_beli WHERE kategori_id = :id1)
                +
                (SELECT COUNT(*) FROM detail_jual WHERE kategori_id = :id2)
                +
                (SELECT COUNT(*) FROM harga_sampah WHERE kategori_id = :id3)
            AS total
        ");
        $stmt->execute(['id1' => $id, 'id2' => $id, 'id3' => $id]);
        $result = $stmt->fetch();

        return (int) ($result['total'] ?? 0);
    }
}