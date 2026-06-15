<?php // sales/quotation/ajax/search_diamond.php — AJAX: cari berlian available untuk quotation
declare(strict_types=1);
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../shared/helper/functions.php';
require_once __DIR__ . '/../../../shared/middleware/auth.php';

require_auth();
require_permission('QUOTATION_EDIT');

$q        = trim(get('q', ''));
$excludes = array_filter(array_map('intval', explode(',', get('exclude', ''))));

$where  = "d.status = 'available' AND d.deleted_at IS NULL";
$params = [];

if ($q !== '') {
    $where   .= " AND (d.sku LIKE ? OR d.color LIKE ? OR d.clarity LIKE ? OR c.certificate_no LIKE ?)";
    $like     = "%{$q}%";
    $params   = array_merge($params, [$like, $like, $like, $like]);
}

if (!empty($excludes)) {
    $ph     = implode(',', array_fill(0, count($excludes), '?'));
    $where .= " AND d.id NOT IN ({$ph})";
    $params = array_merge($params, $excludes);
}

$rows = Database::fetchAll(
    "SELECT d.id, d.sku, d.carat, d.color, d.clarity, d.cut,
            d.selling_price AS price_usd,
            c.certificate_no, c.lab
       FROM diamonds d
  LEFT JOIN diamond_certificates c ON c.diamond_id = d.id AND c.is_primary = 1
      WHERE {$where}
   ORDER BY d.sku ASC
      LIMIT 30",
    $params
);

json_response(true, '', $rows);
