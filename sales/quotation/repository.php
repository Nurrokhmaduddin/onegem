<?php
/**
 * sales/quotation/repository.php
 * Repository Quotation — query database saja
 */
declare(strict_types=1);

class QuotationRepository
{
    public static function getList(
        string $search = '', string $status = '', int $customerId = 0,
        int $salespersonId = 0,
        string $sortBy = 'q.created_at', string $sortDir = 'DESC',
        int $limit = DEFAULT_PER_PAGE, int $offset = 0
    ): array {
        $where  = ['q.deleted_at IS NULL'];
        $params = [];

        if ($search !== '') {
            $like    = '%' . sanitize_like($search) . '%';
            $where[] = '(q.quotation_no LIKE ? OR c.name LIKE ? OR l.name LIKE ?)';
            $params  = array_merge($params, [$like, $like, $like]);
        }
        if ($status !== '')       { $where[] = 'q.status = ?';         $params[] = $status; }
        if ($customerId > 0)      { $where[] = 'q.customer_id = ?';    $params[] = $customerId; }
        if ($salespersonId > 0)   { $where[] = 'q.salesperson_id = ?'; $params[] = $salespersonId; }

        $whereStr  = implode(' AND ', $where);
        $allowSort = ['q.quotation_no','q.quotation_date','q.status','q.total_idr','q.created_at'];
        if (!in_array($sortBy, $allowSort, true)) $sortBy = 'q.created_at';
        $sortDir   = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';
        $params[]  = $limit;
        $params[]  = $offset;

        return Database::fetchAll(
            "SELECT q.*,
                    c.name AS customer_name, c.customer_code,
                    l.name AS lead_name, l.lead_code,
                    u.full_name AS salesperson_name,
                    ua.full_name AS approved_by_name,
                    (SELECT COUNT(*) FROM quotation_items qi WHERE qi.quotation_id = q.id) AS item_count
               FROM quotations q
               LEFT JOIN customers c ON c.id = q.customer_id
               LEFT JOIN leads l ON l.id = q.lead_id
               LEFT JOIN users u ON u.id = q.salesperson_id
               LEFT JOIN users ua ON ua.id = q.approved_by
              WHERE {$whereStr}
              ORDER BY {$sortBy} {$sortDir}
              LIMIT ? OFFSET ?",
            $params
        );
    }

    public static function countList(
        string $search='', string $status='',
        int $customerId=0, int $salespersonId=0
    ): int {
        $where  = ['q.deleted_at IS NULL'];
        $params = [];
        if ($search !== '') {
            $like    = '%' . sanitize_like($search) . '%';
            $where[] = '(q.quotation_no LIKE ? OR c.name LIKE ? OR l.name LIKE ?)';
            $params  = array_merge($params, [$like, $like, $like]);
        }
        if ($status !== '')     { $where[] = 'q.status = ?';         $params[] = $status; }
        if ($customerId > 0)    { $where[] = 'q.customer_id = ?';    $params[] = $customerId; }
        if ($salespersonId > 0) { $where[] = 'q.salesperson_id = ?'; $params[] = $salespersonId; }

        $row = Database::fetchOne(
            "SELECT COUNT(*) n FROM quotations q
               LEFT JOIN customers c ON c.id = q.customer_id
               LEFT JOIN leads l ON l.id = q.lead_id
              WHERE " . implode(' AND ', $where),
            $params
        );
        return (int)($row['n'] ?? 0);
    }

    public static function findById(int $id): ?array
    {
        return Database::fetchOne(
            "SELECT q.*,
                    c.name AS customer_name, c.customer_code, c.phone AS customer_phone,
                    c.email AS customer_email, c.tier AS customer_tier,
                    l.name AS lead_name, l.lead_code,
                    u.full_name AS salesperson_name,
                    ua.full_name AS approved_by_name
               FROM quotations q
               LEFT JOIN customers c ON c.id = q.customer_id
               LEFT JOIN leads l ON l.id = q.lead_id
               LEFT JOIN users u ON u.id = q.salesperson_id
               LEFT JOIN users ua ON ua.id = q.approved_by
              WHERE q.id = ? AND q.deleted_at IS NULL",
            [$id]
        );
    }

    public static function getItems(int $quotationId): array
    {
        return Database::fetchAll(
            "SELECT qi.*,
                    d.internal_code, d.factory_barcode,
                    d.carat_weight, d.color_grade, d.clarity_grade, d.cut_grade,
                    d.status AS diamond_status,
                    ds.name AS shape_name,
                    dc.cert_number, dc.cert_type
               FROM quotation_items qi
               JOIN diamonds d ON d.id = qi.diamond_id
               LEFT JOIN diamond_shapes ds ON ds.id = d.shape_id
               LEFT JOIN diamond_certificates dc ON dc.diamond_id = d.id AND dc.is_primary = 1
              WHERE qi.quotation_id = ?
              ORDER BY qi.sort_order, qi.id",
            [$quotationId]
        );
    }

    public static function getStateHistories(int $quotationId): array
    {
        return Database::fetchAll(
            "SELECT h.*, u.full_name AS actor_name
               FROM quotation_state_histories h
               LEFT JOIN users u ON u.id = h.actor_id
              WHERE h.quotation_id = ?
              ORDER BY h.changed_at DESC",
            [$quotationId]
        );
    }

    public static function generateNo(string $branchCode = 'HO'): string
    {
        return Database::transaction(function () use ($branchCode) {
            $year  = date('Y');
            $month = date('m');
            $like  = "QUO/{$branchCode}/{$year}/{$month}/%";
            $last  = Database::fetchOne(
                "SELECT quotation_no FROM quotations
                  WHERE quotation_no LIKE ? ORDER BY id DESC LIMIT 1
                  FOR UPDATE",
                [$like]
            );
            $seq = $last ? ((int)substr($last['quotation_no'], -5)) + 1 : 1;
            return sprintf('QUO/%s/%s/%s/%05d', $branchCode, $year, $month, $seq);
        });
    }

    public static function create(array $data): int
    {
        return (int) Database::insert(
            "INSERT INTO quotations
               (quotation_no,customer_id,lead_id,salesperson_id,quotation_date,
                valid_until,status,subtotal_usd,discount_usd,total_usd,
                rate_usd_idr,total_idr,notes,internal_notes,created_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,'draft',?)",
            [
                $data['quotation_no'],
                $data['customer_id'] ?: null,
                $data['lead_id']     ?: null,
                $data['salesperson_id'] ?: null,
                $data['quotation_date'],
                $data['valid_until']  ?: null,
                $data['status']       ?? 'draft',
                (float)($data['subtotal_usd'] ?? 0),
                (float)($data['discount_usd'] ?? 0),
                (float)($data['total_usd']    ?? 0),
                (float)($data['rate_usd_idr'] ?? 0),
                (float)($data['total_idr']    ?? 0),
                $data['notes']          ?: null,
                $data['internal_notes'] ?: null,
                $_SESSION['user_id']    ?? null,
            ]
        );
    }

    public static function updateTotals(int $id): void
    {
        $rate = Database::fetchOne(
            "SELECT rate_usd_idr FROM quotations WHERE id=?", [$id]
        )['rate_usd_idr'] ?? 16000;

        Database::query(
            "UPDATE quotations q SET
               subtotal_usd = (SELECT COALESCE(SUM(final_price_usd),0) FROM quotation_items WHERE quotation_id=q.id),
               total_usd    = (SELECT COALESCE(SUM(final_price_usd),0) FROM quotation_items WHERE quotation_id=q.id) - q.discount_usd,
               total_idr    = ((SELECT COALESCE(SUM(final_price_usd),0) FROM quotation_items WHERE quotation_id=q.id) - q.discount_usd) * ?
             WHERE q.id=?",
            [$rate, $id]
        );
    }

    public static function addItem(int $quotationId, int $diamondId, float $discountPct = 0): void
    {
        $diamond = Database::fetchOne(
            "SELECT selling_price_usd FROM diamonds WHERE id=?", [$diamondId]
        );
        if (!$diamond) throw new RuntimeException('Berlian tidak ditemukan.');

        $rate         = Database::fetchOne(
            "SELECT rate_usd_idr FROM quotations WHERE id=?", [$quotationId]
        )['rate_usd_idr'] ?? 16000;

        $sellUsd      = (float)$diamond['selling_price_usd'];
        $discountUsd  = round($sellUsd * $discountPct / 100, 4);
        $finalUsd     = $sellUsd - $discountUsd;
        $finalIdr     = round($finalUsd * $rate, 2);

        $sortOrder = Database::fetchOne(
            "SELECT COALESCE(MAX(sort_order),0)+1 n FROM quotation_items WHERE quotation_id=?",
            [$quotationId]
        )['n'] ?? 1;

        Database::insert(
            "INSERT INTO quotation_items
               (quotation_id,diamond_id,selling_price_usd,discount_pct,
                discount_usd,final_price_usd,final_price_idr,sort_order)
             VALUES (?,?,?,?,?,?,?,?)",
            [$quotationId,$diamondId,$sellUsd,$discountPct,
             $discountUsd,$finalUsd,$finalIdr,$sortOrder]
        );
        self::updateTotals($quotationId);
    }

    public static function removeItem(int $itemId, int $quotationId): void
    {
        Database::query(
            "DELETE FROM quotation_items WHERE id=? AND quotation_id=?",
            [$itemId, $quotationId]
        );
        self::updateTotals($quotationId);
    }

    public static function updateStatus(
        int $id, string $status, string $eventName,
        ?int $approvedBy = null, ?string $notes = null
    ): void {
        $old = Database::fetchOne("SELECT status FROM quotations WHERE id=?", [$id]);

        $setApproved = $approvedBy ? ", approved_by=?, approved_at=NOW()" : '';
        $approvedArr = $approvedBy ? [$approvedBy] : [];

        Database::query(
            "UPDATE quotations SET status=?, updated_by=? {$setApproved} WHERE id=?",
            array_merge([$status, $_SESSION['user_id'] ?? null], $approvedArr, [$id])
        );

        Database::query(
            "INSERT INTO quotation_state_histories
               (quotation_id,from_status,to_status,event_name,actor_id,notes)
             VALUES (?,?,?,?,?,?)",
            [$id, $old['status'] ?? null, $status, $eventName,
             $_SESSION['user_id'] ?? null, $notes]
        );
    }

    public static function softDelete(int $id): void
    {
        Database::query(
            "UPDATE quotations SET deleted_at=NOW(),updated_by=? WHERE id=?",
            [$_SESSION['user_id'] ?? null, $id]
        );
    }

    public static function getStats(): array
    {
        return Database::fetchOne(
            "SELECT COUNT(*) total,
               SUM(status='draft') draft,
               SUM(status='submitted') submitted,
               SUM(status='approved') approved,
               SUM(status='accepted') accepted,
               SUM(status='converted') converted
             FROM quotations WHERE deleted_at IS NULL"
        ) ?? [];
    }
}
