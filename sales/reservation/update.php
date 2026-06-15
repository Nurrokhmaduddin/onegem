<?php // sales/reservation/update.php — Update reservasi aktif
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

$reservationId = post_int('reservation_id');

try {
    ReservationService::update($reservationId, $_POST);
    flash_set('success', 'Reservasi berhasil diperbarui.');
    redirect(url('sales/reservation/detail') . '?id=' . $reservationId);
} catch (RuntimeException $e) {
    $errors = json_decode($e->getMessage(), true);
    $_SESSION['form_errors'] = is_array($errors) ? array_values($errors) : [$e->getMessage()];
    $_SESSION['form_data']   = $_POST;
    redirect(url('sales/reservation/edit') . '?id=' . $reservationId);
}
