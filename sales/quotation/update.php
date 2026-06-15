<?php // sales/quotation/update.php — Update data quotation (hanya status draft)
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
    QuotationService::update($quotationId, $_POST);
    flash_set('success', 'Quotation berhasil diperbarui.');
    redirect(url('sales/quotation/detail') . '?id=' . $quotationId);
} catch (RuntimeException $e) {
    $errors = json_decode($e->getMessage(), true);
    $_SESSION['form_errors'] = is_array($errors) ? array_values($errors) : [$e->getMessage()];
    $_SESSION['form_data']   = $_POST;
    redirect(url('sales/quotation/edit') . '?id=' . $quotationId);
}
