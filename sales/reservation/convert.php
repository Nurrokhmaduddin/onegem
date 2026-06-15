<?php // sales/reservation/convert.php — Konversi reservasi → Sales Order
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_once __DIR__ . '/../../shared/middleware/audit.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

require_auth();
require_permission('RESERVATION_CREATE');
if (!is_post()) redirect(url('sales/reservation'));
csrf_validate();

$reservationId = post_int('reservation_id');

try {
    $soId = ReservationService::convertToSalesOrder($reservationId);
    flash_set('success', 'Sales Order berhasil dibuat dari reservasi ini.');
    redirect(url('sales/so/detail') . '?id=' . $soId);
} catch (RuntimeException $e) {
    flash_set('error', $e->getMessage());
    redirect(url('sales/reservation/detail') . '?id=' . $reservationId);
}
