<?php
/**
 * Notifikasi Model
 *
 * Notifikasi dalam aplikasi (tabel notifikasi).
 * Compatible dengan PHP 7.3+
 */

class Notifikasi
{
    /** @var PDO */
    private $db;

    /**
     * Constructor
     *
     * @param PDO|null $db Koneksi database (opsional)
     */
    public function __construct($db = null)
    {
        $this->db = $db ?? getDbConnection();
    }

    /**
     * Buat notifikasi untuk satu user (users.id).
     *
     * @param int    $userId
     * @param string $tipe  Contoh: PENCAIRAN_DIAJUKAN, PENCAIRAN_DISETUJUI,
     *                      PENCAIRAN_DITOLAK, PENCAIRAN_DICAIRKAN
     * @param string $judul
     * @param string $isi
     * @return bool
     */
    public function buat(int $userId, string $tipe, string $judul, string $isi): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $stmt = $this->db->prepare("
            INSERT INTO notifikasi (user_id, tipe, judul, isi)
            VALUES (:user_id, :tipe, :judul, :isi)
        ");

        return $stmt->execute([
            'user_id' => $userId,
            'tipe' => $tipe,
            'judul' => $judul,
            'isi' => $isi,
        ]);
    }

    /**
     * Buat notifikasi untuk nasabah (dipakai pengelola, yang hanya memegang
     * nasabah_id). Dilewati bila nasabah belum punya akun aplikasi.
     *
     * @param int    $nasabahId
     * @param string $tipe
     * @param string $judul
     * @param string $isi
     * @return bool
     */
    public function buatUntukNasabah(int $nasabahId, string $tipe, string $judul, string $isi): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO notifikasi (user_id, tipe, judul, isi)
            SELECT n.user_id, :tipe, :judul, :isi
            FROM nasabah n
            WHERE n.id = :nasabah_id AND n.user_id IS NOT NULL
        ");

        return $stmt->execute([
            'tipe' => $tipe,
            'judul' => $judul,
            'isi' => $isi,
            'nasabah_id' => $nasabahId,
        ]);
    }

    /**
     * Notifikasi terbaru milik satu user.
     *
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function terbaru(int $userId, int $limit = 8): array
    {
        $stmt = $this->db->prepare("
            SELECT id, judul, isi, tipe, dibaca_at, created_at
            FROM notifikasi
            WHERE user_id = :user_id
            ORDER BY created_at DESC, id DESC
            LIMIT :limit
        ");
        $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue('limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param int $userId
     * @return int
     */
    public function hitungBelumDibaca(int $userId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total
            FROM notifikasi
            WHERE user_id = :user_id AND dibaca_at IS NULL
        ");
        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) ($result['total'] ?? 0);
    }

    /**
     * Tandai semua notifikasi user sebagai sudah dibaca.
     *
     * @param int $userId
     * @return bool
     */
    public function tandaiSemuaDibaca(int $userId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE notifikasi
            SET dibaca_at = NOW()
            WHERE user_id = :user_id AND dibaca_at IS NULL
        ");

        return $stmt->execute(['user_id' => $userId]);
    }
}