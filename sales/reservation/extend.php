<?php // sales/reservation/extend.php — Perpanjang tanggal kedaluwarsa reservasi
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_once __DIR__ . '/../../shared/middleware/audit.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

require_auth();
require_permission('RESERVATION_EDIT');
if (!is_post()) redirect(url('sales/reservation'));
csrf_validate();

$reservationId  = post_int('reservation_id');
$newExpiryDate  = trim(post('new_expiry_date', ''));
$reason         = trim(post('reason', ''));

if (empty($newExpiryDate)) {
    flash_set('error', 'Tanggal kedaluwarsa baru wajib diisi.');
    redirect(url('sales/reservation/detail') . '?id=' . $reservationId);
}

try {
    ReservationService::extend($reservationId, $newExpiryDate, $reason);
    flash_set('success', 'Reservasi berhasil diperpanjang hingga ' . date('d M Y', strtotime($newExpiryDate)) . '.');
} catch (RuntimeException $e) {
    flash_set('error', $e->getMessage());
}
redirect(url('sales/reservation/detail') . '?id=' . $reservationId);
