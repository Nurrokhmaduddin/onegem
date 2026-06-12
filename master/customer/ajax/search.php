<?php
/**
 * master/customer/ajax/search.php
 * AJAX: cari pelanggan untuk dropdown/select2 di modul lain (quotation, dll)
 */
declare(strict_types=1);
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../shared/helper/functions.php';
require_once __DIR__ . '/../../../shared/middleware/auth.php';
require_once __DIR__ . '/../repository.php';

session_name(SESSION_NAME); session_start();
require_auth();

$q       = get_param('q');
$results = CustomerRepository::getDropdown($q);

json_response(true, 'OK', array_map(fn($c) => [
    'id'    => $c['id'],
    'text'  => $c['name'] . ' (' . $c['customer_code'] . ')',
    'code'  => $c['customer_code'],
    'tier'  => $c['tier'],
    'phone' => $c['phone'],
], $results));
