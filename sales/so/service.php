<?php
/**
 * sales/so/service.php
 * Service Sales Order — business rule, validasi, state machine
 *
 * State: draft → submitted → approved → completed
 *                          ↘ cancelled
 *        draft/submitted   ↘ cancelled
 */
declare(strict_types=1);

class SalesOrderService
{
    const STATUS_LABELS = [
        'draft'     => 'Draft',
        'submitted' => 'Menunggu Approval',
        'approved'  => 'Disetujui',
        'cancelled' => 'Dibatalkan',
        'completed' => 'Selesai',
    ];

    // ------------------------------------------------------------------ //
    //  VALIDATE
    // ------------------------------------------------------------------ //
    public static function validate(array $data): array
    {
        $errors = [];
        if (empty($data['customer_id']))
            $errors['customer_id'] = 'Customer wajib dipilih.';
        return $errors;
    }

    // ------------------------------------------------------------------ //
    //  CREATE MANUAL (tanpa reservation)
    // ------------------------------------------------------------------ //
    public static function create(array $data): int
    {
        $errors = self::validate($data);
        if (!empty($errors)) throw new RuntimeException(json_encode($errors));

        return Database::transaction(function () use ($data) {
            $rateRow  = Database::fetchOne(
                "SELECT rate_to_idr FROM currencies
                  WHERE code='USD' AND is_active=1
                  ORDER BY effective_date DESC LIMIT 1"
            );
            $rate = (float) ($rateRow['rate_to_idr'] ?? 16000);

            $soId = SalesOrderRepository::create([
                'so_no'          => SalesOrderRepository::generateNo(),
                'reservation_id' => null,
                'customer_id'    => (int) $data['customer_id'],
                'salesperson_id' => !empty($data['salesperson_id'])
                                    ? (int) $data['salesperson_id']
                                    : ($_SESSION['user_id'] ?? null),
                'status'         => 'draft',
                'total_usd'      => 0,
                'total_idr'      => 0,
                'rate_usd_idr'   => $rate,
                'notes'          => $data['notes'] ?? null,
                'created_by'     => $_SESSION['user_id'] ?? null,
            ]);

            // Tambah item diamond jika ada
            if (!empty($data['diamond_ids']) && is_array($data['diamond_ids'])) {
                foreach ($data['diamond_ids'] as $diamondId) {
                    self::addItem($soId, (int) $diamondId);
                }
                SalesOrderRepository::recalcTotals($soId);
            }

            SalesOrderRepository::updateStatus($soId, 'draft', 'SALES_ORDER_CREATED');

            audit_log('SALES_ORDER', 'CREATE', null, 'sales_orders', (string) $soId,
                null, ['status' => 'draft'], 'Sales Order baru dibuat manual');

            return $soId;
        });
    }

    // ------------------------------------------------------------------ //
    //  ADD ITEM
    // ------------------------------------------------------------------ //
    public static function addItem(int $soId, int $diamondId): void
    {
        $so = SalesOrderRepository::findById($soId);
        if (!$so) throw new RuntimeException('Sales Order tidak ditemukan.');
        if (!in_array($so['status'], ['draft'], true))
            throw new RuntimeException('Item hanya bisa ditambahkan ke SO berstatus Draft.');

        // Cek diamond
        $diamond = Database::fetchOne(
            "SELECT id, status, selling_price FROM diamonds WHERE id = ? AND deleted_at IS NULL",
            [$diamondId]
        );
        if (!$diamond) throw new RuntimeException('Berlian tidak ditemukan.');
        if (!in_array($diamond['status'], ['available', 'reserved'], true))
            throw new RuntimeException("Berlian berstatus '{$diamond['status']}' tidak dapat ditambahkan.");

        // Cek belum ada di SO ini
        $exists = Database::fetchOne(
            "SELECT id FROM sales_order_items WHERE sales_order_id = ? AND diamond_id = ?",
            [$soId, $diamondId]
        );
        if ($exists) throw new RuntimeException('Berlian sudah ada dalam Sales Order ini.');

        $rateRow = Database::fetchOne(
            "SELECT rate_to_idr FROM currencies WHERE code='USD' AND is_active=1
              ORDER BY effective_date DESC LIMIT 1"
        );
        $rate    = (float) ($rateRow['rate_to_idr'] ?? 16000);
        $priceUsd = (float) $diamond['selling_price'];

        Database::query(
            "INSERT INTO sales_order_items
               (sales_order_id, diamond_id, price_usd, price_idr, created_at)
             VALUES (?,?,?,?,NOW())",
            [$soId, $diamondId, $priceUsd, $priceUsd * $rate]
        );

        // Lock diamond jika masih available
        if ($diamond['status'] === 'available') {
            Database::query(
                "UPDATE diamonds SET status='reserved', updated_by=? WHERE id=?",
                [$_SESSION['user_id'] ?? null, $diamondId]
            );
        }

        SalesOrderRepository::recalcTotals($soId);
    }

    // ------------------------------------------------------------------ //
    //  REMOVE ITEM
    // ------------------------------------------------------------------ //
    public static function removeItem(int $itemId, int $soId): void
    {
        $so = SalesOrderRepository::findById($soId);
        if (!$so) throw new RuntimeException('Sales Order tidak ditemukan.');
        if ($so['status'] !== 'draft')
            throw new RuntimeException('Item hanya bisa dihapus dari SO berstatus Draft.');

        $item = Database::fetchOne(
            "SELECT * FROM sales_order_items WHERE id = ? AND sales_order_id = ?",
            [$itemId, $soId]
        );
        if (!$item) throw new RuntimeException('Item tidak ditemukan.');

        Database::query("DELETE FROM sales_order_items WHERE id = ?", [$itemId]);

        // Release diamond kembali ke available
        $diamond = Database::fetchOne(
            "SELECT status FROM diamonds WHERE id = ?", [$item['diamond_id']]
        );
        if ($diamond && $diamond['status'] === 'reserved') {
            Database::query(
                "UPDATE diamonds SET status='available', updated_by=? WHERE id=?",
                [$_SESSION['user_id'] ?? null, $item['diamond_id']]
            );
        }

        SalesOrderRepository::recalcTotals($soId);
    }

    // ------------------------------------------------------------------ //
    //  UPDATE
    // ------------------------------------------------------------------ //
    public static function update(int $id, array $data): void
    {
        $so = SalesOrderRepository::findById($id);
        if (!$so) throw new RuntimeException('Sales Order tidak ditemukan.');
        if (!in_array($so['status'], ['draft'], true))
            throw new RuntimeException('Hanya SO berstatus Draft yang dapat diubah.');

        SalesOrderRepository::update($id, $data);

        audit_log('SALES_ORDER', 'UPDATE', $so['so_no'], 'sales_orders', (string) $id,
            ['notes' => $so['notes']], ['notes' => $data['notes'] ?? null],
            'SO diperbarui');
    }

    // ------------------------------------------------------------------ //
    //  SUBMIT (draft → submitted)
    // ------------------------------------------------------------------ //
    public static function submit(int $id): void
    {
        $so = SalesOrderRepository::findById($id);
        if (!$so) throw new RuntimeException('Sales Order tidak ditemukan.');
        if ($so['status'] !== 'draft')
            throw new RuntimeException('Hanya SO berstatus Draft yang dapat diajukan.');

        $items = SalesOrderRepository::getItems($id);
        if (empty($items))
            throw new RuntimeException('Sales Order harus memiliki minimal 1 item berlian.');

        SalesOrderRepository::updateStatus($id, 'submitted', 'SALES_ORDER_SUBMITTED');

        audit_log('SALES_ORDER', 'UPDATE', $so['so_no'], 'sales_orders', (string) $id,
            ['status' => 'draft'], ['status' => 'submitted'],
            'SO diajukan untuk approval');
    }

    // ------------------------------------------------------------------ //
    //  APPROVE (submitted → approved)
    // ------------------------------------------------------------------ //
    public static function approve(int $id, string $notes = ''): void
    {
        $so = SalesOrderRepository::findById($id);
        if (!$so) throw new RuntimeException('Sales Order tidak ditemukan.');
        if ($so['status'] !== 'submitted')
            throw new RuntimeException('Hanya SO berstatus "Menunggu Approval" yang dapat disetujui.');

        Database::transaction(function () use ($id, $so, $notes) {
            SalesOrderRepository::updateStatus($id, 'approved', 'SALES_ORDER_APPROVED',
                null, $notes);

            // Diamond status → sold (terkunci untuk delivery)
            $items = SalesOrderRepository::getItems($id);
            foreach ($items as $item) {
                Database::query(
                    "UPDATE diamonds SET status='sold', updated_by=? WHERE id=?",
                    [$_SESSION['user_id'] ?? null, $item['diamond_id']]
                );
                Database::query(
                    "INSERT INTO diamond_state_histories
                       (diamond_id,from_status,to_status,event_name,ref_type,ref_id,actor_id,created_at)
                     VALUES (?,'reserved','sold','DIAMOND_SOLD','sales_order',?,?,NOW())",
                    [$item['diamond_id'], $id, $_SESSION['user_id'] ?? null]
                );
            }

            audit_log('SALES_ORDER', 'APPROVE', $so['so_no'], 'sales_orders', (string) $id,
                ['status' => 'submitted'], ['status' => 'approved'],
                "SO disetujui. Notes: {$notes}");
        });
    }

    // ------------------------------------------------------------------ //
    //  REJECT / REVISE (submitted → draft)
    // ------------------------------------------------------------------ //
    public static function reject(int $id, string $reason): void
    {
        if (empty(trim($reason)))
            throw new RuntimeException('Alasan penolakan wajib diisi.');

        $so = SalesOrderRepository::findById($id);
        if (!$so) throw new RuntimeException('Sales Order tidak ditemukan.');
        if ($so['status'] !== 'submitted')
            throw new RuntimeException('Hanya SO berstatus "Menunggu Approval" yang dapat ditolak.');

        SalesOrderRepository::updateStatus($id, 'draft', 'SALES_ORDER_REJECTED', null, $reason);

        audit_log('SALES_ORDER', 'REJECT', $so['so_no'], 'sales_orders', (string) $id,
            ['status' => 'submitted'], ['status' => 'draft'],
            "SO dikembalikan: {$reason}");
    }

    // ------------------------------------------------------------------ //
    //  CANCEL (draft|submitted → cancelled)
    // ------------------------------------------------------------------ //
    public static function cancel(int $id, string $reason = ''): void
    {
        $so = SalesOrderRepository::findById($id);
        if (!$so) throw new RuntimeException('Sales Order tidak ditemukan.');
        if (!in_array($so['status'], ['draft', 'submitted'], true))
            throw new RuntimeException('Hanya SO berstatus Draft atau Menunggu Approval yang dapat dibatalkan.');

        Database::transaction(function () use ($id, $so, $reason) {
            // Release semua diamond kembali ke available
            $items = SalesOrderRepository::getItems($id);
            foreach ($items as $item) {
                $d = Database::fetchOne(
                    "SELECT status FROM diamonds WHERE id=?", [$item['diamond_id']]
                );
                if ($d && in_array($d['status'], ['reserved', 'sold'], true)) {
                    Database::query(
                        "UPDATE diamonds SET status='available', updated_by=? WHERE id=?",
                        [$_SESSION['user_id'] ?? null, $item['diamond_id']]
                    );
                    Database::query(
                        "INSERT INTO diamond_state_histories
                           (diamond_id,from_status,to_status,event_name,ref_type,ref_id,actor_id,created_at)
                         VALUES (?,?,?,'DIAMOND_SO_CANCELLED','sales_order',?,?,NOW())",
                        [$item['diamond_id'], $d['status'], 'available', $id, $_SESSION['user_id'] ?? null]
                    );
                }
            }

            SalesOrderRepository::updateStatus($id, 'cancelled', 'SALES_ORDER_CANCELLED',
                null, $reason);

            audit_log('SALES_ORDER', 'UPDATE', $so['so_no'], 'sales_orders', (string) $id,
                ['status' => $so['status']], ['status' => 'cancelled'],
                "SO dibatalkan: {$reason}");
        });
    }

    // ------------------------------------------------------------------ //
    //  COMPLETE (approved → completed) — dipanggil saat delivery selesai
    // ------------------------------------------------------------------ //
    public static function complete(int $id, string $notes = ''): void
    {
        $so = SalesOrderRepository::findById($id);
        if (!$so) throw new RuntimeException('Sales Order tidak ditemukan.');
        if ($so['status'] !== 'approved')
            throw new RuntimeException('Hanya SO berstatus Disetujui yang dapat diselesaikan.');

        Database::transaction(function () use ($id, $so, $notes) {
            SalesOrderRepository::updateStatus($id, 'completed', 'SALES_ORDER_COMPLETED',
                null, $notes);

            // Diamond → delivered
            $items = SalesOrderRepository::getItems($id);
            foreach ($items as $item) {
                Database::query(
                    "UPDATE diamonds SET status='delivered', updated_by=? WHERE id=?",
                    [$_SESSION['user_id'] ?? null, $item['diamond_id']]
                );
                Database::query(
                    "INSERT INTO diamond_state_histories
                       (diamond_id,from_status,to_status,event_name,ref_type,ref_id,actor_id,created_at)
                     VALUES (?,'sold','delivered','DIAMOND_DELIVERED','sales_order',?,?,NOW())",
                    [$item['diamond_id'], $id, $_SESSION['user_id'] ?? null]
                );
            }

            audit_log('SALES_ORDER', 'UPDATE', $so['so_no'], 'sales_orders', (string) $id,
                ['status' => 'approved'], ['status' => 'completed'],
                "SO selesai / berlian terkirim: {$notes}");
        });
    }
}
