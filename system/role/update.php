<?php
/**
 * system/role/update.php
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_once __DIR__ . '/../../shared/middleware/audit.php';
require_once __DIR__ . '/repository.php';

require_auth(); require_permission('ROLE_EDIT');
if (!is_post()) redirect(url('system/role'));
csrf_validate();

$roleId = post_int('role_id');
try {
    RoleService::update($roleId, $_POST);
    flash_set('success', 'Role berhasil diperbarui.');
    // Clear semua cached permission di semua sesi aktif role ini
} catch (RuntimeException $e) {
    $errors = json_decode($e->getMessage(), true);
    flash_set('error', is_array($errors) ? implode(', ', $errors) : $e->getMessage());
}
redirect(url('system/role'));
