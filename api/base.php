<?php
/**api/base.php
 * API Base Helper
 *
 * Helper untuk response API standard
 */

// Set default JSON response header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Send JSON response
 *
 * @param mixed $data Data to send
 * @param int $statusCode HTTP status code
 * @return void
 */
function api_response($data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Send success response
 *
 * @param mixed $data Data to send
 * @param string $message Success message
 * @param int $statusCode HTTP status code
 * @return void
 */
function api_success($data = null, string $message = 'Success', int $statusCode = 200): void
{
    $response = [
        'success' => true,
        'message' => $message
    ];

    if ($data !== null) {
        $response['data'] = $data;
    }

    api_response($response, $statusCode);
}

/**
 * Send error response
 *
 * @param string $message Error message
 * @param int $statusCode HTTP status code
 * @param mixed $errors Additional errors
 * @return void
 */
function api_error(string $message, int $statusCode = 400, $errors = null): void
{
    $response = [
        'success' => false,
        'message' => $message
    ];

    if ($errors !== null) {
        $response['errors'] = $errors;
    }

    api_response($response, $statusCode);
}

/**
 * Get JSON input from request body
 *
 * @return array
 */
function get_json_input(): array
{
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    return is_array($data) ? $data : [];
}

/**
 * Validate required fields
 *
 * @param array $data Data to validate
 * @param array $required Required fields
 * @return array|null Array of missing fields or null if valid
 */
function validate_required(array $data, array $required): ?array
{
    $missing = [];

    foreach ($required as $field) {
        if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
            $missing[] = $field;
        }
    }

    return empty($missing) ? null : $missing;
}

/**
 * Generate API token (simple version)
 * In production, use JWT or similar
 *
 * @param array $user User data
 * @return string
 */
function generate_api_token(array $user): string
{
    $payload = json_encode([
        'user_id' => $user['id'],
        'role' => $user['role'],
        'exp' => time() + (24 * 60 * 60) // 24 hours
    ]);

    $base64 = base64_encode($payload);
    $signature = hash_hmac('sha256', $base64, $_ENV['API_SECRET'] ?? 'bank-sampah-secret-key');

    return $base64 . '.' . $signature;
}

/**
 * Verify API token
 *
 * @param string|null $token
 * @return array|null User data from token or null if invalid
 */
function verify_api_token(?string $token): ?array
{
    if (empty($token)) {
        return null;
    }

    $parts = explode('.', $token);
    if (count($parts) !== 2) {
        return null;
    }

    [$base64, $signature] = $parts;
    $expectedSignature = hash_hmac('sha256', $base64, $_ENV['API_SECRET'] ?? 'bank-sampah-secret-key');

    if (!hash_equals($expectedSignature, $signature)) {
        return null;
    }

    $payload = json_decode(base64_decode($base64), true);

    if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) {
        return null;
    }

    return $payload;
}

/**
 * Get Bearer token from Authorization header
 *
 * @return string|null
 */
function get_bearer_token(): ?string
{
    $authHeader = $_SERVER['HTTP_AUTHORIZATION']
        ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        ?? '';

    // Cadangan: baca langsung dari header request jika fungsinya tersedia
    if ($authHeader === '' && function_exists('getallheaders')) {
        foreach (getallheaders() as $nama => $nilai) {
            if (strcasecmp($nama, 'Authorization') === 0) {
                $authHeader = $nilai;
                break;
            }
        }
    }

    if (preg_match('/Bearer\s+(.+)$/i', $authHeader, $matches)) {
        return trim($matches[1]);
    }

    return null;
}

/**
 * Authenticate API request
 *
 * @return array|null Returns user payload or exits with 401
 */
function api_auth(): ?array
{
    $token = get_bearer_token();
    $payload = verify_api_token($token);

    if (!$payload) {
        api_error('Unauthorized. Silakan login terlebih dahulu.', 401);
    }

    return $payload;
}

/**
 * Require specific role(s) for API access
 *
 * @param array|string $roles Required role(s)
 * @param array $userPayload User payload from token
 * @return void
 */
function require_role($roles, array $userPayload): void
{
    if (is_string($roles)) {
        $roles = [$roles];
    }

    $userRole = strtoupper($userPayload['role'] ?? '');
    $roles = array_map('strtoupper', $roles);

    if (!in_array($userRole, $roles)) {
        api_error('Forbidden. Anda tidak memiliki akses ke resource ini.', 403);
    }
}
