<?php // sales/lead/save.php
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_once __DIR__ . '/../../shared/middleware/audit.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';
require_auth(); require_permission('LEAD_CREATE');
if (!is_post()) redirect(url('sales/lead'));
csrf_validate();
try {
    $id = LeadService::create($_POST);
    flash_set('success', 'Lead <strong>'.e(post('name')).'</strong> berhasil ditambahkan.');
    redirect(url('sales/lead/detail').'?id='.$id);
} catch (RuntimeException $e) {
    $errors = json_decode($e->getMessage(), true);
    $_SESSION['form_errors'] = is_array($errors) ? array_values($errors) : [$e->getMessage()];
    $_SESSION['form_data']   = $_POST;
    redirect(url('sales/lead/create'));
}
