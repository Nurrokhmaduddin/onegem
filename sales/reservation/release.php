<?php // sales/reservation/release.php — Lepas reservasi & release diamond lock
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_once __DIR__ . '/../../shared/middleware/audit.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

require_auth();
require_permission('RESERVATION_RELEASE');
if (!is_post()) redirect(url('sales/reservation'));
csrf_validate();

$reservationId = post_int('reservation_id');
$notes         = trim(post('notes', ''));

try {
    ReservationService::release($reservationId, $notes);
    flash_set('success', 'Reservasi berhasil dilepas. Lock berlian telah dibebaskan.');
} catch (RuntimeException $e) {
    flash_set('error', $e->getMessage());
}
redirect(url('sales/reservation/detail') . '?id=' . $reservationId);
