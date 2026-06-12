<?php
/**
 * system/user/delete.php — Soft delete pengguna
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
if (!is_post()) redirect(url('system/user'));
csrf_validate();

$userId = post_int('user_id');
try {
    UserService::delete($userId);
    if (is_ajax_request()) {
        json_response(true, 'Pengguna berhasil dihapus.');
    }
    flash_set('success', 'Pengguna berhasil dihapus dari sistem.');
} catch (RuntimeException $e) {
    if (is_ajax_request()) {
        json_response(false, $e->getMessage(), null, 422);
    }
    flash_set('error', $e->getMessage());
}
redirect(url('system/user'));
