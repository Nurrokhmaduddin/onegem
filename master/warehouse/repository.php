<?php
/**
 * master/warehouse/repository.php
 * Repository Warehouse & Branch
 */
declare(strict_types=1);

class WarehouseRepository
{
    public static function getAll(bool $activeOnly = true): array
    {
        $where = $activeOnly ? 'WHERE w.is_active = 1' : '';
        return Database::fetchAll(
            "SELECT w.*, b.name AS branch_name, b.branch_code
               FROM warehouses w
               JOIN branches b ON b.id = w.branch_id
               {$where}
               ORDER BY b.name, w.name"
        );
    }

    public static function getAllGrouped(bool $activeOnly = true): array
    {
        $rows    = self::getAll($activeOnly);
        $grouped = [];
        foreach ($rows as $w) {
            $bid = $w['branch_id'];
            if (!isset($grouped[$bid])) {
                $grouped[$bid] = ['branch_name' => $w['branch_name'], 'warehouses' => []];
            }
            $grouped[$bid]['warehouses'][] = $w;
        }
        return array_values($grouped);
    }

    public static function findById(int $id): ?array
    {
        return Database::fetchOne(
            "SELECT w.*, b.name AS branch_name FROM warehouses w
               JOIN branches b ON b.id = w.branch_id WHERE w.id = ?", [$id]
        );
    }

    public static function isCodeTaken(string $code, int $excludeId = 0): bool
    {
        return Database::fetchOne(
            "SELECT id FROM warehouses WHERE warehouse_code = ? AND id != ?", [$code, $excludeId]
        ) !== null;
    }

    public static function create(array $data): int
    {
        return (int) Database::insert(
            "INSERT INTO warehouses (branch_id, warehouse_code, name, type, description, is_active, created_by)
             VALUES (?, ?, ?, ?, ?, 1, ?)",
            [
                (int)$data['branch_id'], $data['warehouse_code'], $data['name'],
                $data['type'], $data['description'] ?: null, $_SESSION['user_id'] ?? null,
            ]
        );
    }

    public static function update(int $id, array $data): void
    {
        Database::query(
            "UPDATE warehouses SET branch_id=?, name=?, type=?, description=?, is_active=? WHERE id=?",
            [
                (int)$data['branch_id'], $data['name'], $data['type'],
                $data['description'] ?: null, isset($data['is_active']) ? 1 : 0, $id,
            ]
        );
    }

    // Branches
    public static function getAllBranches(): array
    {
        return Database::fetchAll("SELECT * FROM branches WHERE is_active = 1 ORDER BY name");
    }

    public static function findBranchById(int $id): ?array
    {
        return Database::fetchOne("SELECT * FROM branches WHERE id = ?", [$id]);
    }

    public static function createBranch(array $data): int
    {
        return (int) Database::insert(
            "INSERT INTO branches (branch_code, name, address, phone, email, is_head_office, is_active, created_by)
             VALUES (?, ?, ?, ?, ?, ?, 1, ?)",
            [
                $data['branch_code'], $data['name'], $data['address'] ?: null,
                $data['phone'] ?: null, $data['email'] ?: null,
                !empty($data['is_head_office']) ? 1 : 0,
                $_SESSION['user_id'] ?? null,
            ]
        );
    }

    public static function isBranchCodeTaken(string $code, int $excludeId = 0): bool
    {
        return Database::fetchOne(
            "SELECT id FROM branches WHERE branch_code = ? AND id != ?", [$code, $excludeId]
        ) !== null;
    }

    public static function getStockSummary(): array
    {
        return Database::fetchAll(
            "SELECT w.id, w.name AS warehouse_name, w.type, b.name AS branch_name,
                    COUNT(d.id) AS total_items,
                    SUM(d.status='available') AS available,
                    SUM(d.status='reserved') AS reserved,
                    SUM(d.selling_price_usd) AS total_value_usd
               FROM warehouses w
               JOIN branches b ON b.id = w.branch_id
               LEFT JOIN diamonds d ON d.warehouse_id = w.id AND d.deleted_at IS NULL
                    AND d.status NOT IN ('sold','retired')
              WHERE w.is_active = 1
              GROUP BY w.id, w.name, w.type, b.name
              ORDER BY b.name, w.name"
        );
    }
}
