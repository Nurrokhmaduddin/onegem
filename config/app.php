<?php
/**
 * config/app.php
 * Konstanta & konfigurasi aplikasi global
 * ERP Toko Berlian — Only One
 *
 * VERSI LARAGON — subfolder di localhost
 */

declare(strict_types=1);

// ─── Informasi Aplikasi ───────────────────────────────────────────────────────
define('APP_NAME',      'ONEGEM');
define('APP_VERSION',   '1.0.0');
define('APP_LOCALE',    'id_ID');
define('APP_TIMEZONE',  'Asia/Jakarta');
define('APP_ENV',       'development'); // development | production

// ─── Path ─────────────────────────────────────────────────────────────────────
define('BASE_PATH',   dirname(__DIR__));
define('PUBLIC_PATH', BASE_PATH . '/public');
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('LOG_PATH',    BASE_PATH . '/logs');

// ─── URL ──────────────────────────────────────────────────────────────────────
// Nama folder project Anda di dalam www/ Laragon
// Sesuaikan jika nama folder berbeda
// $folder_projek = 'onegem';

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    ? 'https'
    : 'http';

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// VPS / Domain langsung
define('BASE_URL', $protocol . '://' . $host);
define('BASE_FOLDER', '');

// ─── Session ──────────────────────────────────────────────────────────────────
define('SESSION_NAME',     'ERPSID');
define('SESSION_LIFETIME', 28800);   // 8 jam
define('SESSION_SECURE',   false);   // false untuk localhost (non-HTTPS)
// Start Session
session_name(SESSION_NAME);

session_set_cookie_params([
    'lifetime' => SESSION_LIFETIME,
    'path'     => '/',
    'secure'   => SESSION_SECURE,
    'httponly' => true,
    'samesite' => 'Lax'
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// ─── Security ─────────────────────────────────────────────────────────────────
define('CSRF_TOKEN_LENGTH', 32);
define('BCRYPT_COST',       12);
define('MAX_LOGIN_ATTEMPT', 5);
define('LOCK_DURATION_MIN', 15);

// ─── Pagination ───────────────────────────────────────────────────────────────
define('DEFAULT_PER_PAGE', 20);
define('MAX_PER_PAGE',     100);

// ─── Upload ───────────────────────────────────────────────────────────────────
define('MAX_UPLOAD_SIZE',     10 * 1024 * 1024);
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('ALLOWED_DOC_TYPES',   ['application/pdf', 'image/jpeg', 'image/png']);

// ─── Timezone ─────────────────────────────────────────────────────────────────
date_default_timezone_set(APP_TIMEZONE);

// ─── Error handling ───────────────────────────────────────────────────────────
if (APP_ENV === 'development') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
    ini_set('log_errors', '1');
    ini_set('error_log', LOG_PATH . '/php_error.log');
}
