<?php
/**
 * API - Health Check
 *
 * Endpoint untuk cek status API
 */

/**
 * GET /api/health
 *
 * Check API health status
 */
function health_check($params = []): void
{
    $status = [
        'status' => 'ok',
        'service' => 'Bank Sampah API',
        'version' => '1.0.0',
        'timestamp' => date('Y-m-d H:i:s'),
        'checks' => []
    ];

    // Check database connection
    try {
        $db = getDbConnection();
        $db->query("SELECT 1");
        $status['checks']['database'] = [
            'status' => 'ok',
            'message' => 'Connected'
        ];
    } catch (Exception $e) {
        $status['checks']['database'] = [
            'status' => 'error',
            'message' => 'Connection failed'
        ];
        $status['status'] = 'degraded';
    }

    api_response($status);
}
