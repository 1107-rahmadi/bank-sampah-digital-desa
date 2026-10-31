<?php
/**
 * TransaksiJual Model
 *
 * Model untuk interaksi dengan tabel transaksi_jual
 *
 * Aturan yang dijaga model ini (lihat PROSES_BISNIS.md):
 * - BR-01: harga jual yang dipakai disalin ke detail transaksi
 * - BR-07: penjualan tidak boleh melebihi stok per kategori. Stok per
 *           kategori DIJUMILAHKAN bila kategori yang sama muncul di
 *           beberapa baris.
 * - Bagian 2.3: stok berkurang setelah pengepul membayar. Penjualan
 *           yang belum dibayar dicatat sebagai piutang dan belum
 *           mengurangi stok; stoknya baru keluar saat ditandai lunas.
 * - BR-08: penjualan tidak dihapus, koreksi lewat pembatalan dengan alasan
 * - NFR-03: transaksi, detail dan stok berjalan atomik
 *
 * Compatible dengan PHP 7.3+
 */

class TransaksiJual
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
     * Pesan error terakhir.
     *
     * @return string|null
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Generate nomor transaksi.
     *
     * @return string
     */
    public function generateNoTrans(): string
    {
        $prefix = 'TJ-' . date('ymd');
        $stmt = $this->db->prepare("
            SELECT no_transaksi FROM transaksi_jual
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
     * Berat yang sudah dipesan tetapi belum dibayar (piutang berjalan),
     * dijumlahkan per kategori. Barang ini reserving stok sampai
     * transaksinya dilunasi atau dibatalkan.
     *
     * @param int $bankSampahId
     * @return array<int, int> kategori_id => berat (sen-kg)
     */
    public function getReservedByKategori(int $bankSampahId): array
    {
        $stmt = $this->db->prepare("
            SELECT dj.kategori_id, SUM(dj.berat_kg) AS berat
            FROM detail_jual dj
            JOIN transaksi_jual tj ON tj.id = dj.transaksi_jual_id
            WHERE tj.bank_sampah_id = :bank_id
              AND tj.status = 'SAH'
              AND tj.status_bayar = 'BELUM'
            GROUP BY dj.kategori_id
        ");
        $stmt->execute(['bank_id' => $bankSampahId]);

        $hasil = [];
        foreach ($stmt->fetchAll() as $row) {
            $hasil[(int) $row['kategori_id']] = (int) toCents($row['berat']);
        }

        return $hasil;
    }

    /**
     * Buat transaksi jual baru.
     *
     * @param array $data  bank_sampah_id, pengepul_id, total_berat_cents,
     *                     total_nilai_cents, petugas_id, status_bayar, catatan
     * @param array $items tiap item: kategori_id, berat_cents, harga_id,
     *                     harga_cents, subtotal_cents
     * @return int|false
     */
    public function create(array $data, array $items = [])
    {
        $this->lastError = null;

        $bankSampahId = (int) ($data['bank_sampah_id'] ?? 0);
        $pengepulId = (int) ($data['pengepul_id'] ?? 0);

        if ($bankSampahId <= 0) {
            $this->lastError = 'Bank sampah tidak diketahui.';
            return false;
        }
        if ($pengepulId <= 0) {
            $this->lastError = 'Pengepul harus dipilih.';
            return false;
        }
        if (empty($items)) {
            $this->lastError = 'Minimal harus ada 1 item sampah.';
            return false;
        }

        $statusBayar = ($data['status_bayar'] ?? 'BELUM') === 'LUNAS' ? 'LUNAS' : 'BELUM';

        // Pengepul harus aktif dan milik bank sampah ini.
        $stmtPengepul = $this->db->prepare("
            SELECT id, nama, status, bank_sampah_id FROM pengepul WHERE id = :id
        ");
        $stmtPengepul->execute(['id' => $pengepulId]);
        $pengepul = $stmtPengepul->fetch();
        if (!$pengepul || (int) $pengepul['bank_sampah_id'] !== $bankSampahId) {
            $this->lastError = 'Pengepul tidak ditemukan.';
            return false;
        }
        if (($pengepul['status'] ?? '') !== 'AKTIF') {
            $this->lastError = 'Pengepul tidak aktif.';
            return false;
        }

        // Jumlahkan berat per kategori (BR-07).
        $beratPerKategori = [];
        foreach ($items as $item) {
            $kid = (int) $item['kategori_id'];
            $beratPerKategori[$kid] = ($beratPerKategori[$kid] ?? 0) + (int) $item['berat_cents'];
        }

        $this->db->beginTransaction();

        try {
            // Cek stok per kategori, dikurangi barang yang sudah dipesan
            // tapi belum dibayar.
            $reserved = $this->getReservedByKategori($bankSampahId);

            $stmtStok = $this->db->prepare("
                SELECT berat_kg FROM stok_sampah
                WHERE bank_sampah_id = :bank_id AND kategori_id = :kategori_id
                FOR UPDATE
            ");
            $stmtKategori = $this->db->prepare("SELECT nama FROM kategori_sampah WHERE id = :id");

            foreach ($beratPerKategori as $kategoriId => $beratCents) {
                $stmtStok->execute([
                    'bank_id' => $bankSampahId,
                    'kategori_id' => $kategoriId,
                ]);
                $row = $stmtStok->fetch();
                $stokCents = $row ? (int) toCents($row['berat_kg']) : 0;
                $tersedia = $stokCents - ($reserved[$kategoriId] ?? 0);

                if ($beratCents > $tersedia) {
                    $stmtKategori->execute(['id' => $kategoriId]);
                    $kategori = $stmtKategori->fetch();
                    $namaKategori = $kategori ? $kategori['nama'] : 'kategori';

                    throw new RuntimeException(
                        'Stok tidak cukup untuk ' . $namaKategori . '. Tersedia '
                        . formatBerat(max(0, $tersedia)) . ', diminta ' . formatBerat($beratCents) . '.'
                    );
                }
            }

            $noTrans = $this->generateNoTrans();
            $totalBeratCents = (int) ($data['total_berat_cents'] ?? 0);
            $totalNilaiCents = (int) ($data['total_nilai_cents'] ?? 0);

            $stmt = $this->db->prepare("
                INSERT INTO transaksi_jual
                (no_transaksi, bank_sampah_id, pengepul_id, petugas_id, tanggal, total_berat,
                 total_nilai, status_bayar, dibayar_at, status, catatan)
                VALUES
                (:no_trans, :bank_id, :pengepul_id, :petugas_id, :tanggal, :total_berat,
                 :total_nilai, :status_bayar, :dibayar_at, 'SAH', :catatan)
            ");
            $stmt->execute([
                'no_trans' => $noTrans,
                'bank_id' => $bankSampahId,
                'pengepul_id' => $pengepulId,
                'petugas_id' => (int) $data['petugas_id'],
                'tanggal' => $data['tanggal'] ?? date('Y-m-d H:i:s'),
                'total_berat' => fromCents($totalBeratCents),
                'total_nilai' => fromCents($totalNilaiCents),
                'status_bayar' => $statusBayar,
                'dibayar_at' => $statusBayar === 'LUNAS' ? date('Y-m-d H:i:s') : null,
                'catatan' => trim((string) ($data['catatan'] ?? '')) !== '' ? trim((string) $data['catatan']) : null,
            ]);

            $transaksiId = (int) $this->db->lastInsertId();

            $stmtDetail = $this->db->prepare("
                INSERT INTO detail_jual (transaksi_jual_id, kategori_id, berat_kg, harga_per_kg, subtotal, harga_id)
                VALUES (:trans_id, :kategori_id, :berat, :harga, :subtotal, :harga_id)
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
            }

            // Stok keluar hanya setelah pengepul membayar (bagian 2.3).
            if ($statusBayar === 'LUNAS') {
                $this->kurangiStok($bankSampahId, $beratPerKategori);
            }

            $this->db->prepare("
                INSERT INTO audit_log (user_id, aksi, tabel, record_id, sesudah)
                VALUES (:user_id, 'BUAT', 'transaksi_jual', :record_id, :sesudah)
            ")->execute([
                'user_id' => (int) $data['petugas_id'],
                'record_id' => $transaksiId,
                'sesudah' => json_encode([
                    'no_transaksi' => $noTrans,
                    'pengepul_id' => $pengepulId,
                    'status_bayar' => $statusBayar,
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
            error_log('[TransaksiJual::create] gagal: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Kurangi stok per kategori (dijumlahkan per kategori).
     *
     * @param int   $bankSampahId
     * @param array $beratPerKategori kategori_id => berat (sen-kg)
     * @return void
     */
    private function kurangiStok(int $bankSampahId, array $beratPerKategori): void
    {
        $stmt = $this->db->prepare("
            UPDATE stok_sampah
            SET berat_kg = berat_kg - :berat
            WHERE bank_sampah_id = :bank_id AND kategori_id = :kategori_id
        ");

        foreach ($beratPerKategori as $kategoriId => $beratCents) {
            $stmt->execute([
                'berat' => fromCents($beratCents),
                'bank_id' => $bankSampahId,
                'kategori_id' => $kategoriId,
            ]);

            if ($stmt->rowCount() === 0) {
                throw new RuntimeException('Stok kategori tidak ditemukan saat mengurangi stok.');
            }
        }
    }

    /**
     * Tandai penjualan sudah dibayar (LUNAS) dan keluarkan stoknya.
     *
     * @param int $id
     * @param int $userId
     * @return bool
     */
    public function tandaiLunas(int $id, int $userId): bool
    {
        $this->lastError = null;

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("
                SELECT tj.* FROM transaksi_jual tj WHERE tj.id = :id FOR UPDATE
            ");
            $stmt->execute(['id' => $id]);
            $trx = $stmt->fetch();

            if (!$trx) {
                throw new RuntimeException('Transaksi tidak ditemukan.');
            }
            if ($trx['status'] === 'BATAL') {
                throw new RuntimeException('Transaksi ini sudah dibatalkan.');
            }
            if ($trx['status_bayar'] === 'LUNAS') {
                throw new RuntimeException('Penjualan ini sudah berstatus lunas.');
            }

            $stmtDetail = $this->db->prepare("
                SELECT kategori_id, berat_kg FROM detail_jual WHERE transaksi_jual_id = :id
            ");
            $stmtDetail->execute(['id' => $id]);
            $details = $stmtDetail->fetchAll();

            $beratPerKategori = [];
            foreach ($details as $d) {
                $kid = (int) $d['kategori_id'];
                $beratPerKategori[$kid] = ($beratPerKategori[$kid] ?? 0) + (int) toCents($d['berat_kg']);
            }

            // Stok harus benar-benar tersedia saat dilunasi.
            $stmtStok = $this->db->prepare("
                SELECT berat_kg FROM stok_sampah
                WHERE bank_sampah_id = :bank_id AND kategori_id = :kategori_id
                FOR UPDATE
            ");
            foreach ($beratPerKategori as $kategoriId => $beratCents) {
                $stmtStok->execute([
                    'bank_id' => (int) $trx['bank_sampah_id'],
                    'kategori_id' => $kategoriId,
                ]);
                $row = $stmtStok->fetch();
                $stokCents = $row ? (int) toCents($row['berat_kg']) : 0;

                if ($beratCents > $stokCents) {
                    throw new RuntimeException('Stok tidak cukup untuk melunasi penjualan ini.');
                }
            }

            $this->kurangiStok((int) $trx['bank_sampah_id'], $beratPerKategori);

            $this->db->prepare("
                UPDATE transaksi_jual
                SET status_bayar = 'LUNAS', dibayar_at = NOW()
                WHERE id = :id
            ")->execute(['id' => $id]);

            $this->db->prepare("
                INSERT INTO audit_log (user_id, aksi, tabel, record_id, sebelum, sesudah)
                VALUES (:user_id, 'LUNAS', 'transaksi_jual', :record_id, :sebelum, :sesudah)
            ")->execute([
                'user_id' => $userId,
                'record_id' => $id,
                'sebelum' => json_encode(['status_bayar' => 'BELUM']),
                'sesudah' => json_encode(['status_bayar' => 'LUNAS']),
            ]);

            $this->db->commit();

            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->lastError = $e->getMessage();
            error_log('[TransaksiJual::tandaiLunas] gagal: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Batalkan transaksi jual (BR-08).
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
                SELECT tj.* FROM transaksi_jual tj WHERE tj.id = :id FOR UPDATE
            ");
            $stmt->execute(['id' => $id]);
            $trx = $stmt->fetch();

            if (!$trx) {
                throw new RuntimeException('Transaksi tidak ditemukan.');
            }
            if ($trx['status'] === 'BATAL') {
                throw new RuntimeException('Transaksi ini sudah dibatalkan.');
            }

            $stmtDetail = $this->db->prepare("
                SELECT kategori_id, berat_kg FROM detail_jual WHERE transaksi_jual_id = :id
            ");
            $stmtDetail->execute(['id' => $id]);
            $details = $stmtDetail->fetchAll();

            $beratPerKategori = [];
            foreach ($details as $d) {
                $kid = (int) $d['kategori_id'];
                $beratPerKategori[$kid] = ($beratPerKategori[$kid] ?? 0) + (int) toCents($d['berat_kg']);
            }

            // Penjualan yang sudah lunas mengembalikan stok ke gudang.
            if ($trx['status_bayar'] === 'LUNAS') {
                $stmtStok = $this->db->prepare("
                    UPDATE stok_sampah
                    SET berat_kg = berat_kg + :berat
                    WHERE bank_sampah_id = :bank_id AND kategori_id = :kategori_id
                ");
                foreach ($beratPerKategori as $kategoriId => $beratCents) {
                    $stmtStok->execute([
                        'berat' => fromCents($beratCents),
                        'bank_id' => (int) $trx['bank_sampah_id'],
                        'kategori_id' => $kategoriId,
                    ]);
                }
            }

            $this->db->prepare("
                UPDATE transaksi_jual SET status = 'BATAL', alasan_batal = :alasan WHERE id = :id
            ")->execute(['alasan' => $alasan, 'id' => $id]);

            $this->db->prepare("
                INSERT INTO audit_log (user_id, aksi, tabel, record_id, sebelum, sesudah)
                VALUES (:user_id, 'BATAL', 'transaksi_jual', :record_id, :sebelum, :sesudah)
            ")->execute([
                'user_id' => $userId,
                'record_id' => $id,
                'sebelum' => json_encode(['status' => 'SAH', 'status_bayar' => $trx['status_bayar']]),
                'sesudah' => json_encode(['status' => 'BATAL', 'alasan' => $alasan]),
            ]);

            $this->db->commit();

            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->lastError = $e->getMessage();
            error_log('[TransaksiJual::cancel] gagal: ' . $e->getMessage());

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
            SELECT tj.*, b.nama as bank_nama, u.nama as petugas_nama,
                   p.nama as pengepul_nama, p.no_hp as pengepul_hp
            FROM transaksi_jual tj
            LEFT JOIN bank_sampah b ON tj.bank_sampah_id = b.id
            LEFT JOIN users u ON tj.petugas_id = u.id
            LEFT JOIN pengepul p ON tj.pengepul_id = p.id
            WHERE tj.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();

        if ($result) {
            $stmtDetail = $this->db->prepare("
                SELECT dj.*, k.nama as kategori_nama, k.jenis
                FROM detail_jual dj
                JOIN kategori_sampah k ON dj.kategori_id = k.id
                WHERE dj.transaksi_jual_id = :id
                ORDER BY dj.id ASC
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
            SELECT tj.*, b.nama as bank_nama, u.nama as petugas_nama,
                   p.nama as pengepul_nama
            FROM transaksi_jual tj
            LEFT JOIN bank_sampah b ON tj.bank_sampah_id = b.id
            LEFT JOIN users u ON tj.petugas_id = u.id
            LEFT JOIN pengepul p ON tj.pengepul_id = p.id
            WHERE tj.bank_sampah_id = :bank_id
        ";
        $params = ['bank_id' => $bankSampahId];

        if ($tanggalFrom) {
            $sql .= " AND tj.tanggal >= :tanggal_from";
            $params['tanggal_from'] = $tanggalFrom . ' 00:00:00';
        }
        if ($tanggalTo) {
            $sql .= " AND tj.tanggal <= :tanggal_to";
            $params['tanggal_to'] = $tanggalTo . ' 23:59:59';
        }
        if ($status !== null) {
            $sql .= " AND tj.status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY tj.tanggal DESC, tj.id DESC LIMIT :limit OFFSET :offset";
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
     * Statistik transaksi jual
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
                COALESCE(SUM(CASE WHEN status_bayar = 'LUNAS' THEN total_nilai ELSE 0 END), 0) as total_lunas,
                COALESCE(SUM(CASE WHEN status_bayar = 'BELUM' THEN total_nilai ELSE 0 END), 0) as total_belum_bayar
            FROM transaksi_jual
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
