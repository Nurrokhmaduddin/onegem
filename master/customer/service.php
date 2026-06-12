<?php
/**
 * master/customer/service.php
 * Service Customer — validasi dan business rule
 */
declare(strict_types=1);

class CustomerService
{
    public static function validate(array $data, int $customerId = 0): array
    {
        $errors = [];

        if (empty(trim($data['name'] ?? ''))) {
            $errors['name'] = 'Nama pelanggan wajib diisi.';
        } elseif (mb_strlen($data['name']) > 150) {
            $errors['name'] = 'Nama maksimal 150 karakter.';
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Format email tidak valid.';
        }
        if (!empty($data['email']) && CustomerRepository::isEmailTaken($data['email'], $customerId)) {
            $errors['email'] = 'Email sudah digunakan pelanggan lain.';
        }

        if (!empty($data['phone']) && !preg_match('/^[0-9+\-\s]{7,20}$/', $data['phone'])) {
            $errors['phone'] = 'Format nomor telepon tidak valid.';
        }

        $validTiers = ['regular','vip','vvip'];
        if (empty($data['tier']) || !in_array($data['tier'], $validTiers, true)) {
            $errors['tier'] = 'Tier pelanggan wajib dipilih.';
        }

        if (!empty($data['birth_date'])) {
            $d = DateTime::createFromFormat('Y-m-d', $data['birth_date']);
            if (!$d || $d->format('Y-m-d') !== $data['birth_date']) {
                $errors['birth_date'] = 'Format tanggal lahir tidak valid.';
            }
        }

        return $errors;
    }

    public static function create(array $data): int
    {
        $errors = self::validate($data);
        if (!empty($errors)) throw new RuntimeException(json_encode($errors));

        return Database::transaction(function () use ($data) {
            $data['customer_code'] = CustomerRepository::getNextCode();
            $id = CustomerRepository::create($data);
            audit_log('CUSTOMER','CREATE', $data['customer_code'], 'customers', (string)$id,
                null, ['name'=>$data['name'],'tier'=>$data['tier']],
                "Pelanggan baru: {$data['name']}"
            );
            return $id;
        });
    }

    public static function update(int $id, array $data): void
    {
        $existing = CustomerRepository::findById($id);
        if (!$existing) throw new RuntimeException('Pelanggan tidak ditemukan.');

        $errors = self::validate($data, $id);
        if (!empty($errors)) throw new RuntimeException(json_encode($errors));

        Database::transaction(function () use ($id, $data, $existing) {
            CustomerRepository::update($id, $data);
            audit_log('CUSTOMER','UPDATE', $existing['customer_code'], 'customers', (string)$id,
                ['name'=>$existing['name'],'tier'=>$existing['tier']],
                ['name'=>$data['name'],'tier'=>$data['tier']],
                "Pelanggan diperbarui: {$existing['customer_code']}"
            );
        });
    }

    public static function delete(int $id): void
    {
        $existing = CustomerRepository::findById($id);
        if (!$existing) throw new RuntimeException('Pelanggan tidak ditemukan.');

        // Cek jika sudah ada transaksi
        // (akan ditambah saat Sprint 3-4 ketika tabel quotations/sales_orders ada)

        Database::transaction(function () use ($id, $existing) {
            CustomerRepository::softDelete($id);
            audit_log('CUSTOMER','DELETE', $existing['customer_code'], 'customers', (string)$id,
                ['name'=>$existing['name']], null,
                "Pelanggan dihapus: {$existing['customer_code']}"
            );
        });
    }
}
