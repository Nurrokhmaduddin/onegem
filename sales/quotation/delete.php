<?php // sales/quotation/delete.php — Hapus quotation (soft delete, hanya draft/cancelled)
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_once __DIR__ . '/../../shared/middleware/audit.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

require_auth();
require_permission('QUOTATION_DELETE');
if (!is_post()) json_response(false, 'Method tidak valid.', null, 405);
csrf_validate();

try {
    QuotationService::delete(post_int('quotation_id'));
    json_response(true, 'Quotation berhasil dihapus.');
} catch (RuntimeException $e) {
    json_response(false, $e->getMessage(), null, 422);
}
