<?php // sales/so/ajax/search_diamond.php — cari berlian available/reserved untuk SO
declare(strict_types=1);
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../shared/helper/functions.php';
require_once __DIR__ . '/../../../shared/middleware/auth.php';
require_auth();
require_permission('SO_EDIT');

$q       = trim(get('q', ''));
$soId    = (int) get('so_id', 0);
$where   = "d.status IN ('available','reserved') AND d.deleted_at IS NULL";
$params  = [];

// Exclude yang sudah ada di SO ini
if ($soId) {
    $existing = Database::fetchAll(
        "SELECT diamond_id FROM sales_order_items WHERE sales_order_id = ?", [$soId]
    );
    $excIds = array_column($existing, 'diamond_id');
    if (!empty($excIds)) {
        $ph     = implode(',', array_fill(0, count($excIds), '?'));
        $where .= " AND d.id NOT IN ({$ph})";
        $params = array_merge($params, $excIds);
    }
}

if ($q !== '') {
    $like     = '%' . $q . '%';
    $where   .= " AND (d.sku LIKE ? OR d.color LIKE ? OR d.clarity LIKE ? OR c.certificate_no LIKE ?)";
    $params   = array_merge($params, [$like, $like, $like, $like]);
}

$rows = Database::fetchAll(
    "SELECT d.id, d.sku, d.carat, d.color, d.clarity, d.cut, d.status,
            d.selling_price AS price_usd,
            c.certificate_no, c.lab
       FROM diamonds d
  LEFT JOIN diamond_certificates c ON c.diamond_id = d.id AND c.is_primary = 1
      WHERE {$where}
      ORDER BY d.sku ASC LIMIT 30",
    $params
);

json_response(true, '', $rows);
