<?php
/**
 * API - Nasabah
 *
 * Endpoints untuk data nasabah: profile, saldo, riwayat, pencairan
 */

require_once MODEL_PATH . '/Nasabah.php';
require_once MODEL_PATH . '/MutasiSaldo.php';
require_once MODEL_PATH . '/PengajuanPencairan.php';

/**
 * GET /api/nasabah/profile
 *
 * Get profile info of logged in user
 */
function nasabah_profile($params = []): void
{
    $auth = api_auth();
    require_role('NASABAH', $auth);

    $userId = $auth['user_id'];
    $nasabahModel = new Nasabah();
    $nasabah = $nasabahModel->findByUserId($userId);

    if (!$nasabah) {
        api_error('Data profile tidak ditemukan', 404);
    }

    $saldo = $nasabahModel->getSaldo($nasabah['id']);
    $saldoTersedia = $nasabahModel->getSaldoTersedia($nasabah['id']);
    $mutasiModel = new MutasiSaldo($nasabahModel->getDb());

    $totalSetor = $mutasiModel->getTotalByTipe($nasabah['id'], 'SETOR');
    $totalCair = $mutasiModel->getTotalByTipe($nasabah['id'], 'PENCAIRAN');

    api_success([
        'id' => $nasabah['id'],
        'user_id' => $nasabah['user_id'],
        'no_anggota' => $nasabah['no_anggota'],
        'nama' => $nasabah['nama'],
        'no_hp' => $nasabah['no_hp'],
        'nik' => $nasabah['nik'],
        'alamat' => $nasabah['alamat'],
        'rt' => $nasabah['rt'],
        'rw' => $nasabah['rw'],
        'tipe' => $nasabah['tipe'],
        'nama_bank' => $nasabah['nama_bank'],
        'no_rekening' => $nasabah['no_rekening'],
        'bank_sampah_id' => $nasabah['bank_sampah_id'],
        'bank_sampah_nama' => $nasabah['bank_sampah_nama'],
        'status' => $nasabah['status'],
        'tgl_daftar' => $nasabah['tgl_daftar'],
        'saldo' => $saldo,
        'saldo_tersedia' => $saldoTersedia,
        'total_setor' => $totalSetor,
        'total_cair' => $totalCair
    ]);
}

/**
 * GET /api/nasabah/saldo
 *
 * Get current saldo info
 */
function nasabah_saldo($params = []): void
{
    $auth = api_auth();
    require_role('NASABAH', $auth);

    $userId = $auth['user_id'];
    $nasabahModel = new Nasabah();
    $nasabah = $nasabahModel->findByUserId($userId);

    if (!$nasabah) {
        api_error('Data rekening tidak ditemukan', 404);
    }

    $saldo = $nasabahModel->getSaldo($nasabah['id']);
    $saldoTersedia = $nasabahModel->getSaldoTersedia($nasabah['id']);
    $mutasiModel = new MutasiSaldo($nasabahModel->getDb());

    $pencairanModel = new PengajuanPencairan($nasabahModel->getDb());
    $pengajuanAktif = $pencairanModel->getAktifByNasabah($nasabah['id']);
    $totalTerikat = $pencairanModel->getTotalTerikat($nasabah['id']);

    api_success([
        'nasabah_id' => $nasabah['id'],
        'no_anggota' => $nasabah['no_anggota'],
        'saldo' => $saldo,
        'saldo_tersedia' => $saldoTersedia,
        'saldo_terikat' => $totalTerikat,
        'pengajuan_aktif' => $pengajuanAktif,
        'last_updated' => date('Y-m-d H:i:s')
    ]);
}

/**
 * GET /api/nasabah/riwayat
 *
 * Get transaction history (mutasi saldo)
 */
function nasabah_riwayat($params = []): void
{
    $auth = api_auth();
    require_role('NASABAH', $auth);

    $userId = $auth['user_id'];
    $nasabahModel = new Nasabah();
    $nasabah = $nasabahModel->findByUserId($userId);

    if (!$nasabah) {
        api_error('Data rekening tidak ditemukan', 404);
    }

    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(50, max(10, (int)($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;

    $mutasiModel = new MutasiSaldo($nasabahModel->getDb());
    $riwayat = $mutasiModel->getRiwayatLengkap($nasabah['id'], $limit, $offset);
    $total = $mutasiModel->countRiwayat($nasabah['id']);
    $totalPages = ceil($total / $limit);

    api_success([
        'data' => $riwayat,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_items' => $total,
            'per_page' => $limit,
            'has_next' => $page < $totalPages,
            'has_prev' => $page > 1
        ]
    ]);
}

/**
 * POST /api/nasabah/pencairan
 *
 * Create withdrawal request
 */
function nasabah_pencairan($params = []): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        api_error('Method not allowed', 405);
    }

    $auth = api_auth();
    require_role('NASABAH', $auth);

    $userId = $auth['user_id'];
    $input = get_json_input();

    $missing = validate_required($input, ['jumlah', 'metode']);
    if ($missing) {
        api_error('Field wajib diisi: ' . implode(', ', $missing), 400);
    }

    $jumlah = (float)$input['jumlah'];
    $metode = strtoupper($input['metode']);
    $tujuanTransfer = trim($input['tujuan_transfer'] ?? '');

    $nasabahModel = new Nasabah();
    $nasabah = $nasabahModel->findByUserId($userId);

    if (!$nasabah) {
        api_error('Data rekening tidak ditemukan', 404);
    }

    $saldoTersedia = $nasabahModel->getSaldoTersedia($nasabah['id']);
    $errors = PengajuanPencairan::validate($input, $saldoTersedia);

    if (!empty($errors)) {
        api_error('Validasi gagal', 400, $errors);
    }

    $pencairanModel = new PengajuanPencairan($nasabahModel->getDb());
    $result = $pencairanModel->create([
        'nasabah_id' => $nasabah['id'],
        'jumlah' => $jumlah,
        'metode' => $metode,
        'tujuan_transfer' => $metode === 'TRANSFER' ? $tujuanTransfer : null
    ]);

    if (!$result) {
        api_error('Gagal membuat pengajuan pencairan', 500);
    }

    $pengajuan = $pencairanModel->findById($result);

    api_success([
        'id' => $result,
        'no_pengajuan' => $pengajuan['no_pengajuan'],
        'jumlah' => $jumlah,
        'metode' => $metode,
        'status' => $pengajuan['status'],
        'diajukan_at' => $pengajuan['diajukan_at']
    ], 'Pengajuan pencairan berhasil diajukan', 201);
}

/**
 * GET /api/nasabah/pencairan
 *
 * List withdrawal requests
 */
function nasabah_pencairan_list($params = []): void
{
    $auth = api_auth();
    require_role('NASABAH', $auth);

    $userId = $auth['user_id'];
    $nasabahModel = new Nasabah();
    $nasabah = $nasabahModel->findByUserId($userId);

    if (!$nasabah) {
        api_error('Data rekening tidak ditemukan', 404);
    }

    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(50, max(10, (int)($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;

    $pencairanModel = new PengajuanPencairan($nasabahModel->getDb());
    $data = $pencairanModel->getByNasabah($nasabah['id'], $limit, $offset);
    $total = $pencairanModel->countByNasabah($nasabah['id']);
    $totalPages = ceil($total / $limit);

    api_success([
        'data' => $data,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_items' => $total,
            'per_page' => $limit
        ]
    ]);
}
