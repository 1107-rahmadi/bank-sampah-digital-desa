<?php
/**
 * API - Dashboard
 *
 * Endpoints untuk statistik dashboard
 */

require_once MODEL_PATH . '/Analytics.php';
require_once MODEL_PATH . '/Nasabah.php';
require_once MODEL_PATH . '/TransaksiBeli.php';
require_once MODEL_PATH . '/PengajuanPencairan.php';

/**
 * GET /api/dashboard/stats
 *
 * Get dashboard statistics
 */
function dashboard_stats($params = []): void
{
    $auth = api_auth();

    $db = getDbConnection();

    // Get bank_sampah_id based on role
    $bankSampahId = null;
    if (!in_array(strtoupper($auth['role']), ['ADMIN'])) {
        $stmt = $db->prepare("
            SELECT bank_sampah_id FROM users WHERE id = ?
        ");
        $stmt->execute([$auth['user_id']]);
        $result = $stmt->fetch();
        $bankSampahId = $result['bank_sampah_id'] ?? null;
    }

    // Total Nasabah
    $stmtNasabah = $db->prepare("
        SELECT COUNT(*) as total FROM users WHERE role = 'NASABAH'
    ");
    $stmtNasabah->execute();
    $totalNasabah = $stmtNasabah->fetch()['total'];

    // Transaksi hari ini
    $stmtToday = $db->prepare("
        SELECT
            COUNT(*) as total_transaksi,
            COALESCE(SUM(total_nilai), 0) as total_nilai,
            COALESCE(SUM(total_berat), 0) as total_berat
        FROM transaksi_beli
        WHERE DATE(tanggal) = CURDATE()
        AND status = 'SAH'
    ");
    $stmtToday->execute();
    $todayStats = $stmtToday->fetch();

    // Transaksi bulan ini
    $stmtMonth = $db->prepare("SELECT COUNT(*) as total_transaksi,
            COALESCE(SUM(total_nilai), 0) as total_nilai,
            COALESCE(SUM(total_berat), 0) as total_berat
        FROM transaksi_beli
        WHERE MONTH(tanggal) = MONTH(CURDATE())
        AND YEAR(tanggal) = YEAR(CURDATE())
        AND status = 'SAH'
    ");
    $stmtMonth->execute();
    $monthStats = $stmtMonth->fetch();

    // Pending pencairan
    $stmtPending = $db->prepare("SELECT COUNT(*) as total, COALESCE(SUM(jumlah), 0) as total_nilai
        FROM pengajuan_pencairan
        WHERE status IN ('DIAJUKAN', 'DISETUJUI')
    ");
    $stmtPending->execute();
    $pendingStats = $stmtPending->fetch();

    // Saldo total所有Nasabah
    $stmtSaldo = $db->query("SELECT COALESCE(SUM(saldo), 0) as total FROM nasabah");
    $totalSaldo = $stmtSaldo->fetch()['total'];

    api_success([
        'nasabah' => [
            'total' => (int)$totalNasabah
        ],
        'transaksi_hari_ini' => [
            'jumlah' => (int)$todayStats['total_transaksi'],
            'nilai' => (float)$todayStats['total_nilai'],
            'berat' => (float)$todayStats['total_berat']
        ],
        'transaksi_bulan_ini' => [
            'jumlah' => (int)$monthStats['total_transaksi'],
            'nilai' => (float)$monthStats['total_nilai'],
            'berat' => (float)$monthStats['total_berat']
        ],
        'pencairan_pending' => [
            'jumlah' => (int)$pendingStats['total'],
            'nilai' => (float)$pendingStats['total_nilai']
        ],
        'total_saldo' => (float)$totalSaldo
    ]);
}
