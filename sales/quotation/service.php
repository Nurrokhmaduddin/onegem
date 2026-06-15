<?php
/**
 * sales/quotation/service.php
 * Service Quotation — business rule, validasi, lifecycle
 */
declare(strict_types=1);

class QuotationService
{
    const TRANSITIONS = [
        'draft'     => ['submitted', 'cancelled'],
        'submitted' => ['approved', 'rejected', 'cancelled'],
        'approved'  => ['accepted', 'cancelled'],
        'rejected'  => ['draft'],
        'accepted'  => ['converted', 'cancelled'],
        'converted' => [],
        'cancelled' => [],
    ];

    const STATUS_LABELS = [
        'draft'     => 'Draft',
        'submitted' => 'Diajukan',
        'approved'  => 'Disetujui',
        'rejected'  => 'Ditolak',
        'accepted'  => 'Diterima Customer',
        'converted' => 'Konversi ke Reservasi',
        'cancelled' => 'Dibatalkan',
    ];

    public static function validate(array $data): array
    {
        $errors = [];
        if (empty($data['customer_id']) && empty($data['lead_id']))
            $errors['customer_id'] = 'Pilih customer atau lead.';
        if (empty($data['quotation_date']))
            $errors['quotation_date'] = 'Tanggal quotation wajib diisi.';
        if (!empty($data['valid_until']) && $data['valid_until'] < $data['quotation_date'])
            $errors['valid_until'] = 'Tanggal berlaku tidak boleh sebelum tanggal quotation.';
        return $errors;
    }

    public static function create(array $data): int
    {
        $errors = self::validate($data);
        if (!empty($errors)) throw new RuntimeException(json_encode($errors));

        return Database::transaction(function () use ($data) {
            // Ambil kurs aktif
            $rateRow = Database::fetchOne(
                "SELECT rate_to_idr FROM currencies
                  WHERE code='USD' AND is_active=1 AND effective_date<=CURDATE()
                  ORDER BY effective_date DESC LIMIT 1"
            );
            $rate = (float)($rateRow['rate_to_idr'] ?? 16000);

            $data['quotation_no']   = QuotationRepository::generateNo();
            $data['rate_usd_idr']   = $rate;
            $data['subtotal_usd']   = 0;
            $data['discount_usd']   = 0;
            $data['total_usd']      = 0;
            $data['total_idr']      = 0;
            $data['status']         = 'draft';
            $data['salesperson_id'] = $data['salesperson_id'] ?: ($_SESSION['user_id'] ?? null);

            $id = QuotationRepository::create($data);

            // Jika ada diamond_ids yang dipilih sekaligus
            if (!empty($data['diamond_ids']) && is_array($data['diamond_ids'])) {
                foreach ($data['diamond_ids'] as $diamondId) {
                    $discPct = (float)($data['discount_pct'][$diamondId] ?? 0);
                    QuotationRepository::addItem($id, (int)$diamondId, $discPct);
                    // Lock diamond
                    self::lockDiamond((int)$diamondId, $id);
                }
            }

            // Update status lead ke 'quoted'
            if (!empty($data['lead_id'])) {
                Database::query(
                    "UPDATE leads SET status='quoted',updated_by=? WHERE id=? AND status IN ('new','contacted','qualified')",
                    [$_SESSION['user_id'] ?? null, $data['lead_id']]
                );
            }

            QuotationRepository::updateStatus($id, 'draft', 'QUOTATION_CREATED');

            audit_log('QUOTATION', 'CREATE', $data['quotation_no'], 'quotations', (string)$id,
                null, ['status' => 'draft'], "Quotation baru: {$data['quotation_no']}"
            );

            return $id;
        });
    }

    public static function addItem(int $quotationId, int $diamondId, float $discountPct = 0): void
    {
        $quotation = QuotationRepository::findById($quotationId);
        if (!$quotation) throw new RuntimeException('Quotation tidak ditemukan.');
        if (!in_array($quotation['status'], ['draft'], true))
            throw new RuntimeException('Item hanya bisa ditambah pada quotation berstatus Draft.');

        // Cek diamond tersedia
        $diamond = Database::fetchOne(
            "SELECT id, status FROM diamonds WHERE id=? AND deleted_at IS NULL",
            [$diamondId]
        );
        if (!$diamond) throw new RuntimeException('Berlian tidak ditemukan.');
        if (!in_array($diamond['status'], ['available', 'reserved'], true))
            throw new RuntimeException('Berlian tidak tersedia untuk ditambahkan ke quotation.');

        // Cek sudah ada di quotation ini?
        $existing = Database::fetchOne(
            "SELECT id FROM quotation_items WHERE quotation_id=? AND diamond_id=?",
            [$quotationId, $diamondId]
        );
        if ($existing) throw new RuntimeException('Berlian ini sudah ada dalam quotation.');

        Database::transaction(function () use ($quotationId, $diamondId, $discountPct) {
            QuotationRepository::addItem($quotationId, $diamondId, $discountPct);
        });
    }

    public static function removeItem(int $itemId, int $quotationId): void
    {
        $quotation = QuotationRepository::findById($quotationId);
        if (!$quotation) throw new RuntimeException('Quotation tidak ditemukan.');
        if ($quotation['status'] !== 'draft')
            throw new RuntimeException('Item hanya bisa dihapus pada quotation berstatus Draft.');

        // Ambil diamond_id dari item
        $item = Database::fetchOne(
            "SELECT diamond_id FROM quotation_items WHERE id=? AND quotation_id=?",
            [$itemId, $quotationId]
        );

        Database::transaction(function () use ($itemId, $quotationId, $item) {
            QuotationRepository::removeItem($itemId, $quotationId);
        });
    }

    public static function submit(int $id): void
    {
        $quotation = QuotationRepository::findById($id);
        if (!$quotation) throw new RuntimeException('Quotation tidak ditemukan.');
        if ($quotation['status'] !== 'draft')
            throw new RuntimeException('Hanya quotation Draft yang dapat diajukan.');

        $items = QuotationRepository::getItems($id);
        if (empty($items)) throw new RuntimeException('Quotation harus memiliki minimal 1 item berlian.');

        Database::transaction(function () use ($id, $quotation) {
            QuotationRepository::updateStatus($id, 'submitted', 'QUOTATION_SUBMITTED');
            // Notifikasi ke manager (Sprint 7)
            audit_log('QUOTATION', 'UPDATE', $quotation['quotation_no'], 'quotations', (string)$id,
                ['status' => 'draft'], ['status' => 'submitted'],
                "Quotation diajukan untuk approval: {$quotation['quotation_no']}"
            );
        });
    }

    public static function approve(int $id, string $notes = ''): void
    {
        $quotation = QuotationRepository::findById($id);
        if (!$quotation) throw new RuntimeException('Quotation tidak ditemukan.');
        if ($quotation['status'] !== 'submitted')
            throw new RuntimeException('Hanya quotation yang Diajukan yang dapat disetujui.');

        Database::transaction(function () use ($id, $notes, $quotation) {
            QuotationRepository::updateStatus($id, 'approved', 'QUOTATION_APPROVED',
                (int)($_SESSION['user_id'] ?? 0), $notes);

            // Lock semua diamond ke reserved
            $items = QuotationRepository::getItems($id);
            foreach ($items as $item) {
                self::lockDiamond($item['diamond_id'], $id);
            }

            audit_log('QUOTATION', 'APPROVE', $quotation['quotation_no'], 'quotations', (string)$id,
                ['status' => 'submitted'], ['status' => 'approved'],
                "Quotation disetujui: {$quotation['quotation_no']}"
            );
        });
    }

    public static function reject(int $id, string $reason): void
    {
        $quotation = QuotationRepository::findById($id);
        if (!$quotation) throw new RuntimeException('Quotation tidak ditemukan.');
        if ($quotation['status'] !== 'submitted')
            throw new RuntimeException('Hanya quotation yang Diajukan yang dapat ditolak.');
        if (empty(trim($reason))) throw new RuntimeException('Alasan penolakan wajib diisi.');

        Database::transaction(function () use ($id, $reason, $quotation) {
            QuotationRepository::updateStatus($id, 'rejected', 'QUOTATION_REJECTED',
                (int)($_SESSION['user_id'] ?? 0), $reason);
            Database::query(
                "UPDATE quotations SET reject_reason=? WHERE id=?", [$reason, $id]
            );
            audit_log('QUOTATION', 'REJECT', $quotation['quotation_no'], 'quotations', (string)$id,
                ['status' => 'submitted'], ['status' => 'rejected'],
                "Quotation ditolak: {$quotation['quotation_no']} — {$reason}"
            );
        });
    }

    public static function accept(int $id): void
    {
        $quotation = QuotationRepository::findById($id);
        if (!$quotation) throw new RuntimeException('Quotation tidak ditemukan.');
        if ($quotation['status'] !== 'approved')
            throw new RuntimeException('Hanya quotation yang Disetujui yang dapat diterima.');

        Database::transaction(function () use ($id, $quotation) {
            QuotationRepository::updateStatus($id, 'accepted', 'QUOTATION_ACCEPTED');
            audit_log('QUOTATION', 'APPROVE', $quotation['quotation_no'], 'quotations', (string)$id,
                ['status' => 'approved'], ['status' => 'accepted'],
                "Quotation diterima customer: {$quotation['quotation_no']}"
            );
        });
    }

    public static function cancel(int $id, string $reason = ''): void
    {
        $quotation = QuotationRepository::findById($id);
        if (!$quotation) throw new RuntimeException('Quotation tidak ditemukan.');
        if (in_array($quotation['status'], ['converted', 'cancelled'], true))
            throw new RuntimeException('Quotation ini tidak dapat dibatalkan.');

        Database::transaction(function () use ($id, $reason, $quotation) {
            // Release diamond locks
            $items = QuotationRepository::getItems($id);
            foreach ($items as $item) {
                self::releaseDiamond($item['diamond_id']);
            }
            QuotationRepository::updateStatus($id, 'cancelled', 'QUOTATION_CANCELLED',
                null, $reason);
            audit_log('QUOTATION', 'DELETE', $quotation['quotation_no'], 'quotations', (string)$id,
                ['status' => $quotation['status']], ['status' => 'cancelled'],
                "Quotation dibatalkan: {$quotation['quotation_no']}"
            );
        });
    }

    public static function delete(int $id): void
    {
        $quotation = QuotationRepository::findById($id);
        if (!$quotation) throw new RuntimeException('Quotation tidak ditemukan.');
        if (!in_array($quotation['status'], ['draft', 'cancelled'], true))
            throw new RuntimeException('Hanya quotation Draft atau Dibatalkan yang dapat dihapus.');

        QuotationRepository::softDelete($id);
        audit_log('QUOTATION', 'DELETE', $quotation['quotation_no'], 'quotations', (string)$id,
            ['quotation_no' => $quotation['quotation_no']], null,
            "Quotation dihapus: {$quotation['quotation_no']}"
        );
    }

    // Helper: lock diamond (set reserved)
    private static function lockDiamond(int $diamondId, int $quotationId): void
    {
        $diamond = Database::fetchOne(
            "SELECT status FROM diamonds WHERE id=?", [$diamondId]
        );
        if ($diamond && $diamond['status'] === 'available') {
            Database::query(
                "UPDATE diamonds SET status='reserved', updated_by=? WHERE id=?",
                [$_SESSION['user_id'] ?? null, $diamondId]
            );
            Database::query(
                "INSERT INTO diamond_state_histories
                   (diamond_id,from_status,to_status,event_name,ref_type,ref_id,actor_id)
                 VALUES (?,'available','reserved','DIAMOND_RESERVED','quotation',?,?)",
                [$diamondId, $quotationId, $_SESSION['user_id'] ?? null]
            );
        }
    }

    // Helper: release diamond lock
    private static function releaseDiamond(int $diamondId): void
    {
        $diamond = Database::fetchOne(
            "SELECT status FROM diamonds WHERE id=?", [$diamondId]
        );
        if ($diamond && $diamond['status'] === 'reserved') {
            Database::query(
                "UPDATE diamonds SET status='available', updated_by=? WHERE id=?",
                [$_SESSION['user_id'] ?? null, $diamondId]
            );
            Database::query(
                "INSERT INTO diamond_state_histories
                   (diamond_id,from_status,to_status,event_name,actor_id)
                 VALUES (?,'reserved','available','DIAMOND_RESERVATION_RELEASED',?)",
                [$diamondId, $_SESSION['user_id'] ?? null]
            );
        }
    }
}
