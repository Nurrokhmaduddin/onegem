<?php
/**
 * config/database.php
 * Konfigurasi koneksi database — PDO Singleton
 * ERP Toko Berlian — Only One
 *
 * VERSI LARAGON — koneksi langsung tanpa .env loader
 */

declare(strict_types=1);

// ─── Konfigurasi koneksi ──────────────────────────────────────────────────────
// Laragon default: root tanpa password
// Ganti sesuai environment Anda jika berbeda
define('DB_HOST',    '127.0.0.1');
// define('DB_HOST',    '103.41.206.251');
define('DB_PORT',    '3306');
define('DB_NAME',    'erp_berlian');
define('DB_USER',    'root');
define('DB_PASS',    'tmi2026');           // Laragon default: kosong
define('DB_CHARSET', 'utf8mb4');

/**
 * Class Database
 * Singleton PDO — satu koneksi per request
 */
class Database
{
    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone() {}

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, time_zone = '+07:00'",
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                error_log('Database connection failed: ' . $e->getMessage());
                http_response_code(503);
                die(json_encode([
                    'success' => false,
                    'message' => 'Koneksi database gagal: ' . $e->getMessage(), // detail di development
                ]));
            }
        }

        return self::$instance;
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $result = self::query($sql, $params)->fetch();
        return $result ?: null;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function insert(string $sql, array $params = []): string
    {
        self::query($sql, $params);
        return self::getInstance()->lastInsertId();
    }

    public static function transaction(callable $callback): mixed
    {
        $pdo = self::getInstance();
        $pdo->beginTransaction();
        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
