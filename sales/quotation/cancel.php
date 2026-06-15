<?php // sales/quotation/cancel.php — Batalkan quotation & release diamond lock
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_once __DIR__ . '/../../shared/middleware/audit.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

require_auth();
require_permission('QUOTATION_EDIT');
if (!is_post()) redirect(url('sales/quotation'));
csrf_validate();

$quotationId = post_int('quotation_id');

try {
    QuotationService::cancel($quotationId, trim(post('notes', '')));
    flash_set('success', 'Quotation berhasil dibatalkan. Lock berlian telah dilepas.');
} catch (RuntimeException $e) {
    flash_set('error', $e->getMessage());
}
redirect(url('sales/quotation/detail') . '?id=' . $quotationId);
