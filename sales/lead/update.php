<?php // sales/lead/update.php
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_once __DIR__ . '/../../shared/middleware/audit.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';
require_auth(); require_permission('LEAD_EDIT');
if (!is_post()) redirect(url('sales/lead'));
csrf_validate();
$leadId = post_int('lead_id');
try {
    LeadService::update($leadId, $_POST);
    flash_set('success','Data lead berhasil diperbarui.');
    redirect(url('sales/lead/detail').'?id='.$leadId);
} catch (RuntimeException $e) {
    $errors = json_decode($e->getMessage(), true);
    $_SESSION['form_errors'] = is_array($errors) ? array_values($errors) : [$e->getMessage()];
    $_SESSION['form_data']   = $_POST;
    redirect(url('sales/lead/edit').'?id='.$leadId);
}
