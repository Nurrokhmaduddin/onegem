<?php
/**
 * sales/reservation/repository.php
 * Repository Reservasi — semua query database terkait reservasi
 */
declare(strict_types=1);

class ReservationRepository
{
    // ------------------------------------------------------------------ //
    //  GENERATE NO
    // ------------------------------------------------------------------ //
    public static function generateNo(): string
    {
        $prefix = 'RSV-' . date('Ym') . '-';
        $last   = Database::fetchOne(
            "SELECT reservation_no FROM reservations
              WHERE reservation_no LIKE ?
              ORDER BY id DESC LIMIT 1",
            [$prefix . '%']
        );
        $seq = $last ? ((int) substr($last['reservation_no'], -4) + 1) : 1;
        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    // ------------------------------------------------------------------ //
    //  FIND
    // ------------------------------------------------------------------ //
    public static function findById(int $id): ?array
    {
        return Database::fetchOne(
            "SELECT r.*,
                    c.name            AS customer_name,
                    c.customer_no     AS customer_no,
                    c.phone           AS customer_phone,
                    q.quotation_no,
                    CONCAT(u.full_name) AS salesperson_name,
                    ap.full_name      AS approved_by_name
               FROM reservations r
          LEFT JOIN customers c   ON c.id = r.customer_id
          LEFT JOIN quotations q  ON q.id = r.quotation_id
          LEFT JOIN users u       ON u.id = r.salesperson_id
          LEFT JOIN users ap      ON ap.id = r.approved_by
              WHERE r.id = ? AND r.deleted_at IS NULL",
            [$id]
        );
    }

    public static function findByNo(string $no): ?array
    {
        return Database::fetchOne(
            "SELECT * FROM reservations WHERE reservation_no = ? AND deleted_at IS NULL",
            [$no]
        );
    }

    // ------------------------------------------------------------------ //
    //  LIST
    // ------------------------------------------------------------------ //
    public static function getAll(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $where  = ['r.deleted_at IS NULL'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[]  = 'r.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['customer_id'])) {
            $where[]  = 'r.customer_id = ?';
            $params[] = (int) $filters['customer_id'];
        }
        if (!empty($filters['salesperson_id'])) {
            $where[]  = 'r.salesperson_id = ?';
            $params[] = (int) $filters['salesperson_id'];
        }
        if (!empty($filters['date_from'])) {
            $where[]  = 'r.created_at >= ?';
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[]  = 'r.created_at <= ?';
            $params[] = $filters['date_to'] . ' 23:59:59';
        }
        if (!empty($filters['expiring_soon'])) {
            // dalam 2 hari ke depan
            $where[]  = "r.status = 'active' AND r.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 2 DAY)";
        }
        if (!empty($filters['search'])) {
            $like     = '%' . $filters['search'] . '%';
            $where[]  = '(r.reservation_no LIKE ? OR c.name LIKE ?)';
            $params[] = $like;
            $params[] = $like;
        }

        $whereStr = implode(' AND ', $where);
        $params[] = $limit;
        $params[] = $offset;

        return Database::fetchAll(
            "SELECT r.id, r.reservation_no, r.status, r.expiry_date,
                    r.total_usd, r.total_idr, r.notes,
                    r.created_at, r.updated_at,
                    c.name          AS customer_name,
                    u.full_name     AS salesperson_name,
                    q.quotation_no,
                    COUNT(ri.id)    AS item_count
               FROM reservations r
          LEFT JOIN customers c  ON c.id = r.customer_id
          LEFT JOIN users u      ON u.id = r.salesperson_id
          LEFT JOIN quotations q ON q.id = r.quotation_id
          LEFT JOIN reservation_items ri ON ri.reservation_id = r.id
              WHERE {$whereStr}
           GROUP BY r.id
           ORDER BY r.created_at DESC
              LIMIT ? OFFSET ?",
            $params
        );
    }

    public static function countAll(array $filters = []): int
    {
        $where  = ['r.deleted_at IS NULL'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[]  = 'r.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['customer_id'])) {
            $where[]  = 'r.customer_id = ?';
            $params[] = (int) $filters['customer_id'];
        }
        if (!empty($filters['search'])) {
            $like     = '%' . $filters['search'] . '%';
            $where[]  = '(r.reservation_no LIKE ? OR c.name LIKE ?)';
            $params[] = $like;
            $params[] = $like;
        }

        $whereStr = implode(' AND ', $where);
        $row = Database::fetchOne(
            "SELECT COUNT(DISTINCT r.id) AS total
               FROM reservations r
          LEFT JOIN customers c ON c.id = r.customer_id
              WHERE {$whereStr}",
            $params
        );
        return (int) ($row['total'] ?? 0);
    }

    // ------------------------------------------------------------------ //
    //  ITEMS
    // ------------------------------------------------------------------ //
    public static function getItems(int $reservationId): array
    {
        return Database::fetchAll(
            "SELECT ri.*,
                    d.sku, d.carat, d.color, d.clarity, d.cut, d.status AS diamond_status,
                    d.selling_price AS price_usd,
                    c.certificate_no, c.lab
               FROM reservation_items ri
               JOIN diamonds d          ON d.id = ri.diamond_id
          LEFT JOIN diamond_certificates c ON c.diamond_id = d.id AND c.is_primary = 1
              WHERE ri.reservation_id = ?
           ORDER BY ri.id ASC",
            [$reservationId]
        );
    }

    public static function addItem(int $reservationId, int $diamondId): void
    {
        $diamond = Database::fetchOne(
            "SELECT selling_price FROM diamonds WHERE id = ?", [$diamondId]
        );
        if (!$diamond) throw new RuntimeException('Berlian tidak ditemukan.');

        Database::query(
            "INSERT INTO reservation_items (reservation_id, diamond_id, price_usd, created_at)
             VALUES (?, ?, ?, NOW())",
            [$reservationId, $diamondId, $diamond['selling_price']]
        );

        self::recalcTotals($reservationId);
    }

    // ------------------------------------------------------------------ //
    //  CREATE / UPDATE
    // ------------------------------------------------------------------ //
    public static function create(array $data): int
    {
        Database::query(
            "INSERT INTO reservations
               (reservation_no, quotation_id, customer_id, salesperson_id,
                status, expiry_date, total_usd, total_idr,
                rate_usd_idr, notes, created_by, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())",
            [
                $data['reservation_no'],
                $data['quotation_id']   ?? null,
                $data['customer_id']    ?? null,
                $data['salesperson_id'] ?? null,
                $data['status']         ?? 'active',
                $data['expiry_date'],
                $data['total_usd']      ?? 0,
                $data['total_idr']      ?? 0,
                $data['rate_usd_idr']   ?? 16000,
                $data['notes']          ?? null,
                $data['created_by']     ?? null,
            ]
        );
        return (int) Database::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        Database::query(
            "UPDATE reservations
                SET expiry_date  = ?,
                    notes        = ?,
                    updated_by   = ?,
                    updated_at   = NOW()
              WHERE id = ?",
            [
                $data['expiry_date'],
                $data['notes']      ?? null,
                $data['updated_by'] ?? null,
                $id,
            ]
        );
    }

    public static function updateStatus(
        int $id,
        string $toStatus,
        string $eventCode,
        ?int $actorId   = null,
        string $notes   = ''
    ): void {
        $current = self::findById($id);
        if (!$current) return;

        Database::query(
            "UPDATE reservations SET status=?, updated_by=?, updated_at=NOW() WHERE id=?",
            [$toStatus, $actorId ?? $_SESSION['user_id'] ?? null, $id]
        );

        // history
        Database::query(
            "INSERT INTO reservation_state_histories
               (reservation_id, from_status, to_status, event_code, actor_id, notes, created_at)
             VALUES (?,?,?,?,?,?,NOW())",
            [$id, $current['status'], $toStatus, $eventCode,
             $actorId ?? $_SESSION['user_id'] ?? null, $notes ?: null]
        );

        // event log
        Database::query(
            "INSERT INTO resource_events
               (resource_type, resource_id, event_code, actor_id, created_at)
             VALUES ('reservation',?,?,?,NOW())",
            [$id, $eventCode, $actorId ?? $_SESSION['user_id'] ?? null]
        );
    }

    public static function softDelete(int $id): void
    {
        Database::query(
            "UPDATE reservations SET deleted_at=NOW(), updated_by=? WHERE id=?",
            [$_SESSION['user_id'] ?? null, $id]
        );
    }

    // ------------------------------------------------------------------ //
    //  TOTALS
    // ------------------------------------------------------------------ //
    public static function recalcTotals(int $reservationId): void
    {
        $row = Database::fetchOne(
            "SELECT COALESCE(SUM(ri.price_usd),0) AS total_usd
               FROM reservation_items ri
              WHERE ri.reservation_id = ?",
            [$reservationId]
        );
        $totalUsd = (float) ($row['total_usd'] ?? 0);

        $rateRow  = Database::fetchOne(
            "SELECT rate_to_idr FROM currencies
              WHERE code='USD' AND is_active=1
              ORDER BY effective_date DESC LIMIT 1"
        );
        $rate     = (float) ($rateRow['rate_to_idr'] ?? 16000);
        $totalIdr = $totalUsd * $rate;

        Database::query(
            "UPDATE reservations SET total_usd=?, total_idr=?, updated_at=NOW() WHERE id=?",
            [$totalUsd, $totalIdr, $reservationId]
        );
    }

    // ------------------------------------------------------------------ //
    //  DASHBOARD / STATS
    // ------------------------------------------------------------------ //
    public static function getStatusCounts(): array
    {
        $rows = Database::fetchAll(
            "SELECT status, COUNT(*) AS total
               FROM reservations
              WHERE deleted_at IS NULL
           GROUP BY status"
        );
        $map = [];
        foreach ($rows as $r) $map[$r['status']] = (int) $r['total'];
        return $map;
    }

    public static function getExpiringToday(): array
    {
        return Database::fetchAll(
            "SELECT r.*, c.name AS customer_name
               FROM reservations r
          LEFT JOIN customers c ON c.id = r.customer_id
              WHERE r.status = 'active'
                AND r.expiry_date = CURDATE()
                AND r.deleted_at IS NULL
           ORDER BY r.expiry_date ASC"
        );
    }
}
