<?php
/**
 * sales/reservation/service.php
 * Service Reservasi — business rule, validasi, state machine
 *
 * Business Rules (dari dokumen):
 * R001 — Reservation maksimal 7 hari (expiry_date <= created_date + 7)
 * R002 — Diamond harus Available sebelum di-reservasi
 */
declare(strict_types=1);

class ReservationService
{
    const MAX_DAYS = 7; // R001

    const STATUS_LABELS = [
        'active'    => 'Aktif',
        'released'  => 'Dilepas',
        'expired'   => 'Kedaluwarsa',
        'converted' => 'Dikonversi ke Sales Order',
    ];

    // ------------------------------------------------------------------ //
    //  VALIDATE
    // ------------------------------------------------------------------ //
    public static function validate(array $data): array
    {
        $errors = [];

        if (empty($data['customer_id']) && empty($data['quotation_id']))
            $errors['customer_id'] = 'Customer atau Quotation wajib dipilih.';

        if (empty($data['expiry_date']))
            $errors['expiry_date'] = 'Tanggal kedaluwarsa wajib diisi.';

        // R001 — max 7 hari dari hari ini
        if (!empty($data['expiry_date'])) {
            $maxDate = date('Y-m-d', strtotime('+' . self::MAX_DAYS . ' days'));
            if ($data['expiry_date'] < date('Y-m-d'))
                $errors['expiry_date'] = 'Tanggal kedaluwarsa tidak boleh di masa lalu.';
            elseif ($data['expiry_date'] > $maxDate)
                $errors['expiry_date'] = 'Reservasi maksimal ' . self::MAX_DAYS . ' hari ke depan (R001).';
        }

        return $errors;
    }

    // ------------------------------------------------------------------ //
    //  CREATE MANUAL (tanpa quotation)
    // ------------------------------------------------------------------ //
    public static function create(array $data): int
    {
        $errors = self::validate($data);
        if (!empty($errors)) throw new RuntimeException(json_encode($errors));

        return Database::transaction(function () use ($data) {
            $rateRow = Database::fetchOne(
                "SELECT rate_to_idr FROM currencies
                  WHERE code='USD' AND is_active=1
                  ORDER BY effective_date DESC LIMIT 1"
            );
            $rate = (float) ($rateRow['rate_to_idr'] ?? 16000);

            $payload = [
                'reservation_no'  => ReservationRepository::generateNo(),
                'quotation_id'    => $data['quotation_id'] ?? null,
                'customer_id'     => $data['customer_id']  ?? null,
                'salesperson_id'  => $data['salesperson_id'] ?: ($_SESSION['user_id'] ?? null),
                'status'          => 'active',
                'expiry_date'     => $data['expiry_date'],
                'rate_usd_idr'    => $rate,
                'total_usd'       => 0,
                'total_idr'       => 0,
                'notes'           => $data['notes'] ?? null,
                'created_by'      => $_SESSION['user_id'] ?? null,
            ];

            $id = ReservationRepository::create($payload);

            // Tambah diamond jika dipilih
            if (!empty($data['diamond_ids']) && is_array($data['diamond_ids'])) {
                foreach ($data['diamond_ids'] as $diamondId) {
                    self::reserveDiamond((int) $diamondId, $id);
                    ReservationRepository::addItem($id, (int) $diamondId);
                }
            }

            ReservationRepository::updateStatus($id, 'active', 'RESERVATION_CREATED');

            audit_log('RESERVATION', 'CREATE', $payload['reservation_no'],
                'reservations', (string) $id, null, ['status' => 'active'],
                "Reservasi baru: {$payload['reservation_no']}");

            return $id;
        });
    }

    // ------------------------------------------------------------------ //
    //  CREATE FROM QUOTATION (alur utama)
    // ------------------------------------------------------------------ //
    public static function createFromQuotation(int $quotationId, array $data = []): int
    {
        $quotation = Database::fetchOne(
            "SELECT * FROM quotations WHERE id = ? AND deleted_at IS NULL", [$quotationId]
        );
        if (!$quotation) throw new RuntimeException('Quotation tidak ditemukan.');
        if ($quotation['status'] !== 'accepted')
            throw new RuntimeException('Hanya quotation berstatus "Diterima Customer" yang dapat dikonversi.');

        // Cek sudah pernah dikonversi?
        $existing = Database::fetchOne(
            "SELECT id FROM reservations WHERE quotation_id = ? AND deleted_at IS NULL AND status != 'released'",
            [$quotationId]
        );
        if ($existing) throw new RuntimeException('Quotation ini sudah memiliki reservasi aktif.');

        // Hitung expiry — default 7 hari atau override dari form
        $expiryDate = !empty($data['expiry_date'])
            ? $data['expiry_date']
            : date('Y-m-d', strtotime('+' . self::MAX_DAYS . ' days'));

        // Validasi expiry
        $maxDate = date('Y-m-d', strtotime('+' . self::MAX_DAYS . ' days'));
        if ($expiryDate > $maxDate)
            throw new RuntimeException('Reservasi maksimal ' . self::MAX_DAYS . ' hari ke depan (R001).');

        return Database::transaction(function () use ($quotation, $expiryDate, $data, $quotationId) {
            $rateRow = Database::fetchOne(
                "SELECT rate_to_idr FROM currencies
                  WHERE code='USD' AND is_active=1
                  ORDER BY effective_date DESC LIMIT 1"
            );
            $rate = (float) ($rateRow['rate_to_idr'] ?? 16000);

            $no = ReservationRepository::generateNo();

            $payload = [
                'reservation_no' => $no,
                'quotation_id'   => $quotationId,
                'customer_id'    => $quotation['customer_id'],
                'salesperson_id' => $quotation['salesperson_id'],
                'status'         => 'active',
                'expiry_date'    => $expiryDate,
                'rate_usd_idr'   => $rate,
                'total_usd'      => $quotation['total_usd'],
                'total_idr'      => $quotation['total_idr'],
                'notes'          => $data['notes'] ?? $quotation['notes'],
                'created_by'     => $_SESSION['user_id'] ?? null,
            ];

            $id = ReservationRepository::create($payload);

            // Salin item dari quotation
            $qItems = Database::fetchAll(
                "SELECT diamond_id, price_usd FROM quotation_items WHERE quotation_id = ?",
                [$quotationId]
            );
            foreach ($qItems as $item) {
                // R002 — Diamond harus available atau sudah reserved oleh quotation ini
                self::reserveDiamond((int) $item['diamond_id'], $id);
                Database::query(
                    "INSERT INTO reservation_items (reservation_id, diamond_id, price_usd, created_at)
                     VALUES (?,?,?,NOW())",
                    [$id, $item['diamond_id'], $item['price_usd']]
                );
            }

            // Tandai quotation sebagai converted
            Database::query(
                "UPDATE quotations SET status='converted', updated_by=?, updated_at=NOW() WHERE id=?",
                [$_SESSION['user_id'] ?? null, $quotationId]
            );
            Database::query(
                "INSERT INTO quotation_state_histories
                   (quotation_id,from_status,to_status,event_code,actor_id,created_at)
                 VALUES (?,'accepted','converted','QUOTATION_CONVERTED',?,NOW())",
                [$quotationId, $_SESSION['user_id'] ?? null]
            );

            ReservationRepository::updateStatus($id, 'active', 'RESERVATION_CREATED');

            audit_log('RESERVATION', 'CREATE', $no, 'reservations', (string) $id,
                null, ['status' => 'active', 'from_quotation' => $quotationId],
                "Reservasi dibuat dari Quotation #{$quotation['quotation_no']}");

            return $id;
        });
    }

    // ------------------------------------------------------------------ //
    //  UPDATE (hanya perpanjang tanggal / ubah notes)
    // ------------------------------------------------------------------ //
    public static function update(int $id, array $data): void
    {
        $reservation = ReservationRepository::findById($id);
        if (!$reservation) throw new RuntimeException('Reservasi tidak ditemukan.');
        if ($reservation['status'] !== 'active')
            throw new RuntimeException('Hanya reservasi aktif yang dapat diubah.');

        $errors = self::validate(array_merge($reservation, $data));
        if (!empty($errors)) throw new RuntimeException(json_encode($errors));

        ReservationRepository::update($id, array_merge($data, ['updated_by' => $_SESSION['user_id'] ?? null]));

        audit_log('RESERVATION', 'UPDATE', $reservation['reservation_no'],
            'reservations', (string) $id,
            ['expiry_date' => $reservation['expiry_date']],
            ['expiry_date' => $data['expiry_date']],
            'Data reservasi diperbarui');
    }

    // ------------------------------------------------------------------ //
    //  EXTEND
    // ------------------------------------------------------------------ //
    public static function extend(int $id, string $newExpiryDate, string $reason = ''): void
    {
        $reservation = ReservationRepository::findById($id);
        if (!$reservation) throw new RuntimeException('Reservasi tidak ditemukan.');
        if ($reservation['status'] !== 'active')
            throw new RuntimeException('Hanya reservasi aktif yang dapat diperpanjang.');

        // Hitung maksimum dari tanggal awal buat (bukan dari expiry lama)
        $createdDate = date('Y-m-d', strtotime($reservation['created_at']));
        $maxExtend   = date('Y-m-d', strtotime($createdDate . ' +' . self::MAX_DAYS . ' days'));
        if ($newExpiryDate > $maxExtend)
            throw new RuntimeException(
                "Reservasi tidak dapat diperpanjang melewati {$maxExtend} (R001 — maks 7 hari dari tanggal buat)."
            );
        if ($newExpiryDate <= $reservation['expiry_date'])
            throw new RuntimeException('Tanggal baru harus lebih dari tanggal kedaluwarsa saat ini.');

        ReservationRepository::update($id, [
            'expiry_date' => $newExpiryDate,
            'notes'       => $reservation['notes'],
            'updated_by'  => $_SESSION['user_id'] ?? null,
        ]);

        audit_log('RESERVATION', 'UPDATE', $reservation['reservation_no'],
            'reservations', (string) $id,
            ['expiry_date' => $reservation['expiry_date']],
            ['expiry_date' => $newExpiryDate],
            "Reservasi diperpanjang: {$reason}");
    }

    // ------------------------------------------------------------------ //
    //  RELEASE
    // ------------------------------------------------------------------ //
    public static function release(int $id, string $reason = ''): void
    {
        $reservation = ReservationRepository::findById($id);
        if (!$reservation) throw new RuntimeException('Reservasi tidak ditemukan.');
        if ($reservation['status'] !== 'active')
            throw new RuntimeException('Hanya reservasi aktif yang dapat dilepas.');

        Database::transaction(function () use ($id, $reason, $reservation) {
            // Release semua diamond
            $items = ReservationRepository::getItems($id);
            foreach ($items as $item) {
                self::releaseDiamond((int) $item['diamond_id'], $id);
            }

            ReservationRepository::updateStatus($id, 'released', 'RESERVATION_RELEASED',
                null, $reason);

            audit_log('RESERVATION', 'UPDATE', $reservation['reservation_no'],
                'reservations', (string) $id,
                ['status' => 'active'], ['status' => 'released'],
                "Reservasi dilepas: {$reason}");
        });
    }

    // ------------------------------------------------------------------ //
    //  EXPIRE (system trigger)
    // ------------------------------------------------------------------ //
    public static function expireOverdue(): int
    {
        $rows = Database::fetchAll(
            "SELECT id, reservation_no FROM reservations
              WHERE status = 'active'
                AND expiry_date < CURDATE()
                AND deleted_at IS NULL"
        );
        $count = 0;
        foreach ($rows as $r) {
            Database::transaction(function () use ($r) {
                $items = ReservationRepository::getItems((int) $r['id']);
                foreach ($items as $item) {
                    self::releaseDiamond((int) $item['diamond_id'], (int) $r['id']);
                }
                ReservationRepository::updateStatus((int) $r['id'], 'expired',
                    'RESERVATION_EXPIRED', null, 'System auto-expire');

                audit_log('RESERVATION', 'UPDATE', $r['reservation_no'],
                    'reservations', (string) $r['id'],
                    ['status' => 'active'], ['status' => 'expired'],
                    'Auto-expire oleh sistem');
            });
            $count++;
        }
        return $count;
    }

    // ------------------------------------------------------------------ //
    //  CONVERT TO SALES ORDER
    // ------------------------------------------------------------------ //
    public static function convertToSalesOrder(int $id): int
    {
        $reservation = ReservationRepository::findById($id);
        if (!$reservation) throw new RuntimeException('Reservasi tidak ditemukan.');
        if ($reservation['status'] !== 'active')
            throw new RuntimeException('Hanya reservasi aktif yang dapat dikonversi ke Sales Order.');

        $items = ReservationRepository::getItems($id);
        if (empty($items)) throw new RuntimeException('Reservasi tidak memiliki item berlian.');

        return Database::transaction(function () use ($id, $reservation, $items) {
            // Buat Sales Order (header)
            $soNo = self::generateSoNo();
            Database::query(
                "INSERT INTO sales_orders
                   (so_no, reservation_id, customer_id, salesperson_id,
                    status, total_usd, total_idr, rate_usd_idr,
                    notes, created_by, created_at, updated_at)
                 VALUES (?,?,?,?,'draft',?,?,?,?,?,NOW(),NOW())",
                [
                    $soNo,
                    $id,
                    $reservation['customer_id'],
                    $reservation['salesperson_id'],
                    $reservation['total_usd'],
                    $reservation['total_idr'],
                    $reservation['rate_usd_idr'],
                    $reservation['notes'],
                    $_SESSION['user_id'] ?? null,
                ]
            );
            $soId = (int) Database::lastInsertId();

            // Salin items
            foreach ($items as $item) {
                Database::query(
                    "INSERT INTO sales_order_items
                       (sales_order_id, diamond_id, price_usd, created_at)
                     VALUES (?,?,?,NOW())",
                    [$soId, $item['diamond_id'], $item['price_usd']]
                );
                // Diamond tetap reserved sampai SO approved
            }

            // Update status reservation
            ReservationRepository::updateStatus($id, 'converted', 'RESERVATION_CONVERTED');

            // History SO
            Database::query(
                "INSERT INTO sales_order_state_histories
                   (sales_order_id,from_status,to_status,event_code,actor_id,created_at)
                 VALUES (?,'','draft','SALES_ORDER_CREATED',?,NOW())",
                [$soId, $_SESSION['user_id'] ?? null]
            );

            audit_log('RESERVATION', 'UPDATE', $reservation['reservation_no'],
                'reservations', (string) $id,
                ['status' => 'active'], ['status' => 'converted'],
                "Dikonversi ke Sales Order {$soNo}");

            return $soId;
        });
    }

    // ------------------------------------------------------------------ //
    //  HELPERS
    // ------------------------------------------------------------------ //
    private static function reserveDiamond(int $diamondId, int $reservationId): void
    {
        $diamond = Database::fetchOne(
            "SELECT id, status FROM diamonds WHERE id = ? AND deleted_at IS NULL",
            [$diamondId]
        );
        if (!$diamond) throw new RuntimeException("Berlian ID {$diamondId} tidak ditemukan.");

        // R002 — harus available (atau sudah reserved oleh quotation)
        if (!in_array($diamond['status'], ['available', 'reserved'], true))
            throw new RuntimeException(
                "Berlian tidak dapat di-reservasi karena berstatus '{$diamond['status']}' (R002)."
            );

        if ($diamond['status'] !== 'reserved') {
            Database::query(
                "UPDATE diamonds SET status='reserved', updated_by=? WHERE id=?",
                [$_SESSION['user_id'] ?? null, $diamondId]
            );
            Database::query(
                "INSERT INTO diamond_state_histories
                   (diamond_id,from_status,to_status,event_name,ref_type,ref_id,actor_id,created_at)
                 VALUES (?,'available','reserved','DIAMOND_RESERVED','reservation',?,?,NOW())",
                [$diamondId, $reservationId, $_SESSION['user_id'] ?? null]
            );
        }
    }

    private static function releaseDiamond(int $diamondId, int $reservationId): void
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
                   (diamond_id,from_status,to_status,event_name,ref_type,ref_id,actor_id,created_at)
                 VALUES (?,'reserved','available','DIAMOND_RESERVATION_RELEASED','reservation',?,?,NOW())",
                [$diamondId, $reservationId, $_SESSION['user_id'] ?? null]
            );
        }
    }

    private static function generateSoNo(): string
    {
        $prefix = 'SO-' . date('Ym') . '-';
        $last   = Database::fetchOne(
            "SELECT so_no FROM sales_orders WHERE so_no LIKE ? ORDER BY id DESC LIMIT 1",
            [$prefix . '%']
        );
        $seq = $last ? ((int) substr($last['so_no'], -4) + 1) : 1;
        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
