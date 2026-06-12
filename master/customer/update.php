<?php
/**
 * master/customer/update.php
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_once __DIR__ . '/../../shared/middleware/audit.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

require_auth(); require_permission('CUSTOMER_EDIT');
if (!is_post()) redirect(url('master/customer'));
csrf_validate();

$customerId = post_int('customer_id');
if ($customerId <= 0) { flash_set('error','ID tidak valid.'); redirect(url('master/customer')); }

try {
    CustomerService::update($customerId, $_POST);
    flash_set('success', 'Data pelanggan berhasil diperbarui.');
    redirect(url('master/customer/detail') . '?id=' . $customerId);
} catch (RuntimeException $e) {
    $errors = json_decode($e->getMessage(), true);
    $_SESSION['form_errors'] = is_array($errors) ? array_values($errors) : [$e->getMessage()];
    $_SESSION['form_data']   = $_POST;
    redirect(url('master/customer/edit') . '?id=' . $customerId);
}
