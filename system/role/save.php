<?php
/**
 * system/role/save.php
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_once __DIR__ . '/../../shared/middleware/audit.php';
require_once __DIR__ . '/repository.php';

require_auth(); require_permission('ROLE_CREATE');
if (!is_post()) redirect(url('system/role'));
csrf_validate();

try {
    $id = RoleService::create($_POST);
    flash_set('success', 'Role berhasil ditambahkan.');
} catch (RuntimeException $e) {
    flash_set('error', $e->getMessage());
}
redirect(url('system/role'));
