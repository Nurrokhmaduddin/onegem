<?php // sales/lead/add_activity.php
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
    LeadService::addActivity($leadId, post('activity_type'), post('description'));
    flash_set('success','Aktivitas berhasil dicatat.');
} catch (RuntimeException $e) {
    flash_set('error', $e->getMessage());
}
redirect(url('sales/lead/detail').'?id='.$leadId);
