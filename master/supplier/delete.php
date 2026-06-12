<?php // master/supplier/delete.php
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_once __DIR__ . '/../../shared/middleware/audit.php';
require_once __DIR__ . '/repository.php';
require_auth(); require_permission('SUPPLIER_DELETE');
if (!is_post()) json_response(false,'Method tidak valid.',null,405);
csrf_validate();
try {
    SupplierService::delete(post_int('supplier_id'));
    json_response(true,'Supplier berhasil dihapus.');
} catch (RuntimeException $e) {
    json_response(false,$e->getMessage(),null,422);
}
