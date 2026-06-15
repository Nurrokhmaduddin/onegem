<?php // sales/lead/convert.php — Konversi lead ke customer
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_once __DIR__ . '/../../shared/middleware/audit.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';
require_once __DIR__ . '/../../master/customer/repository.php';
require_once __DIR__ . '/../../master/customer/service.php';
require_auth(); require_permission('LEAD_CONVERT');
if (!is_post()) redirect(url('sales/lead'));
csrf_validate();
$leadId = post_int('lead_id');
try {
    $customerId = LeadService::convertToCustomer($leadId, $_POST);
    flash_set('success','Lead berhasil dikonversi. Data customer baru telah dibuat.');
    redirect(url('master/customer/detail').'?id='.$customerId);
} catch (RuntimeException $e) {
    flash_set('error', $e->getMessage());
    redirect(url('sales/lead/detail').'?id='.$leadId);
}
