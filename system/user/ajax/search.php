<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../shared/helper/functions.php';
require_once __DIR__ . '/../../../shared/middleware/auth.php';
require_once __DIR__ . '/../repository.php';
// session_name(SESSION_NAME); session_start();
require_auth();
$q    = get_param('q');
$rows = UserRepository::getList($q, 0, 1, 'u.full_name', 'ASC', 20, 0);
json_response(true, 'OK', array_map(fn($u) => [
    'id'   => $u['id'],
    'text' => $u['full_name'] . ' (@'.$u['username'].')',
    'role' => $u['role_name'],
], $rows));
