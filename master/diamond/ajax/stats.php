<?php
/**
 * master/diamond/ajax/stats.php
 * AJAX: statistik berlian untuk dashboard widget
 */
declare(strict_types=1);
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../shared/helper/functions.php';
require_once __DIR__ . '/../../../shared/middleware/auth.php';
require_once __DIR__ . '/../repository.php';

session_name(SESSION_NAME); session_start();
require_auth();

$stats = DiamondRepository::getStats();
$rate  = DiamondRepository::getActiveRate();

// Hitung total nilai stok available
$valueRow = Database::fetchOne(
    "SELECT SUM(selling_price_usd) AS total_usd
       FROM diamonds
      WHERE status IN ('available','reserved')
        AND deleted_at IS NULL"
);
$totalUsd = (float)($valueRow['total_usd'] ?? 0);

json_response(true, 'OK', [
    'total'       => (int)($stats['total']      ?? 0),
    'available'   => (int)($stats['available']  ?? 0),
    'reserved'    => (int)($stats['reserved']   ?? 0),
    'sold'        => (int)($stats['sold']        ?? 0),
    'registered'  => (int)($stats['registered'] ?? 0),
    'consignment' => (int)($stats['consignment']?? 0),
    'value_usd'   => round($totalUsd, 2),
    'value_idr'   => round($totalUsd * $rate),
    'rate'        => $rate,
]);
