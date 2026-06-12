<?php
/**
 * auth/logout.php
 * VERSI LARAGON
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../shared/helper/functions.php';
require_once __DIR__ . '/../shared/middleware/audit.php';

session_name(SESSION_NAME);
session_start();

if (!empty($_SESSION['session_token'])) {
    Database::query(
        "UPDATE user_sessions SET is_active = 0 WHERE session_token = ?",
        [$_SESSION['session_token']]
    );
    audit_log('AUTH', 'LOGOUT', null, null, (string)($_SESSION['user_id'] ?? ''));
}

session_destroy();
redirect(url('auth/login'));
