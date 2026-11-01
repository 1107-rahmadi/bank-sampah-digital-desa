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
            SELECT id, desa_id, bank_sampah_id, nama, no_hp, email, password_hash, role, status, wajib_ganti_sandi, last_login_at
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
            SELECT id, desa_id, bank_sampah_id, nama, no_hp, email, password_hash, role, status, wajib_ganti_sandi, last_login_at, created_at
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
        $allowedFields = ['nama', 'no_hp', 'email', 'password_hash', 'status', 'role', 'desa_id', 'bank_sampah_id', 'wajib_ganti_sandi'];
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
            INSERT INTO users (nama, no_hp, email, password_hash, role, status, desa_id, bank_sampah_id, wajib_ganti_sandi)
            VALUES (:nama, :no_hp, :email, :password_hash, :role, :status, :desa_id, :bank_sampah_id, :wajib_ganti_sandi)
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
            'wajib_ganti_sandi' => (int) ($data['wajib_ganti_sandi'] ?? 0),
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
            INSERT INTO users (nama, no_hp, email, password_hash, role, status, desa_id, bank_sampah_id, wajib_ganti_sandi)
            VALUES (:nama, :no_hp, :email, :password_hash, :role, :status, :desa_id, :bank_sampah_id, :wajib_ganti_sandi)
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
            'wajib_ganti_sandi' => (int) ($data['wajib_ganti_sandi'] ?? 0),
        ]);
    }

    /**
     * Hapus user
     *
     * Catatan: tidak lagi dipanggil dari modul Kelola Pengguna
     * (akun hanya dinonaktifkan). Dibiarkan karena mungkin dipakai modul lain.
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
     * Susun klausa WHERE untuk daftar Kelola Pengguna.
     * Nasabah selalu dikecualikan (dikelola lewat menu Kelola Nasabah).
     *
     * @param array $filters q, role, status
     * @param array $params  diisi dengan parameter terikat
     * @return string
     */
    private function susunWhereDaftar(array $filters, array &$params): string
    {
        $where = ["u.role IN ('ADMIN', 'PENGELOLA', 'KADES')"];

        if (!empty($filters['role'])) {
            $where[] = 'u.role = :role';
            $params['role'] = $filters['role'];
        }

        if (!empty($filters['status'])) {
            $where[] = 'u.status = :status';
            $params['status'] = $filters['status'];
        }

        if (isset($filters['q']) && $filters['q'] !== '') {
            $like = '%' . addcslashes((string) $filters['q'], '%_\\') . '%';
            $where[] = '(u.nama LIKE :q_nama OR u.no_hp LIKE :q_hp)';
            $params['q_nama'] = $like;
            $params['q_hp'] = $like;
        }

        return 'WHERE ' . implode(' AND ', $where);
    }

    /**
     * Cari pengguna (Admin, Pengelola, Kepala Desa) untuk halaman daftar,
     * lengkap dengan nama bank sampah.
     *
     * @param array $filters q, role, status
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function search(array $filters, int $limit, int $offset = 0): array
    {
        $params = [];
        $where = $this->susunWhereDaftar($filters, $params);

        $stmt = $this->db->prepare("
            SELECT u.id, u.desa_id, u.bank_sampah_id, u.nama, u.no_hp, u.email, u.role, u.status,
                   u.last_login_at, u.created_at, b.nama AS bank_sampah_nama
            FROM users u
            LEFT JOIN bank_sampah b ON b.id = u.bank_sampah_id
            {$where}
            ORDER BY u.created_at DESC, u.id DESC
            LIMIT :limit OFFSET :offset
        ");

        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Hitung hasil pencarian pengguna (pasangan dari search()).
     *
     * @param array $filters q, role, status
     * @return int
     */
    public function countSearch(array $filters): int
    {
        $params = [];
        $where = $this->susunWhereDaftar($filters, $params);

        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM users u {$where}");
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        $stmt->execute();
        $result = $stmt->fetch();

        return (int) ($result['total'] ?? 0);
    }

    /**
     * Hitung Admin yang berstatus aktif.
     *
     * @return int
     */
    public function countAdminAktif(): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM users WHERE role = 'ADMIN' AND status = 'AKTIF'");
        $stmt->execute();
        $result = $stmt->fetch();

        return (int) ($result['total'] ?? 0);
    }

    /**
     * Apakah nomor HP sudah dipakai user lain?
     *
     * @param string $noHp
     * @param int $exceptId Abaikan record ini (saat ubah)
     * @return bool
     */
    public function existsNoHp(string $noHp, int $exceptId = 0): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM users WHERE no_hp = :no_hp AND id <> :id LIMIT 1");
        $stmt->execute(['no_hp' => $noHp, 'id' => $exceptId]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Apakah email sudah dipakai user lain?
     *
     * @param string $email
     * @param int $exceptId Abaikan record ini (saat ubah)
     * @return bool
     */
    public function existsEmail(string $email, int $exceptId = 0): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM users WHERE email = :email AND id <> :id LIMIT 1");
        $stmt->execute(['email' => $email, 'id' => $exceptId]);

        return (bool) $stmt->fetchColumn();
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