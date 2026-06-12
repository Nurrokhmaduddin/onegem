<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../shared/helper/functions.php';
require_once __DIR__ . '/../../../shared/middleware/auth.php';
require_once __DIR__ . '/../repository.php';
// session_name(SESSION_NAME); session_start();
require_auth();
$email     = trim(get_param('email'));
$excludeId = (int)get_param('exclude_id', 0);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['available' => false, 'message' => 'Format email tidak valid.']);
    exit;
}
$taken = UserRepository::isEmailTaken($email, $excludeId);
echo json_encode(['available' => !$taken, 'email' => $email]);
