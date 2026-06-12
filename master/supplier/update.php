<?php // master/supplier/update.php
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_once __DIR__ . '/../../shared/middleware/audit.php';
require_once __DIR__ . '/repository.php';
require_auth(); require_permission('SUPPLIER_EDIT');
if (!is_post()) redirect(url('master/supplier'));
csrf_validate();
$supplierId = post_int('supplier_id');
try {
    SupplierService::update($supplierId, $_POST);
    flash_set('success', 'Data supplier berhasil diperbarui.');
    redirect(url('master/supplier/detail') . '?id=' . $supplierId);
} catch (RuntimeException $e) {
    $errors = json_decode($e->getMessage(), true);
    $_SESSION['form_errors'] = is_array($errors) ? array_values($errors) : [$e->getMessage()];
    $_SESSION['form_data']   = $_POST;
    redirect(url('master/supplier/edit') . '?id=' . $supplierId);
}
