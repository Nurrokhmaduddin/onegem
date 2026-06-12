<?php
/**
 * system/user/toggle_status.php — AJAX: toggle aktif/nonaktif
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
require_permission('USER_DELETE');
if (!is_post()) json_response(false, 'Method tidak valid.', null, 405);
csrf_validate();

$userId = post_int('user_id');
try {
    $newStatus = UserService::toggleStatus($userId);
    $label     = $newStatus ? 'Aktif' : 'Nonaktif';
    json_response(true, "Status pengguna berhasil diubah menjadi {$label}.", ['is_active' => $newStatus]);
} catch (RuntimeException $e) {
    json_response(false, $e->getMessage(), null, 422);
}
