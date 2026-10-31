<?php
/**api.auth.php
 * API - Authentication
 *
 * Endpoints untuk autentikasi: login, register, logout
 */

require_once MODEL_PATH . '/User.php';
require_once MODEL_PATH . '/Nasabah.php';

/**
 * POST /api/auth/login
 *
 * Login dengan no_hp dan password
 */
function auth_login($params = []): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        api_error('Method not allowed', 405);
    }

    $input = get_json_input();
    $missing = validate_required($input, ['no_hp', 'password']);

    if ($missing) {
        api_error('Field wajib diisi: ' . implode(', ', $missing), 400);
    }

    $noHp = trim($input['no_hp']);
    $password = $input['password'];

    // Find user by no HP
    $userModel = new User();
    $user = $userModel->findByNoHp($noHp);

    // Verify password
    if (!$user || !password_verify($password, $user['password_hash'])) {
        api_error('No HP atau password salah', 401);
    }

    // Check if user is active
    if (!User::isActive($user)) {
        api_error('Akun tidak aktif. Hubungi administrator.', 403);
    }

    // Update last login
    $userModel->updateLastLogin($user['id']);

    // Generate token
    $token = generate_api_token($user);

    // Get additional data based on role
    $extraData = [];

    if (User::hasRole($user, 'NASABAH')) {
        $nasabahModel = new Nasabah();
        $nasabah = $nasabahModel->findByUserId($user['id']);
        $extraData['nasabah'] = $nasabah;
        $extraData['saldo'] = $nasabah ? $nasabahModel->getSaldo($nasabah['id']) : 0;
        $extraData['saldo_tersedia'] = $nasabah ? $nasabahModel->getSaldoTersedia($nasabah['id']) : 0;
    }

    api_success([
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'nama' => $user['nama'],
            'no_hp' => $user['no_hp'],
            'email' => $user['email'],
            'role' => $user['role'],
            'status' => $user['status']
        ],
        ...$extraData
    ], 'Login berhasil');
}

/**
 * POST /api/auth/register
 *
 * Registrasi user baru (NASABAH only)
 */
function auth_register($params = []): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        api_error('Method not allowed', 405);
    }

    $input = get_json_input();
    $missing = validate_required($input, ['nama', 'no_hp', 'password']);

    if ($missing) {
        api_error('Field wajib diisi: ' . implode(', ', $missing), 400);
    }

    $nama = trim($input['nama']);
    $noHp = trim($input['no_hp']);
    $password = $input['password'];
    $email = trim($input['email'] ?? '');
    $alamat = trim($input['alamat'] ?? '');

    // Validate no HP format (Indonesian)
    if (!preg_match('/^08[0-9]{8,11}$/', $noHp)) {
        api_error('Format no HP tidak valid. Contoh: 081234567890', 400);
    }

    // Validate password length
    if (strlen($password) < 6) {
        api_error('Password minimal 6 karakter', 400);
    }

    $db = getDbConnection();

    // Check if no HP already exists
    $stmt = $db->prepare("SELECT id FROM users WHERE no_hp = ?");
    $stmt->execute([$noHp]);
    if ($stmt->fetch()) {
        api_error('No HP sudah terdaftar', 409);
    }

    // Create user
    $userModel = new User();
    $userId = $db->query("SELECT MAX(id) + 1 as next_id FROM users")->fetch()['next_id'] ?? 1;

    $userData = [
        'nama' => $nama,
        'no_hp' => $noHp,
        'email' => $email,
        'password_hash' => User::hashPassword($password),
        'role' => 'NASABAH',
        'status' => 'AKTIF',
        'bank_sampah_id' => 1, // Default bank sampah
        'desa_id' => 1
    ];

    $stmt = $db->prepare("
        INSERT INTO users (nama, no_hp, email, password_hash, role, status, bank_sampah_id, desa_id)
        VALUES (:nama, :no_hp, :email, :password_hash, :role, :status, :bank_sampah_id, :desa_id)
    ");

    $stmt->execute($userData);
    $userId = $db->lastInsertId();

    // Create nasabah record
    $noAnggota = 'NS-' . date('ymd') . '-' . str_pad($userId, 4, '0', STR_PAD_LEFT);

    $stmtNasabah = $db->prepare("
        INSERT INTO nasabah (user_id, bank_sampah_id, no_anggota, nama, no_hp, alamat, saldo, status, tgl_daftar)
        VALUES (:user_id, :bank_sampah_id, :no_anggota, :nama, :no_hp, :alamat, 0, 'AKTIF', CURDATE())
    ");

    $stmtNasabah->execute([
        'user_id' => $userId,
        'bank_sampah_id' => 1,
        'no_anggota' => $noAnggota,
        'nama' => $nama,
        'no_hp' => $noHp,
        'alamat' => $alamat
    ]);

    // Get created user
    $user = $userModel->findById($userId);
    $token = generate_api_token($user);

    api_success([
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'nama' => $user['nama'],
            'no_hp' => $user['no_hp'],
            'role' => $user['role']
        ]
    ], 'Registrasi berhasil', 201);
}
