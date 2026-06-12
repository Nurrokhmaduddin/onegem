<?php
/**
 * master/supplier/ajax/search.php
 * AJAX: cari supplier untuk dropdown
 */
declare(strict_types=1);
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../shared/helper/functions.php';
require_once __DIR__ . '/../../../shared/middleware/auth.php';
require_once __DIR__ . '/../repository.php';

session_name(SESSION_NAME); session_start();
require_auth();

$q    = get_param('q');
$type = get_param('type'); // consignment | purchase | both | '' (semua)
$rows = SupplierRepository::getDropdown($q, $type);

json_response(true, 'OK', array_map(fn($s) => [
    'id'       => $s['id'],
    'text'     => $s['name'] . ' (' . $s['supplier_code'] . ')',
    'code'     => $s['supplier_code'],
    'type'     => $s['type'],
    'currency' => $s['currency'],
    'discount' => (float)$s['discount_percent'],
], $rows));
