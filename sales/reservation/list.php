<?php
/**
 * sales/reservation/list.php
 * Daftar semua reservasi dengan filter & pagination
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
require_permission('RESERVATION_VIEW');

$search      = get_param('search', '');
$status      = get_param('status', '');
$dateFrom    = get_param('date_from', '');
$dateTo      = get_param('date_to', '');
$page        = max(1, (int) get_param('page', 1));
$perPage     = DEFAULT_PER_PAGE;
$offset      = pagination_offset($page, $perPage);

$filters = array_filter(compact('search', 'status', 'dateFrom', 'dateTo'));

$total    = ReservationRepository::countAll($filters);
$rows     = ReservationRepository::getAll($filters, $perPage, $offset);
$pagData  = pagination_data($total, $page, $perPage);
$counts   = ReservationRepository::getStatusCounts();

$statusConfig = [
    'active'    => ['label' => 'Aktif',        'color' => 'success'],
    'released'  => ['label' => 'Dilepas',       'color' => 'secondary'],
    'expired'   => ['label' => 'Kedaluwarsa',   'color' => 'warning'],
    'converted' => ['label' => 'Dikonversi',    'color' => 'primary'],
];

$pageTitle   = 'Daftar Reservasi';
$breadcrumbs = [['label' => 'Penjualan'], ['label' => 'Reservasi', 'url' => url('sales/reservation')], ['label' => 'Semua Reservasi']];
require_once __DIR__ . '/../../layout/header.php';
?>

<!-- Status filter pills -->
<div class="d-flex gap-2 flex-wrap mb-4">
  <a href="?<?= http_build_query(array_merge($_GET, ['status' => '', 'page' => 1])) ?>"
     class="btn btn-sm <?= $status === '' ? 'btn-dark' : 'btn-outline-secondary' ?>">
    Semua <span class="badge bg-secondary ms-1"><?= array_sum($counts) ?></span>
  </a>
  <?php foreach ($statusConfig as $k => $cfg): ?>
  <a href="?<?= http_build_query(array_merge($_GET, ['status' => $k, 'page' => 1])) ?>"
     class="btn btn-sm <?= $status === $k ? 'btn-' . $cfg['color'] : 'btn-outline-' . $cfg['color'] ?>">
    <?= $cfg['label'] ?> <span class="badge bg-white text-dark ms-1"><?= $counts[$k] ?? 0 ?></span>
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
               placeholder="No. reservasi, nama customer..."
               value="<?= e($search) ?>" autocomplete="off">
      </div>
      <input type="date" name="date_from" class="form-control" style="width:150px"
             value="<?= e($dateFrom) ?>" placeholder="Dari tanggal">
      <input type="date" name="date_to" class="form-control" style="width:150px"
             value="<?= e($dateTo) ?>" placeholder="Sampai tanggal">
      <button type="submit" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-funnel me-1"></i>Filter
      </button>
      <?php if ($search || $dateFrom || $dateTo): ?>
      <a href="?status=<?= urlencode($status) ?>" class="btn btn-outline-danger btn-sm">
        <i class="bi bi-x me-1"></i>Reset
      </a>
      <?php endif; ?>
    </form>
    <?php if (can('RESERVATION_CREATE')): ?>
    <a href="<?= url('sales/reservation/create') ?>" class="btn btn-primary btn-sm">
      <i class="bi bi-plus-lg me-1"></i>Buat Reservasi
    </a>
    <?php endif; ?>
  </div>

  <?php if (empty($rows)): ?>
  <div class="table-empty">
    <i class="bi bi-bookmark text-muted"></i>
    <p class="mb-0 text-muted">Tidak ada data reservasi<?= $status ? ' berstatus "' . ($statusConfig[$status]['label'] ?? $status) . '"' : '' ?>.</p>
  </div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead>
        <tr>
          <th>#</th>
          <th>No. Reservasi</th>
          <th>Customer</th>
          <th>Berlian</th>
          <th>Total (USD)</th>
          <th>Kedaluwarsa</th>
          <th>Status</th>
          <th>Salesperson</th>
          <th style="width:90px">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $i => $r):
          $expiryTs  = strtotime($r['expiry_date']);
          $daysLeft  = (int) ceil(($expiryTs - time()) / 86400);
          $isUrgent  = $r['status'] === 'active' && $daysLeft <= 1;
          $isWarning = $r['status'] === 'active' && $daysLeft <= 3 && !$isUrgent;
          $sc        = $statusConfig[$r['status']] ?? ['label' => $r['status'], 'color' => 'secondary'];
        ?>
        <tr class="<?= $isUrgent ? 'table-danger' : ($isWarning ? 'table-warning' : '') ?>">
          <td class="text-muted small"><?= $offset + $i + 1 ?></td>
          <td>
            <a href="<?= url('sales/reservation/detail') ?>?id=<?= $r['id'] ?>"
               class="fw-600 font-mono text-decoration-none text-dark" style="font-size:13px">
              <?= e($r['reservation_no']) ?>
            </a>
            <?php if ($r['quotation_no']): ?>
            <div class="small text-muted">QUO: <?= e($r['quotation_no']) ?></div>
            <?php endif; ?>
          </td>
          <td>
            <span class="fw-500"><?= e($r['customer_name'] ?? '—') ?></span>
          </td>
          <td class="text-center">
            <span class="badge bg-primary-subtle text-primary"><?= $r['item_count'] ?> pcs</span>
          </td>
          <td class="fw-500">$<?= number_format((float) $r['total_usd'], 0) ?></td>
          <td>
            <span class="<?= $isUrgent ? 'text-danger fw-600' : ($isWarning ? 'text-warning fw-500' : '') ?>">
              <?= date('d M Y', $expiryTs) ?>
            </span>
            <?php if ($r['status'] === 'active'): ?>
            <div class="small <?= $isUrgent ? 'text-danger' : 'text-muted' ?>">
              <?php if ($daysLeft <= 0): ?>
                Hari ini!
              <?php elseif ($daysLeft === 1): ?>
                Besok
              <?php else: ?>
                <?= $daysLeft ?> hari lagi
              <?php endif; ?>
            </div>
            <?php endif; ?>
          </td>
          <td>
            <span class="badge bg-<?= $sc['color'] ?>-subtle text-<?= $sc['color'] ?> border border-<?= $sc['color'] ?>-subtle">
              <?= e($sc['label']) ?>
            </span>
          </td>
          <td class="small text-muted"><?= e($r['salesperson_name'] ?? '—') ?></td>
          <td>
            <div class="d-flex gap-1">
              <a href="<?= url('sales/reservation/detail') ?>?id=<?= $r['id'] ?>"
                 class="btn btn-icon btn-outline-secondary btn-sm" title="Detail">
                <i class="bi bi-eye"></i>
              </a>
              <?php if (can('RESERVATION_EDIT') && $r['status'] === 'active'): ?>
              <a href="<?= url('sales/reservation/edit') ?>?id=<?= $r['id'] ?>"
                 class="btn btn-icon btn-outline-primary btn-sm" title="Edit">
                <i class="bi bi-pencil"></i>
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
      <?= $pagData['from'] ?>–<?= $pagData['to'] ?> dari <?= number_format($total) ?> reservasi
    </small>
    <nav><ul class="pagination mb-0">
      <li class="page-item <?= !$pagData['has_prev'] ? 'disabled' : '' ?>">
        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
          <i class="bi bi-chevron-left"></i></a>
      </li>
      <?php for ($p = max(1, $page - 2); $p <= min($pagData['total_pages'], $page + 2); $p++): ?>
      <li class="page-item <?= $p === $page ? 'active' : '' ?>">
        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"><?= $p ?></a>
      </li>
      <?php endfor; ?>
      <li class="page-item <?= !$pagData['has_next'] ? 'disabled' : '' ?>">
        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
          <i class="bi bi-chevron-right"></i></a>
      </li>
    </ul></nav>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>
