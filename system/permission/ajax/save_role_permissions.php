<?php
/**
 * system/permission/ajax/save_role_permissions.php
 * AJAX: simpan permission untuk role tertentu
 */
declare(strict_types=1);
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../shared/helper/functions.php';
require_once __DIR__ . '/../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../shared/middleware/audit.php';
require_once __DIR__ . '/../../role/repository.php';

// session_name(SESSION_NAME); session_start();
require_auth();
require_permission('PERMISSION_ASSIGN');
if (!is_post()) json_response(false, 'Method tidak valid.', null, 405);
csrf_validate();

$roleId  = post_int('role_id');
$idsRaw  = post('permission_ids', '');
$ids     = array_filter(array_map('intval', explode(',', $idsRaw)));

try {
    RoleService::savePermissions($roleId, $ids);
    // Hapus cache permission semua user dengan role ini
    // (akan di-refresh otomatis saat request berikutnya)
    json_response(true, 'Permission berhasil disimpan. ' . count($ids) . ' permission aktif.');
} catch (RuntimeException $e) {
    json_response(false, $e->getMessage(), null, 422);
}
