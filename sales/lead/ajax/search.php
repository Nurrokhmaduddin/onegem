<?php // sales/lead/ajax/search.php — AJAX: cari lead qualified untuk form quotation
declare(strict_types=1);
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../shared/helper/functions.php';
require_once __DIR__ . '/../../../shared/middleware/auth.php';

require_auth();

$q = trim(get('q', ''));
$params = [];
$where  = "l.deleted_at IS NULL AND l.status IN ('qualified','contacted','new')";

if ($q !== '') {
    $like     = '%' . $q . '%';
    $where   .= " AND (l.name LIKE ? OR l.phone LIKE ? OR l.email LIKE ?)";
    $params   = [$like, $like, $like];
}

$rows = Database::fetchAll(
    "SELECT l.id, l.name, l.phone, l.email, l.status,
            l.lead_code, c.name AS customer_name, c.id AS customer_id
       FROM leads l
  LEFT JOIN customers c ON c.id = l.customer_id
      WHERE {$where}
      ORDER BY l.name ASC
      LIMIT 20",
    $params
);

json_response(true, '', $rows);
