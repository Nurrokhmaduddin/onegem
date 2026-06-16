<?php // sales/so/cancel.php
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_once __DIR__ . '/../../shared/middleware/audit.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';
require_auth(); require_permission('SO_CANCEL');
if (!is_post()) redirect(url('sales/so'));
csrf_validate();
$soId = post_int('so_id');
try {
    SalesOrderService::cancel($soId, trim(post('reason', '')));
    flash_set('success', 'Sales Order dibatalkan. Berlian dibebaskan kembali.');
} catch (RuntimeException $e) {
    flash_set('error', $e->getMessage());
}
redirect(url('sales/so/detail') . '?id=' . $soId);
