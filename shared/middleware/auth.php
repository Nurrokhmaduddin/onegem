<?php
/**
 * shared/middleware/auth.php
 * Middleware autentikasi — wajib di-include di semua halaman terproteksi
 * VERSI LARAGON — redirect menggunakan url() helper
 */

declare(strict_types=1);

function require_auth(): void
{
echo '<pre>';
print_r($session);
exit;

    
    if (empty($_SESSION['user_id'])) {
        flash_set('error', 'Sesi Anda telah berakhir. Silakan login kembali.');
        redirect(url('auth/login/'));
    }

    // Validasi sesi aktif di database
    $session = Database::fetchOne(
        "SELECT
    s.id,
    s.expires_at,
    s.is_active AS session_active,
    u.is_active AS user_active,
    u.role_id
           FROM user_sessions s
           JOIN users u ON u.id = s.user_id
          WHERE s.session_token = ?
            AND s.is_active = 1
            AND s.user_id = ?",
        [$_SESSION['session_token'] ?? '', $_SESSION['user_id']]
    );

    if (!$session) {
        session_destroy();
        redirect(url('auth/login') . '?reason=session_invalid');
    }

    if (strtotime($session['expires_at']) < time()) {
        Database::query(
            "UPDATE user_sessions SET is_active = 0 WHERE session_token = ?",
            [$_SESSION['session_token']]
        );
        session_destroy();
        redirect(url('auth/login') . '?reason=session_expired');
    }

    if (!$session['is_active']) {
        session_destroy();
        redirect(url('auth/login') . '?reason=account_inactive');
    }

    // Perbarui last_activity
    Database::query(
        "UPDATE user_sessions SET last_activity = NOW() WHERE session_token = ?",
        [$_SESSION['session_token']]
    );
}

function require_permission(string $permissionCode, bool $dieOnFail = true): bool
{
    $userId = $_SESSION['user_id'] ?? 0;

    $result = Database::fetchOne(
        "SELECT rp.id
           FROM role_permissions rp
           JOIN permissions p ON p.id = rp.permission_id
           JOIN users u ON u.role_id = rp.role_id
          WHERE u.id = ?
            AND p.permission_code = ?",
        [$userId, $permissionCode]
    );

    $hasPermission = $result !== null;

    if (!$hasPermission && $dieOnFail) {
        if (is_ajax_request()) {
            json_response(false, 'Anda tidak memiliki izin untuk melakukan aksi ini.', null, 403);
        }
        http_response_code(403);
        require BASE_PATH . '/layout/error_403.php';
        exit;
    }

    return $hasPermission;
}

function can(string $permissionCode): bool
{
    return require_permission($permissionCode, false);
}

function get_user_permissions(): array
{
    if (!empty($_SESSION['permissions'])) {
        return $_SESSION['permissions'];
    }

    $rows = Database::fetchAll(
        "SELECT p.permission_code
           FROM role_permissions rp
           JOIN permissions p ON p.id = rp.permission_id
           JOIN users u ON u.role_id = rp.role_id
          WHERE u.id = ?",
        [$_SESSION['user_id'] ?? 0]
    );

    $perms = array_column($rows, 'permission_code');
    $_SESSION['permissions'] = $perms;
    return $perms;
}

function clear_permission_cache(): void
{
    unset($_SESSION['permissions']);
}
// function require_auth(): void
// {
//     echo "<pre>";
//     print_r($_SESSION);

//     die('AUTH CHECK');
// }