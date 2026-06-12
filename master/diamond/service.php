<?php
/**
 * master/diamond/service.php
 * Service Diamond — validasi, business rule, lifecycle
 */
declare(strict_types=1);

class DiamondService
{
    // Status lifecycle yang diizinkan
    const TRANSITIONS = [
        'registered' => ['available'],
        'available'  => ['reserved','in_repair','retired'],
        'reserved'   => ['available','sold'],
        'sold'       => ['returned'],
        'returned'   => ['available','retired'],
        'in_repair'  => ['available'],
        'retired'    => [],
    ];

    const STATUS_LABELS = [
        'registered' => 'Terdaftar',
        'available'  => 'Tersedia',
        'reserved'   => 'Direservasi',
        'sold'       => 'Terjual',
        'returned'   => 'Diretur',
        'in_repair'  => 'Dalam Reparasi',
        'retired'    => 'Nonaktif',
    ];

    const COLOR_GRADES   = ['D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','Fancy'];
    const CLARITY_GRADES = ['FL','IF','VVS1','VVS2','VS1','VS2','SI1','SI2','I1','I2','I3'];
    const CUT_GRADES     = ['Excellent','Very Good','Good','Fair','Poor'];
    const FLUOR_OPTIONS  = ['None','Faint','Medium','Strong','Very Strong'];
    const ACQ_TYPES      = ['consignment','purchase_returnable','purchase_final'];

    public static function validate(array $data): array
    {
        $errors = [];

        if (empty($data['supplier_id'])||!(int)$data['supplier_id'])
            $errors['supplier_id'] = 'Supplier wajib dipilih.';
        if (empty($data['warehouse_id'])||!(int)$data['warehouse_id'])
            $errors['warehouse_id'] = 'Lokasi penyimpanan wajib dipilih.';
        if (!in_array($data['acquisition_type']??'', self::ACQ_TYPES, true))
            $errors['acquisition_type'] = 'Jenis perolehan wajib dipilih.';
        if (empty($data['acquired_at']))
            $errors['acquired_at'] = 'Tanggal masuk wajib diisi.';

        $carat = (float)($data['carat_weight']??0);
        if ($carat <= 0)
            $errors['carat_weight'] = 'Berat karat wajib diisi dan harus lebih dari 0.';
        if ($carat > 99.999)
            $errors['carat_weight'] = 'Berat karat maksimal 99.999 ct.';

        if (empty($data['color_grade']))
            $errors['color_grade'] = 'Grade warna wajib diisi.';
        if (empty($data['clarity_grade']))
            $errors['clarity_grade'] = 'Grade kejernihan wajib diisi.';

        $cost = (float)($data['cost_price_usd']??0);
        $sell = (float)($data['selling_price_usd']??0);
        if ($cost < 0)
            $errors['cost_price_usd'] = 'Harga pokok tidak boleh negatif.';
        if ($sell <= 0)
            $errors['selling_price_usd'] = 'Harga jual wajib diisi.';
        if ($sell > 0 && $cost > 0 && $sell < $cost)
            $errors['selling_price_usd'] = 'Harga jual tidak boleh lebih kecil dari harga pokok.';

        // Validasi sertifikat jika diisi
        if (!empty($data['cert_number']) && empty($data['cert_type']))
            $errors['cert_type'] = 'Tipe sertifikat wajib dipilih jika nomor sertifikat diisi.';

        return $errors;
    }

    public static function register(array $data, ?array $certData = null): int
    {
        $errors = self::validate($data);
        if (!empty($errors)) throw new RuntimeException(json_encode($errors));

        return Database::transaction(function () use ($data, $certData) {
            // Generate kode internal
            $data['internal_code'] = DiamondRepository::generateInternalCode();

            $id = DiamondRepository::create($data);

            // Catat state awal
            Database::query(
                "INSERT INTO diamond_state_histories
                   (diamond_id,from_status,to_status,event_name,actor_id,notes)
                 VALUES (?,NULL,'registered','DIAMOND_REGISTERED',?,?)",
                [$id, $_SESSION['user_id']??null, 'Berlian baru didaftarkan']
            );

            // Simpan sertifikat jika ada
            if (!empty($certData['cert_number']) && !empty($certData['cert_type'])) {
                DiamondRepository::saveCertificate($id, $certData);
            }

            audit_log('DIAMOND','CREATE', $data['internal_code'], 'diamonds', (string)$id,
                null,
                ['carat'=>$data['carat_weight'],'color'=>$data['color_grade'],'clarity'=>$data['clarity_grade']],
                "Berlian baru didaftarkan: {$data['internal_code']}"
            );

            return $id;
        });
    }

    public static function update(int $id, array $data, ?array $certData = null): void
    {
        $existing = DiamondRepository::findById($id);
        if (!$existing) throw new RuntimeException('Data berlian tidak ditemukan.');

        // Status sold/retired tidak bisa diedit spesifikasinya
        if (in_array($existing['status'], ['sold','retired'], true))
            throw new RuntimeException('Berlian dengan status '.self::STATUS_LABELS[$existing['status']].' tidak dapat diedit.');

        $errors = self::validate($data);
        if (!empty($errors)) throw new RuntimeException(json_encode($errors));

        Database::transaction(function () use ($id, $data, $certData, $existing) {
            DiamondRepository::update($id, $data);

            if (!empty($certData['cert_number']) && !empty($certData['cert_type'])) {
                DiamondRepository::saveCertificate($id, $certData);
            }

            audit_log('DIAMOND','UPDATE', $existing['internal_code'], 'diamonds', (string)$id,
                ['carat'=>$existing['carat_weight'],'status'=>$existing['status']],
                ['carat'=>$data['carat_weight'],'selling_usd'=>$data['selling_price_usd']],
                "Berlian diperbarui: {$existing['internal_code']}"
            );
        });
    }

    public static function activate(int $id, string $notes = ''): void
    {
        $diamond = DiamondRepository::findById($id);
        if (!$diamond) throw new RuntimeException('Data berlian tidak ditemukan.');
        if (!self::canTransition($diamond['status'], 'available'))
            throw new RuntimeException("Tidak dapat mengubah status dari '{$diamond['status']}' ke 'available'.");

        DiamondRepository::updateStatus($id, 'available', 'DIAMOND_RECEIVED', null, null, $notes ?: 'Barang diterima dan siap jual');
        audit_log('DIAMOND','APPROVE', $diamond['internal_code'], 'diamonds', (string)$id,
            ['status'=>$diamond['status']], ['status'=>'available'],
            "Berlian diaktifkan: {$diamond['internal_code']}"
        );
    }

    public static function retire(int $id, string $reason = ''): void
    {
        $diamond = DiamondRepository::findById($id);
        if (!$diamond) throw new RuntimeException('Data berlian tidak ditemukan.');
        if (!self::canTransition($diamond['status'], 'retired'))
            throw new RuntimeException("Status saat ini tidak dapat dinonaktifkan.");

        DiamondRepository::updateStatus($id, 'retired', 'DIAMOND_RETIRED', null, null, $reason);
        audit_log('DIAMOND','DELETE', $diamond['internal_code'], 'diamonds', (string)$id,
            ['status'=>$diamond['status']], ['status'=>'retired'],
            "Berlian dinonaktifkan: {$diamond['internal_code']} — {$reason}"
        );
    }

    public static function delete(int $id): void
    {
        $diamond = DiamondRepository::findById($id);
        if (!$diamond) throw new RuntimeException('Data berlian tidak ditemukan.');
        if (!in_array($diamond['status'], ['registered','retired'], true))
            throw new RuntimeException('Hanya berlian berstatus Terdaftar atau Nonaktif yang dapat dihapus.');

        DiamondRepository::softDelete($id);
        audit_log('DIAMOND','DELETE', $diamond['internal_code'], 'diamonds', (string)$id,
            ['internal_code'=>$diamond['internal_code'],'status'=>$diamond['status']],
            null, "Berlian dihapus: {$diamond['internal_code']}"
        );
    }

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    /** Hitung harga jual IDR dari USD */
    public static function calcIDR(float $usd, float $rate): float
    {
        return round($usd * $rate);
    }

    /** Hitung margin persen */
    public static function calcMargin(float $cost, float $sell): float
    {
        if ($cost <= 0) return 0;
        return round((($sell - $cost) / $cost) * 100, 2);
    }
}
