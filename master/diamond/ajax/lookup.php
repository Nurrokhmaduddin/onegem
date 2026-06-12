<?php
/**
 * master/diamond/ajax/lookup.php
 * AJAX: lookup berlian by barcode atau kode internal
 * Dipakai di layar POS / scan barcode
 */
declare(strict_types=1);
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../shared/helper/functions.php';
require_once __DIR__ . '/../../../shared/middleware/auth.php';
require_once __DIR__ . '/../repository.php';

session_name(SESSION_NAME); session_start();
require_auth();

$q = trim(get_param('q'));
if (empty($q)) json_response(false, 'Kode/barcode wajib diisi.', null, 400);

$diamond = DiamondRepository::findByCode($q);
if (!$diamond) json_response(false, 'Barang tidak ditemukan: ' . $q, null, 404);

$rate = DiamondRepository::getActiveRate();

json_response(true, 'OK', [
    'id'               => $diamond['id'],
    'internal_code'    => $diamond['internal_code'],
    'factory_barcode'  => $diamond['factory_barcode'],
    'status'           => $diamond['status'],
    'carat_weight'     => $diamond['carat_weight'],
    'color_grade'      => $diamond['color_grade'],
    'clarity_grade'    => $diamond['clarity_grade'],
    'cut_grade'        => $diamond['cut_grade'],
    'supplier_name'    => $diamond['supplier_name'],
    'warehouse_name'   => $diamond['warehouse_name'],
    'selling_price_usd'=> (float)$diamond['selling_price_usd'],
    'selling_price_idr'=> round((float)$diamond['selling_price_usd'] * $rate),
    'cost_price_usd'   => (float)$diamond['cost_price_usd'],
    'rate_usd_idr'     => $rate,
]);
