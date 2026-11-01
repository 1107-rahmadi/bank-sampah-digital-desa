<?php
/**
 * TransaksiJual Model
 *
 * Model untuk interaksi dengan tabel transaksi_jual
 *
 * Aturan yang dijaga model ini (lihat PROSES_BISNIS.md):
 * - BR-01: harga jual yang dipakai disalin ke detail transaksi
 * - BR-07: penjualan tidak boleh melebihi stok per kategori. Stok per
 *           kategori DIJUMLAHKAN bila kategori yang sama muncul di
 *           beberapa baris.
 * - Bagian 2.3: stok berkurang setelah pengepul membayar. Penjualan
 *           yang belum dibayar dicatat sebagai piutang dan belum
 *           mengurangi stok; stoknya baru keluar saat ditandai lunas.
 * - BR-08: penjualan tidak dihapus, koreksi lewat pembatalan dengan alasan
 * - NFR-03: transaksi, detail dan stok berjalan atomik
 *
 * Perubahan pada versi ini:
 * - Baris stok dikunci (FOR UPDATE, urut kategori_id) SEBELUM menghitung
 *   barang yang tertahan, agar dua penjualan bersamaan tidak sama-sama lolos.
 * - Item divalidasi (kategori, berat > 0, harga > 0). Total berat dan total
 *   nilai dihitung dari item, bukan dipercaya dari $data.
 * - Nomor transaksi dicoba ulang bila bentrok (butuh UNIQUE INDEX, lihat bawah).
 * - Pengurangan stok tidak bisa membuat stok negatif.
 * - Pengembalian stok saat pembatalan memastikan baris stok ada.
 * - Ditambahkan countByBankSampah() untuk paging daftar dengan filter tanggal.
 *
 * Disarankan (jalankan sekali di database):
 *   ALTER TABLE transaksi_jual ADD UNIQUE KEY uq_no_transaksi (no_transaksi);
 *   ALTER TABLE transaksi_jual ADD KEY idx_bank_tanggal (bank_sampah_id, tanggal);
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
     * dijumlahkan per kategori. Barang ini menahan stok sampai
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
     * @param array $data  bank_sampah_id, pengepul_id, petugas_id, status_bayar
     *                     ('LUNAS' atau 'BELUM'), catatan, tanggal (opsional).
     *                     total_berat_cents dan total_nilai_cents diabaikan,
     *                     totalnya dihitung dari $items.
     * @param array $items tiap item: kategori_id, berat_cents, harga_id,
     *                     harga_cents, subtotal_cents. harga_cents harus
     *                     diambil controller dari tabel harga aktif di server
     *                     (BR-01), bukan dari input form.
     * @return int|false
     */
    public function create(array $data, array $items = [])
    {
        $this->lastError = null;

        $bankSampahId = (int) ($data['bank_sampah_id'] ?? 0);
        $pengepulId = (int) ($data['pengepul_id'] ?? 0);
        $petugasId = (int) ($data['petugas_id'] ?? 0);

        if ($bankSampahId <= 0) {
            $this->lastError = 'Bank sampah tidak diketahui.';
            return false;
        }
        if ($petugasId <= 0) {
            $this->lastError = 'Petugas tidak diketahui.';
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

        // Validasi dan normalisasi item. Total dihitung dari item.
        $itemsOk = [];
        $beratPerKategori = [];
        $totalBeratCents = 0;
        $totalNilaiCents = 0;

        foreach ($items as $item) {
            $kid = (int) ($item['kategori_id'] ?? 0);
            $berat = (int) ($item['berat_cents'] ?? 0);
            $harga = (int) ($item['harga_cents'] ?? 0);
            $subtotal = (int) ($item['subtotal_cents'] ?? 0);

            if ($kid <= 0 || $berat <= 0) {
                $this->lastError = 'Item penjualan tidak valid: kategori atau berat salah.';
                return false;
            }
            if ($harga <= 0) {
                $this->lastError = 'Harga jual belum diatur untuk salah satu jenis sampah. Hubungi admin.';
                return false;
            }

            $item['kategori_id'] = $kid;
            $item['berat_cents'] = $berat;
            $item['harga_cents'] = $harga;
            $item['subtotal_cents'] = $subtotal;
            $itemsOk[] = $item;

            // BR-07: jumlahkan berat per kategori.
            $beratPerKategori[$kid] = ($beratPerKategori[$kid] ?? 0) + $berat;
            $totalBeratCents += $berat;
            $totalNilaiCents += $subtotal;
        }

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

        $this->db->beginTransaction();

        try {
            // 1) Kunci dulu semua baris stok yang terlibat. Urut kategori_id
            //    supaya dua transaksi bersamaan tidak saling menunggu (deadlock).
            ksort($beratPerKategori);

            $stmtStok = $this->db->prepare("
                SELECT berat_kg FROM stok_sampah
                WHERE bank_sampah_id = :bank_id AND kategori_id = :kategori_id
                FOR UPDATE
            ");
            $stokCentsMap = [];
            foreach ($beratPerKategori as $kategoriId => $beratCents) {
                $stmtStok->execute([
                    'bank_id' => $bankSampahId,
                    'kategori_id' => $kategoriId,
                ]);
                $row = $stmtStok->fetch();
                $stokCentsMap[$kategoriId] = $row ? (int) toCents($row['berat_kg']) : 0;
            }

            // 2) Baru hitung barang yang sudah dipesan tapi belum dibayar.
            $reserved = $this->getReservedByKategori($bankSampahId);

            $stmtKategori = $this->db->prepare("SELECT nama FROM kategori_sampah WHERE id = :id");

            foreach ($beratPerKategori as $kategoriId => $beratCents) {
                $tersedia = $stokCentsMap[$kategoriId] - ($reserved[$kategoriId] ?? 0);

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

            $stmt = $this->db->prepare("
                INSERT INTO transaksi_jual
                (no_transaksi, bank_sampah_id, pengepul_id, petugas_id, tanggal, total_berat,
                 total_nilai, status_bayar, dibayar_at, status, catatan)
                VALUES
                (:no_trans, :bank_id, :pengepul_id, :petugas_id, :tanggal, :total_berat,
                 :total_nilai, :status_bayar, :dibayar_at, 'SAH', :catatan)
            ");

            $catatan = trim((string) ($data['catatan'] ?? ''));

            // Coba ulang bila nomor transaksi bentrok (butuh UNIQUE INDEX).
            $noTrans = '';
            $transaksiId = 0;
            $maxCoba = 3;
            for ($coba = 1; $coba <= $maxCoba; $coba++) {
                $noTrans = $this->generateNoTrans();
                try {
                    $stmt->execute([
                        'no_trans' => $noTrans,
                        'bank_id' => $bankSampahId,
                        'pengepul_id' => $pengepulId,
                        'petugas_id' => $petugasId,
                        'tanggal' => $data['tanggal'] ?? date('Y-m-d H:i:s'),
                        'total_berat' => fromCents($totalBeratCents),
                        'total_nilai' => fromCents($totalNilaiCents),
                        'status_bayar' => $statusBayar,
                        'dibayar_at' => $statusBayar === 'LUNAS' ? date('Y-m-d H:i:s') : null,
                        'catatan' => $catatan !== '' ? $catatan : null,
                    ]);
                    $transaksiId = (int) $this->db->lastInsertId();
                    break;
                } catch (PDOException $ex) {
                    $duplikat = isset($ex->errorInfo[1]) && (int) $ex->errorInfo[1] === 1062;
                    if (!$duplikat || $coba === $maxCoba) {
                        throw $ex;
                    }
                }
            }

            // BR-01: harga per kg disalin ke detail transaksi.
            $stmtDetail = $this->db->prepare("
                INSERT INTO detail_jual (transaksi_jual_id, kategori_id, berat_kg, harga_per_kg, subtotal, harga_id)
                VALUES (:trans_id, :kategori_id, :berat, :harga, :subtotal, :harga_id)
            ");
            foreach ($itemsOk as $item) {
                $stmtDetail->execute([
                    'trans_id' => $transaksiId,
                    'kategori_id' => $item['kategori_id'],
                    'berat' => fromCents($item['berat_cents']),
                    'harga' => fromCents($item['harga_cents']),
                    'subtotal' => fromCents($item['subtotal_cents']),
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
                'user_id' => $petugasId,
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
     * Stok tidak boleh menjadi negatif: bila tidak cukup, dilempar exception
     * sehingga seluruh transaksi di-rollback.
     *
     * @param int   $bankSampahId
     * @param array $beratPerKategori kategori_id => berat (sen-kg)
     * @return void
     */
    private function kurangiStok(int $bankSampahId, array $beratPerKategori): void
    {
        ksort($beratPerKategori);

        $stmt = $this->db->prepare("
            UPDATE stok_sampah
            SET berat_kg = berat_kg - :berat
            WHERE bank_sampah_id = :bank_id AND kategori_id = :kategori_id
              AND berat_kg >= :berat_min
        ");

        foreach ($beratPerKategori as $kategoriId => $beratCents) {
            $berat = fromCents($beratCents);
            $stmt->execute([
                'berat' => $berat,
                'berat_min' => $berat,
                'bank_id' => $bankSampahId,
                'kategori_id' => $kategoriId,
            ]);

            if ($stmt->rowCount() === 0) {
                throw new RuntimeException('Stok tidak cukup atau tidak ditemukan saat mengurangi stok.');
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
            ksort($beratPerKategori);

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
            ksort($beratPerKategori);

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

                    if ($stmtStok->rowCount() === 0) {
                        throw new RuntimeException('Baris stok tidak ditemukan saat mengembalikan stok.');
                    }
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
                $stmt->bindValue($key, (int) $value, PDO::PARAM_INT);
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
        $sql = "SELECT COUNT(*) FROM transaksi_jual tj WHERE tj.bank_sampah_id = :bank_id";
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

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
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