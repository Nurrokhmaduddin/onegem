<?php // sales/quotation/ajax/add_item.php — AJAX: tambah item berlian ke quotation
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

$quotationId = post_int('quotation_id');
$diamondId   = post_int('diamond_id');
$discountPct = (float) post('discount_pct', '0');

try {
    QuotationService::addItem($quotationId, $diamondId, $discountPct);

    // Kembalikan item terbaru + total quotation untuk update UI
    $items = QuotationRepository::getItems($quotationId);
    $q     = QuotationRepository::findById($quotationId);

    json_response(true, 'Berlian berhasil ditambahkan.', [
        'items'        => $items,
        'subtotal_usd' => $q['subtotal_usd'],
        'discount_usd' => $q['discount_usd'],
        'total_usd'    => $q['total_usd'],
        'total_idr'    => $q['total_idr'],
    ]);
} catch (RuntimeException $e) {
    json_response(false, $e->getMessage(), null, 422);
}
