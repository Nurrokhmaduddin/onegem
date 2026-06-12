<?php
/**
 * system/role/repository.php
 * Repository Role — query database saja
 */
declare(strict_types=1);

class RoleRepository
{
    public static function getAll(bool $withCount = false): array
    {
        if ($withCount) {
            return Database::fetchAll(
                "SELECT r.*, COUNT(u.id) AS user_count
                   FROM roles r
                   LEFT JOIN users u ON u.role_id = r.id AND u.deleted_at IS NULL
                  GROUP BY r.id ORDER BY r.role_name"
            );
        }
        return Database::fetchAll("SELECT * FROM roles ORDER BY role_name");
    }

    public static function findById(int $id): ?array
    {
        return Database::fetchOne("SELECT * FROM roles WHERE id = ?", [$id]);
    }

    public static function isCodeTaken(string $code, int $excludeId = 0): bool
    {
        return Database::fetchOne(
            "SELECT id FROM roles WHERE role_code = ? AND id != ?", [$code, $excludeId]
        ) !== null;
    }

    public static function create(array $data): int
    {
        return (int) Database::insert(
            "INSERT INTO roles (role_code, role_name, description, is_active) VALUES (?, ?, ?, 1)",
            [$data['role_code'], $data['role_name'], $data['description']]
        );
    }

    public static function update(int $id, array $data): void
    {
        Database::query(
            "UPDATE roles SET role_name = ?, description = ?, is_active = ? WHERE id = ?",
            [$data['role_name'], $data['description'], $data['is_active'], $id]
        );
    }

    public static function delete(int $id): void
    {
        Database::query("DELETE FROM roles WHERE id = ?", [$id]);
    }

    /** Permissions yang dimiliki role, dikelompokkan per modul */
    public static function getPermissions(int $roleId): array
    {
        $rows = Database::fetchAll(
            "SELECT p.*, IF(rp.id IS NOT NULL, 1, 0) AS is_granted
               FROM permissions p
               LEFT JOIN role_permissions rp ON rp.permission_id = p.id AND rp.role_id = ?
              ORDER BY p.module, p.action",
            [$roleId]
        );

        // Kelompokkan per modul
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['module']][] = $row;
        }
        return $grouped;
    }

    /** Simpan ulang semua permission untuk satu role (replace) */
    public static function savePermissions(int $roleId, array $permissionIds, int $grantedBy): void
    {
        Database::transaction(function () use ($roleId, $permissionIds, $grantedBy) {
            Database::query("DELETE FROM role_permissions WHERE role_id = ?", [$roleId]);
            foreach ($permissionIds as $pid) {
                Database::query(
                    "INSERT IGNORE INTO role_permissions (role_id, permission_id, granted_by) VALUES (?, ?, ?)",
                    [$roleId, (int)$pid, $grantedBy]
                );
            }
        });
    }
}


/**
 * system/role/service.php
 * Service Role — business rule
 */
class RoleService
{
    public static function validate(array $data, int $roleId = 0): array
    {
        $errors = [];
        $code   = strtoupper(trim($data['role_code'] ?? ''));
        $name   = trim($data['role_name'] ?? '');

        if ($roleId === 0) { // hanya saat create
            if (empty($code)) {
                $errors['role_code'] = 'Kode role wajib diisi.';
            } elseif (!preg_match('/^[A-Z0-9_]{2,30}$/', $code)) {
                $errors['role_code'] = 'Kode role hanya boleh huruf kapital, angka, dan underscore (2-30 karakter).';
            } elseif (RoleRepository::isCodeTaken($code, $roleId)) {
                $errors['role_code'] = 'Kode role sudah digunakan.';
            }
        }

        if (empty($name)) {
            $errors['role_name'] = 'Nama role wajib diisi.';
        } elseif (mb_strlen($name) > 80) {
            $errors['role_name'] = 'Nama role maksimal 80 karakter.';
        }

        return $errors;
    }

    public static function create(array $data): int
    {
        $errors = self::validate($data);
        if (!empty($errors)) throw new RuntimeException(json_encode($errors));

        $id = RoleRepository::create([
            'role_code'   => strtoupper(trim($data['role_code'])),
            'role_name'   => trim($data['role_name']),
            'description' => trim($data['description'] ?? ''),
        ]);

        audit_log('ROLE', 'CREATE', null, 'roles', (string)$id,
            null, ['role_code' => $data['role_code'], 'role_name' => $data['role_name']],
            "Role baru dibuat: {$data['role_code']}"
        );
        return $id;
    }

    public static function update(int $id, array $data): void
    {
        $existing = RoleRepository::findById($id);
        if (!$existing) throw new RuntimeException('Role tidak ditemukan.');

        // Tidak boleh ubah role sistem bawaan
        $systemRoles = ['OWNER', 'IT_ADMIN'];
        if (in_array($existing['role_code'], $systemRoles, true)) {
            $data['is_active'] = 1; // sistem roles selalu aktif
        }

        $errors = self::validate($data, $id);
        if (!empty($errors)) throw new RuntimeException(json_encode($errors));

        RoleRepository::update($id, [
            'role_name'   => trim($data['role_name']),
            'description' => trim($data['description'] ?? ''),
            'is_active'   => isset($data['is_active']) ? 1 : 0,
        ]);

        audit_log('ROLE', 'UPDATE', null, 'roles', (string)$id,
            ['role_name' => $existing['role_name']],
            ['role_name' => $data['role_name']],
            "Role diperbarui: ID {$id}"
        );
    }

    public static function delete(int $id): void
    {
        $role = RoleRepository::findById($id);
        if (!$role) throw new RuntimeException('Role tidak ditemukan.');

        $systemRoles = ['OWNER', 'IT_ADMIN', 'MANAGER', 'SALES', 'INVENTORY', 'FINANCE'];
        if (in_array($role['role_code'], $systemRoles, true)) {
            throw new RuntimeException('Role sistem bawaan tidak dapat dihapus.');
        }

        $userCount = Database::fetchOne(
            "SELECT COUNT(*) AS n FROM users WHERE role_id = ? AND deleted_at IS NULL", [$id]
        )['n'] ?? 0;

        if ($userCount > 0) {
            throw new RuntimeException("Role tidak dapat dihapus karena masih digunakan oleh {$userCount} pengguna.");
        }

        RoleRepository::delete($id);
        audit_log('ROLE', 'DELETE', null, 'roles', (string)$id,
            ['role_code' => $role['role_code'], 'role_name' => $role['role_name']], null,
            "Role dihapus: {$role['role_code']}"
        );
    }

    public static function savePermissions(int $roleId, array $permissionIds): void
    {
        $role = RoleRepository::findById($roleId);
        if (!$role) throw new RuntimeException('Role tidak ditemukan.');

        RoleRepository::savePermissions($roleId, $permissionIds, (int)($_SESSION['user_id'] ?? 0));

        audit_log('PERMISSION', 'APPROVE', null, 'role_permissions', (string)$roleId,
            null, ['permission_count' => count($permissionIds)],
            "Permission role '{$role['role_code']}' diperbarui: " . count($permissionIds) . " permission diberikan."
        );
    }
}
