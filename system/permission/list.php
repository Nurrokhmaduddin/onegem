<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
session_name(SESSION_NAME); session_start();
redirect(url('system/permission/assign'));
