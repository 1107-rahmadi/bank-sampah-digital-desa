<?php
/**
 * Notifikasi Model
 *
 * Model untuk interaksi dengan tabel notifikasi
 * Compatible dengan PHP 7.3+
 */

class Notifikasi
{
    /** @var PDO */
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?? getDbConnection();
    }

    /**
     * Buat notifikasi baru
     *
     * @param array $data
     * @return bool
     */
    public function create(array $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO notifikasi (user_id, judul, isi, tipe)
            VALUES (:user_id, :judul, :isi, :tipe)
        ");

        return $stmt->execute([
            'user_id' => $data['user_id'],
            'judul' => $data['judul'],
            'isi' => $data['isi'],
            'tipe' => $data['tipe'] ?? 'INFO'
        ]);
    }

    /**
     * Ambil notifikasi user
     *
     * @param int $userId
     * @param int $limit
     * @param bool $unreadOnly
     * @return array
     */
    public function getByUser($userId, $limit = 20, $unreadOnly = false): array
    {
        $sql = "
            SELECT * FROM notifikasi
            WHERE user_id = :user_id
        ";
        $params = ['user_id' => $userId];

        if ($unreadOnly) {
            $sql .= " AND dibaca_at IS NULL";
        }

        $sql .= " ORDER BY created_at DESC LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Hitung notifikasi belum dibaca
     *
     * @param int $userId
     * @return int
     */
    public function countUnread($userId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total FROM notifikasi
            WHERE user_id = :user_id AND dibaca_at IS NULL
        ");
        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch();
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Tandai sudah dibaca
     *
     * @param int $id
     * @param int $userId
     * @return bool
     */
    public function markRead($id, $userId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE notifikasi
            SET dibaca_at = NOW()
            WHERE id = :id AND user_id = :user_id
        ");
        return $stmt->execute(['id' => $id, 'user_id' => $userId]);
    }

    /**
     * Tandai semua sudah dibaca
     *
     * @param int $userId
     * @return bool
     */
    public function markAllRead($userId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE notifikasi
            SET dibaca_at = NOW()
            WHERE user_id = :user_id AND dibaca_at IS NULL
        ");
        return $stmt->execute(['user_id' => $userId]);
    }

    /**
     * Hapus notifikasi
     *
     * @param int $id
     * @param int $userId
     * @return bool
     */
    public function delete($id, $userId): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM notifikasi
            WHERE id = :id AND user_id = :user_id
        ");
        return $stmt->execute(['id' => $id, 'user_id' => $userId]);
    }

    /**
     * Kirim notifikasi ke user
     *
     * @param int $userId
     * @param string $judul
     * @param string $isi
     * @param string $tipe
     * @return bool
     */
    public static function send($userId, $judul, $isi, $tipe = 'INFO'): bool
    {
        $notif = new self();
        return $notif->create([
            'user_id' => $userId,
            'judul' => $judul,
            'isi' => $isi,
            'tipe' => $tipe
        ]);
    }
}
