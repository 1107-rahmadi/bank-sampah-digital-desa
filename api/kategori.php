<?php
/**
 * API - Kategori & Harga
 *
 * Endpoints untuk data kategori sampah dan harga
 */

require_once MODEL_PATH . '/KategoriSampah.php';
require_once MODEL_PATH . '/HargaSampah.php';

/**
 * GET /api/kategori
 *
 * List all waste categories
 */
function kategori_list($params = []): void
{
    $db = getDbConnection();
    $stmt = $db->query("SELECT id, nama, jenis, deskripsi, icon FROM kategori_sampah ORDER BY jenis, nama");
    $data = $stmt->fetchAll();

    // Group by jenis
    $grouped = [];
    foreach ($data as $item) {
        $jenis = $item['jenis'];
        if (!isset($grouped[$jenis])) {
            $grouped[$jenis] = [];
        }
        $grouped[$jenis][] = $item;
    }

    api_success([
        'all' => $data,
        'grouped' => $grouped
    ]);
}

/**
 * GET /api/harga
 *
 * List current prices
 */
function harga_list($params = []): void
{
    $db = getDbConnection();

    // Get prices with category info
    $stmt = $db->query("
        SELECT
            h.id,
            h.kategori_id,
            k.nama as kategori_nama,
            k.jenis,
            h.harga_per_kg,
            h.tgl_berlaku,
            h.status,
            b.nama as bank_sampah_nama
        FROM harga_sampah h
        JOIN kategori_sampah k ON h.kategori_id = k.id
        LEFT JOIN bank_sampah b ON h.bank_sampah_id = b.id
        WHERE h.status = 'AKTIF'
        ORDER BY k.jenis, k.nama
    ");
    $data = $stmt->fetchAll();

    // Group by jenis
    $grouped = [];
    foreach ($data as $item) {
        $jenis = $item['jenis'];
        if (!isset($grouped[$jenis])) {
            $grouped[$jenis] = [];
        }
        $grouped[$jenis][] = [
            'id' => $item['id'],
            'kategori_id' => $item['kategori_id'],
            'kategori_nama' => $item['kategori_nama'],
            'harga_per_kg' => $item['harga_per_kg'],
            'tgl_berlaku' => $item['tgl_berlaku']
        ];
    }

    api_success([
        'all' => $data,
        'grouped' => $grouped,
        'last_updated' => date('Y-m-d H:i:s')
    ]);
}
