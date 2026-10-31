<?php
/**
 * User Model
 *
 * Model untuk interaksi dengan tabel users
 * Compatible dengan PHP 7.3+
 */

class User
{
    /** @var PDO */
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?? getDbConnection();
    }

    /**
     * Cari user berdasarkan nomor HP
     *
     * @param string $noHp
     * @return array|null
     */
    public function findByNoHp(string $noHp)
    {
        $stmt = $this->db->prepare("
            SELECT id, desa_id, bank_sampah_id, nama, no_hp, email, password_hash, role, status, last_login_at
            FROM users
            WHERE no_hp = :no_hp
            LIMIT 1
        ");
        $stmt->execute(['no_hp' => $noHp]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    /**
     * Cari user berdasarkan ID
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id)
    {
        $stmt = $this->db->prepare("
            SELECT id, desa_id, bank_sampah_id, nama, no_hp, email, password_hash, role, status, last_login_at, created_at
            FROM users
            WHERE id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    /**
     * Update last_login_at user
     *
     * @param int $id
     * @return bool
     */
    public function updateLastLogin(int $id): bool
    {
        $stmt = $this->db->prepare("
            UPDATE users
            SET last_login_at = NOW()
            WHERE id = :id
        ");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Update data user
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = ['nama', 'no_hp', 'email', 'password_hash', 'status', 'role', 'desa_id', 'bank_sampah_id'];
        $updates = [];
        $params = ['id' => $id];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $updates[] = "{$key} = :{$key}";
                $params[$key] = $value;
            }
        }

        if (empty($updates)) {
            return false;
        }

        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Buat user baru dan kembalikan id-nya.
     *
     * Dipakai ketika id user dibutuhkan segera setelah pembuatan,
     * misalnya untuk menautkannya ke baris nasabah.
     *
     * @param array $data
     * @return int|false Id user, atau false bila gagal
     */
    public function createDenganId(array $data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO users (nama, no_hp, email, password_hash, role, status, desa_id, bank_sampah_id)
            VALUES (:nama, :no_hp, :email, :password_hash, :role, :status, :desa_id, :bank_sampah_id)
        ");

        $ok = $stmt->execute([
            'nama' => $data['nama'],
            'no_hp' => $data['no_hp'],
            'email' => $data['email'] ?? null,
            'password_hash' => $data['password_hash'],
            'role' => $data['role'] ?? 'NASABAH',
            'status' => $data['status'] ?? 'AKTIF',
            'desa_id' => $data['desa_id'] ?? null,
            'bank_sampah_id' => $data['bank_sampah_id'] ?? null,
        ]);

        return $ok ? (int) $this->db->lastInsertId() : false;
    }

    /**
     * Buat user baru
     *
     * @param array $data
     * @return bool
     */
    public function create(array $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO users (nama, no_hp, email, password_hash, role, status, desa_id, bank_sampah_id)
            VALUES (:nama, :no_hp, :email, :password_hash, :role, :status, :desa_id, :bank_sampah_id)
        ");

        return $stmt->execute([
            'nama' => $data['nama'],
            'no_hp' => $data['no_hp'],
            'email' => $data['email'] ?? null,
            'password_hash' => $data['password_hash'],
            'role' => $data['role'] ?? 'NASABAH',
            'status' => $data['status'] ?? 'AKTIF',
            'desa_id' => $data['desa_id'] ?? null,
            'bank_sampah_id' => $data['bank_sampah_id'] ?? null,
        ]);
    }

    /**
     * Hapus user
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Hitung total user
     *
     * @param string|null $role Filter berdasarkan role
     * @return int
     */
    public function count($role = null): int
    {
        $sql = "SELECT COUNT(*) as total FROM users";
        $params = [];

        if ($role !== null) {
            $sql .= " WHERE role = :role";
            $params['role'] = $role;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();

        return (int) ($result['total'] ?? 0);
    }

    /**
     * Ambil semua user (dengan pagination opsional)
     *
     * @param int|null $limit
     * @param int|null $offset
     * @param string|null $role
     * @return array
     */
    public function getAll($limit = null, $offset = 0, $role = null): array
    {
        $sql = "SELECT id, desa_id, bank_sampah_id, nama, no_hp, email, role, status, last_login_at, created_at FROM users";
        $params = [];

        if ($role) {
            $sql .= " WHERE role = :role";
            $params['role'] = $role;
        }

        $sql .= " ORDER BY created_at DESC";

        if ($limit !== null) {
            $sql .= " LIMIT :limit OFFSET :offset";
            $params['limit'] = $limit;
            $params['offset'] = $offset;
        }

        $stmt = $this->db->prepare($sql);

        if ($limit !== null) {
            $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        }

        foreach ($params as $key => $value) {
            if ($key !== 'limit' && $key !== 'offset') {
                $stmt->bindValue(":$key", $value);
            }
        }

        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Verifikasi password
     *
     * @param string $password Password plaintext
     * @param string $hash Hash dari database
     * @return bool
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Hash password
     *
     * @param string $password
     * @return string
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    }

    /**
     * Check apakah user aktif
     *
     * @param array $user
     * @return bool
     */
    public static function isActive(array $user): bool
    {
        return ($user['status'] ?? '') === 'AKTIF';
    }

    /**
     * Check apakah user punya role tertentu
     *
     * @param array $user
     * @param string|array $roles
     * @return bool
     */
    public static function hasRole(array $user, $roles): bool
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }
        return in_array(strtoupper($user['role'] ?? ''), array_map('strtoupper', $roles));
    }
}
