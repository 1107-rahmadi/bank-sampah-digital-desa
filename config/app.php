<?php
/**
 * Konfigurasi Aplikasi - Bank Sampah Digital Desa
 */

// Load environment variables
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if (!array_key_exists($name, $_ENV)) {
                $_ENV[$name] = $value;
                putenv("$name=$value");
            }
        }
    }
}

// Konfigurasi Aplikasi
define('APP_NAME', 'Bank Sampah Digital Desa');
define('APP_VERSION', '1.0.0');
define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');
define('APP_DEBUG', filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN));
define('APP_TIMEZONE', $_ENV['TIMEZONE'] ?? 'Asia/Jakarta');
define('APP_SESSION_LIFETIME', (int)($_ENV['SESSION_LIFETIME'] ?? 3600));

// Auto-detect APP_URL dari request jika tidak diset
if (empty($_ENV['APP_URL'])) {
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' ? 'https' : 'http';
    $host = trim($_SERVER['HTTP_HOST'] ?? 'localhost', '.');
    $scriptName = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    // Hapus /public dari path jika ada (untuk compatibility dengan route ini)
    $basePath = str_replace('/public', '', $scriptName);
    $basePath = $basePath === '\\' ? '' : $basePath;
    define('APP_URL', "{$scheme}://{$host}{$basePath}");
} else {
    define('APP_URL', rtrim($_ENV['APP_URL'], '/'));
}

// Set timezone
date_default_timezone_set(APP_TIMEZONE);

// Role pengguna sistem
define('ROLES', [
    'nasabah'   => 'Nasabah',
    'pengelola' => 'Pengelola',
    'admin'     => 'Administrator',
    'kades'     => 'Kepala Desa'
]);

// Path constants
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('VIEW_PATH', APP_PATH . '/views');
define('CONTROLLER_PATH', APP_PATH . '/controllers');
define('MODEL_PATH', APP_PATH . '/models');
define('LIB_PATH', ROOT_PATH . '/lib');

/**
 * Generate URL dengan base path
 *
 * @param string $path
 * @return string
 */
function base_url(string $path = ''): string
{
    return APP_URL . '/' . ltrim($path, '/');
}

/**
 * Redirect ke URL lain
 *
 * @param string $url
 * @return void
 */
function redirect(string $url): void
{
    header('Location: ' . base_url($url));
    exit;
}

/**
 * Check if request is AJAX
 *
 * @return bool
 */
function is_ajax(): bool
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}
