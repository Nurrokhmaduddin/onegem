<?php // sales/quotation/convert_to_reservation.php — Konversi quotation accepted → reservation
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_once __DIR__ . '/../../shared/middleware/audit.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';
require_once __DIR__ . '/../reservation/repository.php';
require_once __DIR__ . '/../reservation/service.php';

require_auth();
require_permission('RESERVATION_CREATE');
if (!is_post()) redirect(url('sales/quotation'));
csrf_validate();

$quotationId = post_int('quotation_id');

try {
    $reservationId = ReservationService::createFromQuotation($quotationId, $_POST);
    flash_set('success', 'Reservasi berhasil dibuat dari quotation ini.');
    redirect(url('sales/reservation/detail') . '?id=' . $reservationId);
} catch (RuntimeException $e) {
    flash_set('error', $e->getMessage());
    redirect(url('sales/quotation/detail') . '?id=' . $quotationId);
}
