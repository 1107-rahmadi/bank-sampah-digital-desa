<?php
/**
 * PengajuanPencairan Model
 *
 * Model untuk interaksi dengan tabel pengajuan_pencairan
 *
 * Aturan yang dijaga model ini (lihat PROSES_BISNIS.md):
 * - Bagian 3: pengajuan aktif (DIAJUKAN, DISETUJUI) menahan saldo.
 *   Saldo baru turun saat pengelola menandai pengajuan DICAIRKAN.
 * - BR-05: pencairan minimal dan tidak melebihi saldo tersedia
 * - BR-06: saldo turun hanya saat ditandai dicairkan
 * - Konvensi mutasi: jumlah pencairan dicatat NEGATIF (kurang)
 * - NFR-03: pencairan berjalan atomik dan baris nasabah dikunci (FOR UPDATE)
 * - FR-07: nasabah diberi notifikasi saat status pengajuan berubah
 *
 * Compatible dengan PHP 7.3+
 */

class PengajuanPencairan
{
    /** @var PDO */
    private $db;

    /** @var string|null */
    private $lastError = null;

    // Konstanta untuk validasi
    const MINIMAL_PENCAIRAN = 10000; // Rp 10.000

    // Konstanta status
    const STATUS_DIAJUKAN = 'DIAJUKAN';
    const STATUS_DISETUJUI = 'DISETUJUI';
    const STATUS_DITOLAK = 'DITOLAK';
    const STATUS_DICAIRKAN = 'DICAIRKAN';
    const STATUS_DIBATALKAN = 'DIBATALKAN';

    public function __construct($db = null)
    {
        $this->db = $db ?? getDbConnection();
    }

    /**
     * Pesan error terakhir.
     *
     * @return string|null
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Generate nomor pengajuan baru dengan format PC-XXXX
     *
     * @return string
     */
    public function generateNoPengajuan(): string
    {
        $stmt = $this->db->query("
            SELECT id FROM pengajuan_pencairan ORDER BY id DESC LIMIT 1
        ");
        $last = $stmt->fetch();

        $nextNum = ($last) ? ((int) $last['id']) + 1 : 1;

        return nomorUrut('PC', $nextNum);
    }

    /**
     * Buat pengajuan pencairan baru.
     *
     * Pengajuan tidak mengubah saldo; saldo hanya "ditahan" karena saldo
     * tersedia dihitung dari pengajuan yang berstatus DIAJUKAN/DISETUJUI.
     *
     * @param array $data
     * @return int|false Last insert ID jika berhasil, false jika gagal
     */
    public function create(array $data)
    {
        $this->lastError = null;

        $noPengajuan = $this->generateNoPengajuan();

        $stmt = $this->db->prepare("
            INSERT INTO pengajuan_pencairan
            (no_pengajuan, nasabah_id, jumlah, metode, tujuan_transfer, status, diajukan_at)
            VALUES
            (:no_pengajuan, :nasabah_id, :jumlah, :metode, :tujuan_transfer, :status, :diajukan_at)
        ");

        $params = [
            'no_pengajuan' => $noPengajuan,
            'nasabah_id' => (int) $data['nasabah_id'],
            'jumlah' => fromCents((int) toCents($data['jumlah'])),
            'metode' => $data['metode'],
            'tujuan_transfer' => $data['tujuan_transfer'] ?? null,
            'status' => self::STATUS_DIAJUKAN,
            'diajukan_at' => date('Y-m-d H:i:s')
        ];

        try {
            $stmt->execute($params);
        } catch (PDOException $e) {
            // Nomor bentrok: coba lagi dengan nomor berikutnya.
            if (($e->errorInfo[1] ?? 0) === 1062) {
                $params['no_pengajuan'] = $this->generateNoPengajuan();
                $stmt->execute($params);
            } else {
                $this->lastError = $e->getMessage();
                error_log('[PengajuanPencairan::create] gagal: ' . $e->getMessage());

                return false;
            }
        }

        $pengajuanId = (int) $this->db->lastInsertId();

        // Notifikasi ke pengelola bank sampah ini (bagian 8).
        $this->kirimNotifikasiPengajuanBaru($pengajuanId);

        return $pengajuanId;
    }

    /**
     * Kirim notifikasi ke pengelola bahwa ada pengajuan baru.
     *
     * @param int $pengajuanId
     * @return void
     */
    private function kirimNotifikasiPengajuanBaru(int $pengajuanId): void
    {
        $stmt = $this->db->prepare("
            SELECT p.no_pengajuan, p.jumlah, p.metode, n.nama as nama_nasabah,
                   n.bank_sampah_id, n.user_id as nasabah_user_id
            FROM pengajuan_pencairan p
            JOIN nasabah n ON n.id = p.nasabah_id
            WHERE p.id = :id
        ");
        $stmt->execute(['id' => $pengajuanId]);
        $row = $stmt->fetch();

        if (!$row) {
            return;
        }

        $stmtPengelola = $this->db->prepare("
            SELECT id FROM users
            WHERE role = 'PENGELOLA' AND bank_sampah_id = :bank_id AND status = 'AKTIF'
        ");
        $stmtPengelola->execute(['bank_id' => (int) $row['bank_sampah_id']]);

        $judul = 'Pengajuan pencairan baru';
        $isi = 'Pengajuan ' . $row['no_pengajuan'] . ' sebesar '
            . formatRupiahCents((int) toCents($row['jumlah'])) . ' dari ' . $row['nama_nasabah']
            . ' perlu diproses.';

        foreach ($stmtPengelola->fetchAll() as $pengelola) {
            $this->db->prepare("
                INSERT INTO notifikasi (user_id, judul, isi, tipe)
                VALUES (:user_id, :judul, :isi, 'PENGAJURAN')
            ")->execute([
                'user_id' => (int) $pengelola['id'],
                'judul' => $judul,
                'isi' => $isi,
            ]);
        }
    }

    /**
     * Ambil pengajuan berdasarkan ID
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id)
    {
        $stmt = $this->db->prepare("
            SELECT
                p.*,
                n.nama as nama_nasabah,
                n.no_hp as hp_nasabah,
                n.no_rekening,
                n.nama_bank,
                n.bank_sampah_id,
                n.user_id as nasabah_user_id,
                bs.nama as nama_bank_sampah,
                u.nama as diproses_oleh_nama
            FROM pengajuan_pencairan p
            LEFT JOIN nasabah n ON p.nasabah_id = n.id
            LEFT JOIN bank_sampah bs ON n.bank_sampah_id = bs.id
            LEFT JOIN users u ON p.diproses_oleh = u.id
            WHERE p.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    /**
     * Ambil semua pengajuan oleh seorang Nasabah
     *
     * @param int $nasabahId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getByNasabah(int $nasabahId, $limit = 20, $offset = 0): array
    {
        $stmt = $this->db->prepare("
            SELECT
                id,
                no_pengajuan,
                jumlah,
                metode,
                status,
                diajukan_at,
                diproses_at,
                dicairkan_at
            FROM pengajuan_pencairan
            WHERE nasabah_id = :nasabah_id
            ORDER BY diajukan_at DESC
            LIMIT :limit OFFSET :offset
        ");

        $stmt->bindValue('nasabah_id', $nasabahId, PDO::PARAM_INT);
        $stmt->bindValue('limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', (int) $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Ambil pengajuan yang masih aktif (DIAJUKAN atau DISETUJUI)
     *
     * @param int $nasabahId
     * @return array
     */
    public function getAktifByNasabah(int $nasabahId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                id,
                no_pengajuan,
                jumlah,
                metode,
                status,
                diajukan_at
            FROM pengajuan_pencairan
            WHERE nasabah_id = :nasabah_id
            AND status IN (:diajukan, :disetujui)
            ORDER BY diajukan_at DESC
        ");

        $stmt->bindValue('nasabah_id', $nasabahId, PDO::PARAM_INT);
        $stmt->bindValue('diajukan', self::STATUS_DIAJUKAN, PDO::PARAM_STR);
        $stmt->bindValue('disetujui', self::STATUS_DISETUJUI, PDO::PARAM_STR);

        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Update status pengajuan
     *
     * @param int         $id
     * @param string      $status
     * @param int|null    $processedBy User ID yang memproses
     * @param string|null $alasan Alasan penolakan (jika ditolak)
     * @return bool
     */
    public function updateStatus(int $id, string $status, $processedBy = null, $alasan = null): bool
    {
        $now = date('Y-m-d H:i:s');

        $sql = "UPDATE pengajuan_pencairan SET status = :status, diproses_at = :diproses_at";

        $params = [
            'status' => $status,
            'diproses_at' => $now,
            'id' => $id
        ];

        if ($processedBy !== null) {
            $sql .= ", diproses_oleh = :diproses_oleh";
            $params['diproses_oleh'] = $processedBy;
        }

        if ($status === self::STATUS_DITOLAK && $alasan !== null) {
            $sql .= ", alasan_tolak = :alasan_tolak";
            $params['alasan_tolak'] = $alasan;
        }

        if ($status === self::STATUS_DIBATALKAN) {
            $sql .= ", alasan_batal = :alasan_batal, dibatalkan_at = :dibatalkan_at";
            $params['alasan_batal'] = $alasan;
            $params['dibatalkan_at'] = $now;
        }

        if ($status === self::STATUS_DICAIRKAN) {
            $sql .= ", dicairkan_at = :dicairkan_at";
            $params['dicairkan_at'] = $now;
        }

        $sql .= " WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Tandai pengajuan sudah dicairkan.
     *
     * Semua berikut berjalan dalam satu transaksi database dengan baris
     * nasabah dikunci (SELECT ... FOR UPDATE) supaya pencairan tidak
     * bisa dilakukan dua kali (BR-06, NFR-03).
     *
     * @param int $id
     * @param int $userId
     * @return bool
     */
    public function cairkan(int $id, int $userId): bool
    {
        $this->lastError = null;

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("
                SELECT p.*, n.user_id as nasabah_user_id, n.nama as nama_nasabah
                FROM pengajuan_pencairan p
                JOIN nasabah n ON n.id = p.nasabah_id
                WHERE p.id = :id
                FOR UPDATE
            ");
            $stmt->execute(['id' => $id]);
            $pengajuan = $stmt->fetch();

            if (!$pengajuan) {
                throw new RuntimeException('Pengajuan tidak ditemukan.');
            }
            if ($pengajuan['status'] === self::STATUS_DICAIRKAN) {
                throw new RuntimeException('Pengajuan ini sudah dicairkan.');
            }
            if ($pengajuan['status'] !== self::STATUS_DISETUJUI) {
                throw new RuntimeException('Pengajuan belum disetujui.');
            }

            $nasabahId = (int) $pengajuan['nasabah_id'];
            $jumlahCents = (int) toCents($pengajuan['jumlah']);

            // Kunci baris nasabah.
            $stmtNasabah = $this->db->prepare("
                SELECT id, saldo FROM nasabah WHERE id = :id FOR UPDATE
            ");
            $stmtNasabah->execute(['id' => $nasabahId]);
            $nasabah = $stmtNasabah->fetch();

            if (!$nasabah) {
                throw new RuntimeException('Data nasabah tidak ditemukan.');
            }

            $saldoCents = (int) toCents($nasabah['saldo']);
            if ($jumlahCents > $saldoCents) {
                throw new RuntimeException('Saldo nasabah tidak mencukupi untuk pencairan ini.');
            }

            $saldoBaru = $saldoCents - $jumlahCents;

            $this->db->prepare("UPDATE nasabah SET saldo = :saldo WHERE id = :id")
                ->execute(['saldo' => fromCents($saldoBaru), 'id' => $nasabahId]);

            // Mutasi pencairan: jumlah NEGATIF (kurang), saldo_setelah benar.
            $this->db->prepare("
                INSERT INTO mutasi_saldo (nasabah_id, tipe, jumlah, saldo_setelah, referensi_tipe, referensi_id, keterangan)
                VALUES (:nasabah_id, 'PENCAIRAN', :jumlah, :saldo_setelah, 'pengajuan_pencairan', :ref_id, :keterangan)
            ")->execute([
                'nasabah_id' => $nasabahId,
                'jumlah' => fromCents(-$jumlahCents),
                'saldo_setelah' => fromCents($saldoBaru),
                'ref_id' => $id,
                'keterangan' => 'Pencairan ' . $pengajuan['no_pengajuan'] . ' (' . $pengajuan['metode'] . ')',
            ]);

            $this->db->prepare("
                UPDATE pengajuan_pencairan
                SET status = 'DICAIRKAN', dicairkan_at = NOW(), diproses_at = NOW(), diproses_oleh = :user_id
                WHERE id = :id
            ")->execute(['user_id' => $userId, 'id' => $id]);

            $this->db->prepare("
                INSERT INTO audit_log (user_id, aksi, tabel, record_id, sebelum, sesudah)
                VALUES (:user_id, 'DICAIRKAN', 'pengajuan_pencairan', :record_id, :sebelum, :sesudah)
            ")->execute([
                'user_id' => $userId,
                'record_id' => $id,
                'sebelum' => json_encode(['status' => self::STATUS_DISETUJUI]),
                'sesudah' => json_encode([
                    'status' => self::STATUS_DICAIRKAN,
                    'jumlah' => fromCents($jumlahCents),
                    'saldo_setelah' => fromCents($saldoBaru),
                ]),
            ]);

            // Notifikasi ke nasabah (FR-07).
            if (!empty($pengajuan['nasabah_user_id'])) {
                $this->db->prepare("
                    INSERT INTO notifikasi (user_id, judul, isi, tipe)
                    VALUES (:user_id, :judul, :isi, 'PENCAIRAN')
                ")->execute([
                    'user_id' => (int) $pengajuan['nasabah_user_id'],
                    'judul' => 'Pencairan dicairkan',
                    'isi' => 'Pengajuan ' . $pengajuan['no_pengajuan'] . ' sebesar '
                        . formatRupiahCents($jumlahCents) . ' telah dicairkan. Saldo Anda: '
                        . formatRupiahCents($saldoBaru) . '.',
                ]);
            }

            $this->db->commit();

            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->lastError = $e->getMessage();
            error_log('[PengajuanPencairan::cairkan] gagal: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Batalkan pengajuan yang masih berstatus DIAJUKAN (FR-08).
     *
     * @param int    $id
     * @param int    $userId
     * @param string $alasan
     * @return bool
     */
    public function batalkan(int $id, int $userId, string $alasan = 'Dibatalkan oleh nasabah'): bool
    {
        $this->lastError = null;

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("
                SELECT p.*, n.user_id as nasabah_user_id
                FROM pengajuan_pencairan p
                JOIN nasabah n ON n.id = p.nasabah_id
                WHERE p.id = :id
                FOR UPDATE
            ");
            $stmt->execute(['id' => $id]);
            $pengajuan = $stmt->fetch();

            if (!$pengajuan) {
                throw new RuntimeException('Pengajuan tidak ditemukan.');
            }
            if ($pengajuan['status'] !== self::STATUS_DIAJUKAN) {
                throw new RuntimeException('Hanya pengajuan yang masih menunggu yang bisa dibatalkan.');
            }

            $this->db->prepare("
                UPDATE pengajuan_pencairan
                SET status = 'DIBATALKAN', alasan_batal = :alasan, dibatalkan_at = NOW(),
                    diproses_at = NOW(), diproses_oleh = :user_id
                WHERE id = :id
            ")->execute([
                'alasan' => $alasan,
                'user_id' => $userId,
                'id' => $id,
            ]);

            $this->db->prepare("
                INSERT INTO audit_log (user_id, aksi, tabel, record_id, sebelum, sesudah)
                VALUES (:user_id, 'BATAL', 'pengajuan_pencairan', :record_id, :sebelum, :sesudah)
            ")->execute([
                'user_id' => $userId,
                'record_id' => $id,
                'sebelum' => json_encode(['status' => self::STATUS_DIAJUKAN]),
                'sesudah' => json_encode(['status' => self::STATUS_DIBATALKAN, 'alasan' => $alasan]),
            ]);

            $this->db->commit();

            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->lastError = $e->getMessage();
            error_log('[PengajuanPencairan::batalkan] gagal: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Hitung total pengajuan aktif (belum selesai) seorang Nasabah
     *
     * @param int $nasabahId
     * @return float
     */
    public function getTotalTerikat(int $nasabahId): float
    {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(jumlah), 0) as total
            FROM pengajuan_pencairan
            WHERE nasabah_id = :nasabah_id
            AND status IN (:diajukan, :disetujui)
        ");

        $stmt->bindValue('nasabah_id', $nasabahId, PDO::PARAM_INT);
        $stmt->bindValue('diajukan', self::STATUS_DIAJUKAN, PDO::PARAM_STR);
        $stmt->bindValue('disetujui', self::STATUS_DISETUJUI, PDO::PARAM_STR);

        $stmt->execute();
        $result = $stmt->fetch();

        return (float) ($result['total'] ?? 0);
    }

    /**
     * Hitung total pengajuan seorang Nasabah
     *
     * @param int $nasabahId
     * @return int
     */
    public function countByNasabah(int $nasabahId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total
            FROM pengajuan_pencairan
            WHERE nasabah_id = :nasabah_id
        ");
        $stmt->execute(['nasabah_id' => $nasabahId]);
        $result = $stmt->fetch();

        return (int) ($result['total'] ?? 0);
    }

    /**
     * Ambil semua pengajuan pencairan (untuk admin/pengelola)
     *
     * @param int|null    $bankSampahId Filter berdasarkan bank sampah (null = semua)
     * @param string|null $status Filter berdasarkan status
     * @param int         $limit
     * @param int         $offset
     * @return array
     */
    public function getAll(?int $bankSampahId = null, ?string $status = null, int $limit = 50, int $offset = 0): array
    {
        $sql = "
            SELECT
                p.id,
                p.no_pengajuan,
                p.nasabah_id,
                p.jumlah,
                p.metode,
                p.tujuan_transfer,
                p.status,
                p.alasan_tolak,
                p.alasan_batal,
                p.diajukan_at,
                p.diproses_at,
                p.diproses_oleh,
                p.dicairkan_at,
                n.nama as nama_nasabah,
                n.no_hp as hp_nasabah,
                n.no_rekening,
                n.bank_sampah_id,
                bs.nama as nama_bank_sampah,
                u.nama as diproses_oleh_nama
            FROM pengajuan_pencairan p
            LEFT JOIN nasabah n ON p.nasabah_id = n.id
            LEFT JOIN bank_sampah bs ON n.bank_sampah_id = bs.id
            LEFT JOIN users u ON p.diproses_oleh = u.id
            WHERE 1=1
        ";

        $params = [];

        if ($bankSampahId !== null) {
            $sql .= " AND n.bank_sampah_id = :bank_sampah_id";
            $params['bank_sampah_id'] = $bankSampahId;
        }

        if ($status !== null) {
            $sql .= " AND p.status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY p.diajukan_at DESC, p.id DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', (int) $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Hitung total pengajuan dengan filter
     *
     * @param int|null    $bankSampahId
     * @param string|null $status
     * @return int
     */
    public function countAll(?int $bankSampahId = null, ?string $status = null): int
    {
        $sql = "SELECT COUNT(*) as total FROM pengajuan_pencairan p
                LEFT JOIN nasabah n ON p.nasabah_id = n.id WHERE 1=1";
        $params = [];

        if ($bankSampahId !== null) {
            $sql .= " AND n.bank_sampah_id = :bank_sampah_id";
            $params['bank_sampah_id'] = $bankSampahId;
        }

        if ($status !== null) {
            $sql .= " AND p.status = :status";
            $params['status'] = $status;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();

        return (int) ($result['total'] ?? 0);
    }

    /**
     * Ambil statistik pengajuan per status
     *
     * @param int|null $bankSampahId
     * @return array
     */
    public function getStatistik(?int $bankSampahId = null): array
    {
        $sql = "SELECT p.status, COUNT(*) as jumlah, COALESCE(SUM(p.jumlah), 0) as total_nilai
                FROM pengajuan_pencairan p
                LEFT JOIN nasabah n ON p.nasabah_id = n.id
                WHERE 1=1";

        $params = [];
        if ($bankSampahId !== null) {
            $sql .= " AND n.bank_sampah_id = :bank_sampah_id";
            $params['bank_sampah_id'] = $bankSampahId;
        }

        $sql .= " GROUP BY p.status";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $result = [];
        $totalSemua = ['jumlah' => 0, 'total_nilai' => 0];

        while ($row = $stmt->fetch()) {
            $result[$row['status']] = [
                'jumlah' => (int) $row['jumlah'],
                'total_nilai' => (float) $row['total_nilai']
            ];
            $totalSemua['jumlah'] += (int) $row['jumlah'];
            $totalSemua['total_nilai'] += (float) $row['total_nilai'];
        }

        $result['TOTAL'] = $totalSemua;
        return $result;
    }

    /**
     * Statistik pengajuan pencairan dalam satu periode (FR-17 laporan).
     *
     * Berbeda dengan getStatistik(), metode ini punya batas tanggal.
     * Pengajuan dihitung berdasarkan tanggal saat nasabah mengajukan
     * (diajukan_at), jadi laporan periode isi menunjukkan pengajuan yang
     * masuk pada periode tersebut, apa pun status akhirnya.
     *
     * @param int|null   $bankSampahId
     * @param string|null $tanggalFrom  Format Y-m-d
     * @param string|null $tanggalTo    Format Y-m-d
     * @return array status => ['jumlah' => int, 'total_nilai' => float]
     */
    public function getStatistikPeriode(?int $bankSampahId = null, ?string $tanggalFrom = null, ?string $tanggalTo = null): array
    {
        $sql = "SELECT p.status, COUNT(*) as jumlah, COALESCE(SUM(p.jumlah), 0) as total_nilai
                FROM pengajuan_pencairan p
                LEFT JOIN nasabah n ON p.nasabah_id = n.id
                WHERE 1=1";

        $params = [];
        if ($bankSampahId !== null) {
            $sql .= " AND n.bank_sampah_id = :bank_sampah_id";
            $params['bank_sampah_id'] = $bankSampahId;
        }

        if ($tanggalFrom) {
            $sql .= " AND p.diajukan_at >= :tanggal_from";
            $params['tanggal_from'] = $tanggalFrom . ' 00:00:00';
        }
        if ($tanggalTo) {
            $sql .= " AND p.diajukan_at <= :tanggal_to";
            $params['tanggal_to'] = $tanggalTo . ' 23:59:59';
        }

        $sql .= " GROUP BY p.status";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $result = [];
        $totalSemua = ['jumlah' => 0, 'total_nilai' => 0];

        while ($row = $stmt->fetch()) {
            $result[$row['status']] = [
                'jumlah' => (int) $row['jumlah'],
                'total_nilai' => (float) $row['total_nilai'],
            ];
            $totalSemua['jumlah'] += (int) $row['jumlah'];
            $totalSemua['total_nilai'] += (float) $row['total_nilai'];
        }

        $result['TOTAL'] = $totalSemua;
        return $result;
    }

    /**
     * Validasi data pengajuan
     *
     * @param array $data
     * @param float $saldoTersedia
     * @return array Array of error messages (empty if valid)
     */
    public static function validate(array $data, float $saldoTersedia): array
    {
        $errors = [];

        // Validasi jumlah
        if (!isset($data['jumlah']) || !is_numeric($data['jumlah'])) {
            $errors[] = 'Jumlah pencairan harus diisi dengan angka.';
        } else {
            $jumlah = (float) $data['jumlah'];

            if ($jumlah < self::MINIMAL_PENCAIRAN) {
                $errors[] = 'Jumlah minimal pencairan adalah Rp ' . number_format(self::MINIMAL_PENCAIRAN, 0, ',', '.');
            }

            if ($jumlah > $saldoTersedia) {
                $errors[] = 'Jumlah pencairan melebihi saldo yang tersedia (Rp ' . number_format($saldoTersedia, 0, ',', '.') . ').';
            }
        }

        // Validasi metode
        if (!isset($data['metode']) || !in_array($data['metode'], ['TUNAI', 'TRANSFER'])) {
            $errors[] = 'Metode pencairan harus dipilih.';
        }

        // Validasi tujuan transfer jika metode TRANSFER
        if (isset($data['metode']) && $data['metode'] === 'TRANSFER') {
            $tujuan = trim($data['tujuan_transfer'] ?? '');
            if (empty($tujuan)) {
                $errors[] = 'Tujuan transfer (nomor rekening/e-wallet) wajib diisi.';
            } elseif (strlen($tujuan) < 8) {
                $errors[] = 'Nomor tujuan transfer terlalu pendek.';
            }
        }

        return $errors;
    }
}
