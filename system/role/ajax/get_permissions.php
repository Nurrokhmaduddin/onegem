<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../shared/helper/functions.php';
require_once __DIR__ . '/../../../shared/middleware/auth.php';
require_once __DIR__ . '/../repository.php';
// session_name(SESSION_NAME); session_start();
require_auth();
$roleId  = (int)get_param('role_id', 0);
if ($roleId <= 0) json_response(false, 'Role ID wajib diisi.', null, 400);
$grouped = RoleRepository::getPermissions($roleId);
json_response(true, 'OK', $grouped);
