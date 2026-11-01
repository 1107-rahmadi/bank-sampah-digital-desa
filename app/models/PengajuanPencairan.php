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

    // Label status yang dipakai di seluruh UI.
    // Satu istilah untuk satu status (lihat PROSES_BISNIS.md bagian 3).
    // "Ditunggu" tidak dipakai lagi; yang dipakai adalah "Menunggu".
    const LABEL_DIAJUKAN = 'Menunggu';
    const LABEL_DISETUJUI = 'Disetujui';
    const LABEL_DITOLAK = 'Ditolak';
    const LABEL_DICAIRKAN = 'Dicairkan';
    const LABEL_DIBATALKAN = 'Dibatalkan';

    // Pelaku pembatalan (kolom dibatalkan_oleh)
    const PELAKU_NASABAH = 'NASABAH';
    const PELAKU_PENGELOLA = 'PENGELOLA';

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
     * Semua status pencairan beserta label UI-nya.
     *
     * Dipakai sebagai sumber kebenaran istilah status supaya tab filter,
     * badge, chip, dan halaman detail memakai kata yang sama.
     *
     * @return array status => label
     */
    public static function semuaStatus(): array
    {
        return [
            self::STATUS_DIAJUKAN   => self::LABEL_DIAJUKAN,
            self::STATUS_DISETUJUI  => self::LABEL_DISETUJUI,
            self::STATUS_DITOLAK    => self::LABEL_DITOLAK,
            self::STATUS_DICAIRKAN  => self::LABEL_DICAIRKAN,
            self::STATUS_DIBATALKAN => self::LABEL_DIBATALKAN,
        ];
    }

    /**
     * Label UI dari suatu status.
     *
     * @param string|null $status
     * @return string
     */
    public static function labelStatus(?string $status): string
    {
        $status = (string) $status;
        $map = self::semuaStatus();

        return $map[$status] ?? ($status !== '' ? $status : '-');
    }

    /**
     * Transisi status yang sah.
     *
     * DITOLAK, DICAIRKAN, dan DIBATALKAN bersifat final: begitu sudah
     * sampai ke sana, statusnya tidak bisa diubah lagi.
     *
     * @return array status asal => daftar status tujuan yang sah
     */
    public static function transisi(): array
    {
        return [
            self::STATUS_DIAJUKAN   => [self::STATUS_DISETUJUI, self::STATUS_DITOLAK, self::STATUS_DIBATALKAN],
            self::STATUS_DISETUJUI  => [self::STATUS_DICAIRKAN, self::STATUS_DIBATALKAN],
            self::STATUS_DITOLAK    => [],
            self::STATUS_DICAIRKAN  => [],
            self::STATUS_DIBATALKAN => [],
        ];
    }

    /**
     * Apakah perubahan status dari $dari ke $ke diperbolehkan.
     *
     * @param string|null $dari
     * @param string      $ke
     * @return bool
     */
    public static function bolehTransisi(?string $dari, string $ke): bool
    {
        $transisi = self::transisi();
        $dari = (string) $dari;

        if (!isset($transisi[$dari])) {
            return false;
        }

        return in_array($ke, $transisi[$dari], true);
    }

    /**
     * Status yang tidak bisa diubah lagi.
     *
     * @param string|null $status
     * @return bool
     */
    public static function statusFinal(?string $status): bool
    {
        $transisi = self::transisi();
        $status = (string) $status;

        return isset($transisi[$status]) && empty($transisi[$status]);
    }

    /**
     * Pesan error untuk transisi yang tidak sah.
     *
     * @param string $dari
     * @param string $ke
     * @return string
     */
    public static function pesanTransisiDitolak(string $dari, string $ke): string
    {
        if (self::statusFinal($dari)) {
            return 'Pengajuan yang sudah ' . strtolower(self::labelStatus($dari)) . ' tidak bisa diubah lagi.';
        }

        return 'Status pengajuan tidak bisa diubah dari "' . self::labelStatus($dari)
            . '" ke "' . self::labelStatus($ke) . '".';
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
     * Core perubahan status: validasi transisi, ubah status, catat audit
     * log, lalu kirim notifikasi ke nasabah. Semua dalam satu transaksi.
     *
     * Validasi transisi dilakukan di sini (bukan hanya menyembunyikan
     * tombol di UI), jadi permintaan langsung ke server tetap ditolak.
     *
     * @param int         $id
     * @param string      $statusBaru
     * @param int|null    $userId Pelaku perubahan status
     * @param array       $opsi [
     *     'alasan'         => string|null,
     *     'pelaku_batal'   => string|null (NASABAH|PENGELOLA),
     *     'kirim_notifikasi' => bool
     * ]
     * @return bool
     */
    private function ubahStatus(int $id, string $statusBaru, $userId = null, array $opsi = []): bool
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

            $statusLama = (string) $pengajuan['status'];

            // DICAIRKAN hanya boleh lewat cairkan(): di sana saldo nasabah
            // dikurangi dan mutasi PENCAIRAN dicatat dalam satu transaksi.
            // Lewat jalur lain saldo bisa turun tanpa mutasi (atau sebaliknya).
            if ($statusBaru === self::STATUS_DICAIRKAN) {
                throw new RuntimeException('Status Dicairkan hanya bisa lewat proses pencairan.');
            }

            if (!self::bolehTransisi($statusLama, $statusBaru)) {
                throw new RuntimeException(self::pesanTransisiDitolak($statusLama, $statusBaru));
            }

            // Alasan wajib untuk penolakan dan pembatalan.
            $alasan = isset($opsi['alasan']) ? trim((string) $opsi['alasan']) : '';

            if ($statusBaru === self::STATUS_DITOLAK && $alasan === '') {
                throw new RuntimeException('Alasan penolakan harus diisi.');
            }
            if ($statusBaru === self::STATUS_DIBATALKAN && $alasan === '') {
                throw new RuntimeException('Alasan pembatalan harus diisi.');
            }

            $now = date('Y-m-d H:i:s');

            $sql = "UPDATE pengajuan_pencairan
                    SET status = :status,
                        diproses_at = :diproses_at,
                        diproses_oleh = :diproses_oleh";
            $params = [
                'status' => $statusBaru,
                'diproses_at' => $now,
                'diproses_oleh' => $userId !== null ? (int) $userId : null,
                'id' => $id,
            ];

            if ($statusBaru === self::STATUS_DITOLAK) {
                $sql .= ", alasan_tolak = :alasan_tolak";
                $params['alasan_tolak'] = $alasan;
            }

            if ($statusBaru === self::STATUS_DIBATALKAN) {
                $pelakuBatal = isset($opsi['pelaku_batal']) ? (string) $opsi['pelaku_batal'] : self::PELAKU_NASABAH;
                $sql .= ", alasan_batal = :alasan_batal,
                               dibatalkan_at = :dibatalkan_at,
                               dibatalkan_oleh = :dibatalkan_oleh";
                $params['alasan_batal'] = $alasan;
                $params['dibatalkan_at'] = $now;
                $params['dibatalkan_oleh'] = $pelakuBatal;
            }

            $sql .= " WHERE id = :id";

            $this->db->prepare($sql)->execute($params);

            // Audit log: pelaku, nilai lama, dan nilai baru (NFR-08).
            $sesudah = ['status' => $statusBaru];
            if ($alasan !== '') {
                $sesudah['alasan'] = $alasan;
            }
            if ($statusBaru === self::STATUS_DIBATALKAN) {
                $sesudah['dibatalkan_oleh'] = $params['dibatalkan_oleh'];
            }

            $this->db->prepare("
                INSERT INTO audit_log (user_id, aksi, tabel, record_id, sebelum, sesudah)
                VALUES (:user_id, :aksi, 'pengajuan_pencairan', :record_id, :sebelum, :sesudah)
            ")->execute([
                'user_id' => $userId !== null ? (int) $userId : null,
                'aksi' => self::namaAksi($statusBaru),
                'record_id' => $id,
                'sebelum' => json_encode(['status' => $statusLama]),
                'sesudah' => json_encode($sesudah),
            ]);

            // Notifikasi ke nasabah (FR-07).
            $kirimNotifikasi = array_key_exists('kirim_notifikasi', $opsi)
                ? (bool) $opsi['kirim_notifikasi']
                : true;

            if ($kirimNotifikasi && !empty($pengajuan['nasabah_user_id'])) {
                $pesan = self::pesanNotifikasi($statusBaru, $pengajuan, $alasan, $params['dibatalkan_oleh'] ?? null);
                $this->db->prepare("
                    INSERT INTO notifikasi (user_id, judul, isi, tipe)
                    VALUES (:user_id, :judul, :isi, :tipe)
                ")->execute([
                    'user_id' => (int) $pengajuan['nasabah_user_id'],
                    'judul' => $pesan['judul'],
                    'isi' => $pesan['isi'],
                    'tipe' => $pesan['tipe'],
                ]);
            }

            $this->db->commit();

            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->lastError = $e->getMessage();
            error_log('[PengajuanPencairan::ubahStatus] gagal: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Nama aksi untuk audit log.
     *
     * @param string $status
     * @return string
     */
    private static function namaAksi(string $status): string
    {
        $peta = [
            self::STATUS_DISETUJUI  => 'SETUJUI',
            self::STATUS_DITOLAK    => 'TOLAK',
            self::STATUS_DICAIRKAN  => 'DICAIRKAN',
            self::STATUS_DIBATALKAN => 'BATAL',
        ];

        return $peta[$status] ?? $status;
    }

    /**
     * Judul, isi, dan tipe notifikasi untuk perubahan status tertentu.
     *
     * @param string $status
     * @param array  $pengajuan
     * @param string $alasan
     * @param string|null $pelakuBatal NASABAH|PENGELOLA (untuk status DIBATALKAN)
     * @return array ['judul' => string, 'isi' => string, 'tipe' => string]
     */
    private static function pesanNotifikasi(string $status, array $pengajuan, string $alasan, ?string $pelakuBatal = null): array
    {
        $no = (string) ($pengajuan['no_pengajuan'] ?? '-');
        $jumlah = formatRupiahCents((int) toCents($pengajuan['jumlah'] ?? 0));

        switch ($status) {
            case self::STATUS_DISETUJUI:
                return [
                    'judul' => 'Pengajuan pencairan disetujui',
                    'isi' => 'Pengajuan ' . $no . ' sebesar ' . $jumlah . ' telah disetujui dan siap dicairkan.',
                    'tipe' => 'PENCAIRAN_DISETUJUI',
                ];

            case self::STATUS_DITOLAK:
                return [
                    'judul' => 'Pengajuan pencairan ditolak',
                    'isi' => 'Pengajuan ' . $no . ' ditolak. Alasan: ' . $alasan,
                    'tipe' => 'PENCAIRAN_DITOLAK',
                ];

            case self::STATUS_DIBATALKAN:
                $pelaku = $pelakuBatal === self::PELAKU_PENGELOLA ? 'pengelola' : 'nasabah';

                return [
                    'judul' => 'Pengajuan pencairan dibatalkan',
                    'isi' => 'Pengajuan ' . $no . ' dibatalkan oleh ' . $pelaku . '. Alasan: ' . $alasan,
                    'tipe' => 'PENCAIRAN_DIBATALKAN',
                ];

            case self::STATUS_DICAIRKAN:
                return [
                    'judul' => 'Pencairan dicairkan',
                    'isi' => 'Pengajuan ' . $no . ' sebesar ' . $jumlah . ' telah dicairkan.',
                    'tipe' => 'PENCAIRAN_DICAIRKAN',
                ];
        }

        return ['judul' => 'Status pengajuan pencairan diperbarui', 'isi' => 'Pengajuan ' . $no . '.', 'tipe' => 'PENCAIRAN'];
    }

    /**
     * Setujui pengajuan yang berstatus DIAJUKAN (Menunggu).
     *
     * @param int $id
     * @param int $userId
     * @return bool
     */
    public function setujui(int $id, int $userId): bool
    {
        return $this->ubahStatus($id, self::STATUS_DISETUJUI, $userId);
    }

    /**
     * Tolak pengajuan yang berstatus DIAJUKAN (Menunggu).
     *
     * @param int    $id
     * @param int    $userId
     * @param string $alasan Wajib diisi
     * @return bool
     */
    public function tolak(int $id, int $userId, string $alasan): bool
    {
        return $this->ubahStatus($id, self::STATUS_DITOLAK, $userId, ['alasan' => $alasan]);
    }

    /**
     * Batalkan pengajuan oleh nasabah.
     *
     * Nasabah hanya bisa membatalkan pengajuan yang masih DIAJUKAN
     * (Menunggu). Saldo yang tertahan dilepas kembali karena pengajuan
     * tidak lagi berstatus DIAJUKAN/DISETUJUI.
     *
     * @param int    $id
     * @param int    $userId
     * @param string $alasan
     * @return bool
     */
    public function batalkanNasabah(int $id, int $userId, string $alasan = 'Dibatalkan oleh nasabah'): bool
    {
        return $this->ubahStatus($id, self::STATUS_DIBATALKAN, $userId, [
            'alasan' => $alasan,
            'pelaku_batal' => self::PELAKU_NASABAH,
            // Nasabah yang membatalkan, jadi tidak perlu notifikasi ke dirinya.
            'kirim_notifikasi' => false,
        ]);
    }

    /**
     * Batalkan pengajuan oleh pengelola (hanya dari status DISETUJUI).
     *
     * Alasan pembatalan wajib diisi dan dikirim ke nasabah lewat notifikasi.
     * Saldo yang tertahan dilepas kembali karena pengajuan tidak lagi
     * berstatus DISETUJUI.
     *
     * @param int    $id
     * @param int    $userId
     * @param string $alasan Wajib diisi
     * @return bool
     */
    public function batalkanPengelola(int $id, int $userId, string $alasan): bool
    {
        return $this->ubahStatus($id, self::STATUS_DIBATALKAN, $userId, [
            'alasan' => $alasan,
            'pelaku_batal' => self::PELAKU_PENGELOLA,
        ]);
    }

    /**
     * Update status pengajuan.
     *
     * kept for backward compatibility: perubahan status divalidasi di
     * sisi server, jadi hanya transisi yang sah yang bisa lewat.
     *
     * @param int         $id
     * @param string      $status
     * @param int|null    $processedBy User ID yang memproses
     * @param string|null $alasan Alasan (wajib untuk DITOLAK dan DIBATALKAN)
     * @param string|null $dibatalkanOleh NASABAH|PENGELOLA (untuk DIBATALKAN)
     * @return bool
     */
    public function updateStatus(int $id, string $status, $processedBy = null, ?string $alasan = null, ?string $dibatalkanOleh = null): bool
    {
        $opsi = [];

        if ($alasan !== null) {
            $opsi['alasan'] = $alasan;
        }
        if ($dibatalkanOleh !== null) {
            $opsi['pelaku_batal'] = $dibatalkanOleh;
        }

        return $this->ubahStatus($id, $status, $processedBy, $opsi);
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
            $statusLama = (string) $pengajuan['status'];
            if (!self::bolehTransisi($statusLama, self::STATUS_DICAIRKAN)) {
                throw new RuntimeException(self::pesanTransisiDitolak($statusLama, self::STATUS_DICAIRKAN));
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
                'sebelum' => json_encode(['status' => $statusLama]),
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
                    VALUES (:user_id, :judul, :isi, 'PENCAIRAN_DICAIRKAN')
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
     * Batalkan pengajuan yang masih berstatus DIAJUKAN (Menunggu).
     *
     * Dipertahankan sebagai pembungkus batalkanNasabah() supaya kode lama
     * yang memanggil method ini tetap bekerja.
     *
     * @param int    $id
     * @param int    $userId
     * @param string $alasan
     * @return bool
     */
    public function batalkan(int $id, int $userId, string $alasan = 'Dibatalkan oleh nasabah'): bool
    {
        return $this->batalkanNasabah($id, $userId, $alasan);
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
     * @param int|null            $bankSampahId Filter berdasarkan bank sampah (null = semua)
     * @param string|array|null   $status Filter status (string tunggal atau daftar status)
     * @param int                 $limit
     * @param int                 $offset
     * @return array
     */
    public function getAll(?int $bankSampahId = null, $status = null, int $limit = 50, int $offset = 0): array
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
                p.dibatalkan_oleh,
                p.dibatalkan_at,
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

        $daftarStatus = self::normalisasiStatusFilter($status);
        if ($daftarStatus !== []) {
            $placeholder = [];
            foreach ($daftarStatus as $i => $s) {
                $placeholder[] = ':status_' . $i;
                $params['status_' . $i] = $s;
            }
            $sql .= " AND p.status IN (" . implode(', ', $placeholder) . ")";
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
     * @param int|null          $bankSampahId
     * @param string|array|null $status Status tunggal atau daftar status
     * @return int
     */
    public function countAll(?int $bankSampahId = null, $status = null): int
    {
        $sql = "SELECT COUNT(*) as total FROM pengajuan_pencairan p
                LEFT JOIN nasabah n ON p.nasabah_id = n.id WHERE 1=1";
        $params = [];

        if ($bankSampahId !== null) {
            $sql .= " AND n.bank_sampah_id = :bank_sampah_id";
            $params['bank_sampah_id'] = $bankSampahId;
        }

        $daftarStatus = self::normalisasiStatusFilter($status);
        if ($daftarStatus !== []) {
            $placeholder = [];
            foreach ($daftarStatus as $i => $s) {
                $placeholder[] = ':status_' . $i;
                $params['status_' . $i] = $s;
            }
            $sql .= " AND p.status IN (" . implode(', ', $placeholder) . ")";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();

        return (int) ($result['total'] ?? 0);
    }

    /**
     * Normalisasi filter status.
     *
     * Menerima string tunggal ("DIAJUKAN"), daftar status (array), atau
     * daftar dipisah koma/spasi ("DIAJUKAN,DISETUJUI"). Status yang tidak
     * dikenal dibuang supaya query tetap aman.
     *
     * @param string|array|null $status
     * @return string[]
     */
    private static function normalisasiStatusFilter($status): array
    {
        if ($status === null || $status === '' || $status === []) {
            return [];
        }

        $daftar = is_array($status) ? $status : preg_split('/[\s,]+/', (string) $status);
        $valid = self::semuaStatus();
        $hasil = [];

        foreach ($daftar as $s) {
            $s = trim((string) $s);
            if ($s !== '' && isset($valid[$s]) && !in_array($s, $hasil, true)) {
                $hasil[] = $s;
            }
        }

        return $hasil;
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
