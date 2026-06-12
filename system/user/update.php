<?php
/**
 * system/user/update.php
 * Process file — update data pengguna
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
require_permission('USER_EDIT');

if (!is_post()) redirect(url('system/user'));
csrf_validate();

$userId = post_int('user_id');
if ($userId <= 0) {
    flash_set('error', 'ID pengguna tidak valid.');
    redirect(url('system/user'));
}

$data = [
    'employee_code' => post('employee_code'),
    'full_name'     => post('full_name'),
    'email'         => post('email'),
    'phone'         => post('phone'),
    'role_id'       => post_int('role_id'),
    'is_active'     => !empty($_POST['is_active']) ? 1 : 0,
    'must_change_pw'=> !empty($_POST['must_change_pw']) ? 1 : 0,
];

try {
    UserService::update($userId, $data);
    flash_set('success', 'Data pengguna berhasil diperbarui.');
    redirect(url('system/user/detail') . '?id=' . $userId);

} catch (RuntimeException $e) {
    $errors = json_decode($e->getMessage(), true);
    if (!is_array($errors)) $errors = ['general' => $e->getMessage()];

    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_data']   = $data;
    redirect(url('system/user/edit') . '?id=' . $userId);
}
