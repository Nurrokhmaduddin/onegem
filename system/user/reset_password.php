<?php
/**
 * system/user/reset_password.php — Reset password oleh admin
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
require_permission('USER_RESET_PW');
if (!is_post()) redirect(url('system/user'));
csrf_validate();

$userId   = post_int('user_id');
$newPw    = post('new_password');
$confirm  = post('new_password_confirm');

try {
    UserService::resetPassword($userId, $newPw, $confirm);
    json_response(true, 'Password berhasil direset. Pengguna wajib ganti password saat login berikutnya.');
} catch (RuntimeException $e) {
    $errors = json_decode($e->getMessage(), true);
    $msg    = is_array($errors) ? implode(' ', $errors) : $e->getMessage();
    json_response(false, $msg, null, 422);
}
