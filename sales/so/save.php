<?php // sales/so/save.php
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_once __DIR__ . '/../../shared/middleware/audit.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';
require_auth(); require_permission('SO_CREATE');
if (!is_post()) redirect(url('sales/so'));
csrf_validate();
try {
    $id = SalesOrderService::create($_POST);
    flash_set('success', 'Sales Order berhasil dibuat.');
    redirect(url('sales/so/detail') . '?id=' . $id);
} catch (RuntimeException $e) {
    $errors = json_decode($e->getMessage(), true);
    $_SESSION['form_errors'] = is_array($errors) ? array_values($errors) : [$e->getMessage()];
    $_SESSION['form_data']   = $_POST;
    redirect(url('sales/so/create'));
}
