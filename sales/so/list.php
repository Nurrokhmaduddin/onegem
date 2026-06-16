<?php
/**
 * sales/so/list.php
 * Daftar Sales Order dengan filter & pagination
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_once __DIR__ . '/../../shared/middleware/audit.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

require_auth();
require_permission('SO_VIEW');

$search    = get_param('search', '');
$status    = get_param('status', '');
$dateFrom  = get_param('date_from', '');
$dateTo    = get_param('date_to', '');
$page      = max(1, (int) get_param('page', 1));
$perPage   = DEFAULT_PER_PAGE;
$offset    = pagination_offset($page, $perPage);

$filters = array_filter(compact('search', 'status', 'dateFrom', 'dateTo'));
$total   = SalesOrderRepository::countAll($filters);
$rows    = SalesOrderRepository::getAll($filters, $perPage, $offset);
$pagData = pagination_data($total, $page, $perPage);
$counts  = SalesOrderRepository::getStatusCounts();

$statusConfig = [
    'draft'     => ['label' => 'Draft',              'color' => 'secondary'],
    'submitted' => ['label' => 'Menunggu Approval',  'color' => 'warning'],
    'approved'  => ['label' => 'Disetujui',          'color' => 'success'],
    'cancelled' => ['label' => 'Dibatalkan',         'color' => 'danger'],
    'completed' => ['label' => 'Selesai',            'color' => 'primary'],
];

$pageTitle   = 'Daftar Sales Order';
$breadcrumbs = [
    ['label' => 'Penjualan'],
    ['label' => 'Sales Order'],
];
require_once __DIR__ . '/../../layout/header.php';
?>


<!-- Status Filter Pills -->
<div class="d-flex gap-2 flex-wrap mb-4">
  <a href="?<?= http_build_query(array_merge($_GET, ['status' => '', 'page' => 1])) ?>"
     class="btn btn-sm <?= $status === '' ? 'btn-dark' : 'btn-outline-secondary' ?>">
    Semua <span class="badge bg-secondary ms-1"><?= array_sum($counts) ?></span>
  </a>
  <?php foreach ($statusConfig as $k => $cfg): ?>
  <a href="?<?= http_build_query(array_merge($_GET, ['status' => $k, 'page' => 1])) ?>"
     class="btn btn-sm <?= $status === $k ? 'btn-'.$cfg['color'] : 'btn-outline-'.$cfg['color'] ?>">
    <?= $cfg['label'] ?>
    <span class="badge bg-white text-dark ms-1"><?= $counts[$k] ?? 0 ?></span>
  </a>
  <?php endforeach; ?>
</div>

<div class="table-card">
  <!-- Toolbar -->
  <div class="table-toolbar flex-wrap gap-2">
    <form method="GET" class="d-flex gap-2 flex-wrap align-items-center">
      <input type="hidden" name="status" value="<?= e($status) ?>">
      <div class="input-group" style="width:240px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" name="search" class="form-control"
               placeholder="No. SO, nama customer..."
               value="<?= e($search) ?>" autocomplete="off">
      </div>
      <input type="date" name="date_from" class="form-control" style="width:150px"
             value="<?= e($dateFrom) ?>">
      <input type="date" name="date_to"   class="form-control" style="width:150px"
             value="<?= e($dateTo) ?>">
      <button type="submit" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-funnel me-1"></i>Filter
      </button>
      <?php if ($search || $dateFrom || $dateTo): ?>
      <a href="?status=<?= urlencode($status) ?>" class="btn btn-outline-danger btn-sm">
        <i class="bi bi-x me-1"></i>Reset
      </a>
      <?php endif; ?>
    </form>
    <?php if (can('SO_CREATE')): ?>
    <a href="<?= url('sales/so/create') ?>" class="btn btn-primary btn-sm">
      <i class="bi bi-plus-lg me-1"></i>Buat Sales Order
    </a>
    <?php endif; ?>
  </div>

  <?php if (empty($rows)): ?>
  <div class="table-empty">
    <i class="bi bi-file-earmark-text text-muted"></i>
    <p class="mb-0 text-muted">Tidak ada Sales Order<?= $status ? ' berstatus "'.($statusConfig[$status]['label'] ?? $status).'"' : '' ?>.</p>
  </div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead>
        <tr>
          <th>#</th>
          <th>No. SO</th>
          <th>Customer</th>
          <th>Berlian</th>
          <th>Total (USD)</th>
          <th>Total (IDR)</th>
          <th>Status</th>
          <th>Salesperson</th>
          <th>Tgl Buat</th>
          <th style="width:80px">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $i => $r):
          $sc = $statusConfig[$r['status']] ?? ['label' => $r['status'], 'color' => 'secondary'];
        ?>
        <tr>
          <td class="text-muted small"><?= $offset + $i + 1 ?></td>
          <td>
            <a href="<?= url('sales/so/detail') ?>?id=<?= $r['id'] ?>"
               class="fw-600 font-mono text-decoration-none text-dark" style="font-size:13px">
              <?= e($r['so_no']) ?>
            </a>
            <?php if ($r['reservation_no']): ?>
            <div class="small text-muted">RSV: <?= e($r['reservation_no']) ?></div>
            <?php endif; ?>
          </td>
          <td>
            <div class="fw-500"><?= e($r['customer_name'] ?? '—') ?></div>
            <div class="small text-muted"><?= e($r['customer_code'] ?? '') ?></div>
          </td>
          <td class="text-center">
            <span class="badge bg-primary-subtle text-primary"><?= $r['item_count'] ?> pcs</span>
          </td>
          <td class="fw-500">$<?= number_format((float) $r['total_usd'], 0) ?></td>
          <td class="small text-muted">
            Rp <?= number_format((float) $r['total_idr'], 0, ',', '.') ?>
          </td>
          <td>
            <span class="badge bg-<?= $sc['color'] ?>-subtle text-<?= $sc['color'] ?> border border-<?= $sc['color'] ?>-subtle">
              <?= e($sc['label']) ?>
            </span>
          </td>
          <td class="small text-muted"><?= e($r['salesperson_name'] ?? '—') ?></td>
          <td class="small text-muted"><?= format_date($r['created_at']) ?></td>
          <td>
            <div class="d-flex gap-1">
              <a href="<?= url('sales/so/detail') ?>?id=<?= $r['id'] ?>"
                 class="btn btn-icon btn-outline-secondary btn-sm" title="Detail">
                <i class="bi bi-eye"></i>
              </a>
              <?php if (can('SO_APPROVE') && $r['status'] === 'submitted'): ?>
              <a href="<?= url('sales/so/detail') ?>?id=<?= $r['id'] ?>"
                 class="btn btn-icon btn-warning btn-sm" title="Review & Approve">
                <i class="bi bi-check-circle"></i>
              </a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pagData['total_pages'] > 1): ?>
  <div class="d-flex align-items-center justify-content-between px-3 py-3 border-top">
    <small class="text-muted">
      <?= $pagData['from'] ?>–<?= $pagData['to'] ?> dari <?= number_format($total) ?> SO
    </small>
    <nav><ul class="pagination mb-0">
      <li class="page-item <?= !$pagData['has_prev'] ? 'disabled' : '' ?>">
        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page-1])) ?>">
          <i class="bi bi-chevron-left"></i></a>
      </li>
      <?php for ($p = max(1,$page-2); $p <= min($pagData['total_pages'],$page+2); $p++): ?>
      <li class="page-item <?= $p===$page?'active':'' ?>">
        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"><?= $p ?></a>
      </li>
      <?php endfor; ?>
      <li class="page-item <?= !$pagData['has_next'] ? 'disabled' : '' ?>">
        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page+1])) ?>">
          <i class="bi bi-chevron-right"></i></a>
      </li>
    </ul></nav>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>
