<?php // sales/quotation/ajax/remove_item.php — AJAX: hapus item berlian dari quotation
declare(strict_types=1);
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../shared/helper/functions.php';
require_once __DIR__ . '/../../../shared/middleware/auth.php';
require_once __DIR__ . '/../repository.php';
require_once __DIR__ . '/../service.php';

require_auth();
require_permission('QUOTATION_EDIT');
if (!is_post()) json_response(false, 'Method tidak valid.', null, 405);
csrf_validate();

$itemId      = post_int('item_id');
$quotationId = post_int('quotation_id');

try {
    QuotationService::removeItem($itemId, $quotationId);

    $q = QuotationRepository::findById($quotationId);

    json_response(true, 'Item berhasil dihapus.', [
        'subtotal_usd' => $q['subtotal_usd'],
        'discount_usd' => $q['discount_usd'],
        'total_usd'    => $q['total_usd'],
        'total_idr'    => $q['total_idr'],
    ]);
} catch (RuntimeException $e) {
    json_response(false, $e->getMessage(), null, 422);
}
