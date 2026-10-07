<?php
/**
 * ProMarket Multi-Vendor Marketplace - Configuration
 */
declare(strict_types=1);

// Error reporting (disable display in production)
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Session security
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// Base paths
define('ROOT_PATH', dirname(__DIR__));
// BASE_URL is detected automatically (works in a sub-folder like /ProMarket_MultiVendor or at a domain root)
$__doc  = str_replace('\\', '/', (string)realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
$__root = str_replace('\\', '/', (string)realpath(ROOT_PATH));
define('BASE_URL', ($__doc !== '' && strpos($__root, $__doc) === 0) ? rtrim(substr($__root, strlen($__doc)), '/') : '/ProMarket_MultiVendor');
unset($__doc, $__root);
define('UPLOAD_PATH', ROOT_PATH . '/assets/uploads');
define('UPLOAD_URL', BASE_URL . '/assets/uploads');

// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'promarket_multivendor');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// App
define('APP_NAME', 'ProMarket');
define('APP_VERSION', '2.0.0');
define('CURRENCY', 'USD');
define('CURRENCY_SYMBOL', '$');

// Security
define('CSRF_TOKEN_NAME', 'csrf_token');

// Pagination
define('ITEMS_PER_PAGE', 12);

// File uploads
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024); // 2MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);

// PDO Connection
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // Friendly error for missing DB
    if (php_sapi_name() !== 'cli') {
        die('<div style="font-family:sans-serif;max-width:600px;margin:80px auto;padding:30px;border:1px solid #ddd;border-radius:12px;text-align:center">
            <h2 style="color:#c0392b">Database Connection Failed</h2>
            <p>Please import <code>database/promarket_multivendor.sql</code> via phpMyAdmin, then refresh.</p>
            <p style="color:#666;font-size:14px">' . htmlspecialchars($e->getMessage()) . '</p>
        </div>');
    }
    throw $e;
}

// Load helpers
require_once ROOT_PATH . '/includes/helpers.php';
require_once ROOT_PATH . '/includes/auth.php';
