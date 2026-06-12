<?php
/**
 * system/user/ajax/check_username.php
 * AJAX: cek ketersediaan username
 */
declare(strict_types=1);
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../shared/helper/functions.php';
require_once __DIR__ . '/../../../shared/middleware/auth.php';
require_once __DIR__ . '/../repository.php';

// session_name(SESSION_NAME); session_start();
require_auth();

$username  = trim(get_param('username'));
$excludeId = (int) get_param('exclude_id', 0);

if (strlen($username) < 4) {
    json_response(false, 'Username minimal 4 karakter.', ['available' => false]);
}
if (!preg_match('/^[a-zA-Z0-9._-]{4,60}$/', $username)) {
    json_response(false, 'Format username tidak valid.', ['available' => false]);
}

$taken = UserRepository::isUsernameTaken($username, $excludeId);
echo json_encode(['available' => !$taken, 'username' => $username]);
