<?php
/**
 * system/user/repository.php
 * Repository User — hanya query database, tanpa business rule
 * ERP Toko Berlian — Only One
 */

declare(strict_types=1);

class UserRepository
{
    /**
     * Ambil daftar user dengan filter dan paginasi.
     */
    public static function getList(
        string $search   = '',
        int    $roleId   = 0,
        int    $isActive = -1,
        string $sortBy   = 'u.created_at',
        string $sortDir  = 'DESC',
        int    $limit    = DEFAULT_PER_PAGE,
        int    $offset   = 0
    ): array {
        $where  = ['u.deleted_at IS NULL'];
        $params = [];

        if ($search !== '') {
            $like     = '%' . sanitize_like($search) . '%';
            $where[]  = '(u.full_name LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR u.employee_code LIKE ?)';
            $params   = array_merge($params, [$like, $like, $like, $like]);
        }
        if ($roleId > 0) {
            $where[]  = 'u.role_id = ?';
            $params[] = $roleId;
        }
        if ($isActive >= 0) {
            $where[]  = 'u.is_active = ?';
            $params[] = $isActive;
        }

        $whereStr = implode(' AND ', $where);
        $allowedSort = ['u.full_name','u.username','u.email','u.created_at','r.role_name','u.is_active'];
        if (!in_array($sortBy, $allowedSort, true)) $sortBy = 'u.created_at';
        $sortDir = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT u.id, u.employee_code, u.username, u.full_name, u.email,
                       u.phone, u.is_active, u.must_change_pw, u.last_login_at,
                       u.login_attempt, u.locked_until, u.created_at,
                       r.id AS role_id, r.role_code, r.role_name
                  FROM users u
                  JOIN roles r ON r.id = u.role_id
                 WHERE {$whereStr}
                 ORDER BY {$sortBy} {$sortDir}
                 LIMIT ? OFFSET ?";

        $params[] = $limit;
        $params[] = $offset;

        return Database::fetchAll($sql, $params);
    }

    /**
     * Hitung total untuk paginasi.
     */
    public static function countList(string $search = '', int $roleId = 0, int $isActive = -1): int
    {
        $where  = ['u.deleted_at IS NULL'];
        $params = [];

        if ($search !== '') {
            $like    = '%' . sanitize_like($search) . '%';
            $where[] = '(u.full_name LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR u.employee_code LIKE ?)';
            $params  = array_merge($params, [$like, $like, $like, $like]);
        }
        if ($roleId > 0) { $where[] = 'u.role_id = ?'; $params[] = $roleId; }
        if ($isActive >= 0) { $where[] = 'u.is_active = ?'; $params[] = $isActive; }

        $whereStr = implode(' AND ', $where);
        $row = Database::fetchOne(
            "SELECT COUNT(*) AS total FROM users u WHERE {$whereStr}",
            $params
        );
        return (int) ($row['total'] ?? 0);
    }

    /**
     * Ambil satu user by ID.
     */
    public static function findById(int $id): ?array
    {
        return Database::fetchOne(
            "SELECT u.*, r.role_code, r.role_name
               FROM users u JOIN roles r ON r.id = u.role_id
              WHERE u.id = ? AND u.deleted_at IS NULL",
            [$id]
        );
    }

    /**
     * Cek duplikasi username (exclude ID tertentu).
     */
    public static function isUsernameTaken(string $username, int $excludeId = 0): bool
    {
        $row = Database::fetchOne(
            "SELECT id FROM users WHERE username = ? AND id != ? AND deleted_at IS NULL",
            [$username, $excludeId]
        );
        return $row !== null;
    }

    /**
     * Cek duplikasi email.
     */
    public static function isEmailTaken(string $email, int $excludeId = 0): bool
    {
        $row = Database::fetchOne(
            "SELECT id FROM users WHERE email = ? AND id != ? AND deleted_at IS NULL",
            [$email, $excludeId]
        );
        return $row !== null;
    }

    /**
     * Insert user baru.
     */
    public static function create(array $data): int
    {
        return (int) Database::insert(
            "INSERT INTO users
               (role_id, employee_code, username, full_name, email,
                password_hash, phone, is_active, must_change_pw, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['role_id'],
                $data['employee_code'] ?: null,
                $data['username'],
                $data['full_name'],
                $data['email'],
                $data['password_hash'],
                $data['phone'] ?: null,
                $data['is_active'],
                $data['must_change_pw'],
                $data['created_by'],
            ]
        );
    }

    /**
     * Update user.
     */
    public static function update(int $id, array $data): void
    {
        Database::query(
            "UPDATE users
                SET role_id = ?, employee_code = ?, full_name = ?, email = ?,
                    phone = ?, is_active = ?, updated_by = ?
              WHERE id = ?",
            [
                $data['role_id'],
                $data['employee_code'] ?: null,
                $data['full_name'],
                $data['email'],
                $data['phone'] ?: null,
                $data['is_active'],
                $data['updated_by'],
                $id,
            ]
        );
    }

    /**
     * Update password hash.
     */
    public static function updatePassword(int $id, string $hash, bool $clearForce = true): void
    {
        Database::query(
            "UPDATE users SET password_hash = ?, must_change_pw = ?, updated_by = ? WHERE id = ?",
            [$hash, $clearForce ? 0 : 1, $_SESSION['user_id'] ?? null, $id]
        );
    }

    /**
     * Soft delete user.
     */
    public static function softDelete(int $id, int $deletedBy): void
    {
        Database::query(
            "UPDATE users SET deleted_at = NOW(), is_active = 0, updated_by = ? WHERE id = ?",
            [$deletedBy, $id]
        );
        // Nonaktifkan semua sesi
        Database::query("UPDATE user_sessions SET is_active = 0 WHERE user_id = ?", [$id]);
    }

    /**
     * Toggle status aktif/nonaktif.
     */
    public static function toggleStatus(int $id, int $updatedBy): bool
    {
        Database::query(
            "UPDATE users SET is_active = 1 - is_active, updated_by = ? WHERE id = ?",
            [$updatedBy, $id]
        );
        $user = self::findById($id);
        return (bool) ($user['is_active'] ?? false);
    }

    /**
     * Ambil semua role aktif (untuk dropdown).
     */
    public static function getRoles(): array
    {
        return Database::fetchAll(
            "SELECT id, role_code, role_name FROM roles WHERE is_active = 1 ORDER BY role_name"
        );
    }

    /**
     * Hitung jumlah user per role (untuk dashboard).
     */
    public static function countByRole(): array
    {
        return Database::fetchAll(
            "SELECT r.role_name, COUNT(u.id) AS total
               FROM roles r LEFT JOIN users u ON u.role_id = r.id AND u.deleted_at IS NULL
              GROUP BY r.id, r.role_name ORDER BY total DESC"
        );
    }
}
