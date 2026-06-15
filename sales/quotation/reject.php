<?php // sales/quotation/reject.php — Tolak quotation (manager only)
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_once __DIR__ . '/../../shared/middleware/audit.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

require_auth();
require_permission('QUOTATION_APPROVE');
if (!is_post()) redirect(url('sales/quotation'));
csrf_validate();

$quotationId = post_int('quotation_id');
$reason      = trim(post('notes', ''));

if ($reason === '') {
    flash_set('error', 'Alasan penolakan wajib diisi.');
    redirect(url('sales/quotation/detail') . '?id=' . $quotationId);
}

try {
    QuotationService::reject($quotationId, $reason);
    flash_set('success', 'Quotation ditolak.');
} catch (RuntimeException $e) {
    flash_set('error', $e->getMessage());
}
redirect(url('sales/quotation/detail') . '?id=' . $quotationId);
