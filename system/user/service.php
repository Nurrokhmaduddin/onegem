<?php
/**
 * system/user/service.php
 * Service User — business rule, validasi, dan orkestrasi
 * ERP Toko Berlian — Only One
 */

declare(strict_types=1);

class UserService
{
    /**
     * Validasi data form user (create & update).
     *
     * @return array  ['field' => 'pesan error', ...] — kosong jika valid
     */
    public static function validate(array $data, int $userId = 0): array
    {
        $errors = [];

        // Full name
        if (empty(trim($data['full_name'] ?? ''))) {
            $errors['full_name'] = 'Nama lengkap wajib diisi.';
        } elseif (mb_strlen($data['full_name']) > 150) {
            $errors['full_name'] = 'Nama lengkap maksimal 150 karakter.';
        }

        // Username (hanya saat create atau jika diubah)
        if ($userId === 0 || !empty($data['username'])) {
            $username = trim($data['username'] ?? '');
            if (empty($username)) {
                $errors['username'] = 'Username wajib diisi.';
            } elseif (!preg_match('/^[a-zA-Z0-9._-]{4,60}$/', $username)) {
                $errors['username'] = 'Username hanya boleh huruf, angka, titik, underscore, atau strip (4-60 karakter).';
            } elseif (UserRepository::isUsernameTaken($username, $userId)) {
                $errors['username'] = 'Username sudah digunakan.';
            }
        }

        // Email
        $email = trim($data['email'] ?? '');
        if (empty($email)) {
            $errors['email'] = 'Email wajib diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Format email tidak valid.';
        } elseif (UserRepository::isEmailTaken($email, $userId)) {
            $errors['email'] = 'Email sudah digunakan.';
        }

        // Role
        if (empty($data['role_id']) || !is_numeric($data['role_id'])) {
            $errors['role_id'] = 'Role wajib dipilih.';
        }

        // Password (wajib saat create, opsional saat update)
        if ($userId === 0) {
            $pwErrors = self::validatePassword($data['password'] ?? '', $data['password_confirm'] ?? '');
            $errors   = array_merge($errors, $pwErrors);
        }

        // Employee code (opsional, max 20)
        if (!empty($data['employee_code']) && mb_strlen($data['employee_code']) > 20) {
            $errors['employee_code'] = 'Kode karyawan maksimal 20 karakter.';
        }

        // Phone (opsional)
        if (!empty($data['phone']) && !preg_match('/^[0-9+\-\s]{7,20}$/', $data['phone'])) {
            $errors['phone'] = 'Format nomor telepon tidak valid.';
        }

        return $errors;
    }

    /**
     * Validasi kekuatan password.
     */
    public static function validatePassword(string $password, string $confirm): array
    {
        $errors = [];
        if (empty($password)) {
            $errors['password'] = 'Password wajib diisi.';
        } elseif (mb_strlen($password) < 8) {
            $errors['password'] = 'Password minimal 8 karakter.';
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $errors['password'] = 'Password harus mengandung minimal 1 huruf kapital.';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $errors['password'] = 'Password harus mengandung minimal 1 angka.';
        }

        if (!empty($password) && $password !== $confirm) {
            $errors['password_confirm'] = 'Konfirmasi password tidak cocok.';
        }

        return $errors;
    }

    /**
     * Buat user baru — orkestrasi create dalam transaction.
     *
     * @throws RuntimeException jika validasi gagal
     * @return int  ID user baru
     */
    public static function create(array $data): int
    {
        $errors = self::validate($data);
        if (!empty($errors)) {
            throw new RuntimeException(json_encode($errors));
        }

        return Database::transaction(function () use ($data) {
            $userId = UserRepository::create([
                'role_id'         => (int) $data['role_id'],
                'employee_code'   => trim($data['employee_code'] ?? ''),
                'username'        => strtolower(trim($data['username'])),
                'full_name'       => trim($data['full_name']),
                'email'           => strtolower(trim($data['email'])),
                'password_hash'   => password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]),
                'phone'           => trim($data['phone'] ?? ''),
                'is_active'       => 1,
                'must_change_pw'  => !empty($data['must_change_pw']) ? 1 : 0,
                'created_by'      => $_SESSION['user_id'] ?? null,
            ]);

            audit_log(
                'USER', 'CREATE',
                null, 'users', (string) $userId,
                null,
                ['username' => $data['username'], 'full_name' => $data['full_name'], 'role_id' => $data['role_id']],
                "User baru dibuat: {$data['username']}"
            );

            return $userId;
        });
    }

    /**
     * Update data user.
     *
     * @throws RuntimeException jika validasi gagal atau user tidak ditemukan
     */
    public static function update(int $id, array $data): void
    {
        $existing = UserRepository::findById($id);
        if (!$existing) {
            throw new RuntimeException('Pengguna tidak ditemukan.');
        }

        // Tidak boleh nonaktifkan atau mengubah role diri sendiri
        if ($id === (int) ($_SESSION['user_id'] ?? 0)) {
            $data['is_active'] = $existing['is_active'];
            $data['role_id']   = $existing['role_id'];
        }

        $errors = self::validate($data, $id);
        if (!empty($errors)) {
            throw new RuntimeException(json_encode($errors));
        }

        $before = [
            'full_name' => $existing['full_name'],
            'email'     => $existing['email'],
            'role_id'   => $existing['role_id'],
            'is_active' => $existing['is_active'],
        ];

        Database::transaction(function () use ($id, $data, $before) {
            UserRepository::update($id, [
                'role_id'       => (int) $data['role_id'],
                'employee_code' => trim($data['employee_code'] ?? ''),
                'full_name'     => trim($data['full_name']),
                'email'         => strtolower(trim($data['email'])),
                'phone'         => trim($data['phone'] ?? ''),
                'is_active'     => isset($data['is_active']) ? 1 : 0,
                'updated_by'    => $_SESSION['user_id'] ?? null,
            ]);

            // Hapus cache permission jika role berubah
            if ((int) $data['role_id'] !== (int) $before['role_id']) {
                // Akan di-refresh saat user login berikutnya
            }

            audit_log(
                'USER', 'UPDATE',
                null, 'users', (string) $id,
                $before,
                ['full_name' => $data['full_name'], 'email' => $data['email'], 'role_id' => $data['role_id']],
                "Data user diperbarui: ID {$id}"
            );
        });
    }

    /**
     * Reset password oleh admin.
     */
    public static function resetPassword(int $id, string $newPassword, string $confirm): void
    {
        $errors = self::validatePassword($newPassword, $confirm);
        if (!empty($errors)) {
            throw new RuntimeException(json_encode($errors));
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        UserRepository::updatePassword($id, $hash, false); // must_change_pw = 1

        audit_log('USER', 'APPROVE', null, 'users', (string) $id,
            null, null, "Password di-reset oleh admin untuk user ID {$id}"
        );
    }

    /**
     * Toggle status aktif.
     */
    public static function toggleStatus(int $id): bool
    {
        if ($id === (int) ($_SESSION['user_id'] ?? 0)) {
            throw new RuntimeException('Anda tidak dapat menonaktifkan akun sendiri.');
        }

        $newStatus = UserRepository::toggleStatus($id, (int) ($_SESSION['user_id'] ?? 0));

        audit_log('USER', $newStatus ? 'APPROVE' : 'DELETE',
            null, 'users', (string) $id,
            null, ['is_active' => $newStatus],
            "Status user ID {$id} diubah menjadi " . ($newStatus ? 'Aktif' : 'Nonaktif')
        );

        return $newStatus;
    }

    /**
     * Soft delete user.
     */
    public static function delete(int $id): void
    {
        if ($id === (int) ($_SESSION['user_id'] ?? 0)) {
            throw new RuntimeException('Anda tidak dapat menghapus akun sendiri.');
        }

        $user = UserRepository::findById($id);
        if (!$user) {
            throw new RuntimeException('Pengguna tidak ditemukan.');
        }

        UserRepository::softDelete($id, (int) ($_SESSION['user_id'] ?? 0));

        audit_log('USER', 'DELETE', null, 'users', (string) $id,
            ['username' => $user['username'], 'full_name' => $user['full_name']],
            null, "User dihapus: {$user['username']}"
        );
    }
}
