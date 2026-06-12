<?php
/**
 * system/role/delete.php
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_once __DIR__ . '/../../shared/middleware/audit.php';
require_once __DIR__ . '/repository.php';

require_auth(); require_permission('ROLE_DELETE');
if (!is_post()) json_response(false, 'Method tidak valid.', null, 405);
csrf_validate();

$roleId = post_int('role_id');
try {
    RoleService::delete($roleId);
    json_response(true, 'Role berhasil dihapus.');
} catch (RuntimeException $e) {
    json_response(false, $e->getMessage(), null, 422);
}
