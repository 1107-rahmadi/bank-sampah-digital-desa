<?php
/**
 * Nasabah Model
 *
 * Model untuk interaksi dengan tabel nasabah
 * Compatible dengan PHP 7.3+
 */

class Nasabah
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
     * Ambil koneksi database
     *
     * @return PDO
     */

    /**
 * Perbarui data pribadi nasabah.
 */
public function updateProfil($id, array $data): bool
{
    // Kolom opsional yang kosong disimpan sebagai NULL
    $nullable = function ($v) {
        return ($v === '' || $v === null) ? null : $v;
    };

    $sql = "UPDATE nasabah SET
                nama = :nama, no_hp = :no_hp, email = :email,
                alamat = :alamat, rt = :rt, rw = :rw,
                nama_bank = :nama_bank, no_rekening = :no_rekening
            WHERE id = :id";

    $stmt = $this->db->prepare($sql);

    return $stmt->execute([
        ':nama'        => $data['nama'],
        ':no_hp'       => $data['no_hp'],
        ':email'       => $nullable($data['email'] ?? ''),
        ':alamat'      => $nullable($data['alamat'] ?? ''),
        ':rt'          => $nullable($data['rt'] ?? ''),
        ':rw'          => $nullable($data['rw'] ?? ''),
        ':nama_bank'   => $nullable($data['nama_bank'] ?? ''),
        ':no_rekening' => $nullable($data['no_rekening'] ?? ''),
        ':id'          => (int) $id,
    ]);
}

    public function getDb(): PDO
    {
        return $this->db;
    }

    /**
     * Cari data Nasabah berdasarkan user_id
     *
     * @param int $userId
     * @return array|null
     */
    public function findByUserId(int $userId)
    {
        $stmt = $this->db->prepare("
            SELECT n.*, b.nama as bank_sampah_nama
            FROM nasabah n
            LEFT JOIN bank_sampah b ON n.bank_sampah_id = b.id
            WHERE n.user_id = :user_id
            LIMIT 1
        ");
        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    /**
     * Cari data Nasabah berdasarkan ID
     *
     * @param int $nasabahId
     * @return array|null
     */
    public function findById(int $nasabahId)
    {
        $stmt = $this->db->prepare("
            SELECT n.*, b.nama as bank_sampah_nama,
                   u.email as user_email, u.status as user_status, u.no_hp as user_no_hp
            FROM nasabah n
            LEFT JOIN bank_sampah b ON n.bank_sampah_id = b.id
            LEFT JOIN users u ON n.user_id = u.id
            WHERE n.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $nasabahId]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    /**
     * Ambil saldo tabungan Nasabah
     *
     * @param int $nasabahId
     * @return float
     */
    public function getSaldo(int $nasabahId): float
    {
        $stmt = $this->db->prepare("
            SELECT saldo
            FROM nasabah
            WHERE id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $nasabahId]);
        $result = $stmt->fetch();

        return (float) ($result['saldo'] ?? 0);
    }

    /**
     * Ambil saldo yang bisa dicairkan
     * Yaitu saldo dikurangi total pengajuan pencairan yang statusnya DIAJUKAN atau DISETUJUI
     *
     * @param int $nasabahId
     * @return float
     */
    public function getSaldoTersedia(int $nasabahId): float
    {
        // Ambil saldo saat ini
        $saldo = $this->getSaldo($nasabahId);

        // Hitung total pengajuan yang belum selesai (DIAJUKAN atau DISETUJUI)
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(jumlah), 0) as total_terikat
            FROM pengajuan_pencairan
            WHERE nasabah_id = :nasabah_id
            AND status IN ('DIAJUKAN', 'DISETUJUI')
        ");
        $stmt->execute(['nasabah_id' => $nasabahId]);
        $result = $stmt->fetch();

        $terikat = (float) ($result['total_terikat'] ?? 0);

        // Saldo tersedia = saldo - yang terikat pengajuan
        $tersedia = $saldo - $terikat;

        return max(0, $tersedia);
    }

    /**
     * Update saldo Nasabah
     *
     * @param int $nasabahId
     * @param float $jumlah Jumlah perubahan (positif=tambah, negatif=kurang)
     * @return bool
     */
    public function updateSaldo(int $nasabahId, float $jumlah): bool
    {
        $stmt = $this->db->prepare("
            UPDATE nasabah
            SET saldo = saldo + :jumlah
            WHERE id = :id
            AND (saldo + :jumlah2) >= 0
        ");
        return $stmt->execute([
            'jumlah' => $jumlah,
            'jumlah2' => $jumlah,
            'id' => $nasabahId
        ]);
    }

    /**
     * Set saldo absolute (untuk koreksi)
     *
     * @param int $nasabahId
     * @param float $saldoBaru
     * @return bool
     */
    public function setSaldo(int $nasabahId, float $saldoBaru): bool
    {
        if ($saldoBaru < 0) {
            return false;
        }

        $stmt = $this->db->prepare("
            UPDATE nasabah
            SET saldo = :saldo
            WHERE id = :id
        ");
        return $stmt->execute([
            'saldo' => $saldoBaru,
            'id' => $nasabahId
        ]);
    }

    /**
     * Ambil semua data Nasabah (dengan pagination)
     *
     * @param int|null $limit
     * @param int $offset
     * @param int|null $bankSampahId Filter berdasarkan bank sampah
     * @return array
     */
    public function getAll($limit = null, $offset = 0, $bankSampahId = null): array
    {
        $sql = "
            SELECT n.*, b.nama as bank_sampah_nama, u.no_hp as user_no_hp
            FROM nasabah n
            LEFT JOIN bank_sampah b ON n.bank_sampah_id = b.id
            LEFT JOIN users u ON n.user_id = u.id
            WHERE 1=1
        ";
        $params = [];

        if ($bankSampahId !== null) {
            $sql .= " AND n.bank_sampah_id = :bank_sampah_id";
            $params['bank_sampah_id'] = $bankSampahId;
        }

        $sql .= " ORDER BY n.tgl_daftar DESC";

        if ($limit !== null) {
            $sql .= " LIMIT :limit OFFSET :offset";
            $params['limit'] = $limit;
            $params['offset'] = $offset;
        }

        $stmt = $this->db->prepare($sql);

        if ($limit !== null) {
            $stmt->bindValue('limit', (int) $limit, PDO::PARAM_INT);
            $stmt->bindValue('offset', (int) $offset, PDO::PARAM_INT);
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
     * Hitung total Nasabah aktif
     *
     * @param int|null $bankSampahId
     * @return int
     */
    public function countAktif($bankSampahId = null): int
    {
        $sql = "SELECT COUNT(*) as total FROM users WHERE role = 'NASABAH'";
        $params = [];

        if ($bankSampahId !== null) {
            $sql .= " AND bank_sampah_id = :bank_sampah_id";
            $params['bank_sampah_id'] = $bankSampahId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();

        return (int) ($result['total'] ?? 0);
    }

    /**
     * Susun klausa WHERE bersama untuk pencarian dan ringkasan daftar nasabah.
     *
     * @param mixed $bankSampahId
     * @param string $q Kata kunci (nama, nomor anggota, nomor HP, NIK)
     * @param string $status AKTIF, NONAKTIF, atau kosong untuk semua
     * @return array [string $sql, array $params]
     */
    private function filterDaftar($bankSampahId, string $q = '', string $status = ''): array
    {
        $sql = '';
        $params = [];

        if ($bankSampahId !== null) {
            $sql .= ' AND n.bank_sampah_id = :bank_sampah_id';
            $params['bank_sampah_id'] = $bankSampahId;
        }

        $q = trim($q);
        if ($q !== '') {
            // Setiap kolom memakai penanda sendiri: satu penanda yang dipakai
            // berulang tidak diterima PDO saat emulasi prepare dimatikan.
            $sql .= ' AND (n.nama LIKE :q1 OR n.no_anggota LIKE :q2 OR n.no_hp LIKE :q3'
                . ' OR n.nik LIKE :q4 OR n.alamat LIKE :q5)';
            $params['q1'] = '%' . $q . '%';
            $params['q2'] = '%' . $q . '%';
            $params['q3'] = '%' . $q . '%';
            $params['q4'] = '%' . $q . '%';
            $params['q5'] = '%' . $q . '%';
        }

        $status = strtoupper(trim($status));
        if ($status === 'AKTIF' || $status === 'NONAKTIF') {
            $sql .= ' AND n.status = :status';
            $params['status'] = $status;
        }

        return [$sql, $params];
    }

    /**
     * Daftar nasabah dengan pencarian, filter status, dan paginasi.
     *
     * @param mixed  $bankSampahId
     * @param string $q
     * @param string $status
     * @param int    $limit
     * @param int    $offset
     * @return array
     */
    public function getPaginasi($bankSampahId, string $q = '', string $status = '', int $limit = 20, int $offset = 0): array
    {
        [$where, $params] = $this->filterDaftar($bankSampahId, $q, $status);

        $sql = "
            SELECT n.*, b.nama as bank_sampah_nama,
                   u.no_hp as user_no_hp, u.email as user_email, u.status as user_status
            FROM nasabah n
            LEFT JOIN bank_sampah b ON n.bank_sampah_id = b.id
            LEFT JOIN users u ON n.user_id = u.id
            WHERE 1=1{$where}
            ORDER BY n.tgl_daftar DESC, n.id DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Jumlah nasabah yang cocok dengan filter (untuk paginasi).
     *
     * @param mixed  $bankSampahId
     * @param string $q
     * @param string $status
     * @return int
     */
    public function countPaginasi($bankSampahId, string $q = '', string $status = ''): int
    {
        [$where, $params] = $this->filterDaftar($bankSampahId, $q, $status);

        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM nasabah n WHERE 1=1{$where}");
        $stmt->execute($params);

        return (int) ($stmt->fetch()['total'] ?? 0);
    }

    /**
     * Ringkasan daftar nasabah: jumlah, jumlah aktif, dan total saldo.
     *
     * Dihitung dengan satu kueri agar angka tetap akurat meski daftar
     * dipaginasi (bukan hanyaiset baris yang sedang ditampilkan).
     *
     * @param mixed  $bankSampahId
     * @param string $q
     * @param string $status
     * @return array ['total' => int, 'aktif' => int, 'nonaktif' => int, 'saldo' => float]
     */
    public function getRingkasan($bankSampahId, string $q = '', string $status = ''): array
    {
        [$where, $params] = $this->filterDaftar($bankSampahId, $q, $status);

        $sql = "
            SELECT COUNT(*) as total,
                   SUM(CASE WHEN n.status = 'AKTIF' THEN 1 ELSE 0 END) as aktif,
                   COALESCE(SUM(n.saldo), 0) as saldo
            FROM nasabah n
            WHERE 1=1{$where}
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch() ?: [];

        $total = (int) ($row['total'] ?? 0);
        $aktif = (int) ($row['aktif'] ?? 0);

        return [
            'total' => $total,
            'aktif' => $aktif,
            'nonaktif' => max(0, $total - $aktif),
            'saldo' => (float) ($row['saldo'] ?? 0),
        ];
    }

    /**
     * Saldo yang bisa dicairkan untuk beberapa nasabah sekaligus.
     *
     * Saldo tersedia = saldo - total pengajuan pencairan yang masih tertahan
     * (status DIAJUKAN atau DISETUJUI), sama seperti getSaldoTersedia().
     *
     * @param int[] $ids
     * @return array<int, float> peta id => saldo tersedia
     */
    public function getSaldoTersediaPeta(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (empty($ids)) {
            return [];
        }

        $tanda = implode(',', array_fill(0, count($ids), '?'));

        $stmt = $this->db->prepare("
            SELECT n.id,
                   n.saldo,
                   COALESCE((
                       SELECT SUM(p.jumlah)
                       FROM pengajuan_pencairan p
                       WHERE p.nasabah_id = n.id
                         AND p.status IN ('DIAJUKAN', 'DISETUJUI')
                   ), 0) as terikat
            FROM nasabah n
            WHERE n.id IN ({$tanda})
        ");
        $stmt->execute($ids);

        $peta = [];
        foreach ($stmt->fetchAll() as $row) {
            $tersedia = (float) $row['saldo'] - (float) $row['terikat'];
            $peta[(int) $row['id']] = max(0, $tersedia);
        }

        return $peta;
    }

    /**
     * Cek apakah nomor anggota sudah dipakai.
     *
     * @param string   $noAnggota
     * @param int|null $exceptId Id nasabah yang sedang diubah (diabaikan)
     * @param mixed    $bankSampahId
     * @return bool
     */
    public function noAnggotaExists(string $noAnggota, ?int $exceptId = null, $bankSampahId = null): bool
    {
        $sql = "SELECT COUNT(*) as total FROM nasabah WHERE no_anggota = :no_anggota";
        $params = ['no_anggota' => $noAnggota];

        if ($exceptId) {
            $sql .= ' AND id <> :except_id';
            $params['except_id'] = $exceptId;
        }
        if ($bankSampahId !== null) {
            $sql .= ' AND bank_sampah_id = :bank_sampah_id';
            $params['bank_sampah_id'] = $bankSampahId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return ((int) ($stmt->fetch()['total'] ?? 0)) > 0;
    }

    /**
     * Cek apakah nomor HP sudah dipakai nasabah lain di bank sampah yang sama.
     *
     * @param string   $noHp
     * @param int|null $exceptId
     * @param mixed    $bankSampahId
     * @return bool
     */
    public function noHpExists(string $noHp, ?int $exceptId = null, $bankSampahId = null): bool
    {
        $sql = "SELECT COUNT(*) as total FROM nasabah WHERE no_hp = :no_hp";
        $params = ['no_hp' => $noHp];

        if ($exceptId) {
            $sql .= ' AND id <> :except_id';
            $params['except_id'] = $exceptId;
        }
        if ($bankSampahId !== null) {
            $sql .= ' AND bank_sampah_id = :bank_sampah_id';
            $params['bank_sampah_id'] = $bankSampahId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return ((int) ($stmt->fetch()['total'] ?? 0)) > 0;
    }

    /**
     * Buat Nasabah baru
     *
     * @param array $data
     * @return bool
     */
    public function create(array $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO nasabah (user_id, bank_sampah_id, no_anggota, nama, no_hp, nik, alamat, rt, rw, tipe, nama_bank, no_rekening, email, saldo, status, tgl_daftar)
            VALUES (:user_id, :bank_sampah_id, :no_anggota, :nama, :no_hp, :nik, :alamat, :rt, :rw, :tipe, :nama_bank, :no_rekening, :email, :saldo, :status, :tgl_daftar)
        ");

        return $stmt->execute([
            'user_id' => $data['user_id'] ?? null,
            'bank_sampah_id' => $data['bank_sampah_id'],
            'no_anggota' => $data['no_anggota'],
            'nama' => $data['nama'],
            'no_hp' => $data['no_hp'],
            'nik' => $data['nik'] ?? null,
            'alamat' => $data['alamat'] ?? null,
            'rt' => $data['rt'] ?? null,
            'rw' => $data['rw'] ?? null,
            'tipe' => $data['tipe'] ?? 'PERORANGAN',
            'nama_bank' => $data['nama_bank'] ?? null,
            'no_rekening' => $data['no_rekening'] ?? null,
            'email' => !empty($data['email']) ? $data['email'] : null,
            'saldo' => $data['saldo'] ?? 0,
            'status' => $data['status'] ?? 'AKTIF',
            'tgl_daftar' => $data['tgl_daftar'] ?? date('Y-m-d'),
        ]);
    }

    /**
     * Buat nasabah tanpa membuatkan akun aplikasi.
     *
     * Dipakai ketika pengelola mendaftarkan nasabah yang belum bisa memakai
     * aplikasi (misalnya belum punya nomor HP yang aktif). Akun bisa dibuat
     * belakangan lewat form ubah.
     *
     * @param array $data
     * @return int|false Id nasabah yang baru dibuat, atau false bila gagal
     */
    public function createTanpaAkun(array $data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO nasabah
            (user_id, bank_sampah_id, no_anggota, nama, no_hp, nik, alamat, rt, rw, tipe,
             nama_bank, no_rekening, email, saldo, status, tgl_daftar)
            VALUES (NULL, :bank_sampah_id, :no_anggota, :nama, :no_hp, :nik, :alamat, :rt, :rw, :tipe,
             :nama_bank, :no_rekening, :email, :saldo, :status, :tgl_daftar)
        ");

        $ok = $stmt->execute([
            'bank_sampah_id' => $data['bank_sampah_id'],
            'no_anggota' => $data['no_anggota'],
            'nama' => $data['nama'],
            'no_hp' => $data['no_hp'],
            'nik' => !empty($data['nik']) ? $data['nik'] : null,
            'alamat' => !empty($data['alamat']) ? $data['alamat'] : null,
            'rt' => !empty($data['rt']) ? $data['rt'] : null,
            'rw' => !empty($data['rw']) ? $data['rw'] : null,
            'tipe' => $data['tipe'] ?? 'PERORANGAN',
            'nama_bank' => !empty($data['nama_bank']) ? $data['nama_bank'] : null,
            'no_rekening' => !empty($data['no_rekening']) ? $data['no_rekening'] : null,
            'email' => !empty($data['email']) ? $data['email'] : null,
            'saldo' => $data['saldo'] ?? 0,
            'status' => $data['status'] ?? 'AKTIF',
            'tgl_daftar' => $data['tgl_daftar'] ?? date('Y-m-d'),
        ]);

        return $ok ? (int) $this->db->lastInsertId() : false;
    }

    /**
     * Update Nasabah
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, array $data): bool
    {        $stmt = $this->db->prepare("
            UPDATE nasabah
            SET bank_sampah_id = :bank_sampah_id,
                no_anggota = :no_anggota,
                nama = :nama,
                no_hp = :no_hp,
                nik = :nik,
                alamat = :alamat,
                rt = :rt,
                rw = :rw,
                tipe = :tipe,
                nama_bank = :nama_bank,
                no_rekening = :no_rekening,
                email = :email,
                status = :status
            WHERE id = :id
        ");

        return $stmt->execute([
            'id' => $id,
            'bank_sampah_id' => $data['bank_sampah_id'],
            'no_anggota' => $data['no_anggota'],
            'nama' => $data['nama'],
            'no_hp' => $data['no_hp'],
            'nik' => $data['nik'] ?? null,
            'alamat' => $data['alamat'] ?? null,
            'rt' => $data['rt'] ?? null,
            'rw' => $data['rw'] ?? null,
            'tipe' => $data['tipe'] ?? 'PERORANGAN',
            'nama_bank' => $data['nama_bank'] ?? null,
            'no_rekening' => $data['no_rekening'] ?? null,
            'email' => !empty($data['email']) ? $data['email'] : null,
            'status' => $data['status'] ?? 'AKTIF',
        ]);
    }

    /**
     * Hapus Nasabah.
     *
     * CATATAN: data nasabah yang punya riwayat TIDAK BOLEH dihapus
     * (BR-08). Gunakan nonaktifkan() supaya riwayat tetap utuh.
     *
     * @deprecated Gunakan nonaktifkan()
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        error_log('[Nasabah::delete] dipanggil. Data bullion tidak dihapus; dialihkan ke nonaktifkan().');

        return $this->nonaktifkan($id);
    }

    /**
     * Nonaktifkan nasabah tanpa menghapus riwayatnya (BR-08, keputusan 9).
     *
     * @param int $id
     * @return bool
     */
    public function nonaktifkan(int $id): bool
    {
        $stmt = $this->db->prepare("
            UPDATE nasabah SET status = 'NONAKTIF' WHERE id = :id
        ");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Aktifkan kembali nasabah yang sebelumnya dinonaktifkan.
     *
     * Riwayat transaksi tidak tersentuh, sama seperti nonaktifkan().
     *
     * @param int $id
     * @return bool
     */
    public function aktifkan(int $id): bool
    {
        $stmt = $this->db->prepare("
            UPDATE nasabah SET status = 'AKTIF' WHERE id = :id
        ");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Buat nasabah sekaligus akunnya (FR-02).
     *
     * akun dan data nasabah dibuat dalam satu transaksi database.
     *
     * @param array $data Memuat: nama, no_hp, email, password, bank_sampah_id,
     *                    no_anggota, nik, alamat, rt, rw, tipe, nama_bank, no_rekening
     * @return array|false ['id' => id_nasabah, 'user_id' => id_user] atau false
     */
    public function createDenganAkun(array $data)
    {
        $this->db->beginTransaction();

        try {
            $stmtUser = $this->db->prepare("
                INSERT INTO users (nama, no_hp, email, password_hash, role, status, bank_sampah_id)
                VALUES (:nama, :no_hp, :email, :password_hash, 'NASABAH', 'AKTIF', :bank_id)
            ");
            $stmtUser->execute([
                'nama' => $data['nama'],
                'no_hp' => $data['no_hp'],
                'email' => !empty($data['email']) ? $data['email'] : null,
                'password_hash' => $data['password_hash'],
                'bank_id' => (int) $data['bank_sampah_id'],
            ]);
            $userId = (int) $this->db->lastInsertId();

            $stmtNasabah = $this->db->prepare("
                INSERT INTO nasabah
                (user_id, bank_sampah_id, no_anggota, nama, no_hp, nik, alamat, rt, rw, tipe,
                 nama_bank, no_rekening, email, saldo, status, tgl_daftar)
                VALUES (:user_id, :bank_id, :no_anggota, :nama, :no_hp, :nik, :alamat, :rt, :rw, :tipe,
                 :nama_bank, :no_rekening, :email, 0, 'AKTIF', :tgl_daftar)
            ");
            $stmtNasabah->execute([
                'user_id' => $userId,
                'bank_id' => (int) $data['bank_sampah_id'],
                'no_anggota' => $data['no_anggota'],
                'nama' => $data['nama'],
                'no_hp' => $data['no_hp'],
                'nik' => !empty($data['nik']) ? $data['nik'] : null,
                'alamat' => !empty($data['alamat']) ? $data['alamat'] : null,
                'rt' => !empty($data['rt']) ? $data['rt'] : null,
                'rw' => !empty($data['rw']) ? $data['rw'] : null,
                'tipe' => $data['tipe'] ?? 'PERORANGAN',
                'nama_bank' => !empty($data['nama_bank']) ? $data['nama_bank'] : null,
                'no_rekening' => !empty($data['no_rekening']) ? $data['no_rekening'] : null,
                'email' => !empty($data['email']) ? $data['email'] : null,
                'tgl_daftar' => $data['tgl_daftar'] ?? date('Y-m-d'),
            ]);
            $nasabahId = (int) $this->db->lastInsertId();

            $this->db->commit();

            return ['id' => $nasabahId, 'user_id' => $userId];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('[Nasabah::createDenganAkun] gagal: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Riwayat transaksi nasabah: setoran (jenis, berat, nilai) dan pencairan.
     *
     * Setoran tunai ikut ditampilkan karena tidak memengaruhi saldo
     * (FR-05).
     *
     * @param int $nasabahId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getRiwayatTransaksi(int $nasabahId, int $limit = 20, int $offset = 0): array
    {
        $sql = "
            SELECT
                tb.id,
                tb.no_transaksi,
                tb.tanggal,
                tb.metode_bayar,
                tb.tipe_penjual,
                tb.total_berat,
                tb.total_nilai,
                tb.status,
                tb.catatan,
                (SELECT GROUP_CONCAT(k.nama ORDER BY d.id SEPARATOR ', ')
                   FROM detail_beli d
                   JOIN kategori_sampah k ON k.id = d.kategori_id
                  WHERE d.transaksi_beli_id = tb.id) as kategori_nama,
                pp.no_pengajuan,
                pp.jumlah as pencairan_jumlah,
                pp.metode as pencairan_metode
            FROM transaksi_beli tb
            WHERE tb.nasabah_id = :nasabah_id
            UNION ALL
            SELECT
                NULL AS id,
                pp.no_pengajuan,
                pp.diajukan_at AS tanggal,
                NULL AS metode_bayar,
                NULL AS tipe_penjual,
                NULL AS total_berat,
                pp.jumlah AS total_nilai,
                pp.status,
                pp.alasan_tolak AS catatan,
                NULL AS kategori_nama,
                pp.no_pengajuan,
                pp.jumlah AS pencairan_jumlah,
                pp.metode AS pencairan_metode
            FROM pengajuan_pencairan pp
            WHERE pp.nasabah_id = :nasabah_id2
              AND pp.status = 'DICAIRKAN'
            ORDER BY tanggal DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue('nasabah_id', $nasabahId, PDO::PARAM_INT);
        $stmt->bindValue('nasabah_id2', $nasabahId, PDO::PARAM_INT);
        $stmt->bindValue('limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', (int) $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Hitung total baris riwayat transaksi nasabah.
     *
     * @param int $nasabahId
     * @return int
     */
    public function countRiwayatTransaksi(int $nasabahId): int
    {
        $stmt = $this->db->prepare("
            SELECT (
                (SELECT COUNT(*) FROM transaksi_beli WHERE nasabah_id = :id1)
                +
                (SELECT COUNT(*) FROM pengajuan_pencairan WHERE nasabah_id = :id2 AND status = 'DICAIRKAN')
            ) AS total
        ");
        $stmt->execute(['id1' => $nasabahId, 'id2' => $nasabahId]);
        $result = $stmt->fetch();

        return (int) ($result['total'] ?? 0);
    }
}
