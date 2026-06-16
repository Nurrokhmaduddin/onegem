<?php
/**
 * sales/so/repository.php
 * Repository Sales Order — semua query database
 */
declare(strict_types=1);

class SalesOrderRepository
{
    // ------------------------------------------------------------------ //
    //  GENERATE NO
    // ------------------------------------------------------------------ //
    public static function generateNo(): string
    {
        $prefix = 'SO-' . date('Ym') . '-';
        $last   = Database::fetchOne(
            "SELECT so_no FROM sales_orders
              WHERE so_no LIKE ? AND deleted_at IS NULL
              ORDER BY id DESC LIMIT 1",
            [$prefix . '%']
        );
        $seq = $last ? ((int) substr($last['so_no'], -4) + 1) : 1;
        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    // ------------------------------------------------------------------ //
    //  FIND
    // ------------------------------------------------------------------ //
    public static function findById(int $id): ?array
    {
        return Database::fetchOne(
            "SELECT so.*,
                    c.name            AS customer_name,
                    c.customer_code,
                    c.phone           AS customer_phone,
                    c.email           AS customer_email,
                    sp.full_name      AS salesperson_name,
                    ap.full_name      AS approved_by_name,
                    cb.full_name      AS created_by_name,
                    r.reservation_no
               FROM sales_orders so
          LEFT JOIN customers c     ON c.id  = so.customer_id
          LEFT JOIN users sp        ON sp.id = so.salesperson_id
          LEFT JOIN users ap        ON ap.id = so.approved_by
          LEFT JOIN users cb        ON cb.id = so.created_by
          LEFT JOIN reservations r  ON r.id  = so.reservation_id
              WHERE so.id = ? AND so.deleted_at IS NULL",
            [$id]
        );
    }

    public static function findByNo(string $no): ?array
    {
        return Database::fetchOne(
            "SELECT * FROM sales_orders WHERE so_no = ? AND deleted_at IS NULL",
            [$no]
        );
    }

    // ------------------------------------------------------------------ //
    //  LIST
    // ------------------------------------------------------------------ //
    public static function getAll(
        array $filters = [],
        int $limit     = 50,
        int $offset    = 0
    ): array {
        $where  = ['so.deleted_at IS NULL'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[]  = 'so.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['customer_id'])) {
            $where[]  = 'so.customer_id = ?';
            $params[] = (int) $filters['customer_id'];
        }
        if (!empty($filters['salesperson_id'])) {
            $where[]  = 'so.salesperson_id = ?';
            $params[] = (int) $filters['salesperson_id'];
        }
        if (!empty($filters['date_from'])) {
            $where[]  = 'DATE(so.created_at) >= ?';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[]  = 'DATE(so.created_at) <= ?';
            $params[] = $filters['date_to'];
        }
        if (!empty($filters['search'])) {
            $like     = '%' . $filters['search'] . '%';
            $where[]  = '(so.so_no LIKE ? OR c.name LIKE ?)';
            $params[] = $like;
            $params[] = $like;
        }

        $whereStr = implode(' AND ', $where);
        $params[] = $limit;
        $params[] = $offset;

        return Database::fetchAll(
            "SELECT so.id, so.so_no, so.status,
                    so.total_usd, so.total_idr, so.rate_usd_idr,
                    so.created_at, so.updated_at, so.approved_at,
                    so.notes,
                    c.name          AS customer_name,
                    c.customer_code,
                    sp.full_name    AS salesperson_name,
                    ap.full_name    AS approved_by_name,
                    r.reservation_no,
                    COUNT(soi.id)   AS item_count
               FROM sales_orders so
          LEFT JOIN customers c    ON c.id  = so.customer_id
          LEFT JOIN users sp       ON sp.id = so.salesperson_id
          LEFT JOIN users ap       ON ap.id = so.approved_by
          LEFT JOIN reservations r ON r.id  = so.reservation_id
          LEFT JOIN sales_order_items soi ON soi.sales_order_id = so.id
              WHERE {$whereStr}
           GROUP BY so.id
           ORDER BY so.created_at DESC
              LIMIT ? OFFSET ?",
            $params
        );
    }

    public static function countAll(array $filters = []): int
    {
        $where  = ['so.deleted_at IS NULL'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[]  = 'so.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $like     = '%' . $filters['search'] . '%';
            $where[]  = '(so.so_no LIKE ? OR c.name LIKE ?)';
            $params[] = $like;
            $params[] = $like;
        }

        $whereStr = implode(' AND ', $where);
        $row = Database::fetchOne(
            "SELECT COUNT(DISTINCT so.id) AS total
               FROM sales_orders so
          LEFT JOIN customers c ON c.id = so.customer_id
              WHERE {$whereStr}",
            $params
        );
        return (int) ($row['total'] ?? 0);
    }

    // ------------------------------------------------------------------ //
    //  ITEMS
    // ------------------------------------------------------------------ //
    public static function getItems(int $soId): array
    {
        return Database::fetchAll(
            "SELECT soi.*,
                    d.sku, d.carat, d.color, d.clarity, d.cut,
                    d.cost, d.status AS diamond_status,
                    d.selling_price  AS original_price_usd,
                    c.certificate_no, c.lab
               FROM sales_order_items soi
               JOIN diamonds d ON d.id = soi.diamond_id
          LEFT JOIN diamond_certificates c
                 ON c.diamond_id = d.id AND c.is_primary = 1
              WHERE soi.sales_order_id = ?
              ORDER BY soi.id ASC",
            [$soId]
        );
    }

    // ------------------------------------------------------------------ //
    //  CREATE / UPDATE
    // ------------------------------------------------------------------ //
    public static function create(array $data): int
    {
        Database::query(
            "INSERT INTO sales_orders
               (so_no, reservation_id, customer_id, salesperson_id,
                status, total_usd, total_idr, rate_usd_idr,
                notes, created_by, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,NOW(),NOW())",
            [
                $data['so_no'],
                $data['reservation_id'] ?? null,
                $data['customer_id'],
                $data['salesperson_id']  ?? null,
                $data['status']          ?? 'draft',
                $data['total_usd']       ?? 0,
                $data['total_idr']       ?? 0,
                $data['rate_usd_idr']    ?? 16000,
                $data['notes']           ?? null,
                $data['created_by']      ?? ($_SESSION['user_id'] ?? null),
            ]
        );
        return (int) Database::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        Database::query(
            "UPDATE sales_orders
                SET notes        = ?,
                    updated_by   = ?,
                    updated_at   = NOW()
              WHERE id = ?",
            [
                $data['notes']      ?? null,
                $data['updated_by'] ?? ($_SESSION['user_id'] ?? null),
                $id,
            ]
        );
    }

    public static function updateStatus(
        int    $id,
        string $toStatus,
        string $eventCode,
        ?int   $actorId = null,
        string $notes   = ''
    ): void {
        $current = self::findById($id);
        if (!$current) return;

        $actor = $actorId ?? $_SESSION['user_id'] ?? null;

        $extra = '';
        $extraParams = [];
        if ($toStatus === 'approved') {
            $extra = ', approved_by = ?, approved_at = NOW()';
            $extraParams = [$actor];
        }

        Database::query(
            "UPDATE sales_orders
                SET status = ?, updated_by = ?, updated_at = NOW() {$extra}
              WHERE id = ?",
            array_merge([$toStatus, $actor], $extraParams, [$id])
        );

        // State history
        Database::query(
            "INSERT INTO sales_order_state_histories
               (sales_order_id, from_status, to_status, event_code, actor_id, notes, created_at)
             VALUES (?,?,?,?,?,?,NOW())",
            [$id, $current['status'], $toStatus, $eventCode, $actor, $notes ?: null]
        );

        // Resource event log
        Database::query(
            "INSERT INTO resource_events
               (resource_type, resource_id, event_code, actor_id, created_at)
             VALUES ('sales_order',?,?,?,NOW())",
            [$id, $eventCode, $actor]
        );
    }

    public static function softDelete(int $id): void
    {
        Database::query(
            "UPDATE sales_orders SET deleted_at = NOW(), updated_by = ? WHERE id = ?",
            [$_SESSION['user_id'] ?? null, $id]
        );
    }

    // ------------------------------------------------------------------ //
    //  RECALC TOTALS
    // ------------------------------------------------------------------ //
    public static function recalcTotals(int $soId): void
    {
        $row = Database::fetchOne(
            "SELECT COALESCE(SUM(price_usd), 0) AS total_usd
               FROM sales_order_items WHERE sales_order_id = ?",
            [$soId]
        );
        $totalUsd = (float) ($row['total_usd'] ?? 0);

        $rateRow  = Database::fetchOne(
            "SELECT rate_to_idr FROM currencies
              WHERE code = 'USD' AND is_active = 1
              ORDER BY effective_date DESC LIMIT 1"
        );
        $rate     = (float) ($rateRow['rate_to_idr'] ?? 16000);
        $totalIdr = $totalUsd * $rate;

        Database::query(
            "UPDATE sales_orders
                SET total_usd = ?, total_idr = ?, rate_usd_idr = ?, updated_at = NOW()
              WHERE id = ?",
            [$totalUsd, $totalIdr, $rate, $soId]
        );
    }

    // ------------------------------------------------------------------ //
    //  STATS
    // ------------------------------------------------------------------ //
    public static function getStatusCounts(): array
    {
        $rows = Database::fetchAll(
            "SELECT status, COUNT(*) AS total
               FROM sales_orders WHERE deleted_at IS NULL
              GROUP BY status"
        );
        $map = [];
        foreach ($rows as $r) $map[$r['status']] = (int) $r['total'];
        return $map;
    }

    public static function getPendingApprovalCount(): int
    {
        $row = Database::fetchOne(
            "SELECT COUNT(*) AS n FROM sales_orders
              WHERE status = 'submitted' AND deleted_at IS NULL"
        );
        return (int) ($row['n'] ?? 0);
    }
}
