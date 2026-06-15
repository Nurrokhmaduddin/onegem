<?php
/**
 * sales/lead/repository.php
 * Repository Lead — query database saja
 */
declare(strict_types=1);

class LeadRepository
{
    public static function getList(
        string $search = '', string $status = '', int $assignedTo = 0,
        string $sortBy = 'l.created_at', string $sortDir = 'DESC',
        int $limit = DEFAULT_PER_PAGE, int $offset = 0
    ): array {
        $where  = ['l.deleted_at IS NULL'];
        $params = [];

        if ($search !== '') {
            $like    = '%' . sanitize_like($search) . '%';
            $where[] = '(l.name LIKE ? OR l.phone LIKE ? OR l.email LIKE ? OR l.lead_code LIKE ?)';
            $params  = array_merge($params, [$like, $like, $like, $like]);
        }
        if ($status !== '')   { $where[] = 'l.status = ?';      $params[] = $status; }
        if ($assignedTo > 0)  { $where[] = 'l.assigned_to = ?'; $params[] = $assignedTo; }

        $whereStr  = implode(' AND ', $where);
        $allowSort = ['l.name','l.status','l.source','l.created_at','l.updated_at'];
        if (!in_array($sortBy, $allowSort, true)) $sortBy = 'l.created_at';
        $sortDir   = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';
        $params[]  = $limit;
        $params[]  = $offset;

        return Database::fetchAll(
            "SELECT l.*,
                    u.full_name AS assigned_name,
                    c.name AS customer_name
               FROM leads l
               LEFT JOIN users u ON u.id = l.assigned_to
               LEFT JOIN customers c ON c.id = l.customer_id
              WHERE {$whereStr}
              ORDER BY {$sortBy} {$sortDir}
              LIMIT ? OFFSET ?",
            $params
        );
    }

    public static function countList(string $search='', string $status='', int $assignedTo=0): int
    {
        $where  = ['deleted_at IS NULL'];
        $params = [];
        if ($search !== '') {
            $like    = '%' . sanitize_like($search) . '%';
            $where[] = '(name LIKE ? OR phone LIKE ? OR email LIKE ? OR lead_code LIKE ?)';
            $params  = array_merge($params, [$like, $like, $like, $like]);
        }
        if ($status !== '')  { $where[] = 'status = ?';      $params[] = $status; }
        if ($assignedTo > 0) { $where[] = 'assigned_to = ?'; $params[] = $assignedTo; }
        $row = Database::fetchOne(
            "SELECT COUNT(*) n FROM leads WHERE " . implode(' AND ', $where), $params
        );
        return (int)($row['n'] ?? 0);
    }

    public static function findById(int $id): ?array
    {
        return Database::fetchOne(
            "SELECT l.*, u.full_name AS assigned_name, c.name AS customer_name
               FROM leads l
               LEFT JOIN users u ON u.id = l.assigned_to
               LEFT JOIN customers c ON c.id = l.customer_id
              WHERE l.id = ? AND l.deleted_at IS NULL",
            [$id]
        );
    }

    public static function getNextCode(): string
    {
        $last = Database::fetchOne(
            "SELECT lead_code FROM leads ORDER BY id DESC LIMIT 1"
        );
        if (!$last) return 'LEAD-0001';
        preg_match('/(\d+)$/', $last['lead_code'], $m);
        $num = (int)($m[1] ?? 0) + 1;
        return 'LEAD-' . str_pad((string)$num, 4, '0', STR_PAD_LEFT);
    }

    public static function create(array $data): int
    {
        return (int) Database::insert(
            "INSERT INTO leads
               (lead_code,name,phone,email,source,interest,status,assigned_to,notes,created_by)
             VALUES (?,?,?,?,?,?,?,?,?,?)",
            [
                $data['lead_code'], $data['name'],
                $data['phone']    ?: null, $data['email']    ?: null,
                $data['source'],           $data['interest'] ?: null,
                'new',
                $data['assigned_to'] ?: null,
                $data['notes']       ?: null,
                $_SESSION['user_id'] ?? null,
            ]
        );
    }

    public static function update(int $id, array $data): void
    {
        Database::query(
            "UPDATE leads SET
               name=?,phone=?,email=?,source=?,interest=?,
               assigned_to=?,notes=?,updated_by=?
             WHERE id=?",
            [
                $data['name'],
                $data['phone']      ?: null, $data['email']  ?: null,
                $data['source'],             $data['interest'] ?: null,
                $data['assigned_to'] ?: null,
                $data['notes']       ?: null,
                $_SESSION['user_id'] ?? null,
                $id,
            ]
        );
    }

    public static function updateStatus(int $id, string $status, ?string $notes = null): void
    {
        Database::query(
            "UPDATE leads SET status=?,updated_by=?,notes=COALESCE(?,notes) WHERE id=?",
            [$status, $_SESSION['user_id'] ?? null, $notes, $id]
        );
    }

    public static function convertToCustomer(int $id, int $customerId): void
    {
        Database::query(
            "UPDATE leads SET status='converted',customer_id=?,updated_by=? WHERE id=?",
            [$customerId, $_SESSION['user_id'] ?? null, $id]
        );
    }

    public static function softDelete(int $id): void
    {
        Database::query(
            "UPDATE leads SET deleted_at=NOW(),updated_by=? WHERE id=?",
            [$_SESSION['user_id'] ?? null, $id]
        );
    }

    public static function getActivities(int $leadId): array
    {
        return Database::fetchAll(
            "SELECT a.*, u.full_name AS created_by_name
               FROM lead_activities a
               LEFT JOIN users u ON u.id = a.created_by
              WHERE a.lead_id = ?
              ORDER BY a.activity_at DESC",
            [$leadId]
        );
    }

    public static function addActivity(int $leadId, string $type, string $description): int
    {
        return (int) Database::insert(
            "INSERT INTO lead_activities (lead_id,activity_type,description,created_by)
             VALUES (?,?,?,?)",
            [$leadId, $type, $description, $_SESSION['user_id'] ?? null]
        );
    }

    public static function getStats(): array
    {
        return Database::fetchOne(
            "SELECT COUNT(*) total,
               SUM(status='new') new_count,
               SUM(status='contacted') contacted,
               SUM(status='qualified') qualified,
               SUM(status='quoted') quoted,
               SUM(status='converted') converted,
               SUM(status='lost') lost
             FROM leads WHERE deleted_at IS NULL"
        ) ?? [];
    }

    public static function getSalespersons(): array
    {
        return Database::fetchAll(
            "SELECT u.id, u.full_name
               FROM users u
               JOIN roles r ON r.id = u.role_id
              WHERE u.is_active = 1
                AND r.role_code IN ('SALES','MANAGER','OWNER')
              ORDER BY u.full_name"
        );
    }
}
