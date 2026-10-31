<?php
/**
 * API - Transaksi
 *
 * Endpoints untuk data transaksi
 */

require_once MODEL_PATH . '/TransaksiBeli.php';
require_once MODEL_PATH . '/Nasabah.php';

/**
 * GET /api/transaksi
 *
 * List transaksi (requires auth)
 */
function transaksi_list($params = []): void
{
    $auth = api_auth();

    // Pagination params
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(50, max(10, (int)($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;

    // Optional filters
    $tanggalFrom = $_GET['tanggal_from'] ?? null;
    $tanggalTo = $_GET['tanggal_to'] ?? null;

    // Get bank_sampah_id from user if not admin
    $bankSampahId = null;
    if (!in_array(strtoupper($auth['role']), ['ADMIN', 'PENGELOLA'])) {
        // For NASABAH, get their bank_sampah_id
        $db = getDbConnection();
        $stmt = $db->prepare("
            SELECT n.bank_sampah_id
            FROM users u
            JOIN nasabah n ON u.id = n.user_id
            WHERE u.id = ?
        ");
        $stmt->execute([$auth['user_id']]);
        $result = $stmt->fetch();
        $bankSampahId = $result['bank_sampah_id'] ?? null;
    }

    $transaksiModel = new TransaksiBeli();
    $data = $transaksiModel->getByBankSampah($bankSampahId, $limit, $offset, $tanggalFrom, $tanggalTo);

    api_success([
        'data' => $data,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'offset' => $offset
        ],
        'filters' => [
            'tanggal_from' => $tanggalFrom,
            'tanggal_to' => $tanggalTo
        ]
    ]);
}

/**
 * GET /api/transaksi/:id
 *
 * Get transaksi detail
 */
function transaksi_detail($params = []): void
{
    $auth = api_auth();
    $id = (int)($params['id'] ?? 0);

    if (!$id) {
        api_error('ID transaksi wajib diisi', 400);
    }

    $transaksiModel = new TransaksiBeli();
    $data = $transaksiModel->findById($id);

    if (!$data) {
        api_error('Transaksi tidak ditemukan', 404);
    }

    api_success($data);
}
