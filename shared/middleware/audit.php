<?php
/**
 * shared/middleware/audit.php
 * Audit trail logger — sesuai Level 8 Audit Trail Standard
 * ERP Toko Berlian — Only One
 */

declare(strict_types=1);

/**
 * Catat aktivitas ke audit_logs.
 *
 * @param string      $module      Modul: USER, DIAMOND, SALES, dll
 * @param string      $action      LOGIN, CREATE, UPDATE, DELETE, APPROVE, POST, REJECT
 * @param string|null $documentNo  Nomor dokumen terkait (jika ada)
 * @param string|null $tableName   Nama tabel yang terpengaruh
 * @param string|null $recordId    ID record yang terpengaruh
 * @param mixed       $before      Nilai sebelum perubahan (akan di-encode ke JSON)
 * @param mixed       $after       Nilai setelah perubahan (akan di-encode ke JSON)
 * @param string|null $description Deskripsi bebas
 */
function audit_log(
    string  $module,
    string  $action,
    ?string $documentNo  = null,
    ?string $tableName   = null,
    ?string $recordId    = null,
    mixed   $before      = null,
    mixed   $after       = null,
    ?string $description = null
): void {
    try {
        Database::query(
            "INSERT INTO audit_logs
                (user_id, username, ip_address, user_agent,
                 module, action, document_no, table_name, record_id,
                 before_value, after_value, description)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $_SESSION['user_id']   ?? null,
                $_SESSION['username']  ?? null,
                get_client_ip(),
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $module,
                $action,
                $documentNo,
                $tableName,
                $recordId,
                $before !== null ? json_encode($before, JSON_UNESCAPED_UNICODE) : null,
                $after  !== null ? json_encode($after,  JSON_UNESCAPED_UNICODE) : null,
                $description,
            ]
        );
    } catch (Throwable $e) {
        // Jangan hentikan aplikasi karena gagal audit log
        error_log('Audit log failed: ' . $e->getMessage());
    }
}

/**
 * Shortcut: catat login
 */
function audit_login(int $userId, string $username, bool $success): void
{
    try {
        Database::query(
            "INSERT INTO audit_logs
                (user_id, username, ip_address, user_agent, module, action, description)
             VALUES (?, ?, ?, ?, 'AUTH', ?, ?)",
            [
                $userId,
                $username,
                get_client_ip(),
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $success ? 'LOGIN' : 'LOGIN_FAILED',
                $success
                    ? "Login berhasil dari IP " . get_client_ip()
                    : "Gagal login dari IP " . get_client_ip(),
            ]
        );
    } catch (Throwable $e) {
        error_log('Audit login log failed: ' . $e->getMessage());
    }
}

/**
 * Ambil IP client (mempertimbangkan proxy).
 */
function get_client_ip(): string
{
    $keys = [
        'HTTP_CF_CONNECTING_IP',   // Cloudflare
        'HTTP_X_FORWARDED_FOR',    // Proxy
        'HTTP_X_REAL_IP',          // Nginx proxy
        'REMOTE_ADDR',
    ];

    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = explode(',', $_SERVER[$key])[0];
            $ip = trim($ip);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }

    return '0.0.0.0';
}
