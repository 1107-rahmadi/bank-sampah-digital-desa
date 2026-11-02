<?php
/**api/index.php
 * API Router
 *
 * Entry point untuk semua request API
 */

// Start session for authenticated routes
session_start();

// Load database config
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/base.php';

// Path constants untuk API
define('ROOT_PATH', dirname(__DIR__));
define('MODEL_PATH', ROOT_PATH . '/app/models');

/**
 * API Routes Map
 *
 * Format: 'METHOD /path' => 'api_filename.function'
 */
$routes = [
    // Public routes
    'POST /api/auth/login' => 'auth.login',
    'POST /api/auth/register' => 'auth.register',

    // Nasabah routes (requires auth)
    'GET  /api/nasabah/profile' => 'nasabah.profile',
    'GET  /api/nasabah/saldo' => 'nasabah.saldo',
    'GET  /api/nasabah/riwayat' => 'nasabah.riwayat',
    'POST /api/nasabah/pencairan' => 'nasabah.pencairan',
    'GET  /api/nasabah/pencairan' => 'nasabah.pencairan_list',

    // Transaksi routes (requires auth)
    'GET  /api/transaksi' => 'transaksi.list',
    'GET  /api/transaksi/:id' => 'transaksi.detail',

    // Kategori routes
    'GET  /api/kategori' => 'kategori.list',
    'GET  /api/harga' => 'harga.list',

    // Dashboard stats
    'GET  /api/dashboard/stats' => 'dashboard.stats',

    // Health check
    'GET  /api/health' => 'health.check',
];

/**
 * Match route and call appropriate handler
 */

function match_route(array $routes)
{
    $method = $_SERVER['REQUEST_METHOD'];

    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uri = trim($uri, '/');

    // Hilangkan nama folder proyek dari URI.
    // Contoh: banksampah/api/health menjadi api/health.
    // Folder diambil dari lokasi script, jadi tidak perlu diubah saat folder diganti.
    $projectBase = trim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');

    if ($projectBase !== '' && strpos($uri, $projectBase . '/') === 0) {
        $uri = substr($uri, strlen($projectBase) + 1);
    }

    foreach ($routes as $pattern => $handler) {
        [$routeMethod, $routePath] = explode(' ', $pattern, 2);

        // Samakan format path route dan URI.
        $routePath = trim($routePath, ' /');

        // Bangun regex untuk route biasa dan parameter seperti :id.
        $segments = explode('/', $routePath);
        $regexSegments = [];

        foreach ($segments as $segment) {
            if (preg_match('/^:([a-zA-Z_][a-zA-Z0-9_]*)$/', $segment, $paramMatch)) {
                $regexSegments[] = '(?P<' . $paramMatch[1] . '>[^/]+)';
            } else {
                $regexSegments[] = preg_quote($segment, '#');
            }
        }

        $regex = '#^' . implode('/', $regexSegments) . '$#';

        // Cocokkan metode HTTP dan path.
        if (
            strtoupper($method) === strtoupper(trim($routeMethod)) &&
            preg_match($regex, $uri, $matches)
        ) {
            $params = array_filter(
                $matches,
                'is_string',
                ARRAY_FILTER_USE_KEY
            );

            return function () use ($handler, $params) {
                [$controller, $action] = explode('.', $handler);

                $file = __DIR__ . '/' . $controller . '.php';

                if (!file_exists($file)) {
                    api_error('API endpoint not found', 404);
                }

                require_once $file;

                $fn = $controller . '_' . $action;

                if (!function_exists($fn)) {
                    api_error('API action not found', 404);
                }

                return $fn($params);
            };
        }
    }

    api_error('Endpoint tidak ditemukan', 404);
}


// Execute matched route
$handler = match_route($routes);
$handler();
