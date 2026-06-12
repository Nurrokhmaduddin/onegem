<?php
/**
 * system/user/save.php
 * Process file — simpan user baru
 * ERP Toko Berlian — Only One
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_once __DIR__ . '/../../shared/middleware/audit.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

require_auth();
require_permission('USER_CREATE');

if (!is_post()) {
    redirect(url('system/user'));
}

csrf_validate();

$data = [
    'employee_code'  => post('employee_code'),
    'username'       => post('username'),
    'full_name'      => post('full_name'),
    'email'          => post('email'),
    'phone'          => post('phone'),
    'role_id'        => post_int('role_id'),
    'password'       => post('password'),
    'password_confirm' => post('password_confirm'),
    'must_change_pw' => !empty($_POST['must_change_pw']) ? 1 : 0,
];

try {
    $newId = UserService::create($data);
    flash_set('success', "Pengguna <strong>{$data['full_name']}</strong> berhasil ditambahkan.");
    redirect(url('system/user/detail') . '?id=' . $newId);

} catch (RuntimeException $e) {
    // Decode errors JSON dari service
    $errors = json_decode($e->getMessage(), true);
    if (!is_array($errors)) {
        $errors = ['general' => $e->getMessage()];
    }

    // Simpan errors & data form ke session, redirect kembali ke form
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_data']   = $data;
    redirect(url('system/user/create'));
}
