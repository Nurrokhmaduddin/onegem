<?php
/**
 * sales/lead/service.php
 * Service Lead — business rule dan orkestrasi
 */
declare(strict_types=1);

class LeadService
{
    const STATUS_TRANSITIONS = [
        'new'       => ['contacted', 'qualified', 'lost'],
        'contacted' => ['qualified', 'lost'],
        'qualified' => ['quoted', 'lost'],
        'quoted'    => ['converted', 'lost'],
        'converted' => [],
        'lost'      => ['new'], // bisa di-reopen
    ];

    const STATUS_LABELS = [
        'new'       => 'Baru',
        'contacted' => 'Dihubungi',
        'qualified' => 'Qualified',
        'quoted'    => 'Penawaran Dikirim',
        'converted' => 'Konversi',
        'lost'      => 'Tidak Jadi',
    ];

    const SOURCE_LABELS = [
        'walk_in'      => 'Walk-in',
        'referral'     => 'Referral',
        'social_media' => 'Media Sosial',
        'phone'        => 'Telepon',
        'whatsapp'     => 'WhatsApp',
        'other'        => 'Lainnya',
    ];

    public static function validate(array $data): array
    {
        $errors = [];
        if (empty(trim($data['name'] ?? '')))
            $errors['name'] = 'Nama lead wajib diisi.';
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL))
            $errors['email'] = 'Format email tidak valid.';
        if (!empty($data['phone']) && !preg_match('/^[0-9+\-\s]{7,20}$/', $data['phone']))
            $errors['phone'] = 'Format telepon tidak valid.';
        if (!in_array($data['source'] ?? '', array_keys(self::SOURCE_LABELS), true))
            $errors['source'] = 'Sumber lead wajib dipilih.';
        return $errors;
    }

    public static function create(array $data): int
    {
        $errors = self::validate($data);
        if (!empty($errors)) throw new RuntimeException(json_encode($errors));

        return Database::transaction(function () use ($data) {
            $data['lead_code'] = LeadRepository::getNextCode();
            $id = LeadRepository::create($data);

            // Catat aktivitas awal
            LeadRepository::addActivity($id, 'note',
                'Lead baru dibuat' . (!empty($data['interest']) ? ': ' . $data['interest'] : '')
            );

            audit_log('LEAD', 'CREATE', $data['lead_code'], 'leads', (string)$id,
                null, ['name' => $data['name'], 'source' => $data['source']],
                "Lead baru: {$data['name']}"
            );
            return $id;
        });
    }

    public static function update(int $id, array $data): void
    {
        $existing = LeadRepository::findById($id);
        if (!$existing) throw new RuntimeException('Lead tidak ditemukan.');
        if (in_array($existing['status'], ['converted'], true))
            throw new RuntimeException('Lead yang sudah dikonversi tidak dapat diedit.');

        $errors = self::validate($data);
        if (!empty($errors)) throw new RuntimeException(json_encode($errors));

        Database::transaction(function () use ($id, $data, $existing) {
            LeadRepository::update($id, $data);
            audit_log('LEAD', 'UPDATE', $existing['lead_code'], 'leads', (string)$id,
                ['name' => $existing['name']], ['name' => $data['name']],
                "Lead diperbarui: {$existing['lead_code']}"
            );
        });
    }

    public static function changeStatus(int $id, string $newStatus, string $notes = ''): void
    {
        $lead = LeadRepository::findById($id);
        if (!$lead) throw new RuntimeException('Lead tidak ditemukan.');

        $allowed = self::STATUS_TRANSITIONS[$lead['status']] ?? [];
        if (!in_array($newStatus, $allowed, true))
            throw new RuntimeException(
                "Tidak dapat mengubah status dari '{$lead['status']}' ke '{$newStatus}'."
            );

        Database::transaction(function () use ($id, $newStatus, $notes, $lead) {
            LeadRepository::updateStatus($id, $newStatus, $notes ?: null);
            LeadRepository::addActivity($id, 'note',
                "Status diubah: " . self::STATUS_LABELS[$lead['status']]
                . " → " . self::STATUS_LABELS[$newStatus]
                . ($notes ? " — {$notes}" : '')
            );
            audit_log('LEAD', 'UPDATE', $lead['lead_code'], 'leads', (string)$id,
                ['status' => $lead['status']], ['status' => $newStatus],
                "Status lead diubah ke {$newStatus}: {$lead['lead_code']}"
            );
        });
    }

    public static function convertToCustomer(int $id, array $customerData): int
    {
        $lead = LeadRepository::findById($id);
        if (!$lead) throw new RuntimeException('Lead tidak ditemukan.');
        if ($lead['status'] === 'converted')
            throw new RuntimeException('Lead ini sudah dikonversi sebelumnya.');
        if (!in_array($lead['status'], ['qualified', 'quoted'], true))
            throw new RuntimeException('Hanya lead berstatus Qualified atau Penawaran Dikirim yang dapat dikonversi.');

        // Require customer module
        require_once BASE_PATH . '/master/customer/repository.php';
        require_once BASE_PATH . '/master/customer/service.php';

        return Database::transaction(function () use ($id, $lead, $customerData) {
            // Buat customer baru dari data lead
            $customerData['name']  = $customerData['name']  ?: $lead['name'];
            $customerData['phone'] = $customerData['phone'] ?: $lead['phone'];
            $customerData['email'] = $customerData['email'] ?: $lead['email'];
            $customerData['tier']  = $customerData['tier']  ?: 'regular';

            $customerId = CustomerService::create($customerData);

            LeadRepository::convertToCustomer($id, $customerId);
            LeadRepository::addActivity($id, 'note',
                "Lead dikonversi menjadi customer: kode " .
                (CustomerRepository::findById($customerId)['customer_code'] ?? '')
            );

            audit_log('LEAD', 'APPROVE', $lead['lead_code'], 'leads', (string)$id,
                ['status' => $lead['status']], ['status' => 'converted', 'customer_id' => $customerId],
                "Lead dikonversi ke customer: {$lead['lead_code']}"
            );

            return $customerId;
        });
    }

    public static function addActivity(int $id, string $type, string $description): void
    {
        $lead = LeadRepository::findById($id);
        if (!$lead) throw new RuntimeException('Lead tidak ditemukan.');
        if (empty(trim($description))) throw new RuntimeException('Deskripsi aktivitas wajib diisi.');

        $validTypes = ['call', 'meeting', 'whatsapp', 'email', 'note'];
        if (!in_array($type, $validTypes, true))
            throw new RuntimeException('Tipe aktivitas tidak valid.');

        LeadRepository::addActivity($id, $type, $description);

        // Auto update status ke contacted jika masih new
        if ($lead['status'] === 'new' && in_array($type, ['call','meeting','whatsapp','email'], true)) {
            LeadRepository::updateStatus($id, 'contacted');
        }
    }

    public static function delete(int $id): void
    {
        $lead = LeadRepository::findById($id);
        if (!$lead) throw new RuntimeException('Lead tidak ditemukan.');
        if ($lead['status'] === 'converted')
            throw new RuntimeException('Lead yang sudah dikonversi tidak dapat dihapus.');

        LeadRepository::softDelete($id);
        audit_log('LEAD', 'DELETE', $lead['lead_code'], 'leads', (string)$id,
            ['name' => $lead['name']], null, "Lead dihapus: {$lead['lead_code']}"
        );
    }
}
