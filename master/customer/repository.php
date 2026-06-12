<?php
/**
 * master/customer/repository.php
 * Repository Customer — query database saja, tanpa business rule
 */
declare(strict_types=1);

class CustomerRepository
{
    public static function getList(
        string $search   = '',
        string $tier     = '',
        int    $isActive = -1,
        string $sortBy   = 'c.name',
        string $sortDir  = 'ASC',
        int    $limit    = DEFAULT_PER_PAGE,
        int    $offset   = 0
    ): array {
        $where  = ['c.deleted_at IS NULL'];
        $params = [];

        if ($search !== '') {
            $like    = '%' . sanitize_like($search) . '%';
            $where[] = '(c.name LIKE ? OR c.phone LIKE ? OR c.email LIKE ? OR c.customer_code LIKE ?)';
            $params  = array_merge($params, [$like, $like, $like, $like]);
        }
        if ($tier !== '') {
            $where[]  = 'c.tier = ?';
            $params[] = $tier;
        }
        if ($isActive >= 0) {
            $where[]  = 'c.is_active = ?';
            $params[] = $isActive;
        }

        $whereStr  = implode(' AND ', $where);
        $allowSort = ['c.name','c.customer_code','c.tier','c.created_at','c.phone'];
        if (!in_array($sortBy, $allowSort, true)) $sortBy = 'c.name';
        $sortDir   = strtoupper($sortDir) === 'DESC' ? 'DESC' : 'ASC';

        $sql = "SELECT c.*, u.full_name AS created_by_name
                  FROM customers c
                  LEFT JOIN users u ON u.id = c.created_by
                 WHERE {$whereStr}
                 ORDER BY {$sortBy} {$sortDir}
                 LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        return Database::fetchAll($sql, $params);
    }

    public static function countList(string $search='', string $tier='', int $isActive=-1): int
    {
        $where  = ['deleted_at IS NULL'];
        $params = [];
        if ($search !== '') {
            $like    = '%' . sanitize_like($search) . '%';
            $where[] = '(name LIKE ? OR phone LIKE ? OR email LIKE ? OR customer_code LIKE ?)';
            $params  = array_merge($params, [$like, $like, $like, $like]);
        }
        if ($tier !== '')   { $where[] = 'tier = ?';      $params[] = $tier; }
        if ($isActive >= 0) { $where[] = 'is_active = ?'; $params[] = $isActive; }
        $row = Database::fetchOne(
            "SELECT COUNT(*) n FROM customers WHERE " . implode(' AND ', $where), $params
        );
        return (int)($row['n'] ?? 0);
    }

    public static function findById(int $id): ?array
    {
        return Database::fetchOne(
            "SELECT * FROM customers WHERE id = ? AND deleted_at IS NULL", [$id]
        );
    }

    public static function isCodeTaken(string $code, int $excludeId = 0): bool
    {
        return Database::fetchOne(
            "SELECT id FROM customers WHERE customer_code = ? AND id != ? AND deleted_at IS NULL",
            [$code, $excludeId]
        ) !== null;
    }

    public static function isEmailTaken(string $email, int $excludeId = 0): bool
    {
        if (empty($email)) return false;
        return Database::fetchOne(
            "SELECT id FROM customers WHERE email = ? AND id != ? AND deleted_at IS NULL",
            [$email, $excludeId]
        ) !== null;
    }

    public static function getNextCode(): string
    {
        $last = Database::fetchOne(
            "SELECT customer_code FROM customers ORDER BY id DESC LIMIT 1"
        );
        if (!$last) return 'CUS-0001';
        $num = (int) substr($last['customer_code'], 4) + 1;
        return 'CUS-' . str_pad((string)$num, 4, '0', STR_PAD_LEFT);
    }

    public static function create(array $data): int
    {
        return (int) Database::insert(
            "INSERT INTO customers
               (customer_code,name,identity_type,identity_number,
                phone,phone2,email,address,birth_date,gender,
                tier,ring_size,preferences,notes,is_active,created_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,1,?)",
            [
                $data['customer_code'], $data['name'],
                $data['identity_type']   ?: null, $data['identity_number'] ?: null,
                $data['phone']           ?: null, $data['phone2']          ?: null,
                $data['email']           ?: null, $data['address']         ?: null,
                $data['birth_date']      ?: null, $data['gender']          ?: null,
                $data['tier'],
                $data['ring_size']       ?: null, $data['preferences']     ?: null,
                $data['notes']           ?: null,
                $_SESSION['user_id']     ?? null,
            ]
        );
    }

    public static function update(int $id, array $data): void
    {
        Database::query(
            "UPDATE customers SET
               name=?,identity_type=?,identity_number=?,
               phone=?,phone2=?,email=?,address=?,birth_date=?,gender=?,
               tier=?,ring_size=?,preferences=?,notes=?,is_active=?,updated_by=?
             WHERE id=?",
            [
                $data['name'],
                $data['identity_type']   ?: null, $data['identity_number'] ?: null,
                $data['phone']           ?: null, $data['phone2']          ?: null,
                $data['email']           ?: null, $data['address']         ?: null,
                $data['birth_date']      ?: null, $data['gender']          ?: null,
                $data['tier'],
                $data['ring_size']       ?: null, $data['preferences']     ?: null,
                $data['notes']           ?: null,
                isset($data['is_active']) ? 1 : 0,
                $_SESSION['user_id']     ?? null,
                $id,
            ]
        );
    }

    public static function softDelete(int $id): void
    {
        Database::query(
            "UPDATE customers SET deleted_at=NOW(), is_active=0, updated_by=? WHERE id=?",
            [$_SESSION['user_id'] ?? null, $id]
        );
    }

    public static function getStats(): array
    {
        return Database::fetchOne(
            "SELECT
               COUNT(*) AS total,
               SUM(is_active=1) AS active,
               SUM(tier='vvip') AS vvip,
               SUM(tier='vip') AS vip,
               SUM(tier='regular') AS regular
             FROM customers WHERE deleted_at IS NULL"
        ) ?? [];
    }

    /** Untuk dropdown/select2 di modul lain */
    public static function getDropdown(string $search = ''): array
    {
        $where  = ['deleted_at IS NULL', 'is_active = 1'];
        $params = [];
        if ($search !== '') {
            $like    = '%' . sanitize_like($search) . '%';
            $where[] = '(name LIKE ? OR customer_code LIKE ? OR phone LIKE ?)';
            $params  = [$like, $like, $like];
        }
        return Database::fetchAll(
            "SELECT id, customer_code, name, tier, phone
               FROM customers WHERE " . implode(' AND ', $where) .
            " ORDER BY name LIMIT 50",
            $params
        );
    }
}
