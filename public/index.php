<?php
/**
 * Public Entry Point - Bank Sampah Digital Desa
 *
 * File ini adalah entry point utama aplikasi.
 * Semua request akan diarahkan ke file ini.
 */

// Batas waktu session web: detik tanpa aktivitas.
// 30 = untuk tes. Ganti ke 1800 (30 menit) atau 28800 (8 jam) untuk pemakaian nyata.
define('SESSION_TIMEOUT', 300);

ini_set('session.gc_maxlifetime', (string) SESSION_TIMEOUT);

// Cookie sesi: HttpOnly + SameSite (NFR-01). Flag Secure hanya di HTTPS.
// Di belakang tunnel/reverse proxy, HTTPS berakhir di proxy dan container
// menerima HTTP biasa; proxy memberi tahu lewat header X-Forwarded-Proto.
$httpsAktif = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'secure'   => $httpsAktif,
    'samesite' => 'Lax',
]);

session_start();

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['last_activity'] = time();

// Load konfigurasi
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

// NFR-01: di environment produksi, akses wajib lewat HTTPS.
if (APP_ENV === 'production' && empty($httpsAktif)) {
    $hostRedirect = $_SERVER['HTTP_HOST'] ?? '';
    $uriRedirect = $_SERVER['REQUEST_URI'] ?? '/';
    header('Location: https://' . $hostRedirect . $uriRedirect, true, 301);
    exit;
}

// Load helpers
require_once APP_PATH . '/helpers/auth.php';
require_once APP_PATH . '/helpers/money.php';
// util.php menyediakan uuidV4() (dipakai TransaksiBeli) dan nomorUrut()
// (dipakai PengajuanPencairan). Tanpa file ini kedua fungsi undefined.
require_once APP_PATH . '/helpers/util.php';

// Load AuthController untuk route login/logout
require_once CONTROLLER_PATH . '/AuthController.php';

/**
 * Routing Sederhana
 *
 * Mendukung:
 * - Query string:  ?page=dashboard
 * - Clean URL:      /dashboard
 * - Dengan param:   /nasabah/edit/1
 */

// Ambil URI dari request
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

// Normalisasi path (handle Windows backslash)
$scriptName = str_replace('\\', '/', $scriptName);
$requestUri = str_replace('\\', '/', $requestUri);

// Ambil base path dari script name (folder aplikasi)
$basePath = dirname($scriptName);
if ($basePath === '/' || $basePath === '\\' || $basePath === '') {
    $basePath = '';
}

// Hapus base path dari URI jika ada
if (!empty($basePath)) {
    $uri = str_replace($basePath, '', $requestUri);
} else {
    $uri = $requestUri;
}

$uri = parse_url($uri, PHP_URL_PATH);
$uri = trim($uri, '/');

// Fallback ke query string jika URI kosong
if (empty($uri) && isset($_GET['page'])) {
    $uri = $_GET['page'];
}

// Parse URI menjadi array
$segments = $uri ? explode('/', $uri) : [];

// Default controller dan method
$defaultController = 'Home';
$defaultMethod = 'index';

// Tentukan controller dan action dari URI
$controllerName = !empty($segments[0]) ? ucfirst($segments[0]) : $defaultController;
$methodName = !empty($segments[1]) ? $segments[1] : $defaultMethod;
$param1 = $segments[2] ?? null;
$param2 = $segments[3] ?? null;

// Validasi nama controller (hanya alphanumeric dan underscore)
if (!preg_match('/^[a-zA-Z0-9_]+$/', $controllerName)) {
    $controllerName = $defaultController;
}
if (!preg_match('/^[a-zA-Z0-9_-]+$/', $methodName)) {
    $methodName = $defaultMethod;
}

// Path ke controller
$controllerFile = CONTROLLER_PATH . '/' . $controllerName . 'Controller.php';

/**
 * Autoload untuk Controllers
 */
function autoloadControllers(string $class): void
{
    // Cek apakah class sesuai pattern (XXXController)
    if (preg_match('/^(.+)Controller$/', $class, $matches)) {
        $file = CONTROLLER_PATH . '/' . $matches[1] . 'Controller.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }

    // Cek apakah class sesuai pattern (XXXModel)
    if (preg_match('/^(.+)Model$/', $class, $matches)) {
        $file = MODEL_PATH . '/' . $matches[1] . 'Model.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
}

spl_autoload_register('autoloadControllers');

// Handle routing khusus

// Route: /login
if ($controllerName === 'Login' || ($controllerName === 'Auth' && $methodName === 'index')) {
    $authController = new AuthController();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $authController->login();
    } else {
        $authController->login();
    }
    exit;
}

// Route: /logout
if ($controllerName === 'Logout' || ($controllerName === 'Auth' && $methodName === 'logout')) {
    $authController = new AuthController();
    $authController->logout();
    exit;
}

// Route: /ganti-password (semua peran; wajib saat memakai kata sandi sementara)
// Memakai $segments[0] langsung karena nama bertanda hubung tidak lolos validasi $controllerName.
if (($segments[0] ?? '') === 'ganti-password') {
    require_once CONTROLLER_PATH . '/PasswordController.php';
    $controller = new PasswordController();
    $controller->ganti();
    exit;
}

// Route: /dashboard -> redirect sesuai role
if ($controllerName === 'Dashboard') {
    if (!isLoggedIn()) {
        setFlashMessage('error', 'Silakan login terlebih dahulu.');
        redirect('login');
    }
    redirectToRoleDashboard();
    exit;
}

// Route: /nasabah/riwayat
if ($controllerName === 'Nasabah' && $methodName === 'riwayat') {
    require_once CONTROLLER_PATH . '/NasabahController.php';
    $controller = new NasabahController();
    $controller->riwayat();
    exit;
}

// Route: /nasabah/cairkan
if ($controllerName === 'Nasabah' && $methodName === 'cairkan') {
    require_once CONTROLLER_PATH . '/NasabahController.php';
    $controller = new NasabahController();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller->cairkan();
    } else {
        $controller->cairkan();
    }
    exit;
}

// Route: /nasabah/pencairan/batal
// Nasabah membatalkan pengajuan pencairannya sendiri, hanya saat
// statusnya masih DIAJUKAN (Menunggu).
if ($controllerName === 'Nasabah' && $methodName === 'pencairan' && $param1 === 'batal') {
    require_once CONTROLLER_PATH . '/NasabahController.php';
    $controller = new NasabahController();
    $controller->batalkanPencairan();
    exit;
}

// Route: /admin/users/add, /admin/users/edit, /admin/users/status, /admin/users/reset-password
// (/admin/users/delete dipertahankan hanya agar rute lama tidak error; tidak menghapus apa pun)
if ($controllerName === 'Admin') {
    require_once CONTROLLER_PATH . '/AdminController.php';
    $controller = new AdminController();

    if ($methodName === 'users') {
        if ($param1 === 'add') {
            $controller->userForm();
        } elseif ($param1 === 'edit') {
            $controller->userForm();
        } elseif ($param1 === 'delete') {
            $controller->userDelete();
        } elseif ($param1 === 'status') {
            $controller->userToggleStatus();
        } elseif ($param1 === 'reset-password') {
            $controller->userResetPassword();
        } else {
            $controller->users();
        }
        exit;
    }

    // /admin/desa/add, /admin/desa/edit, /admin/desa/delete
    if ($methodName === 'desa') {
        if ($param1 === 'add') {
            $controller->desaForm();
        } elseif ($param1 === 'edit') {
            $controller->desaForm();
        } elseif ($param1 === 'delete') {
            $controller->desaDelete();
        } else {
            $controller->desa();
        }
        exit;
    }

    // /admin/bank-sampah/add (dash => underscore)
    if ($methodName === 'bank-sampah') {
        if ($param1 === 'add') {
            $controller->bankSampahForm();
        } elseif ($param1 === 'edit') {
            $controller->bankSampahForm();
        } elseif ($param1 === 'delete') {
            $controller->bankSampahDelete();
        } else {
            $controller->bankSampah();
        }
        exit;
    }

    // /admin/nasabah/add, /admin/nasabah/edit, /admin/nasabah/status, /admin/nasabah/reset-password
    // Rute hapus permanen tidak ada. Nasabah tidak pernah dihapus, hanya
    // dinonaktifkan lewat /admin/nasabah/status.
    if ($methodName === 'nasabah') {
        if ($param1 === 'add') {
            $controller->nasabahForm();
        } elseif ($param1 === 'edit') {
            $controller->nasabahForm();
        } elseif ($param1 === 'status') {
            $controller->nasabahToggleStatus();
        } elseif ($param1 === 'reset-password') {
            $controller->nasabahResetPassword();
        } else {
            $controller->nasabah();
        }
        exit;
    }


    // /admin/audit-log
    if ($methodName === 'audit-log') {
        $controller->auditLog();
        exit;
    }

    // /admin/pengaturan
    if ($methodName === 'pengaturan') {
        $controller->pengaturan();
        exit;
    }

    // Default ke dashboard
    $controller->dashboard();
    exit;
}

// Route: /pengelola/* - Pengelola Controller
if ($controllerName === 'Pengelola') {
    require_once CONTROLLER_PATH . '/PengelolaController.php';
    $controller = new PengelolaController();

    // /pengelola/transaksi-beli
    if ($methodName === 'transaksi-beli') {
        if ($param1 === 'add') {
            $controller->transaksiBeliForm();
        } elseif ($param1 === 'detail') {
            $controller->transaksiBeliDetail();
        } elseif ($param1 === 'batal') {
            $controller->transaksiBeliBatal();
        } else {
            $controller->transaksiBeli();
        }
        exit;
    }

    // /pengelola/transaksi-jual
    if ($methodName === 'transaksi-jual') {
        if ($param1 === 'add') {
            $controller->transaksiJualForm();
        } elseif ($param1 === 'detail') {
            $controller->transaksiJualDetail();
        } elseif ($param1 === 'lunas') {
            $controller->transaksiJualLunas();
        } elseif ($param1 === 'batal') {
            $controller->transaksiJualBatal();
        } else {
            $controller->transaksiJual();
        }
        exit;
    }

    // /pengelola/stok
    if ($methodName === 'stok') {
        $controller->stok();
        exit;
    }

    // /pengelola/kategori
    

    // /pengelola/harga
    // Pengelola hanya boleh melihat harga (PROSES_BISNIS.md bagian 1).
    // Pengelolaan harga dan kategori adalah tanggung jawab Admin (FR-19);
    // sampai peran itu ditentukan, tautan tambah/ubah tidak diaktifkan.
    if ($methodName === 'harga') {
        $controller->harga();
        exit;
    }

    // /pengelola/pengepul
    if ($methodName === 'pengepul') {
        if ($param1 === 'add') {
            $controller->pengepulForm();
        } elseif ($param1 === 'edit') {
            $controller->pengepulForm();
        } elseif ($param1 === 'nonaktifkan') {
            $controller->pengepulNonaktifkan();
        } else {
            $controller->pengepul();
        }
        exit;
    }

    // /pengelola/nasabah
    if ($methodName === 'nasabah') {
        if ($param1 === 'add') {
            $controller->nasabahForm();
        } elseif ($param1 === 'edit') {
            $controller->nasabahForm();
        } elseif ($param1 === 'nonaktifkan') {
            $controller->nasabahNonaktifkan();
        } elseif ($param1 === 'aktifkan') {
            $controller->nasabahAktifkan();
        } else {
            $controller->nasabah();
        }
        exit;
    }

    // /pengelola/laporan
    if ($methodName === 'laporan') {
        if ($param1 === 'export-pdf') {
            $controller->laporanExportPdf();
        } else {
            $controller->laporan();
        }
        exit;
    }

    // /pengelola/pencairan
    if ($methodName === 'pencairan') {
        if ($param1 === 'detail') {
            $controller->pencairanDetail();
        } elseif ($param1 === 'setuju') {
            $controller->pencairanSetuju();
        } elseif ($param1 === 'tolak') {
            $controller->pencairanTolak();
        } elseif ($param1 === 'cairkan') {
            $controller->pencairanCairkan();
        } elseif ($param1 === 'batal') {
            $controller->pencairanBatal();
        } elseif ($param1 === 'export-pdf') {
            $controller->pencairanExportPdf();
        } else {
            $controller->pencairan();
        }
        exit;
    }

    // Default dashboard
    $controller->dashboard();
    exit;
}

// Route: /kades/* - Kades Controller
if ($controllerName === 'Kades') {
    require_once CONTROLLER_PATH . '/KadesController.php';
    $controller = new KadesController();

    if ($methodName === 'monitoring') {
        $controller->monitoring();
    } elseif ($methodName === 'laporan') {
        $controller->laporan();
    } elseif ($methodName === 'export') {
        $controller->export();
    } else {
        $controller->dashboard();
    }
    exit;
}

// Cek apakah controller ada
if (file_exists($controllerFile)) {
    require_once $controllerFile;
    $fullControllerName = $controllerName . 'Controller';

    if (class_exists($fullControllerName)) {
        $controller = new $fullControllerName();

        if (method_exists($controller, $methodName)) {
            // Panggil method dengan parameter
            $controller->$methodName($param1, $param2);
        } else {
            http_response_code(404);
            echo "<h1>404 - Method Tidak Ditemukan</h1>";
            echo "<p>Method '$methodName' tidak ditemukan di controller '$controllerName'.</p>";
            echo "<a href='" . base_url() . "'>Kembali ke Beranda</a>";
        }
    } else {
        http_response_code(404);
        echo "<h1>404 - Controller Tidak Ditemukan</h1>";
        echo "<p>Class controller '$fullControllerName' tidak ditemukan.</p>";
        echo "<a href='" . base_url() . "'>Kembali ke Beranda</a>";
    }
} else {
    // Controller tidak ditemukan - coba render view langsung
    http_response_code(404);

    // Coba cari view berdasarkan URI
    $viewPath = VIEW_PATH . '/' . strtolower($controllerName) . '.php';
    if (file_exists($viewPath)) {
        include $viewPath;
    } else {
        // Halaman 404 default
        echo "<h1>404 - Halaman Tidak Ditemukan</h1>";
        echo "<p>Halaman yang Anda cari tidak tersedia.</p>";
        echo "<a href='" . base_url() . "'>Kembali ke Beranda</a>";
    }
}