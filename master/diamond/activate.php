<?php // master/diamond/activate.php — AJAX aktivasi berlian registered → available
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_once __DIR__ . '/../../shared/middleware/audit.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

require_auth(); require_permission('DIAMOND_EDIT');
if (!is_post()) json_response(false,'Method tidak valid.',null,405);
csrf_validate();

try {
    DiamondService::activate(post_int('diamond_id'), post('notes'));
    json_response(true, 'Berlian berhasil diaktifkan. Status sekarang: Tersedia.');
} catch (RuntimeException $e) {
    json_response(false, $e->getMessage(), null, 422);
}
