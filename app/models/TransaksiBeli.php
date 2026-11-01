<?php
/**
 * TransaksiBeli Model
 *
 * Model untuk interaksi dengan tabel transaksi_beli
 *
 * Aturan yang dijaga model ini (lihat PROSES_BISNIS.md):
 * - BR-01: harga per kg disalin ke detail transaksi (harga_id ikut tersimpan)
 * - BR-02: pembelian dari non-nasabah selalu TUNAI (dipaksa di server)
 * - BR-03/BR-06: saldo hanya bergerak lewat mutasi_saldo
 * - BR-08: transaksi tidak dihapus, koreksi lewat pembatalan dengan alasan
 * - NFR-03: transaksi, detail, stok, saldo dan mutasi berjalan atomik
 *
 * Compatible dengan PHP 7.3+
 */

class TransaksiBeli
{
    /** @var PDO */
    private $db;

    /** @var string|null */
    private $lastError = null;

    public function __construct($db = null)
    {
        $this->db = $db ?? getDbConnection();
    }

    /**
     * Pesan error terakhir (untuk ditampilkan ke pengguna).
     *
     * @return string|null
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Generate nomor transaksi.
     * Format: TB-yymmdd-0001 (maksimal 20 karakter, sesuai konvensi bagian 8).
     *
     * @return string
     */
    public function generateNoTrans(): string
    {
        $prefix = 'TB-' . date('ymd');
        $stmt = $this->db->prepare("
            SELECT no_transaksi FROM transaksi_beli
            WHERE no_transaksi LIKE :prefix
            ORDER BY no_transaksi DESC LIMIT 1
        ");
        $stmt->execute(['prefix' => $prefix . '%']);
        $last = $stmt->fetch();

        $num = 1;
        if ($last && preg_match('/(\d+)$/', $last['no_transaksi'], $m)) {
            $num = (int) $m[1] + 1;
        }

        return $prefix . '-' . str_pad((string) $num, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Buat transaksi beli baru.
     *
     * Semua efek (transaksi, detail, stok, saldo, mutasi, notifikasi, audit)
     * berjalan dalam satu transaksi database.
     *
     * @param array $data  bank_sampah_id, tipe_penjual, nasabah_id, nama_penjual,
     *                     metode_bayar, tanggal, total_berat_cents, total_nilai_cents,
     *                     petugas_id, catatan
     * @param array $items tiap item: kategori_id, berat_cents, harga_id,
     *                     harga_cents, subtotal_cents
     * @return int|false ID transaksi, atau false bila gagal
     */
    public function create(array $data, array $items = [])
    {
        $this->lastError = null;

        $tipe = $data['tipe_penjual'] ?? '';
        $metode = $data['metode_bayar'] ?? '';

        // BR-02: non-nasabah selalu dibayar tunai, dipaksa di server.
        if ($tipe === 'NON_NASABAH') {
            $metode = 'TUNAI';
        }

        if (!in_array($tipe, ['NASABAH', 'NON_NASABAH'], true)) {
            $this->lastError = 'Tipe penjual tidak valid.';
            return false;
        }

        if (!in_array($metode, ['TABUNG', 'TUNAI'], true)) {
            $this->lastError = 'Metode pembayaran tidak valid.';
            return false;
        }

        if ($tipe === 'NASABAH' && empty($data['nasabah_id'])) {
            $this->lastError = 'Nasabah harus dipilih.';
            return false;
        }

        if ($tipe === 'NON_NASABAH' && !empty($data['nasabah_id'])) {
            $this->lastError = 'Transaksi non-nasabah tidak boleh terhubung ke nasabah.';
            return false;
        }

        if (empty($items)) {
            $this->lastError = 'Minimal harus ada 1 item sampah.';
            return false;
        }

        $bankSampahId = (int) ($data['bank_sampah_id'] ?? 0);
        if ($bankSampahId <= 0) {
            $this->lastError = 'Bank sampah tidak diketahui.';
            return false;
        }

        $tanggal = $data['tanggal'] ?? date('Y-m-d H:i:s');
        $totalBeratCents = (int) ($data['total_berat_cents'] ?? 0);
        $totalNilaiCents = (int) ($data['total_nilai_cents'] ?? 0);

        $this->db->beginTransaction();

        try {
            // Nasabah dicek dan dikunci supaya saldo tidak berubah di tengah proses.
            $nasabahId = null;
            $saldoNasabah = 0;
            $nasabahUserId = null;

            if ($tipe === 'NASABAH') {
                $stmtNasabah = $this->db->prepare("
                    SELECT n.id, n.user_id, n.saldo, n.status, n.bank_sampah_id
                    FROM nasabah n
                    WHERE n.id = :id
                    FOR UPDATE
                ");
                $stmtNasabah->execute(['id' => (int) $data['nasabah_id']]);
                $nasabah = $stmtNasabah->fetch();

                if (!$nasabah) {
                    throw new RuntimeException('Data nasabah tidak ditemukan.');
                }
                if ((int) $nasabah['bank_sampah_id'] !== $bankSampahId) {
                    throw new RuntimeException('Nasabah ini bukan anggota bank sampah Anda.');
                }
                if (($nasabah['status'] ?? '') !== 'AKTIF') {
                    throw new RuntimeException('Status nasabah tidak aktif.');
                }

                $nasabahId = (int) $nasabah['id'];
                $nasabahUserId = $nasabah['user_id'] ? (int) $nasabah['user_id'] : null;
                $saldoNasabah = (int) toCents($nasabah['saldo']);
            }

            $noTrans = $this->generateNoTrans();

            $stmt = $this->db->prepare("
                INSERT INTO transaksi_beli
                (uuid, no_transaksi, bank_sampah_id, tipe_penjual, nasabah_id, nama_penjual,
                 metode_bayar, tanggal, total_berat, total_nilai, petugas_id, status, catatan,
                 received_at)
                VALUES
                (:uuid, :no_trans, :bank_id, :tipe, :nasabah_id, :nama_penjual,
                 :metode, :tanggal, :total_berat, :total_nilai, :petugas_id, 'SAH', :catatan,
                 NOW())
            ");

            $stmt->execute([
                'uuid' => uuidV4(),
                'no_trans' => $noTrans,
                'bank_id' => $bankSampahId,
                'tipe' => $tipe,
                'nasabah_id' => $nasabahId,
                'nama_penjual' => $tipe === 'NON_NASABAH'
                    ? trim((string) ($data['nama_penjual'] ?? ''))
                    : null,
                'metode' => $metode,
                'tanggal' => $tanggal,
                'total_berat' => fromCents($totalBeratCents),
                'total_nilai' => fromCents($totalNilaiCents),
                'petugas_id' => (int) $data['petugas_id'],
                'catatan' => trim((string) ($data['catatan'] ?? '')) !== '' ? trim((string) $data['catatan']) : null,
            ]);

            $transaksiId = (int) $this->db->lastInsertId();

            // Detail item + harga yang dipakai disalin (BR-01).
            $stmtDetail = $this->db->prepare("
                INSERT INTO detail_beli (transaksi_beli_id, kategori_id, berat_kg, harga_per_kg, subtotal, harga_id)
                VALUES (:trans_id, :kategori_id, :berat, :harga, :subtotal, :harga_id)
            ");

            $stmtStok = $this->db->prepare("
                INSERT INTO stok_sampah (bank_sampah_id, kategori_id, berat_kg)
                VALUES (:bank_id, :kategori_id, :berat)
                ON DUPLICATE KEY UPDATE berat_kg = berat_kg + :berat2
            ");

            foreach ($items as $item) {
                $stmtDetail->execute([
                    'trans_id' => $transaksiId,
                    'kategori_id' => (int) $item['kategori_id'],
                    'berat' => fromCents((int) $item['berat_cents']),
                    'harga' => fromCents((int) $item['harga_cents']),
                    'subtotal' => fromCents((int) $item['subtotal_cents']),
                    'harga_id' => !empty($item['harga_id']) ? (int) $item['harga_id'] : null,
                ]);

                $stmtStok->execute([
                    'bank_id' => $bankSampahId,
                    'kategori_id' => (int) $item['kategori_id'],
                    'berat' => fromCents((int) $item['berat_cents']),
                    'berat2' => fromCents((int) $item['berat_cents']),
                ]);
            }

            // Saldo & mutasi (hanya bila nilai masuk tabungan).
            if ($tipe === 'NASABAH' && $metode === 'TABUNG' && $totalNilaiCents > 0) {
                $saldoBaru = $saldoNasabah + $totalNilaiCents;

                $stmtSaldo = $this->db->prepare("
                    UPDATE nasabah SET saldo = :saldo WHERE id = :id
                ");
                $stmtSaldo->execute([
                    'saldo' => fromCents($saldoBaru),
                    'id' => $nasabahId,
                ]);

                $stmtMutasi = $this->db->prepare("
                    INSERT INTO mutasi_saldo (nasabah_id, tipe, jumlah, saldo_setelah, referensi_tipe, referensi_id, keterangan)
                    VALUES (:nasabah_id, 'SETOR', :jumlah, :saldo_setelah, 'transaksi_beli', :ref_id, :keterangan)
                ");
                $stmtMutasi->execute([
                    'nasabah_id' => $nasabahId,
                    'jumlah' => fromCents($totalNilaiCents),
                    'saldo_setelah' => fromCents($saldoBaru),
                    'ref_id' => $transaksiId,
                    'keterangan' => 'Setoran ' . $noTrans,
                ]);

                // Notifikasi ke nasabah (bagian 8: notifikasi dalam aplikasi).
                if ($nasabahUserId) {
                    $stmtNotif = $this->db->prepare("
                        INSERT INTO notifikasi (user_id, judul, isi, tipe)
                        VALUES (:user_id, :judul, :isi, 'SETORAN')
                    ");
                    $stmtNotif->execute([
                        'user_id' => $nasabahUserId,
                        'judul' => 'Setoran sampah dicatat',
                        'isi' => 'Setoran ' . $noTrans . ' sebesar ' . formatRupiahCents($totalNilaiCents)
                            . ' telah masuk ke saldo tabungan Anda.',
                    ]);
                }
            }

            // Audit log (NFR-08).
            $stmtAudit = $this->db->prepare("
                INSERT INTO audit_log (user_id, aksi, tabel, record_id, sesudah)
                VALUES (:user_id, 'BUAT', 'transaksi_beli', :record_id, :sesudah)
            ");
            $stmtAudit->execute([
                'user_id' => (int) $data['petugas_id'],
                'record_id' => $transaksiId,
                'sesudah' => json_encode([
                    'no_transaksi' => $noTrans,
                    'tipe_penjual' => $tipe,
                    'metode_bayar' => $metode,
                    'total_berat' => fromCents($totalBeratCents),
                    'total_nilai' => fromCents($totalNilaiCents),
                ]),
            ]);

            $this->db->commit();

            return $transaksiId;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->lastError = $e->getMessage();
            error_log('[TransaksiBeli::create] gagal: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Batalkan transaksi beli (BR-08).
     * Transaksi tidak dihapus: statusnya BATAL, efek stok dan saldo dikembalikan,
     * dan pembatalan tercatat di audit log.
     *
     * @param int    $id
     * @param string $alasan
     * @param int    $userId
     * @return bool
     */
    public function cancel(int $id, string $alasan, int $userId): bool
    {
        $this->lastError = null;
        $alasan = trim($alasan);

        if ($alasan === '') {
            $this->lastError = 'Alasan pembatalan wajib diisi.';
            return false;
        }

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("
                SELECT tb.* FROM transaksi_beli tb
                WHERE tb.id = :id
                FOR UPDATE
            ");
            $stmt->execute(['id' => $id]);
            $trx = $stmt->fetch();

            if (!$trx) {
                throw new RuntimeException('Transaksi tidak ditemukan.');
            }
            if ($trx['status'] === 'BATAL') {
                throw new RuntimeException('Transaksi ini sudah dibatalkan.');
            }

            // Kembalikan stok.
            $stmtDetail = $this->db->prepare("
                SELECT kategori_id, berat_kg FROM detail_beli WHERE transaksi_beli_id = :id
            ");
            $stmtDetail->execute(['id' => $id]);
            $details = $stmtDetail->fetchAll();

            $stmtStok = $this->db->prepare("
                UPDATE stok_sampah
                SET berat_kg = GREATEST(0, berat_kg - :berat)
                WHERE bank_sampah_id = :bank_id AND kategori_id = :kategori_id
            ");
            foreach ($details as $d) {
                $stmtStok->execute([
                    'berat' => $d['berat_kg'],
                    'bank_id' => (int) $trx['bank_sampah_id'],
                    'kategori_id' => (int) $d['kategori_id'],
                ]);
            }

            // Kembalikan saldo bila nilai sempat masuk tabungan.
            if ($trx['tipe_penjual'] === 'NASABAH'
                && $trx['metode_bayar'] === 'TABUNG'
                && !empty($trx['nasabah_id'])) {
                $nasabahId = (int) $trx['nasabah_id'];

                $stmtNasabah = $this->db->prepare("
                    SELECT saldo FROM nasabah WHERE id = :id FOR UPDATE
                ");
                $stmtNasabah->execute(['id' => $nasabahId]);
                $row = $stmtNasabah->fetch();
                if (!$row) {
                    throw new RuntimeException('Data nasabah tidak ditemukan.');
                }

                $saldoSekarang = (int) toCents($row['saldo']);
                $nilai = (int) toCents($trx['total_nilai']);
                $saldoBaru = max(0, $saldoSekarang - $nilai);

                $this->db->prepare("UPDATE nasabah SET saldo = :saldo WHERE id = :id")
                    ->execute(['saldo' => fromCents($saldoBaru), 'id' => $nasabahId]);

                $this->db->prepare("
                    INSERT INTO mutasi_saldo (nasabah_id, tipe, jumlah, saldo_setelah, referensi_tipe, referensi_id, keterangan)
                    VALUES (:nasabah_id, 'KOREKSI', :jumlah, :saldo_setelah, 'transaksi_beli', :ref_id, :keterangan)
                ")->execute([
                    'nasabah_id' => $nasabahId,
                    'jumlah' => fromCents(-$nilai),
                    'saldo_setelah' => fromCents($saldoBaru),
                    'ref_id' => (int) $id,
                    'keterangan' => 'Pembatalan ' . $trx['no_transaksi'] . ': ' . $alasan,
                ]);
            }

            $this->db->prepare("
                UPDATE transaksi_beli SET status = 'BATAL', alasan_batal = :alasan WHERE id = :id
            ")->execute(['alasan' => $alasan, 'id' => $id]);

            $this->db->prepare("
                INSERT INTO audit_log (user_id, aksi, tabel, record_id, sebelum, sesudah)
                VALUES (:user_id, 'BATAL', 'transaksi_beli', :record_id, :sebelum, :sesudah)
            ")->execute([
                'user_id' => $userId,
                'record_id' => $id,
                'sebelum' => json_encode(['status' => 'SAH', 'no_transaksi' => $trx['no_transaksi']]),
                'sesudah' => json_encode(['status' => 'BATAL', 'alasan' => $alasan]),
            ]);

            $this->db->commit();

            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->lastError = $e->getMessage();
            error_log('[TransaksiBeli::cancel] gagal: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Ambil transaksi dengan detail
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id)
    {
        $stmt = $this->db->prepare("
            SELECT tb.*, b.nama as bank_nama, u.nama as petugas_nama,
            n.nama as nasabah_nama, n.no_hp as nasabah_hp
            FROM transaksi_beli tb
            LEFT JOIN bank_sampah b ON tb.bank_sampah_id = b.id
            LEFT JOIN users u ON tb.petugas_id = u.id
            LEFT JOIN nasabah n ON tb.nasabah_id = n.id
            WHERE tb.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();

        if ($result) {
            // Ambil detail items
            $stmtDetail = $this->db->prepare("
                SELECT db.*, k.nama as kategori_nama, k.jenis
                FROM detail_beli db
                JOIN kategori_sampah k ON db.kategori_id = k.id
                WHERE db.transaksi_beli_id = :id
                ORDER BY db.id ASC
            ");
            $stmtDetail->execute(['id' => $id]);
            $result['items'] = $stmtDetail->fetchAll();
        }

        return $result ?: null;
    }

    /**
     * Ambil transaksi berdasarkan bank sampah
     *
     * @param int         $bankSampahId
     * @param int         $limit
     * @param int         $offset
     * @param string|null $tanggalFrom
     * @param string|null $tanggalTo
     * @param string|null $status
     * @return array
     */
    public function getByBankSampah($bankSampahId, $limit = 50, $offset = 0, $tanggalFrom = null, $tanggalTo = null, $status = null): array
    {
        $sql = "
            SELECT tb.*, b.nama as bank_nama, u.nama as petugas_nama,
            n.nama as nasabah_nama, n.no_anggota
            FROM transaksi_beli tb
            LEFT JOIN bank_sampah b ON tb.bank_sampah_id = b.id
            LEFT JOIN users u ON tb.petugas_id = u.id
            LEFT JOIN nasabah n ON tb.nasabah_id = n.id
            WHERE tb.bank_sampah_id = :bank_id
        ";
        $params = ['bank_id' => $bankSampahId];

        if ($tanggalFrom) {
            $sql .= " AND tb.tanggal >= :tanggal_from";
            $params['tanggal_from'] = $tanggalFrom . ' 00:00:00';
        }

        if ($tanggalTo) {
            $sql .= " AND tb.tanggal <= :tanggal_to";
            $params['tanggal_to'] = $tanggalTo . ' 23:59:59';
        }

        if ($status !== null) {
            $sql .= " AND tb.status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY tb.tanggal DESC, tb.id DESC LIMIT :limit OFFSET :offset";
        $params['limit'] = $limit;
        $params['offset'] = $offset;

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            if ($key === 'limit' || $key === 'offset') {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

            /**
         * Hitung jumlah transaksi (untuk paging). Syarat filter sama dengan
         * getByBankSampah() supaya jumlah halaman akurat.
         *
         * @param int         $bankSampahId
         * @param string|null $tanggalFrom
         * @param string|null $tanggalTo
         * @param string|null $status
         * @return int
         */
        public function countByBankSampah($bankSampahId, $tanggalFrom = null, $tanggalTo = null, $status = null): int
        {
            $sql = "SELECT COUNT(*) FROM transaksi_beli tb WHERE tb.bank_sampah_id = :bank_id";
            $params = ['bank_id' => $bankSampahId];

            if ($tanggalFrom) {
                $sql .= " AND tb.tanggal >= :tanggal_from";
                $params['tanggal_from'] = $tanggalFrom . ' 00:00:00';
            }
            if ($tanggalTo) {
                $sql .= " AND tb.tanggal <= :tanggal_to";
                $params['tanggal_to'] = $tanggalTo . ' 23:59:59';
            }
            if ($status !== null) {
                $sql .= " AND tb.status = :status";
                $params['status'] = $status;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        }

    /**
     * Statistik transaksi beli
     *
     * @param int         $bankSampahId
     * @param string|null $tanggalFrom
     * @param string|null $tanggalTo
     * @return array
     */
    public function getStatistik($bankSampahId, $tanggalFrom = null, $tanggalTo = null): array
    {
        $sql = "
            SELECT
                COUNT(*) as total_transaksi,
                COALESCE(SUM(total_berat), 0) as total_berat,
                COALESCE(SUM(total_nilai), 0) as total_nilai,
                COALESCE(SUM(CASE WHEN metode_bayar = 'TABUNG' THEN total_nilai ELSE 0 END), 0) as total_tabungan,
                COALESCE(SUM(CASE WHEN metode_bayar = 'TUNAI' THEN total_nilai ELSE 0 END), 0) as total_tunai
            FROM transaksi_beli
            WHERE bank_sampah_id = :bank_id AND status = 'SAH'
        ";
        $params = ['bank_id' => $bankSampahId];

        if ($tanggalFrom) {
            $sql .= " AND tanggal >= :tanggal_from";
            $params['tanggal_from'] = $tanggalFrom . ' 00:00:00';
        }

        if ($tanggalTo) {
            $sql .= " AND tanggal <= :tanggal_to";
            $params['tanggal_to'] = $tanggalTo . ' 23:59:59';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
}
