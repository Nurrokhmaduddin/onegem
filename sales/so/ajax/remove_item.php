<?php // sales/so/ajax/remove_item.php — AJAX hapus item dari SO
declare(strict_types=1);
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../shared/helper/functions.php';
require_once __DIR__ . '/../../../shared/middleware/auth.php';
require_once __DIR__ . '/../repository.php';
require_once __DIR__ . '/../service.php';
require_auth();
require_permission('SO_EDIT');
if (!is_post()) json_response(false, 'Method tidak valid.', null, 405);
csrf_validate();

try {
    SalesOrderService::removeItem(post_int('item_id'), post_int('so_id'));
    $so = SalesOrderRepository::findById(post_int('so_id'));
    json_response(true, 'Item berhasil dihapus.', [
        'total_usd' => $so['total_usd'],
        'total_idr' => $so['total_idr'],
    ]);
} catch (RuntimeException $e) {
    json_response(false, $e->getMessage(), null, 422);
}
