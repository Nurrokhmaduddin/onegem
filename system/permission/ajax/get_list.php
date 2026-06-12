<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../shared/helper/functions.php';
require_once __DIR__ . '/../../../shared/middleware/auth.php';
// session_name(SESSION_NAME); session_start();
require_auth(); require_permission('PERMISSION_VIEW');
$module = get_param('module');
$where  = $module ? "WHERE module = ?" : "";
$params = $module ? [$module] : [];
$perms  = Database::fetchAll("SELECT * FROM permissions {$where} ORDER BY module, action", $params);
json_response(true, 'OK', $perms);
